import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../config/app_config.dart';

class ApiException implements Exception {
  final int? statusCode;
  final String message;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() =>
      'ApiException(statusCode: $statusCode, message: $message)';
}

class LookCrimeApi {
  final String baseUrl;
  final http.Client _client;

  LookCrimeApi({required this.baseUrl, http.Client? client})
    : _client = client ?? http.Client();

  factory LookCrimeApi.fromConfig() {
    return LookCrimeApi(baseUrl: AppConfig.apiBaseUrl);
  }

  Uri _uri(String path, [Map<String, String>? query]) {
    final normalized = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$normalized$p').replace(queryParameters: query);
  }

  String _extractMessage(http.Response res) {
    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map) {
        final map = Map<String, dynamic>.from(decoded);
        final message = map['message'];
        if (message is String && message.trim().isNotEmpty) return message;
        final error = map['error'];
        if (error is String && error.trim().isNotEmpty) return error;
      }
    } catch (_) {}
    return res.body.isNotEmpty ? res.body : 'Error HTTP ${res.statusCode}';
  }

  String _previewBody(String body, {int maxChars = 280}) {
    final trimmed = body.trim();
    if (trimmed.isEmpty) return '';
    if (trimmed.length <= maxChars) return trimmed;
    return '${trimmed.substring(0, maxChars)}…';
  }

  List<dynamic>? _extractListFromDecoded(dynamic decoded) {
    if (decoded is List) return decoded;
    if (decoded is Map) {
      final map = Map<String, dynamic>.from(decoded);

      for (final key in const [
        'data',
        'categories',
        'register_categories',
        'items',
        'result',
      ]) {
        final v = map[key];
        if (v is List) return v;
        if (v is Map) {
          final nested = _extractListFromDecoded(v);
          if (nested != null) return nested;
        }
      }

      if (map.length == 1) {
        final onlyValue = map.values.first;
        return _extractListFromDecoded(onlyValue);
      }
    }
    return null;
  }

  Future<({String tokenType, String token})> login({
    required String email,
    required String password,
  }) async {
    final res = await _client.post(
      _uri('/api/v1/login'),
      headers: {'Accept': 'application/json'},
      body: {'email': email, 'password': password},
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    final decoded = jsonDecode(res.body);
    final map = Map<String, dynamic>.from(decoded as Map);
    final token = (map['token'] as String?) ?? '';
    final tokenType = (map['token_type'] as String?) ?? 'Bearer';

    if (token.trim().isEmpty) {
      throw ApiException('Login OK pero no se recibió token');
    }

    return (tokenType: tokenType, token: token);
  }

  Future<void> forgotPassword({required String email}) async {
    final res = await _client.post(
      _uri('/api/v1/forgot-password'),
      headers: {'Accept': 'application/json'},
      body: {'email': email},
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }
  }

  Future<List<({String key, String label})>> getRegisterCategories({
    String lang = 'pt',
  }) async {
    final res = await _client.get(
      _uri('/api/v1/register-categories', {'lang': lang}),
      headers: {'Accept': 'application/json'},
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    dynamic decoded;
    try {
      decoded = jsonDecode(res.body);
    } catch (_) {
      throw ApiException(
        'Respuesta inválida de categorías: JSON malformado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final list = _extractListFromDecoded(decoded);
    if (list == null) {
      throw ApiException(
        'Respuesta inválida de categorías: formato no soportado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    return list
        .whereType<Map>()
        .map((e) {
          final m = Map<String, dynamic>.from(e);
          return (
            key: (m['key'] as String?) ?? '',
            label: (m['label'] as String?) ?? '',
          );
        })
        .where((e) => e.key.trim().isNotEmpty)
        .toList(growable: false);
  }

  Future<({List<Map<String, dynamic>> items, int page, int perPage, int total})>
  getRegisters({
    required String authorizationHeaderValue,
    int page = 1,
    int perPage = 20,
    String? query,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
      'per_page': perPage.toString(),
    };
    if (query != null && query.trim().isNotEmpty) {
      queryParams['q'] = query.trim();
    }

    final res = await _client.get(
      _uri('/api/v1/registers', queryParams),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    dynamic decoded;
    try {
      decoded = jsonDecode(res.body);
    } catch (_) {
      throw ApiException(
        'Respuesta invalida de registros: JSON malformado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    if (decoded is! Map) {
      throw ApiException(
        'Respuesta invalida de registros: formato no soportado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final map = Map<String, dynamic>.from(decoded);
    final data = map['data'];
    if (data is! List) {
      throw ApiException(
        'Respuesta invalida de registros: data no es lista. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final meta = map['meta'] is Map
        ? Map<String, dynamic>.from(map['meta'] as Map)
        : <String, dynamic>{};

    return (
      items: data
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList(),
      page: (meta['page'] as int?) ?? page,
      perPage: (meta['per_page'] as int?) ?? perPage,
      total: (meta['total'] as int?) ?? data.length,
    );
  }

  Future<Map<String, dynamic>> getRegister({
    required String authorizationHeaderValue,
    required int id,
  }) async {
    final res = await _client.get(
      _uri('/api/v1/registers/$id'),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    dynamic decoded;

    try {
      decoded = jsonDecode(res.body);
    } catch (_) {
      throw ApiException(
        'Respuesta invalida del registro: JSON malformado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    if (decoded is! Map) {
      throw ApiException(
        'Respuesta invalida del registro: formato no soportado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final map = Map<String, dynamic>.from(decoded);

    if (map['data'] is Map) {
      return Map<String, dynamic>.from(map['data'] as Map);
    }

    return map;
  }

  Future<void> createRegister({
    required String authorizationHeaderValue,
    required String title,
    required String description,
    required String category,
    required String latitude,
    required String longitude,
    required String address,
    required List<int> imageBytes,
    required String imageFilename,
  }) async {
    final req = http.MultipartRequest('POST', _uri('/api/v1/registers'));
    req.headers['Accept'] = 'application/json';
    req.headers['Authorization'] = authorizationHeaderValue;

    req.fields['title'] = title;
    req.fields['description'] = description;
    req.fields['category'] = category;
    req.fields['latitude'] = latitude;
    req.fields['longitude'] = longitude;
    req.fields['address'] = address;

    req.files.add(
      http.MultipartFile.fromBytes(
        'image',
        imageBytes,
        filename: imageFilename,
      ),
    );

    final streamed = await req.send();
    final res = await http.Response.fromStream(streamed);

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }
  }

  Future<({Map<String, dynamic> user, List<String> permissions})> getMe({
    required String authorizationHeaderValue,
  }) async {
    final res = await _client.get(
      _uri('/api/v1/me'),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
    );

    debugPrint('GET /api/v1/me STATUS: ${res.statusCode}');
    debugPrint('GET /api/v1/me BODY: ${res.body}');

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    dynamic decoded;

    try {
      decoded = jsonDecode(res.body);
    } catch (_) {
      throw ApiException(
        'Respuesta invalida de usuario: JSON malformado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    if (decoded is! Map) {
      throw ApiException(
        'Respuesta invalida de usuario: formato no soportado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final map = Map<String, dynamic>.from(decoded);

    debugPrint('GET /api/v1/me DECODED MAP: $map');

    final user = map['user'] is Map
        ? Map<String, dynamic>.from(map['user'] as Map)
        : <String, dynamic>{};

    for (final key in const [
      'role',
      'role_name',
      'roleName',
      'user_role',
      'userRole',
    ]) {
      final value = map[key];

      if (value != null && user[key] == null) {
        user[key] = value;
      }
    }

    final permissions = map['permissions'] is List
        ? (map['permissions'] as List).whereType<String>().toList(
            growable: false,
          )
        : const <String>[];

    debugPrint('GET /api/v1/me FINAL USER MAP: $user');
    debugPrint('GET /api/v1/me PERMISSIONS: $permissions');

    return (user: user, permissions: permissions);
  }

  Future<({Map<String, dynamic> user, List<String> permissions})> updateMeName({
    required String authorizationHeaderValue,
    required String name,
  }) async {
    final res = await _client.patch(
      _uri('/api/v1/me'),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
      body: {'name': name},
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }

    dynamic decoded;

    try {
      decoded = jsonDecode(res.body);
    } catch (_) {
      throw ApiException(
        'Respuesta invalida al actualizar usuario: JSON malformado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    if (decoded is! Map) {
      throw ApiException(
        'Respuesta invalida al actualizar usuario: formato no soportado. Body: ${_previewBody(res.body)}',
        statusCode: res.statusCode,
      );
    }

    final map = Map<String, dynamic>.from(decoded);

    final user = map['user'] is Map
        ? Map<String, dynamic>.from(map['user'] as Map)
        : <String, dynamic>{};

    final permissions = map['permissions'] is List
        ? (map['permissions'] as List).whereType<String>().toList(
            growable: false,
          )
        : const <String>[];

    return (user: user, permissions: permissions);
  }

  Future<void> requestEmailChange({
    required String authorizationHeaderValue,
    required String currentPassword,
    required String newEmail,
  }) async {
    final res = await _client.post(
      _uri('/api/v1/me/email-change'),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
      body: {'current_password': currentPassword, 'email': newEmail},
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }
  }

  Future<void> updatePassword({
    required String authorizationHeaderValue,
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final res = await _client.put(
      _uri('/api/v1/me/password'),
      headers: {
        'Accept': 'application/json',
        'Authorization': authorizationHeaderValue,
      },
      body: {
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': confirmPassword,
      },
    );

    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(_extractMessage(res), statusCode: res.statusCode);
    }
  }
}
