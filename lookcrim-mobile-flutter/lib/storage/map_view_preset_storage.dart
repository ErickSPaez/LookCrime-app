import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class MapViewPresetStorage {
  static const _keyMapViewPreset = 'map_view_preset';

  final FlutterSecureStorage _storage;

  MapViewPresetStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  Future<void> write(Map<String, dynamic> value) async {
    await _storage.write(key: _keyMapViewPreset, value: jsonEncode(value));
  }

  Future<Map<String, dynamic>?> read() async {
    final raw = await _storage.read(key: _keyMapViewPreset);
    if (raw == null || raw.trim().isEmpty) return null;

    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
    } catch (_) {
      // Ignore invalid payload and treat as missing.
    }

    return null;
  }

  Future<void> clear() async {
    await _storage.delete(key: _keyMapViewPreset);
  }
}
