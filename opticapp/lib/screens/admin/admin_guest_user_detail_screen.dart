import 'package:flutter/material.dart';

import '../../api/guest_users_api.dart';
import 'admin_guest_invite_screen.dart';
import 'admin_scaffold.dart';
import 'widgets/admin_page_ui.dart';
import 'widgets/admin_users_ui.dart';

class AdminGuestUserDetailScreen extends StatefulWidget {
  const AdminGuestUserDetailScreen({super.key, required this.guestUserId});

  final int guestUserId;

  @override
  State<AdminGuestUserDetailScreen> createState() => _AdminGuestUserDetailScreenState();
}

class _AdminGuestUserDetailScreenState extends State<AdminGuestUserDetailScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;
  int _score = 5;
  final _comment = TextEditingController();
  bool _savingRating = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await getGuestUserDetail(widget.guestUserId);
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

  Future<void> _saveRating() async {
    setState(() => _savingRating = true);
    try {
      await rateGuestUser(widget.guestUserId, score: _score, comment: _comment.text);
      if (!mounted) return;
      _comment.clear();
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rating saved.')));
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))));
    } finally {
      if (mounted) setState(() => _savingRating = false);
    }
  }

  String _formatDate(String? iso) {
    if (iso == null || iso.isEmpty) return '–';
    try {
      final d = DateTime.parse(iso).toLocal();
      return '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
    } catch (_) {
      return iso;
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = _data;
    final summary = data?['rating_summary'] as Map<String, dynamic>?;
    final history = (data?['work_history'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final ratings = (data?['ratings'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final count = (summary?['count'] as num?)?.toInt() ?? 0;

    return AdminScaffold(
      title: 'Guest profile',
      body: _loading
          ? const AdminPageLoading()
          : _error != null
              ? AdminPageError(message: _error!)
              : data == null
                  ? const AdminPageEmpty(icon: Icons.person_off, title: 'Guest not found')
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        AdminSectionCard(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(data['name']?.toString() ?? '–', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                              Text(data['email']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade700)),
                              if ((data['phone']?.toString() ?? '').isNotEmpty)
                                Text(data['phone'].toString(), style: TextStyle(color: Colors.grey.shade700)),
                              const SizedBox(height: 8),
                              Text(
                                count > 0
                                    ? 'Average ${summary?['average'] ?? '–'} / 5 ($count ratings)'
                                    : 'No ratings yet',
                                style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFFFA8900)),
                              ),
                              if ((data['experience_bio']?.toString() ?? '').isNotEmpty) ...[
                                const SizedBox(height: 12),
                                const Text('Experience', style: TextStyle(fontWeight: FontWeight.w700)),
                                Text(data['experience_bio'].toString()),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton.icon(
                          onPressed: () async {
                            final sent = await Navigator.push<bool>(
                              context,
                              MaterialPageRoute(
                                builder: (_) => AdminGuestInviteScreen(guestUserId: widget.guestUserId, guest: data),
                              ),
                            );
                            if (sent == true && mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Invitation sent.')));
                              Navigator.pop(context);
                            }
                          },
                          icon: const Icon(Icons.send_outlined),
                          label: const Text('Send invitation'),
                        ),
                        const SizedBox(height: 16),
                        AdminSectionCard(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Work history', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                              const SizedBox(height: 8),
                              if (history.isEmpty)
                                Text('No prior work history.', style: TextStyle(color: Colors.grey.shade600))
                              else
                                ...history.map((t) {
                                  final ended = t['ended_at']?.toString();
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 10),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(t['vendor_name']?.toString() ?? 'Vendor', style: const TextStyle(fontWeight: FontWeight.w600)),
                                        Text(t['role_label']?.toString() ?? ''),
                                        Text(
                                          '${_formatDate(t['started_at']?.toString())} – ${ended == null || ended.isEmpty ? 'Present' : _formatDate(ended)}',
                                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                        ),
                                      ],
                                    ),
                                  );
                                }),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        AdminSectionCard(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Ratings', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                              const SizedBox(height: 8),
                              if (ratings.isEmpty)
                                Text('No ratings yet.', style: TextStyle(color: Colors.grey.shade600))
                              else
                                ...ratings.map((r) => Padding(
                                      padding: const EdgeInsets.only(bottom: 8),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Expanded(child: Text(r['vendor_name']?.toString() ?? 'Vendor', style: const TextStyle(fontWeight: FontWeight.w600))),
                                              Text('${r['score']}/5', style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFFFA8900))),
                                            ],
                                          ),
                                          if ((r['comment']?.toString() ?? '').isNotEmpty) Text(r['comment'].toString()),
                                        ],
                                      ),
                                    )),
                              const Divider(height: 24),
                              const Text('Rate this worker', style: TextStyle(fontWeight: FontWeight.w700)),
                              const SizedBox(height: 8),
                              DropdownButtonFormField<int>(
                                value: _score,
                                decoration: const InputDecoration(labelText: 'Score', border: OutlineInputBorder()),
                                items: [5, 4, 3, 2, 1]
                                    .map((s) => DropdownMenuItem(value: s, child: Text('$s / 5')))
                                    .toList(),
                                onChanged: (v) => setState(() => _score = v ?? 5),
                              ),
                              const SizedBox(height: 8),
                              TextField(
                                controller: _comment,
                                maxLines: 3,
                                decoration: const InputDecoration(
                                  labelText: 'Comment',
                                  border: OutlineInputBorder(),
                                  alignLabelWithHint: true,
                                ),
                              ),
                              const SizedBox(height: 12),
                              FilledButton(
                                onPressed: _savingRating ? null : _saveRating,
                                child: _savingRating
                                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                    : const Text('Save rating'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
    );
  }
}
