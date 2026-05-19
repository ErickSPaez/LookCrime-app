import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class ViewRegisterLocationScreen extends StatelessWidget {
  final double latitude;
  final double longitude;
  final String address;

  const ViewRegisterLocationScreen({
    super.key,
    required this.latitude,
    required this.longitude,
    required this.address,
  });

  static const Color _deepRed = Color(0xFF820000);
  static const Color _panelFill = Color(0xFFF5ECEC);
  static const String _tileTemplate =
      'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  @override
  Widget build(BuildContext context) {
    final point = LatLng(latitude, longitude);

    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            FlutterMap(
              options: MapOptions(initialCenter: point, initialZoom: 16),
              children: [
                TileLayer(
                  urlTemplate: _tileTemplate,
                  userAgentPackageName: 'lookcrime_mobile',
                ),
                MarkerLayer(
                  markers: [
                    Marker(
                      width: 44,
                      height: 44,
                      point: point,
                      child: const Icon(
                        Icons.location_on,
                        color: _deepRed,
                        size: 40,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            Positioned(
              top: 14,
              left: 14,
              child: InkWell(
                onTap: () => Navigator.of(context).maybePop(),
                borderRadius: BorderRadius.circular(9),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: _deepRed,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  child: const Icon(
                    Icons.chevron_left,
                    color: Colors.white,
                    size: 28,
                  ),
                ),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                decoration: const BoxDecoration(
                  color: _panelFill,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.location_on, color: _deepRed),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        address.trim().isEmpty
                            ? 'Location not available'
                            : address,
                        style: const TextStyle(
                          color: Colors.black,
                          fontWeight: FontWeight.w600,
                          height: 1.35,
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
