import 'dart:convert';

import 'client.dart';

Future<List<Map<String, dynamic>>> listContractTerminationRequests({
  required String apiPrefix,
  String? status,
}) async {
  final suffix = status != null && status.isNotEmpty ? '?status=$status' : '';
  final res = await apiGet('/$apiPrefix/contract-termination$suffix');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load requests');
  }
  final data = map?['data'] as List<dynamic>? ?? [];
  return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

Future<Map<String, dynamic>> submitContractTerminationRequest({
  required String apiPrefix,
  required String reason,
}) async {
  final res = await apiPost('/$apiPrefix/contract-termination', {'reason': reason});
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 201 && res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to submit request');
  }
  return map;
}

Future<void> cancelContractTerminationRequest({
  required String apiPrefix,
  required int id,
}) async {
  final res = await apiPost('/$apiPrefix/contract-termination/$id/cancel', {});
  final map = jsonDecode(res.body) as Map<String, dynamic>;
  if (res.statusCode != 200) {
    throw Exception(map['message']?.toString() ?? 'Failed to cancel request');
  }
}

String contractTerminationApiPrefixForRole(String role) {
  return switch (role) {
    'agent' => 'agent',
    'teamleader' => 'team-leader',
    'regional_manager' => 'regional-manager',
    _ => 'agent',
  };
}

String contractTerminationRouteForRole(String role) {
  return switch (role) {
    'agent' => '/agent/contract-termination',
    'teamleader' => '/team-leader/contract-termination',
    'regional_manager' => '/regional-manager/contract-termination',
    _ => '/agent/contract-termination',
  };
}
