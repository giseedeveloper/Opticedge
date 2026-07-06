import 'package:flutter/material.dart';

import '../../api/auth_api.dart';
import '../../api/client.dart';
import '../../theme/app_theme.dart';
import '../admin/widgets/admin_page_ui.dart';

class GuestWaitingScreen extends StatefulWidget {
  const GuestWaitingScreen({super.key});

  @override
  State<GuestWaitingScreen> createState() => _GuestWaitingScreenState();
}

class _GuestWaitingScreenState extends State<GuestWaitingScreen> {
  Map<String, dynamic>? _profile;
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
      final res = await apiGet('/guest/dashboard');
      final map = decodeApiJsonMap(res);
      if (res.statusCode != 200) {
        throw Exception(map?['message']?.toString() ?? 'Failed to load status');
      }
      if (!mounted) return;
      setState(() {
        _profile = map?['data'] as Map<String, dynamic>?;
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Waiting for assignment'),
        actions: [
          IconButton(
            tooltip: 'Sign out',
            onPressed: () => performLogout(),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: _loading
          ? const AdminPageLoading()
          : _error != null
              ? AdminPageError(message: _error!)
              : Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: sectionCardDecoration(context),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Hello, ${_profile?['name'] ?? 'there'}',
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _profile?['email']?.toString() ?? '',
                              style: TextStyle(color: Colors.grey.shade600),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _profile?['message']?.toString() ??
                                  'Your account is registered. A vendor administrator will assign you as an agent, team leader, or regional manager.',
                              style: const TextStyle(height: 1.4),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      OutlinedButton(
                        onPressed: _load,
                        child: const Text('Refresh status'),
                      ),
                    ],
                  ),
                ),
    );
  }
}
