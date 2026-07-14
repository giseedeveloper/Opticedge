import 'dart:convert';

import 'client.dart';

Future<List<Map<String, dynamic>>> listMajorContractTerminationApprovals({
  required String apiPrefix,
  String? status,
}) async {
  final suffix = status != null && status.isNotEmpty ? '?status=$status' : '';
  final res = await apiGet('/$apiPrefix/contract-termination-approvals$suffix');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load approvals');
  }
  final data = map?['data'] as List<dynamic>? ?? [];
  return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

Future<void> approveMajorContractTermination({
  required String apiPrefix,
  required int id,
  String? note,
}) async {
  final res = await apiPost('/$apiPrefix/contract-termination-approvals/$id/approve', {
    if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
  });
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to approve');
  }
}

Future<void> rejectMajorContractTermination({
  required String apiPrefix,
  required int id,
  String? note,
}) async {
  final res = await apiPost('/$apiPrefix/contract-termination-approvals/$id/reject', {
    if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
  });
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to reject');
  }
}
