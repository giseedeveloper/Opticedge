import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../api/contract_termination_api.dart';
import '../../theme/app_theme.dart';
import '../admin/widgets/admin_page_ui.dart';

class ContractTerminationContent extends StatefulWidget {
  const ContractTerminationContent({super.key, required this.apiPrefix});

  final String apiPrefix;

  @override
  State<ContractTerminationContent> createState() => _ContractTerminationContentState();
}

class _ContractTerminationContentState extends State<ContractTerminationContent> {
  final _reason = TextEditingController();
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  bool _submitting = false;
  String? _error;
  int? _busyId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  String _formatDate(String? value) {
    if (value == null || value.trim().isEmpty) return '–';
    try {
      return DateFormat('MMM dd, yyyy · HH:mm').format(DateTime.parse(value).toLocal());
    } catch (_) {
      return value;
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final items = await listContractTerminationRequests(apiPrefix: widget.apiPrefix);
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

  Future<void> _submit() async {
    final reason = _reason.text.trim();
    if (reason.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a reason for ending your contract.')),
      );
      return;
    }

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Submit termination request?'),
        content: const Text(
          'Your vendor admin will review this request. If approved, you will leave this vendor and return to guest status.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Submit')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _submitting = true);
    try {
      await submitContractTerminationRequest(apiPrefix: widget.apiPrefix, reason: reason);
      if (!mounted) return;
      _reason.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Termination request submitted.')),
      );
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _cancel(Map<String, dynamic> item) async {
    final id = (item['id'] as num).toInt();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel request?'),
        content: const Text('Your pending termination request will be withdrawn.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Keep')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Cancel request')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _busyId = id);
    try {
      await cancelContractTerminationRequest(apiPrefix: widget.apiPrefix, id: id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Request cancelled.')),
      );
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

  bool get _hasPending => _items.any((e) => e['status']?.toString() == 'pending');

  @override
  Widget build(BuildContext context) {
    if (_loading) return const AdminPageLoading();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_error != null) ...[
            AdminPageError(message: _error!),
            Center(child: TextButton(onPressed: _load, child: const Text('Retry'))),
            const SizedBox(height: 16),
          ],
          Container(
            padding: const EdgeInsets.all(16),
            decoration: sectionCardDecoration(context),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Request to end contract',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 8),
                Text(
                  'Explain why you want to leave your current vendor. An admin must approve before your contract ends.',
                  style: TextStyle(color: Colors.grey.shade700),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _reason,
                  maxLines: 4,
                  enabled: !_hasPending && !_submitting,
                  decoration: const InputDecoration(
                    labelText: 'Reason',
                    hintText: 'Why do you want to terminate your contract?',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: (_hasPending || _submitting) ? null : _submit,
                  child: _submitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Submit request'),
                ),
                if (_hasPending) ...[
                  const SizedBox(height: 8),
                  Text(
                    'You already have a pending request. Cancel it below to submit a new one.',
                    style: TextStyle(color: Colors.orange.shade800, fontSize: 13),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 20),
          Text(
            'Your requests',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          if (_items.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text('No termination requests yet.', style: TextStyle(color: Colors.grey.shade600)),
              ),
            )
          else
            ..._items.map((item) {
              final status = item['status']?.toString() ?? '';
              final canCancel = item['can_cancel'] == true;
              final busy = _busyId == (item['id'] as num?)?.toInt();

              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: sectionCardDecoration(context),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              item['vendor_name']?.toString() ?? 'Vendor',
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                          ),
                          _StatusChip(status: status),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text('Submitted: ${_formatDate(item['created_at']?.toString())}'),
                      if (item['role_label'] != null) Text('Role: ${item['role_label']}'),
                      const SizedBox(height: 8),
                      Text(item['reason']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade800)),
                      if (item['admin_note'] != null && item['admin_note'].toString().isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text('Admin note: ${item['admin_note']}', style: TextStyle(color: Colors.grey.shade700)),
                      ],
                      if (canCancel) ...[
                        const SizedBox(height: 12),
                        OutlinedButton(
                          onPressed: busy ? null : () => _cancel(item),
                          child: busy
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                )
                              : const Text('Cancel request'),
                        ),
                      ],
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

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final Color fg;
    final Color bg;
    switch (status) {
      case 'pending':
        fg = Colors.orange.shade800;
        bg = Colors.orange.withValues(alpha: 0.12);
      case 'approved':
        fg = Colors.green.shade800;
        bg = Colors.green.withValues(alpha: 0.12);
      case 'rejected':
        fg = Colors.red.shade800;
        bg = Colors.red.withValues(alpha: 0.12);
      default:
        fg = Colors.grey.shade800;
        bg = Colors.grey.withValues(alpha: 0.12);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        status.isEmpty ? 'Unknown' : status[0].toUpperCase() + status.substring(1),
        style: TextStyle(color: fg, fontWeight: FontWeight.w600, fontSize: 12),
      ),
    );
  }
}
