import 'dart:convert';

import 'client.dart';

Future<List<Map<String, dynamic>>> listAdminContractTerminations({String? status}) async {
  final suffix = status != null && status.isNotEmpty ? '?status=$status' : '';
  final res = await apiGet('/admin/contract-terminations$suffix');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load contract terminations');
  }
  final data = map?['data'] as List<dynamic>? ?? [];
  return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

Future<void> approveAdminContractTermination(int id, {String? adminNote}) async {
  final res = await apiPost('/admin/contract-terminations/$id/approve', {
    if (adminNote != null && adminNote.trim().isNotEmpty) 'admin_note': adminNote.trim(),
  });
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to approve request');
  }
}

Future<void> rejectAdminContractTermination(int id, {String? adminNote}) async {
  final res = await apiPost('/admin/contract-terminations/$id/reject', {
    if (adminNote != null && adminNote.trim().isNotEmpty) 'admin_note': adminNote.trim(),
  });
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to reject request');
  }
}
