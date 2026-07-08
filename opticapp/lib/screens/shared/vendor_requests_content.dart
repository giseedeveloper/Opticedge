import 'package:flutter/material.dart';

import '../../api/client.dart';
import '../../api/guest_api.dart';
import '../admin/widgets/admin_page_ui.dart';

/// Pending vendor invitations (accept / decline).
class VendorRequestsContent extends StatefulWidget {
  const VendorRequestsContent({super.key, this.onChanged});

  final VoidCallback? onChanged;

  @override
  State<VendorRequestsContent> createState() => _VendorRequestsContentState();
}

class _VendorRequestsContentState extends State<VendorRequestsContent> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  String? _error;
  int? _busyId;

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
      if (!mounted || isSessionExpiredError(e)) return;
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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You joined the vendor successfully.')),
      );
      Navigator.pushReplacementNamed(context, guestRoleDashboardRoute(role));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invitation declined.')),
      );
      widget.onChanged?.call();
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
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
        itemBuilder: (context, index) => _VendorInvitationCard(
          item: _items[index],
          busy: _busyId == (_items[index]['id'] as num).toInt(),
          onAccept: () => _accept(_items[index]),
          onDecline: () => _decline(_items[index]),
        ),
      ),
    );
  }
}

class _VendorInvitationCard extends StatelessWidget {
  const _VendorInvitationCard({
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
    final roleLabel =
        item['proposed_role_label']?.toString() ?? item['proposed_role']?.toString() ?? 'Role';

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
                child: Icon(
                  Icons.storefront_outlined,
                  color: Theme.of(context).colorScheme.primary,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['vendor_name']?.toString() ?? 'Vendor',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        roleLabel,
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: Theme.of(context).colorScheme.primary,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (item['invited_by_name'] != null) ...[
            const SizedBox(height: 10),
            Text(
              'From: ${item['invited_by_name']}',
              style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
            ),
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
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Accept'),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: busy ? null : onDecline,
                child: const Text('Decline'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
