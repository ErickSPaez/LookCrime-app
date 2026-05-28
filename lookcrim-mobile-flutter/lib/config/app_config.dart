import 'dart:convert';

import 'package:flutter/services.dart';

class AppConfig {
  static const String _envApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue:
        'https://lookcrime-staging-862536847304.europe-southwest1.run.app',
  );

  static String apiBaseUrl = _envApiBaseUrl;

  static Future<void> init() async {
    try {
      final raw = await rootBundle.loadString('assets/config/app_config.json');
      final decoded = jsonDecode(raw);

      if (decoded is Map) {
        final map = Map<String, dynamic>.from(decoded);
        final fromFile = map['apiBaseUrl'];

        if (fromFile is String && fromFile.trim().isNotEmpty) {
          apiBaseUrl = fromFile.trim();
          return;
        }
      }
    } catch (_) {
      // Fall back to compile-time value when config file is missing/invalid.
    }

    apiBaseUrl = _envApiBaseUrl.trim();
  }
}
