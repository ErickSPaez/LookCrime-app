import 'dart:convert';
// dart:typed_data not required; Uint8List available from foundation import

import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:geocoding/geocoding.dart';

import '../api/lookcrime_api.dart';
import '../storage/offline_local_db.dart';
import '../utils/app_localizations.dart';

class OfflineUnavailableException implements Exception {
  final String message;

  OfflineUnavailableException(this.message);

  @override
  String toString() => message;
}

class OfflineUserContext {
  final int? userId;
  final double? cityCenterLat;
  final double? cityCenterLng;
  final int? cityRadiusMeters;
  final String? cityName;
  final List<String> permissions;

  const OfflineUserContext({
    required this.userId,
    required this.cityCenterLat,
    required this.cityCenterLng,
    required this.cityRadiusMeters,
    required this.cityName,
    required this.permissions,
  });

  bool get hasCityCoordinates => cityCenterLat != null && cityCenterLng != null;
}

class OfflineSyncService {
  OfflineSyncService._();

  static final OfflineSyncService instance = OfflineSyncService._();

  final OfflineLocalDb _db = OfflineLocalDb.instance;
  final Connectivity _connectivity = Connectivity();
  final ValueNotifier<bool> onlineNotifier = ValueNotifier<bool>(false);
  final ValueNotifier<int> pendingCountNotifier = ValueNotifier<int>(0);
  // connectivity subscription is started in init()

  Future<void> init() async {
    await _db.hasUserContext();
    // initialize notifiers
    onlineNotifier.value = await isOnline();
    pendingCountNotifier.value = await pendingCount();

    _connectivity.onConnectivityChanged.listen((_) async {
      final nowOnline = await isOnline();
      if (onlineNotifier.value != nowOnline) {
        onlineNotifier.value = nowOnline;
      }
      // keep pending count fresh on connectivity changes
      pendingCountNotifier.value = await pendingCount();
    });
  }

  Future<bool> isOnline() async {
    final result = await _connectivity.checkConnectivity();
    final str = result.toString().toLowerCase();
    return !str.contains('none');
  }

  Future<void> cacheCategories(
    String lang,
    List<({String key, String label})> categories,
  ) async {
    await _db.saveCategories(lang, categories);
    // nothing else
  }

  Future<List<({String key, String label})>> loadCachedCategories(
    String lang,
  ) async {
    return _db.loadCategories(lang);
  }

  Future<List<({String key, String label})>> getCategories({
    required String lang,
    required Future<List<({String key, String label})>> Function() remoteLoader,
  }) async {
    if (await isOnline()) {
      final remote = await remoteLoader();
      await cacheCategories(lang, remote);
      return remote;
    }

    final cached = await loadCachedCategories(lang);
    if (cached.isNotEmpty) return cached;
    throw OfflineUnavailableException(
      AppLocalizations.t('offline_missing_categories'),
    );
  }

  Future<void> cacheUserContext({
    required int? userId,
    required double? cityCenterLat,
    required double? cityCenterLng,
    required int? cityRadiusMeters,
    required String? cityName,
    required List<String> permissions,
  }) async {
    await _db.saveUserContext(
      userId: userId,
      cityCenterLat: cityCenterLat,
      cityCenterLng: cityCenterLng,
      cityRadiusMeters: cityRadiusMeters,
      cityName: cityName,
      permissions: permissions,
    );
  }

  Future<OfflineUserContext?> loadCachedUserContext() async {
    final row = await _db.loadUserContext();
    if (row == null) return null;

    final permissionsRaw = row['permissions_json'] as String?;
    final permissionsDecoded = permissionsRaw == null || permissionsRaw.isEmpty
        ? const <String>[]
        : (jsonDecode(permissionsRaw) as List).whereType<String>().toList(
            growable: false,
          );

    return OfflineUserContext(
      userId: (row['user_id'] as num?)?.toInt(),
      cityCenterLat: (row['city_center_lat'] as num?)?.toDouble(),
      cityCenterLng: (row['city_center_lng'] as num?)?.toDouble(),
      cityRadiusMeters: (row['city_radius_m'] as num?)?.toInt(),
      cityName: row['city_name'] as String?,
      permissions: permissionsDecoded,
    );
  }

