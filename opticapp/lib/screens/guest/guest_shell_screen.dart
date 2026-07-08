import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../api/auth_api.dart';
import '../../api/guest_api.dart';
import '../../providers/notifications_provider.dart';
import '../../services/push_notification_service.dart';
import '../../theme/app_theme.dart';
import '../admin/widgets/admin_page_ui.dart';

/// Guest portal: home, vendor requests (accept/decline), and profile.
class GuestShellScreen extends StatefulWidget {
  const GuestShellScreen({super.key, this.initialTab = 0});

  final int initialTab;

  @override
  State<GuestShellScreen> createState() => _GuestShellScreenState();
}

class _GuestShellScreenState extends State<GuestShellScreen> with WidgetsBindingObserver {
  static const _pollInterval = Duration(seconds: 45);

  late int _tab;
  int _pendingCount = 0;
  int _lastAlertedCount = -1;
  Timer? _pollTimer;
  final ValueNotifier<int> _requestsRefresh = ValueNotifier(0);

  @override
  void initState() {
    super.initState();
    _tab = widget.initialTab;
    WidgetsBinding.instance.addObserver(this);
    _refreshPendingCount();
    _pollTimer = Timer.periodic(_pollInterval, (_) => _refreshPendingCount());
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<NotificationsProvider>().refreshSilently();
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _requestsRefresh.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed || !mounted) return;
    _refreshPendingCount();
    context.read<NotificationsProvider>().refreshSilently();
  }

  Future<void> _refreshPendingCount() async {
    try {
      final data = await getGuestDashboard();
      if (!mounted) return;
      final pending = (data['pending_invitations_count'] as num?)?.toInt() ?? 0;
      final increased = _lastAlertedCount >= 0 && pending > _lastAlertedCount;

      if (increased) {
        final delta = pending - _lastAlertedCount;
        final title = delta == 1 ? 'New vendor request' : '$delta new vendor requests';
        final body = delta == 1
            ? 'A vendor invited you to join their team. Tap to review.'
            : 'You have $pending vendor requests waiting. Tap to review.';
        await PushNotificationService.showLocalAlert(
          title: title,
          body: body,
          route: '/guest/requests',
          type: 'guest.invitation',
        );
        _requestsRefresh.value++;
        if (mounted) {
          context.read<NotificationsProvider>().refreshSilently();
        }
      }

      setState(() {
        _pendingCount = pending;
        _lastAlertedCount = pending;
      });
    } catch (_) {}
  }

  void _openRequestsTab() {
    setState(() => _tab = 1);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(['Home', 'Requests', 'Profile'][_tab]),
        actions: [
          Consumer<NotificationsProvider>(
            builder: (context, notifications, _) {
              return IconButton(
                tooltip: 'Notifications',
                onPressed: () => Navigator.pushNamed(context, '/notifications'),
                icon: Badge(
                  isLabelVisible: notifications.unreadCount > 0,
                  label: Text('${notifications.unreadCount}'),
                  child: const Icon(Icons.notifications_outlined),
                ),
              );
            },
          ),
          IconButton(
            tooltip: 'Sign out',
            onPressed: () => performLogout(),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: IndexedStack(
        index: _tab,
        children: [
          _GuestHomeTab(
            onOpenRequests: _openRequestsTab,
            onPendingCount: (count) {
              if (!mounted) return;
              if (_lastAlertedCount < 0) {
                _lastAlertedCount = count;
              }
              if (count != _pendingCount) {
                setState(() => _pendingCount = count);
              }
            },
          ),
          _GuestRequestsTab(
            refreshSignal: _requestsRefresh,
            onChanged: _refreshPendingCount,
          ),
          const _GuestProfileTab(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: _pendingCount > 0,
              label: Text('$_pendingCount'),
              child: const Icon(Icons.mail_outline),
            ),
            selectedIcon: Badge(
              isLabelVisible: _pendingCount > 0,
              label: Text('$_pendingCount'),
              child: const Icon(Icons.mail),
            ),
            label: 'Requests',
          ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

class _GuestHomeTab extends StatefulWidget {
  const _GuestHomeTab({required this.onOpenRequests, required this.onPendingCount});

  final VoidCallback onOpenRequests;
  final void Function(int count) onPendingCount;

  @override
  State<_GuestHomeTab> createState() => _GuestHomeTabState();
}

class _GuestHomeTabState extends State<_GuestHomeTab> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await getGuestDashboard();
      if (!mounted) return;
      final pending = (data['pending_invitations_count'] as num?)?.toInt() ?? 0;
      widget.onPendingCount(pending);
      setState(() {
        _data = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const AdminPageLoading();
    if (_error != null) {
      return ListView(
        children: [
          AdminPageError(message: _error!),
          Center(
            child: TextButton(onPressed: _load, child: const Text('Retry')),
          ),
        ],
      );
    }

    final pending = (_data?['pending_invitations_count'] as num?)?.toInt() ?? 0;
    final avatar = _data?['avatar']?.toString();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: sectionCardDecoration(context),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (avatar != null && avatar.isNotEmpty)
                  CircleAvatar(
                    radius: 28,
                    backgroundImage: NetworkImage(avatar),
                  )
                else
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                    child: Icon(Icons.person, color: Theme.of(context).colorScheme.primary),
                  ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Hi, ${_data?['name'] ?? 'there'}',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 4),
                      Text(_data?['email']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade600)),
                      if ((_data?['phone'] as String?)?.isNotEmpty == true) ...[
                        const SizedBox(height: 4),
                        Text(_data!['phone'].toString(), style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: sectionCardDecoration(context),
            child: Text(
              _data?['message']?.toString() ??
                  'Your account is registered. Vendors can send you assignment requests.',
              style: const TextStyle(height: 1.45),
            ),
          ),
          if (pending > 0) ...[
            const SizedBox(height: 16),
            Material(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(16),
              child: InkWell(
                onTap: widget.onOpenRequests,
                borderRadius: BorderRadius.circular(16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.notifications_active_outlined, color: Colors.orange.shade800),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '$pending vendor request${pending == 1 ? '' : 's'}',
                              style: TextStyle(fontWeight: FontWeight.w700, color: Colors.orange.shade900),
                            ),
                            const SizedBox(height: 4),
                            const Text('Tap to review and accept or decline'),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _GuestRequestsTab extends StatefulWidget {
  const _GuestRequestsTab({required this.refreshSignal, required this.onChanged});

  final ValueNotifier<int> refreshSignal;
  final VoidCallback onChanged;

  @override
  State<_GuestRequestsTab> createState() => _GuestRequestsTabState();
}

class _GuestRequestsTabState extends State<_GuestRequestsTab> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  String? _error;
  int? _busyId;

  @override
  void initState() {
    super.initState();
    widget.refreshSignal.addListener(_onRefreshSignal);
    _load();
  }

  @override
  void dispose() {
    widget.refreshSignal.removeListener(_onRefreshSignal);
    super.dispose();
  }

  void _onRefreshSignal() {
    if (mounted) _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final items = await getGuestInvitations();
      if (!mounted) return;
      setState(() {
        _items = items;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  Future<void> _accept(Map<String, dynamic> item) async {
    final id = (item['id'] as num).toInt();
    final role = item['proposed_role']?.toString() ?? 'agent';
    final vendor = item['vendor_name']?.toString() ?? 'this vendor';
    final roleLabel = item['proposed_role_label']?.toString() ?? role;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Accept invitation?'),
        content: Text('Join $vendor as $roleLabel?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Accept')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _busyId = id);
    try {
      await acceptGuestInvitation(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('You joined the vendor successfully.')));
      Navigator.pushReplacementNamed(context, guestRoleDashboardRoute(role));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _decline(Map<String, dynamic> item) async {
    final id = (item['id'] as num).toInt();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Decline invitation?'),
        content: const Text('The vendor will be notified that you declined.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Decline')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _busyId = id);
    try {
      await declineGuestInvitation(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Invitation declined.')));
      widget.onChanged();
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const AdminPageLoading();
    if (_error != null) {
      return ListView(
        children: [
          AdminPageError(message: _error!),
          Center(
            child: TextButton(onPressed: _load, child: const Text('Retry')),
          ),
        ],
      );
    }

    if (_items.isEmpty) {
      return RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          children: [
            SizedBox(
              height: MediaQuery.sizeOf(context).height * 0.45,
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.inbox_outlined, size: 48, color: Colors.grey.shade400),
                      const SizedBox(height: 12),
                      Text(
                        'No pending vendor requests',
                        style: Theme.of(context).textTheme.titleMedium,
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'When a vendor invites you, it will appear here.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) => _InvitationCard(
          item: _items[index],
          busy: _busyId == (_items[index]['id'] as num).toInt(),
          onAccept: () => _accept(_items[index]),
          onDecline: () => _decline(_items[index]),
        ),
      ),
    );
  }
}

class _InvitationCard extends StatelessWidget {
  const _InvitationCard({
    required this.item,
    required this.busy,
    required this.onAccept,
    required this.onDecline,
  });

  final Map<String, dynamic> item;
  final bool busy;
  final VoidCallback onAccept;
  final VoidCallback onDecline;

  @override
  Widget build(BuildContext context) {
    final roleLabel = item['proposed_role_label']?.toString() ?? item['proposed_role']?.toString() ?? 'Role';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: sectionCardDecoration(context),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                child: Icon(Icons.storefront_outlined, color: Theme.of(context).colorScheme.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['vendor_name']?.toString() ?? 'Vendor',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(roleLabel, style: TextStyle(fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.primary, fontSize: 12)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (item['invited_by_name'] != null) ...[
            const SizedBox(height: 10),
            Text('From: ${item['invited_by_name']}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
          ],
          if (item['branch_name'] != null || item['region_name'] != null) ...[
            const SizedBox(height: 6),
            Text(
              [
                if (item['branch_name'] != null) 'Branch: ${item['branch_name']}',
                if (item['region_name'] != null) 'Region: ${item['region_name']}',
              ].join(' · '),
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
          ],
          if (item['message'] != null && item['message'].toString().isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(item['message'].toString()),
            ),
          ],
          const SizedBox(height: 14),
          Row(
            children: [
              FilledButton(
                onPressed: busy ? null : onAccept,
                child: busy
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('Accept'),
              ),
              const SizedBox(width: 8),
              OutlinedButton(onPressed: busy ? null : onDecline, child: const Text('Decline')),
            ],
          ),
        ],
      ),
    );
  }
}

class _GuestProfileTab extends StatefulWidget {
  const _GuestProfileTab();

  @override
  State<_GuestProfileTab> createState() => _GuestProfileTabState();
}

class _GuestProfileTabState extends State<_GuestProfileTab> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  bool _loading = true;
  bool _saving = false;
  String? _email;
  String? _avatar;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await getGuestProfile();
      if (!mounted) return;
      _name.text = data['name']?.toString() ?? '';
      _phone.text = data['phone']?.toString() ?? '';
      _email = data['email']?.toString();
      _avatar = data['avatar']?.toString();
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      await updateGuestProfile(name: _name.text.trim(), phone: _phone.text.trim());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile saved.')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const AdminPageLoading();

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: sectionCardDecoration(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: _avatar != null && _avatar!.isNotEmpty
                    ? CircleAvatar(radius: 40, backgroundImage: NetworkImage(_avatar!))
                    : CircleAvatar(
                        radius: 40,
                        backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                        child: Icon(Icons.person, size: 40, color: Theme.of(context).colorScheme.primary),
                      ),
              ),
              const SizedBox(height: 16),
              Text('Signed in as guest', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
              Text(_email ?? '', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 20),
              TextField(
                controller: _name,
                decoration: const InputDecoration(labelText: 'Full name', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'Phone', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('Save profile'),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
