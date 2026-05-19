import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/lookcrime_api.dart';
import 'set_location_screen.dart';
import '../utils/user_friendly_error.dart';
import '../services/language_service.dart';
import '../utils/app_localizations.dart';

class CreateRegisterScreen extends StatefulWidget {
  final LookCrimeApi api;
  final String authorizationHeaderValue;

  const CreateRegisterScreen({
    super.key,
    required this.api,
    required this.authorizationHeaderValue,
  });

  @override
  State<CreateRegisterScreen> createState() => _CreateRegisterScreenState();
}

class _CreateRegisterScreenState extends State<CreateRegisterScreen> {
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _latController = TextEditingController(text: '-22.9');
  final _lngController = TextEditingController(text: '-43.2');

  bool _loading = false;
  bool _loadingCategories = true;
  String? _error;

  List<({String key, String label})> _categories = const [];
  String? _selectedCategory;

  double? _cityCenterLat;
  double? _cityCenterLng;
  int? _cityRadiusMeters;

  Uint8List? _imageBytes;
  String? _imageFilename;
  String? _selectedAddress;
  late final VoidCallback _localeListener;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _loadCategories();
    _loadCityCenter();
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    _titleController.dispose();
    _descriptionController.dispose();
    _latController.dispose();
    _lngController.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    setState(() {
      _loadingCategories = true;
      _error = null;
    });