  Future<OfflineUserContext> getUserContext({
    required Future<({Map<String, dynamic> user, List<String> permissions})>
    Function()
    remoteLoader,
  }) async {
    if (await isOnline()) {
      final remote = await remoteLoader();
      await cacheUserContext(
        userId: _int(remote.user['id']),
        cityCenterLat: _double(remote.user['city_center_lat']),
        cityCenterLng: _double(remote.user['city_center_lng']),
        cityRadiusMeters: _int(remote.user['city_radius_m']),
        cityName: _extractCityName(remote.user),
        permissions: remote.permissions,
      );
      return OfflineUserContext(
        userId: _int(remote.user['id']),
        cityCenterLat: _double(remote.user['city_center_lat']),
        cityCenterLng: _double(remote.user['city_center_lng']),
        cityRadiusMeters: _int(remote.user['city_radius_m']),
        cityName: _extractCityName(remote.user),
        permissions: remote.permissions,
      );
    }

    final cached = await loadCachedUserContext();
    if (cached != null) return cached;
    throw OfflineUnavailableException(
      AppLocalizations.t('offline_missing_user_context'),
    );
  }

  Future<void> cacheRegisters(List<Map<String, dynamic>> items) async {
    await _db.replaceRegisters(items);
  }

  Future<List<Map<String, dynamic>>> loadCachedRegisters({
    String? query,
  }) async {
    final cached = await _db.loadRegisters(query: query);
    final pending = await _db.loadPendingRegisters();
    if (query != null && query.trim().isNotEmpty) {
      final normalized = query.trim().toLowerCase();
      final filteredPending = pending
          .where((item) {
            final searchText = _searchBlob(item).toLowerCase();
            return searchText.contains(normalized);
          })
          .toList(growable: false);
      return [...filteredPending, ...cached];
    }
    return [...pending, ...cached];
  }

  Future<void> addPendingRegister({
    required String title,
    required String description,
    required String category,
    required String latitude,
    required String longitude,
    required String address,
    required Uint8List imageBytes,
    required String imageFilename,
  }) async {
    const maxImageBytes = 2500000; // 2.5 MB
    if (imageBytes.lengthInBytes > maxImageBytes) {
      throw Exception(AppLocalizations.t('image_too_large'));
    }

    await _db.addPendingRegister(
      title: title,
      description: description,
      category: category,
      latitude: latitude,
      longitude: longitude,
      address: address,
      imageFilename: imageFilename,
      imageBase64: base64Encode(imageBytes),
    );
    pendingCountNotifier.value = await pendingCount();
  }

  Future<int> pendingCount() async {
    return _db.pendingCount();
  }

  Future<void> cancelPending(int localId) async {
    await _db.removePendingRegister(localId);
    pendingCountNotifier.value = await pendingCount();
  }

  Future<bool> hasOfflineMinimum({String lang = 'pt'}) async {
    final hasCategories = await _db.hasCategories(lang);
    final hasContext = await _db.hasUserContext();
    final hasRegisters =
        (await _db.pendingCount()) > 0 ||
        (await _db.loadRegisters()).isNotEmpty;
    return hasCategories && hasContext && hasRegisters;
  }

