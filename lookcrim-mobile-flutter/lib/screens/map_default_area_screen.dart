import 'package:flutter/material.dart';
import 'dart:async';

import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:latlong2/latlong.dart';

import '../services/map_view_preset_service.dart';
import '../utils/app_localizations.dart';

class MapDefaultAreaScreen extends StatefulWidget {
  final double cityLatitude;
  final double cityLongitude;
  final double cityZoom;
  final double initialLatitude;
  final double initialLongitude;
  final double initialZoom;

  const MapDefaultAreaScreen({
    super.key,
    required this.cityLatitude,
    required this.cityLongitude,
    required this.cityZoom,
    required this.initialLatitude,
    required this.initialLongitude,
    required this.initialZoom,
  });

  @override
  State<MapDefaultAreaScreen> createState() => _MapDefaultAreaScreenState();
}

class _MapDefaultAreaScreenState extends State<MapDefaultAreaScreen> {
  static const _deepRed = Color(0xFF820000);
  static const _tileTemplate = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  final MapController _mapController = MapController();

  late LatLng _selectedCenter;
  late double _selectedZoom;
  String _address = '';
  Timer? _addressTimer;

  @override
  void initState() {
    super.initState();
    _selectedCenter = LatLng(widget.initialLatitude, widget.initialLongitude);
    _selectedZoom = widget.initialZoom;
    // Initialize address for the starting center
    _updateAddressDebounced(_selectedCenter);
  }

  Future<String> _reverseGeocode(LatLng latLng) async {
    try {
      final placemarks = await placemarkFromCoordinates(
        latLng.latitude,
        latLng.longitude,
      );
      if (placemarks.isEmpty) return AppLocalizations.t('your_location');
      final place = placemarks.first;
      final parts = <String>[
        if ((place.street ?? '').trim().isNotEmpty) place.street!.trim(),
        if ((place.postalCode ?? '').trim().isNotEmpty)
          place.postalCode!.trim(),
        if ((place.locality ?? '').trim().isNotEmpty) place.locality!.trim(),
        if ((place.country ?? '').trim().isNotEmpty) place.country!.trim(),
      ];
      return parts.isEmpty
          ? AppLocalizations.t('your_location')
          : parts.join(', ');
    } catch (_) {
      return AppLocalizations.t('your_location');
    }
  }

  void _updateAddressDebounced(LatLng latLng) {
    _addressTimer?.cancel();
    _addressTimer = Timer(const Duration(milliseconds: 450), () async {
      final a = await _reverseGeocode(latLng);
      if (!mounted) return;
      setState(() => _address = a);
    });
  }

  Future<void> _saveCustom() async {
    await MapViewPresetService.instance.setCustom(
      latitude: _selectedCenter.latitude,
      longitude: _selectedCenter.longitude,
      zoom: _selectedZoom,
    );
    if (!mounted) return;
    Navigator.of(context).pop(true);
  }

  Future<void> _useCityDefault() async {
    await MapViewPresetService.instance.useCityDefault();
    if (!mounted) return;
    Navigator.of(context).pop(true);
  }

  void _previewCityDefault() {
    final city = LatLng(widget.cityLatitude, widget.cityLongitude);
    setState(() {
      _selectedCenter = city;
      _selectedZoom = widget.cityZoom;
    });
    _mapController.move(city, widget.cityZoom);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFFAFA),
      appBar: AppBar(
        title: Text(AppLocalizations.t('map_default_area')),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 2, 14, 10),
              child: Text(
                AppLocalizations.t('map_default_area_description'),
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Colors.black.withValues(alpha: 0.68),
                ),
              ),
            ),
            Expanded(
              child: Stack(
                children: [
                  FlutterMap(
                    mapController: _mapController,
                    options: MapOptions(
                      initialCenter: _selectedCenter,
                      initialZoom: _selectedZoom,
                      onPositionChanged: (position, _) {
                        final center = position.center;
                        final zoom = position.zoom;
                        if (center == null || zoom == null) return;
                        setState(() {
                          _selectedCenter = center;
                          _selectedZoom = zoom;
                        });
                        _updateAddressDebounced(center);
                      },
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: _tileTemplate,
                        userAgentPackageName: 'lookcrime_mobile',
                      ),
                    ],
                  ),
                  const Center(
                    child: Icon(Icons.location_on, color: _deepRed, size: 42),
                  ),
                  Positioned(
                    top: 12,
                    right: 12,
                    child: FilledButton.tonal(
                      onPressed: _previewCityDefault,
                      style: FilledButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: _deepRed,
                      ),
                      child: Text(AppLocalizations.t('preview_city_default')),
                    ),
                  ),
                ],
              ),
            ),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
              color: const Color(0xFFF5ECEC),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.location_on, color: _deepRed),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _address.isEmpty
                              ? AppLocalizations.t('your_location')
                              : _address,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _useCityDefault,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: _deepRed,
                            side: const BorderSide(color: _deepRed),
                          ),
                          child: Text(AppLocalizations.t('use_city_default')),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: FilledButton(
                          onPressed: _saveCustom,
                          style: FilledButton.styleFrom(
                            backgroundColor: _deepRed,
                          ),
                          child: Text(AppLocalizations.t('save_custom_area')),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
