import 'package:flutter/foundation.dart';

import '../storage/map_view_preset_storage.dart';

enum MapDefaultMode { city, custom }

class MapViewPreset {
  final MapDefaultMode mode;
  final double? latitude;
  final double? longitude;
  final double? zoom;

  const MapViewPreset({
    required this.mode,
    this.latitude,
    this.longitude,
    this.zoom,
  });

  bool get hasCustomCoordinates => latitude != null && longitude != null;

  Map<String, dynamic> toJson() => {
    'mode': mode.name,
    'latitude': latitude,
    'longitude': longitude,
    'zoom': zoom,
  };

  factory MapViewPreset.fromJson(Map<String, dynamic> json) {
    final modeRaw = (json['mode'] as String?)?.trim().toLowerCase();
    final mode = modeRaw == MapDefaultMode.custom.name
        ? MapDefaultMode.custom
        : MapDefaultMode.city;

    return MapViewPreset(
      mode: mode,
      latitude: _toDouble(json['latitude']),
      longitude: _toDouble(json['longitude']),
      zoom: _toDouble(json['zoom']),
    );
  }

  static double? _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }
}

class MapViewPresetService {
  MapViewPresetService._();

  static final MapViewPresetService instance = MapViewPresetService._();

  final MapViewPresetStorage _storage = MapViewPresetStorage();

  final ValueNotifier<MapViewPreset> presetNotifier = ValueNotifier(
    const MapViewPreset(mode: MapDefaultMode.city),
  );

  MapViewPreset get current => presetNotifier.value;

  Future<void> init() async {
    final saved = await _storage.read();
    if (saved == null) {
      presetNotifier.value = const MapViewPreset(mode: MapDefaultMode.city);
      return;
    }

    presetNotifier.value = MapViewPreset.fromJson(saved);
  }

  Future<void> setCustom({
    required double latitude,
    required double longitude,
    required double zoom,
  }) async {
    final value = MapViewPreset(
      mode: MapDefaultMode.custom,
      latitude: latitude,
      longitude: longitude,
      zoom: zoom,
    );
    presetNotifier.value = value;
    await _storage.write(value.toJson());
  }

  Future<void> useCityDefault() async {
    const value = MapViewPreset(mode: MapDefaultMode.city);
    presetNotifier.value = value;
    await _storage.write(value.toJson());
  }
}
