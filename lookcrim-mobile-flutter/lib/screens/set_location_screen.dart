import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

import '../services/language_service.dart';
import '../services/map_tile_cache_service.dart';
import '../services/offline_sync_service.dart';
import '../services/map_view_preset_service.dart';
import '../utils/app_localizations.dart';

typedef LocationResult = ({double latitude, double longitude, String address});

class SetLocationScreen extends StatefulWidget {
  final double cityLatitude;
  final double cityLongitude;
  final double initialLatitude;
  final double initialLongitude;
  final double? initialZoom;
  final int? radiusMeters;

  const SetLocationScreen({
    super.key,
    required this.cityLatitude,
    required this.cityLongitude,
    required this.initialLatitude,
    required this.initialLongitude,
    this.initialZoom,
    this.radiusMeters,
  });

  @override
  State<SetLocationScreen> createState() => _SetLocationScreenState();
}

class _SetLocationScreenState extends State<SetLocationScreen> {
  static const _deepRed = Color(0xFF820000);
  static const _background = Color(0xFFFFFAFA);
  static const _panelFill = Color(0xFFF5ECEC);
  static const _textDark = Color(0xFF000000);
  static const _textMuted = Color(0xFF6B6B6B);
  static const _searchFill = Color(0xFFC6B6BC);
  static const _tileTemplate = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  final MapController _mapController = MapController();
  final TextEditingController _searchController = TextEditingController();

  late LatLng _cityCenter;
  late double _cityZoom;
  late double _cityRadiusMeters;
  late LatLng _defaultViewCenter;
  late double _defaultViewZoom;
  LatLng? _currentLocation;
  late LatLng _selectedLocation;
  bool _usingCurrentLocation = false;
  bool _loadingLocation = false;
  bool _showRecenter = false;
  int _mapReloadToken = 0;
  String _address = '';
  late final TileProvider _tileProvider;
  late final VoidCallback _localeListener;
  late final VoidCallback _onlineListener;

  bool get _hasCustomPreset {
    final preset = MapViewPresetService.instance.current;
    return preset.mode == MapDefaultMode.custom &&
        preset.latitude != null &&
        preset.longitude != null;
  }

  LatLng get _startingCenter {
    if (_hasCustomPreset) {
      final preset = MapViewPresetService.instance.current;
      return LatLng(preset.latitude!, preset.longitude!);
    }
    return _cityCenter;
  }

  double get _startingZoom {
    if (_hasCustomPreset) {
      final preset = MapViewPresetService.instance.current;
      return preset.zoom ?? _cityZoom;
    }
    return _cityZoom;
  }