  Future<int> syncPendingRegisters({
    required LookCrimeApi api,
    required String authorizationHeaderValue,
  }) async {
    if (!await isOnline()) return 0;

    final pending = await _db.loadPendingRegisters();
    var synced = 0;

    for (final item in pending) {
      final localId = item['local_id'] as int?;
      if (localId == null) continue;

      try {
        final imageBase64 = item['image_base64'] as String? ?? '';
        final imageBytes = base64Decode(imageBase64);
        final resolvedAddress = await _resolvePendingAddress(item);
        final addressToSend =
            resolvedAddress ?? item['address']?.toString() ?? '';

        if (resolvedAddress != null && resolvedAddress.trim().isNotEmpty) {
          await _db.updatePendingRegisterAddress(
            localId: localId,
            address: resolvedAddress,
            searchText: _buildPendingSearchText(
              title: item['title']?.toString() ?? '',
              description: item['description']?.toString() ?? '',
              category: item['category']?.toString() ?? '',
              latitude: item['latitude']?.toString() ?? '',
              longitude: item['longitude']?.toString() ?? '',
              address: resolvedAddress,
            ),
          );
        }

        await api.createRegister(
          authorizationHeaderValue: authorizationHeaderValue,
          title: item['title']?.toString() ?? '',
          description: item['description']?.toString() ?? '',
          category: item['category']?.toString() ?? '',
          latitude: item['latitude']?.toString() ?? '',
          longitude: item['longitude']?.toString() ?? '',
          address: addressToSend,
          imageBytes: imageBytes,
          imageFilename: item['image_filename']?.toString() ?? 'image.jpg',
        );
        await _db.removePendingRegister(localId);
        synced++;
      } catch (_) {
        // Keep the pending item for a later retry.
      }
    }

    pendingCountNotifier.value = await pendingCount();
    return synced;
  }

