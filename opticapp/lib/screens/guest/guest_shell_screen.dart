import 'package:flutter/material.dart';

import '../../api/auth_api.dart';
import '../../api/guest_api.dart';
import '../../theme/app_theme.dart';
import '../admin/widgets/admin_page_ui.dart';

class GuestShellScreen extends StatefulWidget {
  const GuestShellScreen({super.key});

  @override
  State<GuestShellScreen> createState() => _GuestShellScreenState();
}

class _GuestShellScreenState extends State<GuestShellScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(['Home', 'Requests', 'Profile'][_tab]),
        actions: [
          IconButton(
            tooltip: 'Sign out',
            onPressed: () => performLogout(),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: IndexedStack(
        index: _tab,
        children: const [
          _GuestHomeTab(),
          _GuestRequestsTab(),
          _GuestProfileTab(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.mail_outline), selectedIcon: Icon(Icons.mail), label: 'Requests'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}

class _GuestHomeTab extends StatefulWidget {
  const _GuestHomeTab();

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
    if (_error != null) return AdminPageError(message: _error!);

    final pending = (_data?['pending_invitations_count'] as num?)?.toInt() ?? 0;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: sectionCardDecoration(context),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Hi, ${_data?['name'] ?? 'there'}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
                const SizedBox(height: 8),
                Text(_data?['email']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade600)),
                const SizedBox(height: 16),
                Text(_data?['message']?.toString() ?? '', style: const TextStyle(height: 1.4)),
              ],
            ),
          ),
          if (pending > 0) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: sectionCardDecoration(context).copyWith(
                color: Colors.orange.shade50,
              ),
              child: Text('$pending vendor request${pending == 1 ? '' : 's'} waiting — open the Requests tab.'),
            ),
          ],
        ],
      ),
    );
  }
}

class _GuestRequestsTab extends StatefulWidget {
  const _GuestRequestsTab();

  @override
  State<_GuestRequestsTab> createState() => _GuestRequestsTabState();
}

class _GuestRequestsTabState extends State<_GuestRequestsTab> {
  List<Map<String, dynamic>> _items = [];
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

  Future<void> _accept(int id, String role) async {
    try {
      await acceptGuestInvitation(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Joined vendor successfully.')));
      final route = switch (role) {
        'agent' => '/agent/dashboard',
        'teamleader' => '/team-leader/dashboard',
        'regional_manager' => '/regional-manager/dashboard',
        _ => '/home',
      };
      Navigator.pushReplacementNamed(context, route);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    }
  }

  Future<void> _decline(int id) async {
    try {
      await declineGuestInvitation(id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Invitation declined.')));
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const AdminPageLoading();
    if (_error != null) return AdminPageError(message: _error!);

    if (_items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text('No pending vendor requests.', style: TextStyle(color: Colors.grey.shade600)),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = _items[index];
          final id = (item['id'] as num).toInt();
          final role = item['proposed_role']?.toString() ?? 'agent';
          return Container(
            padding: const EdgeInsets.all(16),
            decoration: sectionCardDecoration(context),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item['vendor_name']?.toString() ?? 'Vendor', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(item['proposed_role_label']?.toString() ?? role, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w600)),
                if (item['message'] != null && item['message'].toString().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(item['message'].toString()),
                ],
                const SizedBox(height: 12),
                Row(
                  children: [
                    FilledButton(onPressed: () => _accept(id, role), child: const Text('Accept')),
                    const SizedBox(width: 8),
                    OutlinedButton(onPressed: () => _decline(id), child: const Text('Decline')),
                  ],
                ),
              ],
            ),
          );
        },
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
      setState(() => _loading = false);
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
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
              Text('Email', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
              Text(_email ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 16),
              TextField(
                controller: _name,
                decoration: const InputDecoration(labelText: 'Full name', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _phone,
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
