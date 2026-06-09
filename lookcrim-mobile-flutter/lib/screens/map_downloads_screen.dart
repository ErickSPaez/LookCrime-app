import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:latlong2/latlong.dart';

import '../services/map_tile_cache_service.dart';
import '../services/map_view_preset_service.dart';
import '../utils/app_localizations.dart';

class MapDownloadsScreen extends StatefulWidget {
  final double cityLatitude;
  final double cityLongitude;
  final int cityRadiusMeters;
  final MapViewPreset currentDefaultPreset;

  const MapDownloadsScreen({
    super.key,
    required this.cityLatitude,
    required this.cityLongitude,
    required this.cityRadiusMeters,
    required this.currentDefaultPreset,
  });

  @override
  State<MapDownloadsScreen> createState() => _MapDownloadsScreenState();
}

class _MapDownloadsScreenState extends State<MapDownloadsScreen> {
  static const Color _red = Color(0xFF820000);
  static const Color _softCard = Color(0xFFF5ECEC);
  static const String _tileTemplate =
      'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  final MapController _mapController = MapController();

  late final Future<void> _tileInitFuture;
  late LatLng _selectedCenter;
  late double _selectedZoom;
  bool _isDownloading = false;

  @override
  void initState() {
    super.initState();
    final preset = widget.currentDefaultPreset;
    final useCustom =
        preset.mode == MapDefaultMode.custom &&
        preset.latitude != null &&
        preset.longitude != null;

    _selectedCenter = LatLng(
      useCustom ? preset.latitude! : widget.cityLatitude,
      useCustom ? preset.longitude! : widget.cityLongitude,
    );
    _selectedZoom = useCustom
        ? (preset.zoom ?? _zoomForRadius(widget.cityRadiusMeters.toDouble()))
        : _zoomForRadius(widget.cityRadiusMeters.toDouble());

    _tileInitFuture = MapTileCacheService.instance.init();
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

  double _metersPerPixel(double latitude, double zoom) {
    return 156543.03392 *
        math.cos(latitude * math.pi / 180.0) /
        math.pow(2, zoom);
  }

  double _estimateVisibleRadiusMeters(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    final metersPerPixel = _metersPerPixel(
      _selectedCenter.latitude,
      _selectedZoom,
    );
    final halfSpanPixels = size.shortestSide * 0.55;
    return halfSpanPixels * metersPerPixel;
  }

  void _onPositionChanged(MapPosition position) {
    final center = position.center;
    final zoom = position.zoom;
    if (center == null || zoom == null) return;

    setState(() {
      _selectedCenter = center;
      _selectedZoom = zoom;
    });
  }

  void _showCityArea() {
    final target = LatLng(widget.cityLatitude, widget.cityLongitude);
    final targetZoom = _zoomForRadius(widget.cityRadiusMeters.toDouble());

    setState(() {
      _selectedCenter = target;
      _selectedZoom = targetZoom;
    });
    _mapController.move(target, targetZoom);
  }

  Future<void> _downloadVisibleArea() async {
    setState(() => _isDownloading = true);

    final radiusMeters = _estimateVisibleRadiusMeters(context);
    final started = await MapTileCacheService.instance.prefetchRectangleArea(
      center: _selectedCenter,
      radiusMeters: radiusMeters,
      label: AppLocalizations.t('visible_map_area'),
      cacheKey:
          'visible:${_selectedCenter.latitude.toStringAsFixed(4)}:${_selectedCenter.longitude.toStringAsFixed(4)}:${_selectedZoom.toStringAsFixed(2)}',
      notifyStatus: true,
    );

    if (!mounted) return;
    if (started) {
      Navigator.of(context).pop();
      return;
    }

    setState(() => _isDownloading = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.t('map_downloads_load_failed'))),
    );
  }

  Widget _buildPrimaryButton({
    required String label,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: ElevatedButton(
        onPressed: _isDownloading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: _red,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(28),
          ),
          elevation: 0,
        ),
        child: _isDownloading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
      ),
    );
  }

  Widget _buildContent(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFFAFA),
      appBar: AppBar(
        title: Text(AppLocalizations.t('map_downloads')),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.black,
        elevation: 0,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
              child: Text(
                AppLocalizations.t('map_downloads_hint'),
                style: GoogleFonts.poppins(fontSize: 14, color: Colors.black54),
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
                      onPositionChanged: (position, _) =>
                          _onPositionChanged(position),
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: _tileTemplate,
                        userAgentPackageName: 'lookcrime_mobile',
                        tileProvider: MapTileCacheService.instance.provider,
                      ),
                    ],
                  ),
                  const Center(
                    child: Icon(Icons.location_on, color: _red, size: 42),
                  ),
                  Positioned(
                    top: 12,
                    right: 12,
                    child: FilledButton.tonal(
                      onPressed: _showCityArea,
                      style: FilledButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: _red,
                      ),
                      child: Text(AppLocalizations.t('city_area')),
                    ),
                  ),
                ],
              ),
            ),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
              color: _softCard,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.map_outlined, color: _red),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          AppLocalizations.t('download_this_area_description'),
                          style: GoogleFonts.poppins(
                            fontSize: 13,
                            color: Colors.black54,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _buildPrimaryButton(
                    label: AppLocalizations.t('download_this_area'),
                    onPressed: _downloadVisibleArea,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<void>(
      future: _tileInitFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return Scaffold(
            backgroundColor: const Color(0xFFFFFAFA),
            appBar: AppBar(
              title: Text(AppLocalizations.t('map_downloads')),
              backgroundColor: Colors.transparent,
              foregroundColor: Colors.black,
              elevation: 0,
            ),
            body: const Center(child: CircularProgressIndicator()),
          );
        }

        if (snapshot.hasError) {
          return Scaffold(
            backgroundColor: const Color(0xFFFFFAFA),
            appBar: AppBar(
              title: Text(AppLocalizations.t('map_downloads')),
              backgroundColor: Colors.transparent,
              foregroundColor: Colors.black,
              elevation: 0,
            ),
            body: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  AppLocalizations.t('map_downloads_load_failed'),
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(fontSize: 14),
                ),
              ),
            ),
          );
        }

        return _buildContent(context);
      },
    );
  }
}
