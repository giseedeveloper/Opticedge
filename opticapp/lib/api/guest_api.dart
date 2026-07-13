import 'dart:convert';

import 'package:http/http.dart' as http;

import 'client.dart';

Future<Map<String, dynamic>> getGuestDashboard() async {
  final res = await apiGet('/guest/dashboard');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load dashboard');
  }
  return map?['data'] as Map<String, dynamic>? ?? {};
}

Future<Map<String, dynamic>> getGuestProfile() async {
  final res = await apiGet('/guest/profile');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load profile');
  }
  return map?['data'] as Map<String, dynamic>? ?? {};
}

Future<Map<String, dynamic>> updateGuestProfile({
  required String name,
  String? phone,
  String? experienceBio,
}) async {
  final res = await apiPut('/guest/profile', {
    'name': name,
    'phone': phone ?? '',
    'experience_bio': experienceBio ?? '',
  });
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to update profile');
  }
  final data = map?['data'] as Map<String, dynamic>? ?? {};
  final stored = await getStoredUser();
  if (stored != null) {
    await setStoredUser({...stored, ...data});
  }
  return data;
}

Future<List<Map<String, dynamic>>> getGuestInvitations({String status = 'pending'}) async {
  final res = await apiGet('/guest/invitations?status=$status');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load requests');
  }
  final data = map?['data'] as List<dynamic>? ?? [];
  return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

Future<Map<String, dynamic>> acceptGuestInvitation(int id) async {
  final res = await apiPost('/guest/invitations/$id/accept', {});
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to accept invitation');
  }
  final user = map['data']?['user'] as Map<String, dynamic>?;
  if (user != null) {
    await setStoredUser(user);
  }
  return map;
}

Future<void> declineGuestInvitation(int id) async {
  final res = await apiPost('/guest/invitations/$id/decline', {});
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to decline invitation');
  }
}

String guestRoleDashboardRoute(String role) {
  return switch (role) {
    'agent' => '/agent/dashboard',
    'teamleader' => '/team-leader/dashboard',
    'regional_manager' => '/regional-manager/dashboard',
    _ => '/guest/waiting',
  };
}

/// Web app root (without `/api`) for routes like `/db/migrate`.
Future<String> resolveWebBaseUrl() async {
  final api = await resolveBaseUrl();
  if (api.endsWith('/api')) {
    return api.substring(0, api.length - 4);
  }
  final uri = Uri.parse(api);
  return Uri(
    scheme: uri.scheme,
    host: uri.host,
    port: uri.hasPort ? uri.port : null,
  ).toString();
}

Future<List<dynamic>> getPublicPackages() async {
  final res = await apiGet('/public/packages', token: null);
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load packages');
  }
  return map?['data'] as List<dynamic>? ?? [];
}

Future<Map<String, dynamic>> createVendorSubscribeIntent({
  required String packageSlug,
  required String vendorName,
  required String brandName,
  required String adminName,
  required String email,
  required String phone,
  required String password,
  required String passwordConfirmation,
}) async {
  final res = await apiPost('/public/vendor-subscribe/intent', {
    'package_slug': packageSlug,
    'vendor_name': vendorName,
    'brand_name': brandName,
    'admin_name': adminName,
    'email': email,
    'phone': phone,
    'password': password,
    'password_confirmation': passwordConfirmation,
  }, token: null);
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 201 && res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to save registration');
  }
  return map ?? {};
}

Future<void> startVendorPayment({
  required int intentId,
  required String paymentPhone,
}) async {
  final res = await apiPost('/public/vendor-subscribe/intent/$intentId/pay', {
    'payment_phone': paymentPhone,
  }, token: null);
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to start payment');
  }
}

Future<Map<String, dynamic>> pollVendorSubscribeStatus(int intentId) async {
  final res = await apiGet('/public/vendor-subscribe/intent/$intentId/status', token: null);
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to check payment status');
  }
  return map ?? {};
}

Future<String> sendEmailVerification() async {
  final res = await apiPost('/email/verification-notification', {});
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to send verification email');
  }
  return map?['message']?.toString() ?? 'Verification link sent.';
}

Future<String> verifyEmailWithHash({
  required int userId,
  required String hash,
}) async {
  final res = await apiPost('/email/verify/$userId/$hash', {});
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Email verification failed');
  }
  return map?['message']?.toString() ?? 'Email verified successfully.';
}

Future<Map<String, dynamic>> runDbSetupAction(String action, String password) async {
  final webBase = await resolveWebBaseUrl();
  final path = switch (action) {
    'migrate' => '/db/migrate',
    'seed' => '/db/seed',
    'setup' => '/db/setup/run',
    _ => throw ArgumentError('Unknown DB setup action: $action'),
  };
  final uri = Uri.parse('$webBase$path').replace(queryParameters: {'pass': password});
  final res = await http.get(uri, headers: {'Accept': 'application/json'});
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200 || map?['ok'] == false) {
    throw Exception(map?['message']?.toString() ?? 'DB setup failed (HTTP ${res.statusCode})');
  }
  return map ?? {};
}
