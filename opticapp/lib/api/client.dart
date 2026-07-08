import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../app_navigator.dart';

/// Thrown when an authenticated API call returns 401 and the app redirects to login.
class SessionExpiredException implements Exception {
  const SessionExpiredException();
}

bool isSessionExpiredError(Object error) => error is SessionExpiredException;

Future<http.Response> _guardAuthenticatedResponse(http.Response res) async {
  if (res.statusCode != 401) return res;
  if (await getStoredToken() == null) return res;

  await clearStoredAuth();
  appNavigatorKey.currentState?.pushNamedAndRemoveUntil('/login', (route) => false);
  throw const SessionExpiredException();
}

/// Default API root when no custom URL is saved (full path including `/api`).
/// Uses staging while production Google OAuth is still being rolled out.
const String kInternalApiBaseUrl = 'https://stage.opticedgeafrica.net/api';

/// Production API root.
const String kProductionApiBaseUrl = 'https://opticedgeafrica.net/api';

/// Previous production host; auto-remapped to [kInternalApiBaseUrl].
const String _previousProductionApiBaseUrl = 'https://optic.opticedgeafrica.net/api';

const Set<String> _allowedProductionApiHosts = {
  'opticedgeafrica.net',
  'optic.opticedgeafrica.net',
  'stage.opticedgeafrica.net',
};

const String _prefsKeyServerSettingsApiUrl = 'server_settings_api_url';
const String _prefsKeyLegacyApiBaseUrlOverride = 'api_base_url_override';
const String _prefsKeyAuthApiBaseUrl = 'auth_api_base_url';

String? _cachedResolvedBaseUrl;

String _normalizeApiBaseUrl(String url) {
  var s = url.trim();
  while (s.endsWith('/')) {
    s = s.substring(0, s.length - 1);
  }
  return s;
}

/// Vendor tenant subdomains (e.g. optic-edge-africa.opticedgeafrica.net) are no longer used.
bool isLegacyTenantApiBaseUrl(String url) {
  final uri = Uri.tryParse(_normalizeApiBaseUrl(url));
  final host = uri?.host ?? '';
  if (host.isEmpty || _allowedProductionApiHosts.contains(host)) return false;
  return host.endsWith('.opticedgeafrica.net');
}

/// Accepts user input; adds `http://` when the scheme is omitted.
String? normalizeServerSettingsApiUrlInput(String value) {
  var t = value.trim();
  if (t.isEmpty) return null;
  if (!t.contains('://')) {
    t = 'http://$t';
  }
  final u = Uri.tryParse(t);
  if (u == null || !u.hasScheme || (u.scheme != 'http' && u.scheme != 'https')) {
    return null;
  }
  if (u.host.isEmpty) return null;
  return _normalizeApiBaseUrl(t);
}

/// Maps previous production API roots to [kInternalApiBaseUrl].
String _canonicalizeProductionApiUrl(String url) {
  final normalized = _normalizeApiBaseUrl(url);
  if (normalized == _normalizeApiBaseUrl(_previousProductionApiBaseUrl)) {
    return kInternalApiBaseUrl;
  }
  final uri = Uri.tryParse(normalized);
  if (uri != null &&
      _allowedProductionApiHosts.contains(uri.host) &&
      (uri.path == '/api' || uri.path.isEmpty)) {
    return kInternalApiBaseUrl;
  }
  return normalized;
}

/// Parses an API response body as JSON; throws a readable error when the server returns plain text/HTML.
dynamic decodeApiJsonBody(String body, {required int statusCode}) {
  final trimmed = body.trim();
  if (trimmed.isEmpty) {
    throw Exception('Server returned an empty response (HTTP $statusCode).');
  }
  try {
    return jsonDecode(trimmed);
  } on FormatException {
    if (trimmed.contains('Composer detected issues in your platform')) {
      throw Exception(
        'API server has an incompatible PHP runtime. '
        'Check hosting PHP version or use a working API URL in Server settings.',
      );
    }
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw Exception(
        'Server returned a web page instead of JSON. '
        'Check API URL in Server settings (expected $kInternalApiBaseUrl).',
      );
    }
    final preview = trimmed.length > 180 ? '${trimmed.substring(0, 180)}…' : trimmed;
    throw Exception('Invalid server response (HTTP $statusCode): $preview');
  }
}

Map<String, dynamic>? decodeApiJsonMap(http.Response res) {
  final decoded = decodeApiJsonBody(res.body, statusCode: res.statusCode);
  if (decoded is Map<String, dynamic>) return decoded;
  if (decoded is Map) return Map<String, dynamic>.from(decoded);
  throw Exception('Expected JSON object from server (HTTP ${res.statusCode}).');
}

