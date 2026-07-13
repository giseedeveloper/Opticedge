import 'client.dart';

Future<List<Map<String, dynamic>>> listGuestUsers({String? search}) async {
  final q = (search != null && search.trim().isNotEmpty)
      ? '?search=${Uri.encodeQueryComponent(search.trim())}'
      : '';
  final res = await apiGet('/admin/guest-users$q');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load guest users');
  }
  final data = map?['data'] as List<dynamic>? ?? [];
  return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

Future<Map<String, dynamic>> getGuestUserDetail(int id) async {
  final res = await apiGet('/admin/guest-users/$id');
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to load guest profile');
  }
  return map?['data'] as Map<String, dynamic>? ?? {};
}

Future<Map<String, dynamic>> inviteGuestUser(int id, Map<String, dynamic> payload) async {
  final res = await apiPost('/admin/guest-users/$id/assign', payload);
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 201 && res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to send invitation');
  }
  return map?['data'] as Map<String, dynamic>? ?? {};
}

Future<Map<String, dynamic>> rateGuestUser(int id, {required int score, String? comment}) async {
  final res = await apiPost('/admin/guest-users/$id/ratings', {
    'score': score,
    if (comment != null && comment.trim().isNotEmpty) 'comment': comment.trim(),
  });
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to save rating');
  }
  return map ?? {};
}

Future<Map<String, dynamic>> rateFieldUser(int id, {required int score, String? comment}) async {
  final res = await apiPost('/admin/users/$id/ratings', {
    'score': score,
    if (comment != null && comment.trim().isNotEmpty) 'comment': comment.trim(),
  });
  final map = decodeApiJsonMap(res);
  if (res.statusCode != 200) {
    throw Exception(map?['message']?.toString() ?? 'Failed to save rating');
  }
  return map ?? {};
}
