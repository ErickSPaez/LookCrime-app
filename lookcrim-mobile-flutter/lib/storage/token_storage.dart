import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  static const _keyTokenType = 'token_type';
  static const _keyToken = 'token';

  final FlutterSecureStorage _storage;

  TokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  Future<void> writeToken({required String tokenType, required String token}) async {
    await _storage.write(key: _keyTokenType, value: tokenType);
    await _storage.write(key: _keyToken, value: token);
  }

  Future<void> clear() async {
    await _storage.delete(key: _keyTokenType);
    await _storage.delete(key: _keyToken);
  }

  Future<String?> readAuthorizationHeaderValue() async {
    final tokenType = await _storage.read(key: _keyTokenType);
    final token = await _storage.read(key: _keyToken);
    if (tokenType == null || token == null) return null;
    final trimmedType = tokenType.trim();
    final trimmedToken = token.trim();
    if (trimmedType.isEmpty || trimmedToken.isEmpty) return null;
    return '$trimmedType $trimmedToken';
  }
}
