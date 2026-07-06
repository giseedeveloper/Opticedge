import 'dart:convert';

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

Future<void> updateGuestProfile({required String name, String? phone}) async {
  final res = await apiPut('/guest/profile', {
    'name': name,
    if (phone != null) 'phone': phone,
  });
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to update profile');
  }
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