void _invalidateResolvedBaseUrlCache() {
  _cachedResolvedBaseUrl = null;
}

Future<String?> _readServerSettingsApiUrl(SharedPreferences prefs) async {
  await prefs.reload();
  var raw = prefs.getString(_prefsKeyServerSettingsApiUrl);
  if (raw == null || raw.trim().isEmpty) {
    raw = prefs.getString(_prefsKeyLegacyApiBaseUrlOverride);
    if (raw != null && raw.trim().isNotEmpty) {
      final migrated = _canonicalizeProductionApiUrl(_normalizeApiBaseUrl(raw));
      await prefs.setString(_prefsKeyServerSettingsApiUrl, migrated);
      await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
      return migrated;
    }
  }
  final trimmed = raw?.trim();
  if (trimmed == null || trimmed.isEmpty) return null;
  final normalized = _canonicalizeProductionApiUrl(_normalizeApiBaseUrl(trimmed));
  if (normalized != _normalizeApiBaseUrl(trimmed)) {
    await prefs.setString(_prefsKeyServerSettingsApiUrl, normalized);
  }
  return normalized;
}

/// Resolved URL used for every request: server settings URL if saved, otherwise [kInternalApiBaseUrl].
Future<String> resolveBaseUrl() async {
  if (_cachedResolvedBaseUrl != null) return _cachedResolvedBaseUrl!;

  final prefs = await SharedPreferences.getInstance();
  final saved = await _readServerSettingsApiUrl(prefs);
  if (saved == null) {
    _cachedResolvedBaseUrl = kInternalApiBaseUrl;
    return kInternalApiBaseUrl;
  }
  if (isLegacyTenantApiBaseUrl(saved)) {
    await prefs.remove(_prefsKeyServerSettingsApiUrl);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
    _cachedResolvedBaseUrl = kInternalApiBaseUrl;
    return kInternalApiBaseUrl;
  }
  final resolved = _canonicalizeProductionApiUrl(saved);
  _cachedResolvedBaseUrl = resolved;
  return resolved;
}

/// Saves server settings API URL. Pass null or blank to clear and use [kInternalApiBaseUrl].
Future<void> setServerSettingsApiUrl(String? url) async {
  _invalidateResolvedBaseUrlCache();
  final prefs = await SharedPreferences.getInstance();
  final normalized = normalizeServerSettingsApiUrlInput(url ?? '');
  if (normalized == null) {
    await prefs.remove(_prefsKeyServerSettingsApiUrl);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
  } else {
    final remapped = _canonicalizeProductionApiUrl(normalized);
    await prefs.setString(_prefsKeyServerSettingsApiUrl, remapped);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
    _cachedResolvedBaseUrl = remapped;
  }
}

Future<String?> getServerSettingsApiUrl() async {
  final prefs = await SharedPreferences.getInstance();
  return _readServerSettingsApiUrl(prefs);
}

Future<String?> getStoredToken() async {
  final prefs = await SharedPreferences.getInstance();
  return prefs.getString('token');
}

Future<void> setStoredToken(String token) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('token', token);
  await prefs.setString(_prefsKeyAuthApiBaseUrl, await resolveBaseUrl());
}

Future<void> clearStoredAuth() async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('token');
  await prefs.remove('user');
  await prefs.remove(_prefsKeyAuthApiBaseUrl);
}

Future<bool> storedAuthMatchesResolvedBaseUrl() async {
  final prefs = await SharedPreferences.getInstance();
  final token = prefs.getString('token');
  if (token == null) return true;

  return prefs.getString(_prefsKeyAuthApiBaseUrl) == await resolveBaseUrl();
}

Future<Map<String, dynamic>?> getStoredUser() async {
  final prefs = await SharedPreferences.getInstance();
  final s = prefs.getString('user');
  if (s == null) return null;
  try {
    return jsonDecode(s) as Map<String, dynamic>;
  } catch (_) {
    return null;
  }
}

Future<void> setStoredUser(Map<String, dynamic> user) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('user', jsonEncode(user));
}

Future<http.Response> apiGet(String path, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  final res = await http.get(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
  );
  return _guardAuthenticatedResponse(res);
}

Future<http.Response> apiPost(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  final res = await http.post(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
  return _guardAuthenticatedResponse(res);
}

Future<http.Response> apiPut(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  final res = await http.put(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
  return _guardAuthenticatedResponse(res);
}

Future<http.Response> apiDelete(String path, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  final res = await http.delete(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
  );
  return _guardAuthenticatedResponse(res);
}

Future<http.Response> apiPatch(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  final res = await http.patch(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
  return _guardAuthenticatedResponse(res);
}