  Future<bool> syncSinglePending({
    required LookCrimeApi api,
    required String authorizationHeaderValue,
    required int localId,
  }) async {
    if (!await isOnline()) return false;

    final pending = await _db.loadPendingRegisters();
    final item = pending.firstWhere(
      (it) => (it['local_id'] as int?) == localId,
      orElse: () => <String, dynamic>{},
    );

    if (item.isEmpty) return false;

    try {
      final imageBase64 = item['image_base64'] as String? ?? '';
      final imageBytes = base64Decode(imageBase64);
      final resolvedAddress = await _resolvePendingAddress(item);
      final addressToSend =
          resolvedAddress ?? item['address']?.toString() ?? '';

      if (resolvedAddress != null && resolvedAddress.trim().isNotEmpty) {
        await _db.updatePendingRegisterAddress(
          localId: localId,
          address: resolvedAddress,
          searchText: _buildPendingSearchText(
            title: item['title']?.toString() ?? '',
            description: item['description']?.toString() ?? '',
            category: item['category']?.toString() ?? '',
            latitude: item['latitude']?.toString() ?? '',
            longitude: item['longitude']?.toString() ?? '',
            address: resolvedAddress,
          ),
        );
      }

      await api.createRegister(
        authorizationHeaderValue: authorizationHeaderValue,
        title: item['title']?.toString() ?? '',
        description: item['description']?.toString() ?? '',
        category: item['category']?.toString() ?? '',
        latitude: item['latitude']?.toString() ?? '',
        longitude: item['longitude']?.toString() ?? '',
        address: addressToSend,
        imageBytes: imageBytes,
        imageFilename: item['image_filename']?.toString() ?? 'image.jpg',
      );

      final id = (item['local_id'] as int?);
      if (id != null) await _db.removePendingRegister(id);
      pendingCountNotifier.value = await pendingCount();
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<String?> _resolvePendingAddress(Map<String, dynamic> item) async {
    final currentAddress = item['address']?.toString() ?? '';
    if (currentAddress.trim().isNotEmpty &&
        !_looksLikeCoordinates(currentAddress)) {
      return currentAddress.trim();
    }

    final latitude = double.tryParse(item['latitude']?.toString() ?? '');
    final longitude = double.tryParse(item['longitude']?.toString() ?? '');
    if (latitude == null || longitude == null) return null;

    try {
      final placemarks = await placemarkFromCoordinates(latitude, longitude);
      if (placemarks.isEmpty) return null;

      final place = placemarks.first;
      final parts = <String>[
        if ((place.street ?? '').trim().isNotEmpty) place.street!.trim(),
        if ((place.postalCode ?? '').trim().isNotEmpty)
          place.postalCode!.trim(),
        if ((place.locality ?? '').trim().isNotEmpty) place.locality!.trim(),
        if ((place.administrativeArea ?? '').trim().isNotEmpty)
          place.administrativeArea!.trim(),
        if ((place.country ?? '').trim().isNotEmpty) place.country!.trim(),
      ];

      if (parts.isEmpty) return null;
      return parts.join(', ');
    } catch (e) {
      debugPrint('Pending register reverse geocode failed: $e');
      return null;
    }
  }

  bool _looksLikeCoordinates(String value) {
    final normalized = value.trim().toLowerCase();
    return normalized.startsWith('lat ') || normalized.contains(', lng ');
  }

  String _buildPendingSearchText({
    required String title,
    required String description,
    required String category,
    required String latitude,
    required String longitude,
    required String address,
  }) {
    final parts = <String>[
      title,
      description,
      category,
      latitude,
      longitude,
      address,
    ];
    return parts
        .where((part) => part.trim().isNotEmpty)
        .join(' ')
        .toLowerCase();
  }

  Future<void> fetchAndCacheRegisters({
    required Future<
      ({List<Map<String, dynamic>> items, int page, int perPage, int total})
    >
    Function()
    remoteLoader,
  }) async {
    final res = await remoteLoader();
    await cacheRegisters(res.items);
  }

  /// Fetch registers from API in pages until we cover [daysBack] days (default 31)
  /// or until no more items. Caches the collected items.
  Future<void> fetchAndCacheRecentRegisters({
    required LookCrimeApi api,
    required String authorizationHeaderValue,
    int perPage = 100,
    int daysBack = 31,
  }) async {
    if (!await isOnline()) return;

    final cutoff = DateTime.now().subtract(Duration(days: daysBack));
    final collected = <Map<String, dynamic>>[];
    var page = 1;

    try {
      while (true) {
        final res = await api.getRegisters(
          authorizationHeaderValue: authorizationHeaderValue,
          page: page,
          perPage: perPage,
        );

        if (res.items.isEmpty) break;

        collected.addAll(res.items);

        final oldestInPage = _oldestCreatedAt(res.items);
        final reachedEnd = page * perPage >= res.total;

        if (reachedEnd ||
            (oldestInPage != null && oldestInPage.isBefore(cutoff))) {
          break;
        }

        page++;
      }
    } catch (e) {
      debugPrint('Recent register cache refresh stopped: $e');
    }

    if (collected.isNotEmpty) {
      await cacheRegisters(collected);
      pendingCountNotifier.value = await pendingCount();
    }
  }

  Future<bool> canOpenOfflineApp({String lang = 'pt'}) async {
    return hasOfflineMinimum(lang: lang);
  }

  String _searchBlob(Map<String, dynamic> item) {
    final parts = <String>[
      item['title']?.toString() ?? '',
      item['description']?.toString() ?? '',
      item['category']?.toString() ?? '',
      item['address']?.toString() ?? '',
      item['author_name']?.toString() ?? '',
    ];

    return parts.where((part) => part.trim().isNotEmpty).join(' ');
  }

  DateTime? _oldestCreatedAt(List<Map<String, dynamic>> items) {
    DateTime? oldest;

    for (final item in items) {
      final raw = item['created_at']?.toString();
      final parsed = raw == null ? null : DateTime.tryParse(raw);
      if (parsed == null) continue;

      if (oldest == null || parsed.isBefore(oldest)) {
        oldest = parsed;
      }
    }

    return oldest;
  }

  double? _double(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  int? _int(dynamic value) {
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }

  String? _extractCityName(Map<String, dynamic> user) {
    final cityName = user['city_name'];
    if (cityName is String && cityName.trim().isNotEmpty) {
      return cityName.trim();
    }

    final city = user['city'];
    if (city is Map) {
      final map = Map<String, dynamic>.from(city);
      final name = map['name'];
      if (name is String && name.trim().isNotEmpty) return name.trim();
    }

    return null;
  }
}
