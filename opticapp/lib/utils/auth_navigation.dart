import 'package:flutter/material.dart';

import '../api/client.dart';

/// Navigate to the correct home screen after a successful login or registration.
void navigateForAuthenticatedUser(BuildContext context, Map<String, dynamic>? user) {
  if (!context.mounted || user == null) return;

  final role = user['role'] as String?;
  final status = user['status'] as String? ?? 'active';

  if (role == 'admin' || role == 'subadmin') {
    Navigator.pushReplacementNamed(context, '/admin/dashboard');
    return;
  }
  if (role == 'superadmin') {
    Navigator.pushReplacementNamed(context, '/superadmin/dashboard');
    return;
  }
  if (role == 'agent') {
    Navigator.pushReplacementNamed(context, '/agent/dashboard');
    return;
  }
  if (role == 'regional_manager') {
    Navigator.pushReplacementNamed(context, '/regional-manager/dashboard');
    return;
  }
  if (role == 'teamleader') {
    Navigator.pushReplacementNamed(context, '/team-leader/dashboard');
    return;
  }
  if (role == 'guest') {
    Navigator.pushReplacementNamed(context, '/guest/waiting');
    return;
  }
  if (role == 'customer' || role == 'dealer') {
    if (role == 'dealer' && status != 'active') {
      Navigator.pushReplacementNamed(context, '/shop/dealer-pending');
    } else {
      Navigator.pushReplacementNamed(context, '/shop/dashboard');
    }
    return;
  }
  Navigator.pushReplacementNamed(context, '/home');
}

/// Load stored user and navigate to their portal.
Future<void> navigateForStoredUser(BuildContext context) async {
  final user = await getStoredUser();
  if (!context.mounted) return;
  navigateForAuthenticatedUser(context, user);
}
