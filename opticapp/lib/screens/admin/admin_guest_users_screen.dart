import 'package:flutter/material.dart';

import '../../api/guest_users_api.dart';
import 'admin_guest_user_detail_screen.dart';
import 'admin_scaffold.dart';
import 'widgets/admin_page_ui.dart';
import 'widgets/admin_users_ui.dart';

class AdminGuestUsersScreen extends StatefulWidget {
  const AdminGuestUsersScreen({super.key});

  @override
  State<AdminGuestUsersScreen> createState() => _AdminGuestUsersScreenState();
}

class _AdminGuestUsersScreenState extends State<AdminGuestUsersScreen> {
  final _search = TextEditingController();
  List<Map<String, dynamic>> _list = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await listGuestUsers(search: _search.text);
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

  @override
  Widget build(BuildContext context) {
    return AdminScaffold(
      title: 'Guest users',
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AdminUsersPageHeader(
            eyebrow: 'Users',
            title: 'Guest users',
            subtitle: 'Review work history and ratings, then invite guests to join your vendor.',
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _search,
                    decoration: const InputDecoration(
                      labelText: 'Search name or email',
                      border: OutlineInputBorder(),
                      isDense: true,
                    ),
                    onSubmitted: (_) => _load(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(onPressed: _load, child: const Text('Search')),
              ],
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const AdminPageLoading();
    if (_error != null) return AdminPageError(message: _error!);
    if (_list.isEmpty) {
      return const AdminPageEmpty(icon: Icons.person_search_outlined, title: 'No guest users found');
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        itemCount: _list.length,
        itemBuilder: (context, index) {
          final u = _list[index];
          final id = (u['id'] as num?)?.toInt();
          final count = (u['ratings_count'] as num?)?.toInt() ?? 0;
          final avg = u['avg_rating'];
          return Container(
            margin: const EdgeInsets.only(bottom: 10),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: id == null
                    ? null
                    : () async {
                        await Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => AdminGuestUserDetailScreen(guestUserId: id)),
                        );
                        _load();
                      },
                child: AdminSectionCard(
                  padding: const EdgeInsets.all(14),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(u['name']?.toString() ?? '–', style: const TextStyle(fontWeight: FontWeight.w700)),
                            Text(u['email']?.toString() ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                            const SizedBox(height: 4),
                            Text(
                              count > 0 ? 'Rating $avg / 5 ($count)' : 'No ratings yet',
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                            ),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
