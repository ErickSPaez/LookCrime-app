import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class LangStorage {
  static const _keyAppLang = 'app_lang';

  final FlutterSecureStorage _storage;

  LangStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  Future<void> writeLang(String code) async {
    await _storage.write(key: _keyAppLang, value: code);
  }

  Future<String?> readLang() async {
    return await _storage.read(key: _keyAppLang);
  }

  Future<void> clear() async {
    await _storage.delete(key: _keyAppLang);
  }
}
