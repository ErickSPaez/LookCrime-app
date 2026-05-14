import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

typedef LocationResult = ({double latitude, double longitude, String address});

class SetLocationScreen extends StatefulWidget {
  final double initialLatitude;
  final double initialLongitude;
  final int? radiusMeters;

  const SetLocationScreen({
    super.key,
    required this.initialLatitude,
    required this.initialLongitude,
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
  LatLng? _currentLocation;
  late LatLng _selectedLocation;
  bool _usingCurrentLocation = false;
  bool _loadingLocation = false;
  String _address = 'City area';

  @override
  void initState() {
    super.initState();
    _cityCenter = LatLng(widget.initialLatitude, widget.initialLongitude);
    _cityRadiusMeters = (widget.radiusMeters ?? 4000).toDouble();
    _cityZoom = _zoomForRadius(_cityRadiusMeters);
    _selectedLocation = _cityCenter;
  }

  @override
  void dispose() {
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
        _showError('Location services are disabled.');
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied) {
        _showError('Location permission denied.');
        return;
      }
      if (permission == LocationPermission.deniedForever) {
        _showError('Location permission permanently denied.');
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      final latLng = LatLng(position.latitude, position.longitude);
      final address = await _reverseGeocode(latLng);
      if (!mounted) return;

      setState(() {
        _currentLocation = latLng;
        _usingCurrentLocation = true;
        _address = address;
        _selectedLocation = latLng;
      });
      _mapController.move(latLng, 16);
    } catch (_) {
      _showError('Unable to get current location.');
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
      if (placemarks.isEmpty) return 'Your location';
      final place = placemarks.first;
      final parts = <String>[
        if ((place.street ?? '').trim().isNotEmpty) place.street!.trim(),
        if ((place.postalCode ?? '').trim().isNotEmpty)
          place.postalCode!.trim(),
        if ((place.locality ?? '').trim().isNotEmpty) place.locality!.trim(),
        if ((place.country ?? '').trim().isNotEmpty) place.country!.trim(),
      ];
      return parts.isEmpty ? 'Your location' : parts.join(', ');
    } catch (_) {
      return 'Your location';
    }
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
    setState(() {
      _usingCurrentLocation = false;
      _address = 'City area';
      _selectedLocation = _cityCenter;
    });
    _mapController.move(_cityCenter, _cityZoom);
  }

  Future<void> _selectLocation(LatLng latLng) async {
    setState(() {
      _selectedLocation = latLng;
    });

    final address = await _reverseGeocode(latLng);
    if (!mounted) return;
    setState(() {
      _address = address;
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
        : _cityCenter;
    final cityZoom = _usingCurrentLocation ? 16.0 : _cityZoom;

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
              ),
              children: [
                TileLayer(
                  urlTemplate: _tileTemplate,
                  userAgentPackageName: 'lookcrime_mobile',
                ),
                CircleLayer(
                  circles: _usingCurrentLocation
                      ? []
                      : [
                          CircleMarker(
                            point: _cityCenter,
                            radius: _cityRadiusMeters,
                            useRadiusInMeter: true,
                            color: _deepRed.withOpacity(0.18),
                            borderStrokeWidth: 1.5,
                            borderColor: _deepRed.withOpacity(0.35),
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
                            decoration: const InputDecoration(
                              prefixIcon: Icon(Icons.search, color: _textDark),
                              hintText: 'Search',
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
                    child: SizedBox(
                      height: 34,
                      child: FilledButton(
                        style: FilledButton.styleFrom(
                          backgroundColor: _deepRed,
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        onPressed: _loadingLocation
                            ? null
                            : (_usingCurrentLocation
                                  ? _showCityArea
                                  : _requestCurrentLocation),
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
                                _usingCurrentLocation
                                    ? 'City area'
                                    : 'Current location',
                                style: const TextStyle(color: Colors.white),
                              ),
                      ),
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
                      'Your Location',
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
                        label: const Text(
                          'Set location',
                          style: TextStyle(color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
