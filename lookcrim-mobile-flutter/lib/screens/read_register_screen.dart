import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:latlong2/latlong.dart';

import '../api/lookcrime_api.dart';
import 'view_register_location_screen.dart';
import '../services/language_service.dart';
import '../utils/app_localizations.dart';

class ReadRegisterScreen extends StatefulWidget {
  final LookCrimeApi api;
  final String authorizationHeaderValue;
  final int registerId;

  const ReadRegisterScreen({
    super.key,
    required this.api,
    required this.authorizationHeaderValue,
    required this.registerId,
  });

  @override
  State<ReadRegisterScreen> createState() => _ReadRegisterScreenState();
}

class _ReadRegisterScreenState extends State<ReadRegisterScreen> {
  late Future<Map<String, dynamic>> _future;
  final PageController _pageController = PageController();
  late final VoidCallback _localeListener;

  int _imageIndex = 0;
  bool _descriptionExpanded = false;

  static const Color _red = Color(0xFF820000);
  static const Color _softCard = Color(0xFFF5ECEC);
  static const Color _mutedText = Color(0xFF6B6B6B);
  static const String _tileTemplate =
      'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _future = widget.api.getRegister(
      authorizationHeaderValue: widget.authorizationHeaderValue,
      id: widget.registerId,
    );
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    _pageController.dispose();
    super.dispose();
  }

  String _string(dynamic value, {String fallback = ''}) {
    if (value is String && value.trim().isNotEmpty) return value.trim();
    if (value is num) return value.toString();
    return fallback;
  }

  double? _double(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  String _cleanDescription(String value) {
    var cleaned = value.replaceAll(RegExp(r'<[^>]*>'), ' ');
    cleaned = cleaned
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>');
    return cleaned.replaceAll(RegExp(r'\s+'), ' ').trim();
  }

  String _formatDate(dynamic raw) {
    final value = _string(raw);
    if (value.isEmpty) return AppLocalizations.t('na');

    final date = DateTime.tryParse(value);
    if (date == null) return value;

    final months = LanguageService.instance.currentLocale.languageCode == 'pt'
        ? const [
            'Jan',
            'Fev',
            'Mar',
            'Abr',
            'Mai',
            'Jun',
            'Jul',
            'Ago',
            'Set',
            'Out',
            'Nov',
            'Dez',
          ]
        : const [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
          ];

    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  String _formatCategory(String value) {
    if (value.trim().isEmpty) return AppLocalizations.t('na');

    return value
        .replaceAll('_', ' ')
        .split(' ')
        .where((part) => part.trim().isNotEmpty)
        .map((part) {
          final cleaned = part.trim();
          return cleaned[0].toUpperCase() + cleaned.substring(1);
        })
        .join(' ');
  }

  List<String> _extractImages(Map<String, dynamic> data) {
    final result = <String>[];

    final images = data['images'];
    if (images is List) {
      for (final item in images) {
        if (item is String && item.trim().isNotEmpty) {
          result.add(item.trim());
        }
      }
    }

    final singleImage = data['image_url'];
    if (singleImage is String && singleImage.trim().isNotEmpty) {
      result.add(singleImage.trim());
    }

    return result.toSet().toList(growable: false);
  }

  void _openFullMap({
    required double latitude,
    required double longitude,
    required String address,
  }) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ViewRegisterLocationScreen(
          latitude: latitude,
          longitude: longitude,
          address: address,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFFEFE),
      body: SafeArea(
        child: Stack(
          children: [
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Opacity(
                opacity: 0.35,
                child: Image.asset(
                  'assets/images/bg_mapv1.png',
                  fit: BoxFit.cover,
                  height: 190,
                  width: double.infinity,
                ),
              ),
            ),
            FutureBuilder<Map<String, dynamic>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Text(
                        '${AppLocalizations.t('error_loading_register')}${snapshot.error}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.red),
                      ),
                    ),
                  );
                }

                final data = snapshot.data ?? <String, dynamic>{};

                final title = _string(
                  data['title'],
                  fallback: AppLocalizations.t('untitled'),
                );
                final author = _string(
                  data['author_name'],
                  fallback: AppLocalizations.t('unknown'),
                );
                final category = _formatCategory(
                  _string(data['category'], fallback: AppLocalizations.t('na')),
                );
                final date = _formatDate(data['created_at']);
                final description = _cleanDescription(
                  _string(
                    data['description'],
                    fallback: AppLocalizations.t('no_description'),
                  ),
                );
                final address = _string(
                  data['address'],
                  fallback: AppLocalizations.t('location_not_available'),
                );
                final lat = _double(data['latitude']);
                final lng = _double(data['longitude']);
                final images = _extractImages(data);

                return SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(28, 24, 28, 28),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildHeader(context),
                      const SizedBox(height: 26),
                      _buildImageCarousel(images),
                      const SizedBox(height: 22),
                      _buildTitle(title),
                      const SizedBox(height: 16),
                      _buildMetaRow(
                        author: author,
                        category: category,
                        date: date,
                      ),
                      const SizedBox(height: 20),
                      _buildDescription(description),
                      const SizedBox(height: 24),
                      if (lat != null && lng != null)
                        _buildMapPreview(
                          latitude: lat,
                          longitude: lng,
                          address: address,
                        )
                      else
                        _buildNoMap(),
                      const SizedBox(height: 16),
                      _buildLocationCard(
                        address: address,
                        latitude: lat,
                        longitude: lng,
                      ),
                      const SizedBox(height: 18),
                      _buildExportButton(),
                    ],
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return SizedBox(
      height: 54,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: InkWell(
              onTap: () => Navigator.of(context).maybePop(),
              borderRadius: BorderRadius.circular(8),
              child: Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: _red,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.chevron_left,
                  color: Colors.white,
                  size: 31,
                ),
              ),
            ),
          ),
          Text(
            AppLocalizations.t('read_register'),
            style: GoogleFonts.poppins(
              fontSize: 21,
              fontWeight: FontWeight.w700,
              color: Colors.black,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildImageCarousel(List<String> images) {
    return Column(
      children: [
        SizedBox(
          height: 205,
          child: Stack(
            alignment: Alignment.center,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: images.isEmpty
                    ? Container(
                        width: double.infinity,
                        height: 205,
                        color: const Color(0xFFD5D5D5),
                        child: const Icon(
                          Icons.photo,
                          color: Colors.white70,
                          size: 52,
                        ),
                      )
                    : PageView.builder(
                        controller: _pageController,
                        itemCount: images.length,
                        onPageChanged: (index) {
                          setState(() => _imageIndex = index);
                        },
                        itemBuilder: (context, index) {
                          return CachedNetworkImage(
                            imageUrl: images[index],
                            fit: BoxFit.cover,
                            width: double.infinity,
                            placeholder: (_, _) => Container(
                              color: const Color(0xFFE7E7E7),
                              child: const Center(
                                child: CircularProgressIndicator(),
                              ),
                            ),
                            errorWidget: (_, _, _) => Container(
                              color: const Color(0xFFD5D5D5),
                              child: const Icon(
                                Icons.broken_image,
                                color: Colors.white70,
                                size: 44,
                              ),
                            ),
                          );
                        },
                      ),
              ),
              if (images.length > 1) ...[
                Positioned(
                  left: -2,
                  child: _buildRoundImageButton(
                    icon: Icons.chevron_left,
                    onTap: () {
                      final next = _imageIndex <= 0 ? 0 : _imageIndex - 1;
                      _pageController.animateToPage(
                        next,
                        duration: const Duration(milliseconds: 250),
                        curve: Curves.easeOut,
                      );
                    },
                  ),
                ),
                Positioned(
                  right: -2,
                  child: _buildRoundImageButton(
                    icon: Icons.chevron_right,
                    onTap: () {
                      final next = _imageIndex >= images.length - 1
                          ? images.length - 1
                          : _imageIndex + 1;
                      _pageController.animateToPage(
                        next,
                        duration: const Duration(milliseconds: 250),
                        curve: Curves.easeOut,
                      );
                    },
                  ),
                ),
              ],
            ],
          ),
        ),
        if (images.length > 1) ...[
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(images.length, (index) {
              final active = index == _imageIndex;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                width: active ? 9 : 7,
                height: active ? 9 : 7,
                margin: const EdgeInsets.symmetric(horizontal: 4),
                decoration: BoxDecoration(
                  color: active ? _red : const Color(0xFFE8DADA),
                  shape: BoxShape.circle,
                ),
              );
            }),
          ),
        ],
      ],
    );
  }

  Widget _buildRoundImageButton({
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.white,
      shape: const CircleBorder(),
      elevation: 2,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, color: _red, size: 28),
        ),
      ),
    );
  }

  Widget _buildTitle(String title) {
    return Text(
      title,
      style: GoogleFonts.poppins(
        fontSize: 22,
        fontWeight: FontWeight.w700,
        color: Colors.black,
      ),
    );
  }

  Widget _buildMetaRow({
    required String author,
    required String category,
    required String date,
  }) {
    return Row(
      children: [
        Expanded(
          child: _buildMetaCard(
            icon: Icons.person,
            label: AppLocalizations.t('author'),
            value: author,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _buildMetaCard(
            icon: Icons.local_offer,
            label: AppLocalizations.t('category'),
            value: category,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _buildMetaCard(
            icon: Icons.calendar_month,
            label: AppLocalizations.t('date'),
            value: date,
          ),
        ),
      ],
    );
  }

  Widget _buildMetaCard({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return ConstrainedBox(
      constraints: const BoxConstraints(minHeight: 56),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        decoration: BoxDecoration(
          color: _softCard,
          borderRadius: BorderRadius.circular(7),
        ),
        child: Row(
          children: [
            Icon(icon, color: _red, size: 19),
            const SizedBox(width: 7),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.poppins(
                      fontSize: 11,
                      color: _mutedText,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    value,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: Colors.black,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDescription(String description) {
    final shouldCollapse = description.length > 170;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocalizations.t('description'),
          style: GoogleFonts.poppins(
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: Colors.black,
          ),
        ),
        const SizedBox(height: 10),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
          decoration: BoxDecoration(
            color: _softCard,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                description,
                maxLines: shouldCollapse && !_descriptionExpanded ? 4 : null,
                overflow: shouldCollapse && !_descriptionExpanded
                    ? TextOverflow.ellipsis
                    : TextOverflow.visible,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  height: 1.45,
                  color: Colors.black,
                  fontWeight: FontWeight.w400,
                ),
              ),
              if (shouldCollapse) ...[
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: () {
                    setState(() {
                      _descriptionExpanded = !_descriptionExpanded;
                    });
                  },
                  child: Text(
                    _descriptionExpanded
                        ? AppLocalizations.t('show_less')
                        : AppLocalizations.t('read_more'),
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      color: _red,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMapPreview({
    required double latitude,
    required double longitude,
    required String address,
  }) {
    final point = LatLng(latitude, longitude);

    return GestureDetector(
      onTap: () {
        _openFullMap(
          latitude: latitude,
          longitude: longitude,
          address: address,
        );
      },
      child: ClipRRect(
        borderRadius: BorderRadius.circular(10),
        child: SizedBox(
          height: 210,
          child: FlutterMap(
            options: MapOptions(
              initialCenter: point,
              initialZoom: 15,
              interactionOptions: const InteractionOptions(
                flags: InteractiveFlag.none,
              ),
            ),
            children: [
              TileLayer(
                urlTemplate: _tileTemplate,
                userAgentPackageName: 'lookcrime_mobile',
              ),
              CircleLayer(
                circles: [
                  CircleMarker(
                    point: point,
                    radius: 45,
                    color: _red.withValues(alpha: 0.12),
                    borderColor: _red.withValues(alpha: 0.18),
                    borderStrokeWidth: 1,
                  ),
                ],
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    width: 42,
                    height: 42,
                    point: point,
                    child: const Icon(Icons.location_on, color: _red, size: 36),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNoMap() {
    return Container(
      height: 130,
      decoration: BoxDecoration(
        color: _softCard,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Center(child: Text(AppLocalizations.t('map_not_available'))),
    );
  }

  Widget _buildLocationCard({
    required String address,
    required double? latitude,
    required double? longitude,
  }) {
    final canOpen = latitude != null && longitude != null;

    return InkWell(
      onTap: canOpen
          ? () {
              _openFullMap(
                latitude: latitude,
                longitude: longitude,
                address: address,
              );
            }
          : null,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.fromLTRB(18, 15, 14, 15),
        decoration: BoxDecoration(
          color: _softCard,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          children: [
            const Icon(Icons.location_on, color: _red, size: 29),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppLocalizations.t('location'),
                    style: GoogleFonts.poppins(
                      fontSize: 15,
                      color: Colors.black,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    address,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      color: Colors.black,
                      height: 1.35,
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                ],
              ),
            ),
            if (canOpen)
              const Icon(Icons.chevron_right, color: Colors.black, size: 27),
          ],
        ),
      ),
    );
  }

  Widget _buildExportButton() {
    return SizedBox(
      height: 48,
      child: ElevatedButton.icon(
        onPressed: null,
        icon: const Icon(Icons.download, color: Colors.white),
        label: Text(
          AppLocalizations.t('export_register'),
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w700,
          ),
        ),
        style: ElevatedButton.styleFrom(
          disabledBackgroundColor: _red,
          disabledForegroundColor: Colors.white,
          backgroundColor: _red,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(7)),
        ),
      ),
    );
  }
}
