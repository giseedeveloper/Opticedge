import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../api/admin_contract_terminations_api.dart';
import 'admin_scaffold.dart';
import 'widgets/admin_page_ui.dart';

class AdminContractTerminationsScreen extends StatefulWidget {
  const AdminContractTerminationsScreen({super.key});

  @override
  State<AdminContractTerminationsScreen> createState() => _AdminContractTerminationsScreenState();
}

class _AdminContractTerminationsScreenState extends State<AdminContractTerminationsScreen> {
  List<Map<String, dynamic>> _list = [];
  bool _loading = true;
  String? _error;
  String? _statusFilter;

  String _formatDate(String? value) {
    if (value == null || value.trim().isEmpty) return '–';
    try {
      return DateFormat('MMM dd, yyyy · HH:mm').format(DateTime.parse(value).toLocal());
    } catch (_) {
      return value ?? '–';
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
      final list = await listAdminContractTerminations(status: _statusFilter);
      if (!mounted) return;
      setState(() {
        _list = list;
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

  Future<void> _approve(Map<String, dynamic> row) async {
    final id = row['id'];
    final int? rid = id is int ? id : (id is num ? id.toInt() : null);
    if (rid == null) return;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Approve termination?'),
        content: Text(
          'Approve contract termination for ${row['user']?['name'] ?? 'this user'}? They will leave your vendor and return to guest status.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Approve')),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    try {
      await approveAdminContractTermination(rid);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Termination approved.')));
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _reject(Map<String, dynamic> row) async {
    final id = row['id'];
    final int? rid = id is int ? id : (id is num ? id.toInt() : null);
    if (rid == null) return;

    try {
      await rejectAdminContractTermination(rid);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Termination rejected.')));
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdminScaffold(
      title: 'Contract terminations',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: DropdownButtonFormField<String?>(
              value: _statusFilter,
              decoration: const InputDecoration(
                labelText: 'Status',
                border: OutlineInputBorder(),
                isDense: true,
              ),
              items: const [
                DropdownMenuItem(value: null, child: Text('All')),
                DropdownMenuItem(value: 'pending', child: Text('Pending')),
                DropdownMenuItem(value: 'approved', child: Text('Approved')),
                DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
                DropdownMenuItem(value: 'cancelled', child: Text('Cancelled')),
              ],
              onChanged: (v) {
                setState(() => _statusFilter = v);
                _load();
              },
            ),
          ),
          Expanded(
            child: _loading
                ? const AdminPageLoading()
                : _error != null
                    ? ListView(
                        children: [
                          AdminPageError(message: _error!),
                          Center(child: TextButton(onPressed: _load, child: const Text('Retry'))),
                        ],
                      )
                    : _list.isEmpty
                        ? const AdminPageEmpty(title: 'No contract termination requests')
                        : RefreshIndicator(
                            onRefresh: _load,
                            child: ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _list.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 12),
                              itemBuilder: (context, index) {
                                final row = _list[index];
                                final user = row['user'] as Map<String, dynamic>?;
                                final pending = row['status']?.toString() == 'pending';

                                return Container(
                                  padding: const EdgeInsets.all(16),
                                  decoration: sectionCardDecoration(context),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        user?['name']?.toString() ?? 'User',
                                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                              fontWeight: FontWeight.w700,
                                            ),
                                      ),
                                      if (user?['email'] != null)
                                        Text(user!['email'].toString(), style: TextStyle(color: Colors.grey.shade600)),
                                      const SizedBox(height: 6),
                                      Text('Role: ${row['role_label'] ?? row['role_at_request'] ?? '–'}'),
                                      Text('Requested: ${_formatDate(row['created_at']?.toString())}'),
                                      Text('Status: ${row['status'] ?? '–'}'),
                                      const SizedBox(height: 8),
                                      Text(row['reason']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade800)),
                                      if (row['admin_note'] != null && row['admin_note'].toString().isNotEmpty)
                                        Padding(
                                          padding: const EdgeInsets.only(top: 8),
                                          child: Text('Admin note: ${row['admin_note']}'),
                                        ),
                                      if (pending) ...[
                                        const SizedBox(height: 12),
                                        Wrap(
                                          spacing: 8,
                                          children: [
                                            FilledButton(
                                              onPressed: () => _approve(row),
                                              child: const Text('Approve'),
                                            ),
                                            OutlinedButton(
                                              onPressed: () => _reject(row),
                                              child: const Text('Reject'),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}
