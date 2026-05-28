import 'dart:async';
import 'dart:math';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_map_cache/flutter_map_cache.dart';
import 'package:latlong2/latlong.dart';

import 'map_cache_store.dart';
import 'offline_sync_service.dart';

class MapTileCacheService {
  MapTileCacheService._();

  static final MapTileCacheService instance = MapTileCacheService._();

  static const String _tileTemplate =
      'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  static final Map<String, String> _requestHeaders = {
    'User-Agent': 'lookcrime_mobile',
  };

  bool _initialized = false;
  late final CachedTileProvider _provider;
  final Set<String> _activePrefetches = <String>{};

  TileProvider get provider {
    if (!_initialized) {
      throw StateError('MapTileCacheService.init() must be called first.');
    }
    return _provider;
  }

  Future<void> init() async {
    if (_initialized) return;

    final store = await createMapCacheStore();
    _provider = CachedTileProvider(
      store: store,
      headers: Map<String, String>.from(_requestHeaders),
      maxStale: const Duration(days: 30),
    );

    _initialized = true;
  }

  Future<void> prefetchCircleArea({
    required LatLng center,
    required double radiusMeters,
    int minZoom = 11,
    int maxZoom = 17,
    String? cacheKey,
  }) async {
    if (!await OfflineSyncService.instance.isOnline()) return;
    if (!_initialized) {
      await init();
    }

    final key =
        cacheKey ??
        '${center.latitude.toStringAsFixed(4)}:${center.longitude.toStringAsFixed(4)}:${radiusMeters.toStringAsFixed(0)}:$minZoom:$maxZoom';
    if (_activePrefetches.contains(key)) return;

    _activePrefetches.add(key);

    unawaited(() async {
      try {
        final bounds = _boundsAroundCircle(center, radiusMeters);
        await _prefetchBounds(
          bounds: bounds,
          minZoom: minZoom,
          maxZoom: maxZoom,
        );
      } catch (e) {
        debugPrint('Map tile prefetch failed: $e');
      } finally {
        _activePrefetches.remove(key);
      }
    }());
  }

  Future<void> prefetchRectangleArea({
    required LatLng center,
    required double radiusMeters,
    double paddingFactor = 0.35,
    int minZoom = 11,
    int maxZoom = 17,
    String? cacheKey,
  }) async {
    if (!await OfflineSyncService.instance.isOnline()) return;
    if (!_initialized) {
      await init();
    }

    final key =
        cacheKey ??
        'rect:${center.latitude.toStringAsFixed(4)}:${center.longitude.toStringAsFixed(4)}:${radiusMeters.toStringAsFixed(0)}:$minZoom:$maxZoom:${paddingFactor.toStringAsFixed(2)}';
    if (_activePrefetches.contains(key)) return;

    _activePrefetches.add(key);

    unawaited(() async {
      try {
        const distance = Distance();
        final paddedRadius = radiusMeters * (1 + paddingFactor);

        final north = distance.offset(center, paddedRadius, 0);
        final south = distance.offset(center, paddedRadius, 180);
        final east = distance.offset(center, paddedRadius, 90);
        final west = distance.offset(center, paddedRadius, 270);

        final bounds = LatLngBounds(
          LatLng(south.latitude, west.longitude),
          LatLng(north.latitude, east.longitude),
        );

        await _prefetchBounds(
          bounds: bounds,
          minZoom: minZoom,
          maxZoom: maxZoom,
        );
      } catch (e) {
        debugPrint('Map tile rectangle prefetch failed: $e');
      } finally {
        _activePrefetches.remove(key);
      }
    }());
  }

  Future<void> _prefetchBounds({
    required LatLngBounds bounds,
    required int minZoom,
    required int maxZoom,
  }) async {
    final urls = <String>[];

    for (var zoom = minZoom; zoom <= maxZoom; zoom++) {
      final topLeft = _latLngToTile(bounds.northWest, zoom);
      final bottomRight = _latLngToTile(bounds.southEast, zoom);
      final xStart = min(topLeft.x, bottomRight.x);
      final xEnd = max(topLeft.x, bottomRight.x);
      final yStart = min(topLeft.y, bottomRight.y);
      final yEnd = max(topLeft.y, bottomRight.y);

      for (var x = xStart; x <= xEnd; x++) {
        for (var y = yStart; y <= yEnd; y++) {
          urls.add(_tileUrl(zoom: zoom, x: x, y: y));
        }
      }
    }

    await _prefetchUrls(urls);
  }

  Future<void> _prefetchUrls(List<String> urls) async {
    const batchSize = 8;
    for (var i = 0; i < urls.length; i += batchSize) {
      final batch = urls.skip(i).take(batchSize).toList(growable: false);
      await Future.wait(batch.map(_prefetchUrl));
    }
  }

  Future<void> _prefetchUrl(String url) async {
    try {
      await _provider.dio.get<List<int>>(
        url,
        options: Options(
          responseType: ResponseType.bytes,
          headers: Map<String, String>.from(_requestHeaders),
        ),
      );
    } catch (_) {
      // Ignore individual tile failures while warming the cache.
    }
  }

  String _tileUrl({required int zoom, required int x, required int y}) {
    return _tileTemplate
        .replaceAll('{z}', zoom.toString())
        .replaceAll('{x}', x.toString())
        .replaceAll('{y}', y.toString());
  }

  _TileCoordinate _latLngToTile(LatLng point, int zoom) {
    final scale = 1 << zoom;
    final latRadians = point.latitude * pi / 180.0;
    final x = ((point.longitude + 180.0) / 360.0 * scale).floor();
    final y =
        ((1.0 - log(tan(latRadians) + 1 / cos(latRadians)) / pi) / 2.0 * scale)
            .floor();
    return _TileCoordinate(
      x: x.clamp(0, scale - 1).toInt(),
      y: y.clamp(0, scale - 1).toInt(),
    );
  }

  LatLngBounds _boundsAroundCircle(LatLng center, double radiusMeters) {
    const distance = Distance();
    final north = distance.offset(center, radiusMeters, 0);
    final south = distance.offset(center, radiusMeters, 180);
    final east = distance.offset(center, radiusMeters, 90);
    final west = distance.offset(center, radiusMeters, 270);

    return LatLngBounds(
      LatLng(south.latitude, west.longitude),
      LatLng(north.latitude, east.longitude),
    );
  }
}

class _TileCoordinate {
  const _TileCoordinate({required this.x, required this.y});

  final int x;
  final int y;
}
