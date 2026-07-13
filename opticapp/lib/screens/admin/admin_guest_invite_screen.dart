import 'package:flutter/material.dart';

import '../../api/guest_users_api.dart';
import '../../api/users_api.dart';
import 'admin_scaffold.dart';
import 'widgets/admin_page_ui.dart';
import 'widgets/admin_users_ui.dart';

class AdminGuestInviteScreen extends StatefulWidget {
  const AdminGuestInviteScreen({super.key, required this.guestUserId, required this.guest});

  final int guestUserId;
  final Map<String, dynamic> guest;

  @override
  State<AdminGuestInviteScreen> createState() => _AdminGuestInviteScreenState();
}

class _AdminGuestInviteScreenState extends State<AdminGuestInviteScreen> {
  final _phone = TextEditingController();
  final _businessName = TextEditingController();
  final _notes = TextEditingController();
  final _message = TextEditingController();

  String _role = 'agent';
  Map<String, dynamic>? _formData;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  int? _branchId;
  int? _regionId;
  int? _teamLeaderId;
  int? _regionalManagerId;

  @override
  void initState() {
    super.initState();
    _phone.text = widget.guest['phone']?.toString() ?? '';
    _loadForm();
  }

  @override
  void dispose() {
    _phone.dispose();
    _businessName.dispose();
    _notes.dispose();
    _message.dispose();
    super.dispose();
  }

  Future<void> _loadForm() async {
    try {
      final data = await getUserCreateFormData(_role);
      if (!mounted) return;
      setState(() {
        _formData = data;
        _loading = false;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  Future<void> _onRoleChanged(String? role) async {
    if (role == null) return;
    setState(() {
      _role = role;
      _branchId = null;
      _regionId = null;
      _teamLeaderId = null;
      _regionalManagerId = null;
      _loading = true;
    });
    await _loadForm();
  }

  List<Map<String, dynamic>> _listOf(String key) {
    final raw = _formData?[key] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  Future<void> _submit() async {
    setState(() => _saving = true);
    try {
      final payload = <String, dynamic>{
        'role': _role,
        'phone': _phone.text.trim(),
        'message': _message.text.trim(),
        if (_businessName.text.trim().isNotEmpty) 'business_name': _businessName.text.trim(),
        if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(),
        if (_branchId != null) 'branch_id': _branchId,
        if (_regionId != null) 'region_id': _regionId,
        if (_teamLeaderId != null) 'team_leader_id': _teamLeaderId,
        if (_regionalManagerId != null) 'regional_manager_id': _regionalManagerId,
      };
      await inviteGuestUser(widget.guestUserId, payload);
      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final branches = _listOf('branches');
    final regions = _listOf('regions');
    final teamLeaders = _listOf('team_leaders');
    final regionalManagers = _listOf('regional_managers');

    return AdminScaffold(
      title: 'Invite guest',
      body: _loading
          ? const AdminPageLoading()
          : _error != null
              ? AdminPageError(message: _error!)
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    AdminSectionCard(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(widget.guest['name']?.toString() ?? 'Guest', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                          Text(widget.guest['email']?.toString() ?? ''),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    AdminSectionCard(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        children: [
                          DropdownButtonFormField<String>(
                            value: _role,
                            decoration: const InputDecoration(labelText: 'Role', border: OutlineInputBorder()),
                            items: const [
                              DropdownMenuItem(value: 'agent', child: Text('Agent')),
                              DropdownMenuItem(value: 'teamleader', child: Text('Team leader')),
                              DropdownMenuItem(value: 'regional_manager', child: Text('Regional manager')),
                            ],
                            onChanged: _onRoleChanged,
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _phone,
                            decoration: const InputDecoration(labelText: 'Phone', border: OutlineInputBorder()),
                          ),
                          if (_role == 'agent' || _role == 'teamleader') ...[
                            const SizedBox(height: 12),
                            DropdownButtonFormField<int?>(
                              value: _branchId,
                              decoration: InputDecoration(
                                labelText: _role == 'teamleader' ? 'Branch *' : 'Branch',
                                border: const OutlineInputBorder(),
                              ),
                              items: [
                                const DropdownMenuItem<int?>(value: null, child: Text('None')),
                                ...branches.map((b) => DropdownMenuItem<int?>(
                                      value: (b['id'] as num).toInt(),
                                      child: Text(b['name']?.toString() ?? ''),
                                    )),
                              ],
                              onChanged: (v) => setState(() => _branchId = v),
                            ),
                          ],
                          if (_role == 'agent') ...[
                            const SizedBox(height: 12),
                            DropdownButtonFormField<int?>(
                              value: _teamLeaderId,
                              decoration: const InputDecoration(labelText: 'Team leader', border: OutlineInputBorder()),
                              items: [
                                const DropdownMenuItem<int?>(value: null, child: Text('None')),
                                ...teamLeaders.map((t) => DropdownMenuItem<int?>(
                                      value: (t['id'] as num).toInt(),
                                      child: Text(t['name']?.toString() ?? ''),
                                    )),
                              ],
                              onChanged: (v) => setState(() => _teamLeaderId = v),
                            ),
                          ],
                          if (_role == 'teamleader' || _role == 'regional_manager') ...[
                            const SizedBox(height: 12),
                            DropdownButtonFormField<int?>(
                              value: _regionId,
                              decoration: const InputDecoration(labelText: 'Region *', border: OutlineInputBorder()),
                              items: [
                                const DropdownMenuItem<int?>(value: null, child: Text('Select region')),
                                ...regions.map((r) => DropdownMenuItem<int?>(
                                      value: (r['id'] as num).toInt(),
                                      child: Text(r['name']?.toString() ?? ''),
                                    )),
                              ],
                              onChanged: (v) => setState(() => _regionId = v),
                            ),
                          ],
                          if (_role == 'teamleader') ...[
                            const SizedBox(height: 12),
                            DropdownButtonFormField<int?>(
                              value: _regionalManagerId,
                              decoration: const InputDecoration(labelText: 'Regional manager *', border: OutlineInputBorder()),
                              items: [
                                const DropdownMenuItem<int?>(value: null, child: Text('Select RM')),
                                ...regionalManagers.map((r) => DropdownMenuItem<int?>(
                                      value: (r['id'] as num).toInt(),
                                      child: Text(r['name']?.toString() ?? ''),
                                    )),
                              ],
                              onChanged: (v) => setState(() => _regionalManagerId = v),
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _businessName,
                              decoration: const InputDecoration(labelText: 'Business name', border: OutlineInputBorder()),
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _notes,
                              maxLines: 3,
                              decoration: const InputDecoration(labelText: 'Notes', border: OutlineInputBorder()),
                            ),
                          ],
                          if (_role == 'regional_manager') ...[
                            const SizedBox(height: 12),
                            TextField(
                              controller: _businessName,
                              decoration: const InputDecoration(labelText: 'Business name', border: OutlineInputBorder()),
                            ),
                          ],
                          const SizedBox(height: 12),
                          TextField(
                            controller: _message,
                            maxLines: 3,
                            decoration: const InputDecoration(
                              labelText: 'Message to guest',
                              border: OutlineInputBorder(),
                              alignLabelWithHint: true,
                            ),
                          ),
                          const SizedBox(height: 16),
                          FilledButton(
                            onPressed: _saving ? null : _submit,
                            child: _saving
                                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                : const Text('Send invitation'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
    );
  }
}
