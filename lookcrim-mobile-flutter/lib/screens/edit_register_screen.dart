import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:latlong2/latlong.dart';
import 'package:http/http.dart' as http;

import '../api/lookcrime_api.dart';
import '../services/language_service.dart';
import '../services/offline_sync_service.dart';
import '../utils/app_localizations.dart';
import 'set_location_screen.dart';

class EditRegisterScreen extends StatefulWidget {
  final LookCrimeApi api;
  final String authorizationHeaderValue;
  final int registerId;

  const EditRegisterScreen({
    super.key,
    required this.api,
    required this.authorizationHeaderValue,
    required this.registerId,
  });

  @override
  State<EditRegisterScreen> createState() => _EditRegisterScreenState();
}

class _EditRegisterScreenState extends State<EditRegisterScreen> {
  static const Color _red = Color(0xFF820000);
  static const Color _softCard = Color(0xFFF5ECEC);
  static const Color _mutedText = Color(0xFF6B6B6B);
  static const Color _fieldFill = Color(0xFFFCF4F4);
  static const Color _fieldBorder = Color(0xFF9E9D9D);
  static const String _tileTemplate =
      'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  final TextEditingController _titleController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final PageController _imagePageController = PageController();

  late final VoidCallback _localeListener;

  bool _loading = true;
  bool _saving = false;
  String? _error;

  String? _authorName;
  String? _createdAt;
  String? _selectedCategory;
  String? _address;
  double? _latitude;
  double? _longitude;
  double? _cityCenterLat;
  double? _cityCenterLng;
  int? _cityRadiusMeters;
  double? _cityZoom;
  int _imageIndex = 0;

  List<({String key, String label})> _categories = const [];
  List<_EditableImage> _images = <_EditableImage>[];

  bool get _hasLocation => _latitude != null && _longitude != null;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _loadInitialData();
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    _titleController.dispose();
    _descriptionController.dispose();
    _imagePageController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final lang = LanguageService.instance.currentLocale.languageCode;
      final ctx = await OfflineSyncService.instance.getUserContext(
        remoteLoader: () => widget.api.getMe(
          authorizationHeaderValue: widget.authorizationHeaderValue,
        ),
      );
      final register = await widget.api.getRegister(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        id: widget.registerId,
      );
      final categories = await OfflineSyncService.instance.getCategories(
        lang: lang,
        remoteLoader: () => widget.api.getRegisterCategories(lang: lang),
      );

      final images = _extractImages(register);
      final editableImages = await _loadImageBytes(images);

