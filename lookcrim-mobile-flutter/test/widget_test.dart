import 'package:flutter_test/flutter_test.dart';

import 'package:lookcrime_mobile/api/lookcrime_api.dart';
import 'package:lookcrime_mobile/config/app_config.dart';

void main() {
  test('AppConfig.apiBaseUrl is set', () {
    expect(AppConfig.apiBaseUrl.trim(), isNotEmpty);
  });

  test('LookCrimeApi.fromConfig uses AppConfig', () {
    final api = LookCrimeApi.fromConfig();
    expect(api.baseUrl, AppConfig.apiBaseUrl);
  });
}
