import 'package:flutter/services.dart';
import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';

/// Google sign-in via the same Laravel Socialite redirect flow as the website.
class WebsiteGoogleAuthService {
  WebsiteGoogleAuthService._();

  static const String callbackScheme = 'app.opticedgesales.com';

  /// Opens the website OAuth page and returns a Sanctum token, or null if cancelled.
  static Future<String?> signInViaWebsite(String authUrl) async {
    try {
      final result = await FlutterWebAuth2.authenticate(
        url: authUrl,
        callbackUrlScheme: callbackScheme,
      );
      final uri = Uri.parse(result);
      final error = uri.queryParameters['error'];
      if (error != null && error.isNotEmpty) {
        throw Exception(Uri.decodeComponent(error));
      }
      final token = uri.queryParameters['token'];
      if (token == null || token.isEmpty) {
        throw Exception('Google sign-in did not return a session token.');
      }
      return token;
    } on PlatformException catch (e) {
      if (e.code == 'CANCELED' || e.code == 'cancelled') {
        return null;
      }
      rethrow;
    }
  }
}