      if (!mounted) return;
      setState(() {
        _cityCenterLat = ctx.cityCenterLat;
        _cityCenterLng = ctx.cityCenterLng;
        _cityRadiusMeters = ctx.cityRadiusMeters;
        _cityZoom = _zoomForRadius((ctx.cityRadiusMeters ?? 4000).toDouble());
        _categories = categories;
        _titleController.text = _string(register['title'], fallback: '');
        _descriptionController.text = _string(
          register['description'],
          fallback: '',
        );
        _authorName = _string(
          register['author_name'],
          fallback: AppLocalizations.t('unknown'),
        );
        _createdAt = _formatDate(register['created_at']);
        _selectedCategory = _string(register['category'], fallback: '');
        _address = _string(
          register['address'],
          fallback: AppLocalizations.t('location_not_available'),
        );
        _latitude = _double(register['latitude']);
        _longitude = _double(register['longitude']);
        _images = editableImages;
        _loading = false;
      });
    } catch (e) {
      debugPrint('Load edit register failed: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
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

  Future<List<_EditableImage>> _loadImageBytes(List<String> urls) async {
    final result = <_EditableImage>[];

    for (final url in urls) {
      try {
        final response = await http.get(Uri.parse(url));
        if (response.statusCode < 200 || response.statusCode >= 300) {
          continue;
        }

        result.add(
          _EditableImage(
            bytes: response.bodyBytes,
            filename: _filenameFromUrl(url),
          ),
        );
      } catch (_) {
        // ignore single image failures and keep the rest editable
      }
    }

    return result;
  }

  String _filenameFromUrl(String url) {
    try {
      final uri = Uri.parse(url);
      final segment = uri.pathSegments.isNotEmpty ? uri.pathSegments.last : '';
      if (segment.trim().isNotEmpty) return segment.trim();
    } catch (_) {
      // ignore
    }
    return 'image.jpg';
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

  Future<void> _save() async {
    final title = _titleController.text.trim();
    final description = _descriptionController.text.trim();
    final category = _selectedCategory?.trim() ?? '';

    if (title.isEmpty || description.isEmpty || category.isEmpty) {
      _showMessage(AppLocalizations.t('fill_all_fields'), isError: true);
      return;
    }

    if (!_hasLocation) {
      _showMessage(AppLocalizations.t('location_not_available'), isError: true);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final imageBytes = _images
          .map((item) => item.bytes)
          .toList(growable: false);
      final imageFilenames = _images
          .map((item) => item.filename)
          .toList(growable: false);

      await widget.api.updateRegister(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        id: widget.registerId,
        title: title,
        description: description,
        category: category,
        latitude: _latitude!.toString(),
        longitude: _longitude!.toString(),
        address: _address ?? '',
        imageBytes: imageBytes,
        imageFilenames: imageFilenames,
        clearImages: _images.isEmpty,
      );

      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
      });
    } finally {
      if (mounted) {
        setState(() {
          _saving = false;
        });
      }
    }
  }

  Future<void> _openCategoryPicker() async {
    if (_categories.isEmpty) return;

    final selected = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) {
        return SafeArea(
          child: ListView.separated(
            shrinkWrap: true,
            itemCount: _categories.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final item = _categories[index];
              return ListTile(
                title: Text(item.label.isNotEmpty ? item.label : item.key),
                trailing: _selectedCategory == item.key
                    ? const Icon(Icons.check, color: _red)
                    : null,
                onTap: () => Navigator.of(sheetContext).pop(item.key),
              );
            },
          ),
        );
      },
    );

    if (selected == null || !mounted) return;
    setState(() {
      _selectedCategory = selected;
    });
  }

  Future<void> _pickImage() async {
    if (_images.length >= 3) {
      _showMessage(AppLocalizations.t('image_too_large'), isError: true);
      return;
    }

    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined),
                title: Text(AppLocalizations.t('camera')),
                onTap: () => Navigator.of(sheetContext).pop(ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined),
                title: Text(AppLocalizations.t('files')),
                onTap: () =>
                    Navigator.of(sheetContext).pop(ImageSource.gallery),
              ),
            ],
          ),
        );
      },
    );

    if (source == null) return;

    final picker = ImagePicker();
    final picked = await picker.pickImage(source: source);
    if (picked == null) return;

    final bytes = await picked.readAsBytes();
    if (!mounted) return;

    setState(() {
      _images.add(
        _EditableImage(
          bytes: bytes,
          filename: picked.name.isNotEmpty
              ? picked.name
              : (source == ImageSource.camera ? 'camera.jpg' : 'image.jpg'),
        ),
      );
    });
  }

  Future<void> _openLocationEditor() async {
    final cityLat = _cityCenterLat ?? _latitude ?? -22.9;
    final cityLng = _cityCenterLng ?? _longitude ?? -43.2;
    final initialLat = _latitude ?? cityLat;
    final initialLng = _longitude ?? cityLng;
    final initialZoom = _cityZoom ?? 13.0;

    final result = await Navigator.of(context).push<LocationResult>(
      MaterialPageRoute(
        builder: (_) => SetLocationScreen(
          cityLatitude: cityLat,
          cityLongitude: cityLng,
          initialLatitude: initialLat,
          initialLongitude: initialLng,
          initialZoom: initialZoom,
          radiusMeters: _cityRadiusMeters,
        ),
      ),
    );

    if (result == null || !mounted) return;
    setState(() {
      _latitude = result.latitude;
      _longitude = result.longitude;
      _address = result.address.trim().isEmpty
          ? AppLocalizations.t('selected_location')
          : result.address;
    });
  }

  void _removeImageAt(int index) {
    if (index < 0 || index >= _images.length) return;
    setState(() {
      _images.removeAt(index);
      if (_imageIndex >= _images.length) {
        _imageIndex = _images.isEmpty ? 0 : _images.length - 1;
      }
    });
  }

  void _showMessage(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red.shade700 : _red,
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
            if (_loading)
              const Center(child: CircularProgressIndicator())
            else
              SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
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
                              color: _red,
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
                          child: Text(
                            AppLocalizations.t('edit_register'),
                            textAlign: TextAlign.center,
                            style: GoogleFonts.poppins(
                              fontSize: 24,
                              fontWeight: FontWeight.w700,
                              color: Colors.black,
                            ),
                          ),
                        ),
                        const SizedBox(width: 48),
                      ],
                    ),
                    if (_error != null) ...[
                      const SizedBox(height: 16),
                      Text(_error!, style: const TextStyle(color: Colors.red)),
                    ],
                    const SizedBox(height: 18),
                    _buildSectionCard(
                      title: AppLocalizations.t('edit_images'),
                      trailing: IconButton(
                        onPressed: _saving ? null : _pickImage,
                        icon: const Icon(Icons.edit, color: _red),
                      ),
                      child: _buildImagesEditor(),
                    ),
                    const SizedBox(height: 14),
                    _buildTextFieldCard(
                      label: AppLocalizations.t('title'),
                      controller: _titleController,
                      hint: AppLocalizations.t('set_title_to_register'),
                    ),
                    const SizedBox(height: 14),
                    _buildSectionCard(
                      title: AppLocalizations.t('category'),
                      trailing: IconButton(
                        onPressed: _saving ? null : _openCategoryPicker,
                        icon: const Icon(Icons.edit, color: _red),
                      ),
                      child: InkWell(
                        onTap: _saving ? null : _openCategoryPicker,
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          width: double.infinity,
                          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
                          decoration: BoxDecoration(
                            color: _fieldFill,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: _fieldBorder),
                          ),
                          child: Text(
                            _selectedCategory?.trim().isNotEmpty == true
                                ? _selectedCategory!
                                : AppLocalizations.t('tap_select_category'),
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildReadOnlyCard(
                      label: AppLocalizations.t('author'),
                      value: _authorName ?? AppLocalizations.t('unknown'),
                    ),
                    const SizedBox(height: 14),
                    _buildReadOnlyCard(
                      label: AppLocalizations.t('date'),
                      value: _createdAt ?? AppLocalizations.t('na'),
                    ),
                    const SizedBox(height: 14),
                    _buildSectionCard(
                      title: AppLocalizations.t('description'),
                      trailing: const Icon(Icons.edit, color: _red),
                      child: TextField(
                        controller: _descriptionController,
                        maxLines: 5,
                        decoration: InputDecoration(
                          hintText: AppLocalizations.t('details_of_incident'),
                          filled: true,
                          fillColor: _fieldFill,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide: const BorderSide(color: _fieldBorder),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide: const BorderSide(
                              color: _red,
                              width: 1.5,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildSectionCard(
                      title: AppLocalizations.t('location'),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (_hasLocation)
                            GestureDetector(
                              onTap: _saving ? null : _openLocationEditor,
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: SizedBox(
                                  height: 210,
                                  child: FlutterMap(
                                    options: MapOptions(
                                      initialCenter: LatLng(
                                        _latitude!,
                                        _longitude!,
                                      ),
                                      initialZoom: 15,
                                      interactionOptions:
                                          const InteractionOptions(
                                            flags: InteractiveFlag.none,
                                          ),
                                    ),
                                    children: [
                                      TileLayer(
                                        urlTemplate: _tileTemplate,
                                        userAgentPackageName:
                                            'lookcrime_mobile',
                                      ),
                                      MarkerLayer(
                                        markers: [
                                          Marker(
                                            width: 44,
                                            height: 44,
                                            point: LatLng(
                                              _latitude!,
                                              _longitude!,
                                            ),
                                            child: const Icon(
                                              Icons.location_on,
                                              color: _red,
                                              size: 40,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            )
                          else
                            Container(
                              height: 130,
                              decoration: BoxDecoration(
                                color: _softCard,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Center(
                                child: Text(
                                  AppLocalizations.t('location_not_available'),
                                ),
                              ),
                            ),
                          const SizedBox(height: 12),
                          InkWell(
                            onTap: _saving ? null : _openLocationEditor,
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.fromLTRB(
                                16,
                                14,
                                16,
                                14,
                              ),
                              decoration: BoxDecoration(
                                color: _softCard,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.location_on, color: _red),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _address ??
                                          AppLocalizations.t(
                                            'location_not_available',
                                          ),
                                      style: GoogleFonts.poppins(
                                        fontSize: 13,
                                        color: Colors.black,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                  const Icon(Icons.edit, color: _red, size: 20),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    SizedBox(
                      height: 48,
                      child: FilledButton.icon(
                        onPressed: _saving ? null : _save,
                        style: FilledButton.styleFrom(
                          backgroundColor: _red,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        icon: _saving
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.save, color: Colors.white),
                        label: Text(
                          AppLocalizations.t('save_changes'),
                          style: GoogleFonts.poppins(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
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

  Widget _buildSectionCard({
    required String title,
    required Widget child,
    Widget? trailing,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: Colors.black,
                  ),
                ),
              ),
              ?trailing,
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }

  Widget _buildTextFieldCard({
    required String label,
    required TextEditingController controller,
    required String hint,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: Colors.black,
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: controller,
            decoration: InputDecoration(
              hintText: hint,
              filled: true,
              fillColor: _fieldFill,
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 14,
                vertical: 12,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: _fieldBorder),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: _red, width: 1.5),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReadOnlyCard({required String label, required String value}) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: Colors.black,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  value,
                  style: GoogleFonts.poppins(
                    fontSize: 13,
                    color: _mutedText,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildImagesEditor() {
    if (_images.isEmpty) {
      return InkWell(
        onTap: _saving ? null : _pickImage,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          height: 130,
          decoration: BoxDecoration(
            color: _softCard,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: _fieldBorder),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.add_a_photo_outlined, color: _red, size: 34),
              const SizedBox(height: 8),
              Text(
                AppLocalizations.t('add_image'),
                style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Column(
      children: [
        SizedBox(
          height: 170,
          child: Stack(
            alignment: Alignment.center,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: PageView.builder(
                  controller: _imagePageController,
                  itemCount: _images.length,
                  onPageChanged: (index) {
                    setState(() => _imageIndex = index);
                  },
                  itemBuilder: (context, index) {
                    final image = _images[index];
                    return Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.memory(image.bytes, fit: BoxFit.cover),
                        Positioned(
                          top: 10,
                          right: 10,
                          child: Material(
                            color: Colors.black.withValues(alpha: 0.35),
                            shape: const CircleBorder(),
                            child: IconButton(
                              onPressed: _saving
                                  ? null
                                  : () => _removeImageAt(index),
                              icon: const Icon(
                                Icons.close,
                                color: Colors.white,
                              ),
                              tooltip: AppLocalizations.t('remove_image'),
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
              if (_images.length > 1) ...[
                Positioned(
                  left: -4,
                  child: _buildRoundImageButton(
                    icon: Icons.chevron_left,
                    onTap: () {
                      final next = _imageIndex <= 0 ? 0 : _imageIndex - 1;
                      _imagePageController.animateToPage(
                        next,
                        duration: const Duration(milliseconds: 250),
                        curve: Curves.easeOut,
                      );
                    },
                  ),
                ),
                Positioned(
                  right: -4,
                  child: _buildRoundImageButton(
                    icon: Icons.chevron_right,
                    onTap: () {
                      final next = _imageIndex >= _images.length - 1
                          ? _images.length - 1
                          : _imageIndex + 1;
                      _imagePageController.animateToPage(
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
        const SizedBox(height: 12),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              '${_images.length}/3',
              style: GoogleFonts.poppins(
                color: _mutedText,
                fontWeight: FontWeight.w600,
              ),
            ),
            TextButton.icon(
              onPressed: _saving ? null : _pickImage,
              icon: const Icon(Icons.add, color: _red),
              label: Text(
                AppLocalizations.t('add_image'),
                style: GoogleFonts.poppins(
                  color: _red,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
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
}

class _EditableImage {
  final Uint8List bytes;
  final String filename;

  const _EditableImage({required this.bytes, required this.filename});
}