    try {
      final cats = await widget.api.getRegisterCategories(lang: 'pt');
      if (!mounted) return;
      setState(() {
        _categories = cats;
        _selectedCategory = null;
      });
    } catch (e) {
      debugPrint('Load categories failed: $e');
      if (!mounted) return;
      setState(
        () => _error = userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('categories_load_fail'),
          operation: 'createRegister',
        ),
      );
    } finally {
      if (mounted) setState(() => _loadingCategories = false);
    }
  }

  Future<void> _loadCityCenter() async {
    try {
      final res = await widget.api.getMe(
        authorizationHeaderValue: widget.authorizationHeaderValue,
      );
      final user = res.user;
      final lat = _parseDouble(user['city_center_lat']);
      final lng = _parseDouble(user['city_center_lng']);
      final radius = _parseInt(user['city_radius_m']);

      if (!mounted) return;
      setState(() {
        _cityCenterLat = lat;
        _cityCenterLng = lng;
        _cityRadiusMeters = radius;
        if (lat != null && lng != null) {
          _latController.text = lat.toStringAsFixed(6);
          _lngController.text = lng.toStringAsFixed(6);
        }
      });
    } catch (_) {
      // ignore
    }
  }

  double? _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  int? _parseInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }

  Future<ImageSource?> _selectImageSource() async {
    return showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined),
                title: Text(AppLocalizations.t('camera')),
                onTap: () => Navigator.of(context).pop(ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined),
                title: Text(AppLocalizations.t('files')),
                onTap: () => Navigator.of(context).pop(ImageSource.gallery),
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _pickImage() async {
    final source = await _selectImageSource();
    if (source == null) return;
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: source);
    if (picked == null) return;
    final bytes = await picked.readAsBytes();
    if (!mounted) return;

    setState(() {
      _imageBytes = bytes;
      final fallback = source == ImageSource.camera
          ? 'camera.jpg'
          : 'image.jpg';
      _imageFilename = picked.name.isNotEmpty ? picked.name : fallback;
    });
  }

  void _openSetLocation() {
    final initialLat = double.tryParse(_latController.text.trim()) ?? -22.9;
    final initialLng = double.tryParse(_lngController.text.trim()) ?? -43.2;
    Navigator.of(context)
        .push<LocationResult>(
          MaterialPageRoute(
            builder: (_) => SetLocationScreen(
              initialLatitude: initialLat,
              initialLongitude: initialLng,
              radiusMeters: _cityRadiusMeters,
            ),
          ),
        )
        .then((result) {
          if (result == null) return;
          setState(() {
            _latController.text = result.latitude.toStringAsFixed(6);
            _lngController.text = result.longitude.toStringAsFixed(6);
            _selectedAddress = result.address.trim().isEmpty
                ? AppLocalizations.t('selected_location')
                : result.address;
          });
        });
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final cat = _selectedCategory;
      final img = _imageBytes;
      final imgName = _imageFilename;

      if (cat == null || cat.trim().isEmpty) {
        throw Exception(AppLocalizations.t('select_category'));
      }
      if (img == null || imgName == null) {
        throw Exception(AppLocalizations.t('select_image'));
      }

      await widget.api.createRegister(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        title: _titleController.text.trim(),
        description: _descriptionController.text.trim(),
        category: cat,
        latitude: _latController.text.trim(),
        longitude: _lngController.text.trim(),
        address: _selectedAddress ?? '',
        imageBytes: img,
        imageFilename: imgName,
      );

      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      debugPrint('Create register failed: $e');
      if (!mounted) return;
      setState(
        () => _error = userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('create_fail'),
          operation: 'createRegister',
        ),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const deepRed = Color(0xFF820000);
    const background = Color(0xFFFFFAFA);
    const fieldFill = Color(0xFFFCF4F4);
    const fieldBorder = Color(0xFF9E9D9D);
    const darkText = Color(0xFF000000);
    const addressFill = Color(0xFFF5ECEC);
    const sectionSpacing = 14.0;
    const labelSpacing = 6.0;
    final hasSelectedLocation = _selectedAddress != null;

    InputDecoration buildFieldDecoration(String hint) {
      return InputDecoration(
        hintText: hint,
        filled: true,
        fillColor: fieldFill,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 12,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: fieldBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: deepRed, width: 1.5),
        ),
      );
    }

    return Scaffold(
      backgroundColor: background,
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: Align(
                alignment: Alignment.topCenter,
                child: Opacity(
                  opacity: 0.35,
                  child: Image.asset(
                    'assets/images/bg_mapv1.png',
                    fit: BoxFit.cover,
                    height: 180,
                    width: double.infinity,
                  ),
                ),
              ),
            ),
            Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: Row(
                    children: [
                      InkWell(
                        onTap: () => Navigator.of(context).maybePop(),
                        borderRadius: BorderRadius.circular(9),
                        child: Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: deepRed,
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
                      Text(
                        AppLocalizations.t('create_register_title'),
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(
                              fontWeight: FontWeight.w800,
                              color: darkText,
                              letterSpacing: 0.6,
                              fontSize: 16,
                            ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 50, 16, 16),
                    children: [
                      if (_error != null) ...[
                        Text(
                          _error!,
                          style: const TextStyle(color: Colors.red),
                        ),
                        const SizedBox(height: 12),
                      ],
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.06),
                              blurRadius: 12,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              AppLocalizations.t('title'),
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: darkText,
                                  ),
                            ),
                            const SizedBox(height: labelSpacing),
                            TextField(
                              controller: _titleController,
                              decoration: buildFieldDecoration(
                                AppLocalizations.t('set_title_to_register'),
                              ),
                            ),
                            const SizedBox(height: sectionSpacing),
                            Text(
                              AppLocalizations.t('category'),
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: darkText,
                                  ),
                            ),
                            const SizedBox(height: labelSpacing),
                            if (_loadingCategories)
                              const LinearProgressIndicator()
                            else
                              DropdownButtonFormField<String>(
                                key: ValueKey(_selectedCategory),
                                initialValue: _selectedCategory,
                                hint: Text(
                                  AppLocalizations.t('tap_select_category'),
                                ),
                                items: _categories
                                    .map(
                                      (c) => DropdownMenuItem<String>(
                                        value: c.key,
                                        child: Text(
                                          c.label.isNotEmpty ? c.label : c.key,
                                        ),
                                      ),
                                    )
                                    .toList(growable: false),
                                onChanged: _loading
                                    ? null
                                    : (v) =>
                                          setState(() => _selectedCategory = v),
                                decoration: buildFieldDecoration(
                                  AppLocalizations.t('set_category'),
                                ),
                                icon: const Icon(
                                  Icons.keyboard_arrow_down_rounded,
                                  color: fieldBorder,
                                ),
                                iconSize: 26,
                              ),
                            const SizedBox(height: sectionSpacing),
                            Text(
                              AppLocalizations.t('images'),
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: darkText,
                                  ),
                            ),
                            const SizedBox(height: labelSpacing),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                style: FilledButton.styleFrom(
                                  backgroundColor: deepRed,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 11,
                                  ),
                                ),
                                onPressed: _loading ? null : _pickImage,
                                icon: const Icon(
                                  Icons.file_upload_outlined,
                                  color: Colors.white,
                                ),
                                label: Text(
                                  _imageFilename == null
                                      ? AppLocalizations.t('add_images')
                                      : '${AppLocalizations.t('image')}: $_imageFilename',
                                  style: const TextStyle(color: Colors.white),
                                ),
                              ),
                            ),
                            const SizedBox(height: sectionSpacing),
                            Text(
                              AppLocalizations.t('content'),
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: darkText,
                                  ),
                            ),
                            const SizedBox(height: labelSpacing),
                            TextField(
                              controller: _descriptionController,
                              decoration: buildFieldDecoration(
                                AppLocalizations.t('details_of_incident'),
                              ),
                              maxLines: 4,
                            ),
                            const SizedBox(height: sectionSpacing),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                style: FilledButton.styleFrom(
                                  backgroundColor: deepRed,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 11,
                                  ),
                                ),
                                onPressed: _loading ? null : _openSetLocation,
                                icon: const Icon(
                                  Icons.check_box_outlined,
                                  color: Colors.white,
                                ),
                                label: Text(
                                  hasSelectedLocation
                                      ? AppLocalizations.t('edit_location')
                                      : AppLocalizations.t('set_location'),
                                  style: const TextStyle(color: Colors.white),
                                ),
                              ),
                            ),
                            if (hasSelectedLocation) ...[
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 10,
                                ),
                                decoration: BoxDecoration(
                                  color: addressFill,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Icon(
                                      Icons.location_on,
                                      color: deepRed,
                                      size: 18,
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        _selectedAddress ?? '',
                                        style: Theme.of(context)
                                            .textTheme
                                            .bodySmall
                                            ?.copyWith(
                                              color: darkText,
                                              fontWeight: FontWeight.w600,
                                            ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 26),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          style: FilledButton.styleFrom(
                            backgroundColor: deepRed,
                            padding: const EdgeInsets.symmetric(vertical: 13),
                          ),
                          onPressed: _loading ? null : _submit,
                          icon: _loading
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(
                                  Icons.check_box_outlined,
                                  color: Colors.white,
                                ),
                          label: Text(
                            AppLocalizations.t('create_register'),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