  String get _presetAreaLabel {
    return _hasCustomPreset
        ? AppLocalizations.t('custom_area')
        : AppLocalizations.t('city_area');
  }

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _onlineListener = () {
      if (!mounted) return;
      if (OfflineSyncService.instance.onlineNotifier.value) {
        setState(() {
          _mapReloadToken++;
        });
        unawaited(_prefetchDefaultArea());
      }
    };
    OfflineSyncService.instance.onlineNotifier.addListener(_onlineListener);
    _tileProvider = MapTileCacheService.instance.provider;
    _cityCenter = LatLng(widget.cityLatitude, widget.cityLongitude);
    _cityRadiusMeters = (widget.radiusMeters ?? 4000).toDouble();
    _cityZoom = _zoomForRadius(_cityRadiusMeters);
    _selectedLocation = _startingCenter;
    _defaultViewCenter = _selectedLocation;
    _defaultViewZoom = _startingZoom;
    _address = _presetAreaLabel;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _mapController.move(_defaultViewCenter, _defaultViewZoom);
      unawaited(_prefetchDefaultArea());
    });
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    OfflineSyncService.instance.onlineNotifier.removeListener(_onlineListener);
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _requestCurrentLocation() async {
    setState(() {
      _loadingLocation = true;
    });

    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        _showError(AppLocalizations.t('location_services_disabled'));
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied) {
        _showError(AppLocalizations.t('location_permission_denied'));
        return;
      }
      if (permission == LocationPermission.deniedForever) {
        _showError(AppLocalizations.t('location_permission_denied_forever'));
        return;
      }

      Position? position = await Geolocator.getLastKnownPosition();
      position ??= await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.low,
        ),
      );

      final latLng = LatLng(position.latitude, position.longitude);
      final address = await _reverseGeocode(latLng);
      if (!mounted) return;

      setState(() {
        _currentLocation = latLng;
        _usingCurrentLocation = true;
        _address = address;
        _selectedLocation = latLng;
        _defaultViewCenter = latLng;
        _defaultViewZoom = 16;
        _showRecenter = false;
      });
      _mapController.move(latLng, 16);
      unawaited(_prefetchArea(latLng, 5000));
    } catch (_) {
      _showError(AppLocalizations.t('unable_get_current_location'));
    } finally {
      if (mounted) {
        setState(() => _loadingLocation = false);
      }
    }
  }

  Future<String> _reverseGeocode(LatLng latLng) async {
    try {
      final placemarks = await placemarkFromCoordinates(
        latLng.latitude,
        latLng.longitude,
      );
      if (placemarks.isEmpty) return _formatCoordinates(latLng);
      final place = placemarks.first;
      final parts = <String>[
        if ((place.street ?? '').trim().isNotEmpty) place.street!.trim(),
        if ((place.postalCode ?? '').trim().isNotEmpty)
          place.postalCode!.trim(),
        if ((place.locality ?? '').trim().isNotEmpty) place.locality!.trim(),
        if ((place.country ?? '').trim().isNotEmpty) place.country!.trim(),
      ];
      return parts.isEmpty ? _formatCoordinates(latLng) : parts.join(', ');
    } catch (_) {
      return _formatCoordinates(latLng);
    }
  }

  String _formatCoordinates(LatLng latLng) {
    final lat = latLng.latitude.toStringAsFixed(6);
    final lng = latLng.longitude.toStringAsFixed(6);
    return 'Lat $lat, Lng $lng';
  }

  double _zoomForRadius(double radiusMeters) {
    if (radiusMeters >= 30000) return 10.5;
    if (radiusMeters >= 20000) return 11.0;
    if (radiusMeters >= 12000) return 11.6;
    if (radiusMeters >= 8000) return 12.2;
    if (radiusMeters >= 5000) return 12.7;
    if (radiusMeters >= 3000) return 13.2;
    return 14.0;
  }

  void _showCityArea() {
    _showPresetArea();
  }

  Future<void> _showPresetArea() async {
    final preset = MapViewPresetService.instance.current;
    setState(() {
      _usingCurrentLocation = false;
      if (preset.mode == MapDefaultMode.custom &&
          preset.latitude != null &&
          preset.longitude != null) {
        _address = AppLocalizations.t('custom_area');
        _selectedLocation = LatLng(preset.latitude!, preset.longitude!);
        _defaultViewCenter = _selectedLocation;
        _defaultViewZoom = preset.zoom ?? _cityZoom;
      } else {
        _address = AppLocalizations.t('city_area');
        _selectedLocation = _cityCenter;
        _defaultViewCenter = _cityCenter;
        _defaultViewZoom = _cityZoom;
      }
      _showRecenter = false;
    });
    _mapController.move(_selectedLocation, _defaultViewZoom);
    unawaited(_prefetchDefaultArea());
  }

  Future<void> _prefetchDefaultArea() async {
    if (!await OfflineSyncService.instance.isOnline()) return;
    await MapTileCacheService.instance.prefetchRectangleArea(
      center: _defaultViewCenter,
      radiusMeters: _cityRadiusMeters,
      paddingFactor: 0.4,
      minZoom: 11,
      maxZoom: 17,
      cacheKey:
          'default_rect:${_defaultViewCenter.latitude.toStringAsFixed(4)}:${_defaultViewCenter.longitude.toStringAsFixed(4)}:${_cityRadiusMeters.toStringAsFixed(0)}',
    );
  }

  Future<void> _prefetchArea(LatLng center, double radiusMeters) async {
    if (!await OfflineSyncService.instance.isOnline()) return;
    await MapTileCacheService.instance.prefetchCircleArea(
      center: center,
      radiusMeters: radiusMeters,
      minZoom: 11,
      maxZoom: 17,
      cacheKey:
          'area:${center.latitude.toStringAsFixed(4)}:${center.longitude.toStringAsFixed(4)}:${radiusMeters.toStringAsFixed(0)}',
    );
  }

  bool _isAtDefaultView(LatLng center, double zoom) {
    const centerTolerance = 0.00035;
    const zoomTolerance = 0.20;

    final latDelta = (center.latitude - _defaultViewCenter.latitude).abs();
    final lngDelta = (center.longitude - _defaultViewCenter.longitude).abs();
    final zoomDelta = (zoom - _defaultViewZoom).abs();

    return latDelta <= centerTolerance &&
        lngDelta <= centerTolerance &&
        zoomDelta <= zoomTolerance;
  }

  void _onMapPositionChanged(dynamic position) {
    final center = position.center;
    final zoom = position.zoom;
    if (center == null || zoom == null) return;

    final showRecenter = !_isAtDefaultView(center, zoom);
    if (showRecenter == _showRecenter) return;

    if (mounted) {
      setState(() {
        _showRecenter = showRecenter;
      });
    }
  }

  void _recenterToDefaultView() {
    _mapController.move(_defaultViewCenter, _defaultViewZoom);
    setState(() {
      _showRecenter = false;
    });
  }

  Future<void> _selectLocation(LatLng latLng) async {
    setState(() {
      _selectedLocation = latLng;
      _usingCurrentLocation = false;
      _address = _formatCoordinates(latLng);
    });

    final address = await _reverseGeocode(latLng);
    if (!mounted) return;
    setState(() {
      _address = address.trim().isEmpty ? _formatCoordinates(latLng) : address;
    });
  }

  void _confirmLocation() {
    final target = _selectedLocation;
    Navigator.of(context).pop<LocationResult>((
      latitude: target.latitude,
      longitude: target.longitude,
      address: _address,
    ));
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final center = _usingCurrentLocation && _currentLocation != null
        ? _currentLocation!
        : _selectedLocation;
    final cityZoom = _usingCurrentLocation
        ? 16.0
        : (widget.initialZoom ?? _cityZoom);

    return Scaffold(
      backgroundColor: _background,
      body: SafeArea(
        child: Stack(
          children: [
            FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: center,
                initialZoom: cityZoom,
                onTap: (_, latLng) => _selectLocation(latLng),
                onPositionChanged: (position, _) =>
                    _onMapPositionChanged(position),
              ),
              children: [
                TileLayer(
                  key: ValueKey(_mapReloadToken),
                  urlTemplate: _tileTemplate,
                  userAgentPackageName: 'lookcrime_mobile',
                  tileProvider: _tileProvider,
                ),
                CircleLayer(
                  circles: _usingCurrentLocation
                      ? []
                      : [
                          CircleMarker(
                            point: _cityCenter,
                            radius: _cityRadiusMeters,
                            useRadiusInMeter: true,
                            color: _deepRed.withValues(alpha: 0.18),
                            borderStrokeWidth: 1.5,
                            borderColor: _deepRed.withValues(alpha: 0.35),
                          ),
                        ],
                ),
                MarkerLayer(
                  markers: [
                    Marker(
                      width: 44,
                      height: 44,
                      point: _selectedLocation,
                      child: const Icon(
                        Icons.location_on,
                        color: _deepRed,
                        size: 36,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            Positioned(
              top: 12,
              left: 12,
              right: 12,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      InkWell(
                        onTap: () => Navigator.of(context).maybePop(),
                        borderRadius: BorderRadius.circular(9),
                        child: Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: _deepRed,
                            borderRadius: BorderRadius.circular(9),
                          ),
                          child: const Icon(
                            Icons.chevron_left,
                            color: Colors.white,
                            size: 24,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          height: 38,
                          decoration: BoxDecoration(
                            color: _searchFill,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: TextField(
                            controller: _searchController,
                            readOnly: true,
                            decoration: InputDecoration(
                              prefixIcon: Icon(Icons.search, color: _textDark),
                              hintText: AppLocalizations.t('search'),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.only(top: 8),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Align(
                    alignment: Alignment.centerRight,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        SizedBox(
                          height: 34,
                          child: OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              backgroundColor: Colors.white,
                              foregroundColor: _deepRed,
                              side: const BorderSide(color: _deepRed),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            onPressed: _showCityArea,
                            child: Text(_presetAreaLabel),
                          ),
                        ),
                        const SizedBox(width: 8),
                        SizedBox(
                          height: 34,
                          child: FilledButton(
                            style: FilledButton.styleFrom(
                              backgroundColor: _deepRed,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 14,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            onPressed: _loadingLocation
                                ? null
                                : _requestCurrentLocation,
                            child: _loadingLocation
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(
                                    AppLocalizations.t('current_location'),
                                    style: const TextStyle(color: Colors.white),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                padding: const EdgeInsets.fromLTRB(18, 14, 18, 20),
                decoration: const BoxDecoration(
                  color: _panelFill,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      AppLocalizations.t('your_location_title'),
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        color: _textMuted,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.location_on, color: _deepRed),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _address,
                            style: Theme.of(context).textTheme.bodyMedium
                                ?.copyWith(
                                  color: _textDark,
                                  fontWeight: FontWeight.w600,
                                ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          backgroundColor: _deepRed,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        onPressed: _confirmLocation,
                        icon: const Icon(
                          Icons.location_on,
                          color: Colors.white,
                        ),
                        label: Text(
                          AppLocalizations.t('set_location'),
                          style: TextStyle(color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (_showRecenter)
              Positioned(
                right: 16,
                bottom: 166,
                child: FloatingActionButton.small(
                  backgroundColor: _deepRed,
                  foregroundColor: Colors.white,
                  tooltip: AppLocalizations.t('recenter_map'),
                  onPressed: _recenterToDefaultView,
                  child: const Icon(Icons.my_location),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
