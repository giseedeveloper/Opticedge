import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../api/major_contract_termination_api.dart';
import '../admin/widgets/admin_page_ui.dart';

class MajorContractTerminationApprovalsContent extends StatefulWidget {
  const MajorContractTerminationApprovalsContent({super.key, required this.apiPrefix});

  final String apiPrefix;

  @override
  State<MajorContractTerminationApprovalsContent> createState() => _MajorContractTerminationApprovalsContentState();
}

class _MajorContractTerminationApprovalsContentState extends State<MajorContractTerminationApprovalsContent> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  String? _error;
  int? _busyId;

  String _formatDate(String? value) {
    if (value == null || value.trim().isEmpty) return '–';
    try {
      return DateFormat('MMM dd, yyyy · HH:mm').format(DateTime.parse(value).toLocal());
    } catch (_) {
      return value;
    }
  }

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
      final items = await listMajorContractTerminationApprovals(
        apiPrefix: widget.apiPrefix,
        status: 'awaiting_major',
      );
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

  Future<void> _decide(Map<String, dynamic> row, {required bool approve}) async {
    final id = (row['id'] as num?)?.toInt();
    if (id == null) return;
    final noteCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(approve ? 'Approve exit request?' : 'Reject exit request?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              approve
                  ? 'Confirm ${row['user']?['name'] ?? 'this user'} returned all devices to you before approving.'
                  : 'Reject this contract termination request?',
            ),
            const SizedBox(height: 12),
            TextField(
              controller: noteCtrl,
              decoration: const InputDecoration(labelText: 'Note (optional)', border: OutlineInputBorder()),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: Text(approve ? 'Approve' : 'Reject')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _busyId = id);
    try {
      if (approve) {
        await approveMajorContractTermination(apiPrefix: widget.apiPrefix, id: id, note: noteCtrl.text);
      } else {
        await rejectMajorContractTermination(apiPrefix: widget.apiPrefix, id: id, note: noteCtrl.text);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(approve ? 'Approved for admin review.' : 'Request rejected.')),
      );
      _load();
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
          Center(child: TextButton(onPressed: _load, child: const Text('Retry'))),
        ],
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            'Workers who want to leave must return all unsold devices to you first. Approve only after you have accepted their returns.',
            style: TextStyle(color: Colors.grey.shade700, height: 1.35),
          ),
          const SizedBox(height: 16),
          if (_items.isEmpty)
            const AdminPageEmpty(icon: Icons.verified_user_outlined, title: 'No exit requests waiting')
          else
            ..._items.map((row) {
              final id = (row['id'] as num?)?.toInt();
              final busy = id != null && _busyId == id;
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: AdminSectionCard(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(row['user']?['name']?.toString() ?? 'User', style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(row['role_label']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade700)),
                      const SizedBox(height: 6),
                      Text(row['reason']?.toString() ?? '', style: const TextStyle(height: 1.3)),
                      const SizedBox(height: 6),
                      Text(_formatDate(row['created_at']?.toString()), style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: busy ? null : () => _decide(row, approve: false),
                              child: const Text('Reject'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: FilledButton(
                              style: FilledButton.styleFrom(backgroundColor: const Color(0xFFFA8900)),
                              onPressed: busy ? null : () => _decide(row, approve: true),
                              child: busy
                                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Text('Approve'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }
}
