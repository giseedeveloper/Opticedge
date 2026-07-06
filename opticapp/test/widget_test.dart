import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:opticapp/api/client.dart';

void main() {
  test('uses opticedgeafrica.net as the default API base URL', () async {
    SharedPreferences.setMockInitialValues({});

    await setServerSettingsApiUrl(null);

    expect(await resolveBaseUrl(), 'https://opticedgeafrica.net/api');
  });

  test('canonicalizes the previous production API host to the default', () async {
    SharedPreferences.setMockInitialValues({});

    await setServerSettingsApiUrl('https://optic.opticedgeafrica.net/api');

    expect(await getServerSettingsApiUrl(), 'https://opticedgeafrica.net/api');
    expect(await resolveBaseUrl(), 'https://opticedgeafrica.net/api');
  });

  test('rejects stored auth created before base URL tracking', () async {
    SharedPreferences.setMockInitialValues({'token': 'stale-token'});

    await setServerSettingsApiUrl(null);

    expect(await storedAuthMatchesResolvedBaseUrl(), isFalse);
  });

  test('accepts stored auth created for the current base URL', () async {
    SharedPreferences.setMockInitialValues({});

    await setServerSettingsApiUrl(null);
    await setStoredToken('fresh-token');

    expect(await storedAuthMatchesResolvedBaseUrl(), isTrue);
  });
}
