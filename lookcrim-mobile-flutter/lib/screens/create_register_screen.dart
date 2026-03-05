import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';

class CreateRegisterScreen extends StatefulWidget {
  final LookCrimeApi api;
  final TokenStorage tokenStorage;
  final String authorizationHeaderValue;
  final VoidCallback onLogout;

  const CreateRegisterScreen({
    super.key,
    required this.api,
    required this.tokenStorage,
    required this.authorizationHeaderValue,
    required this.onLogout,
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

  Uint8List? _imageBytes;
  String? _imageFilename;

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  @override
  void dispose() {
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
        _selectedCategory = cats.isNotEmpty ? cats.first.key : null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loadingCategories = false);
    }
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery);
    if (picked == null) return;
    final bytes = await picked.readAsBytes();
    if (!mounted) return;

    setState(() {
      _imageBytes = bytes;
      _imageFilename = picked.name.isNotEmpty ? picked.name : 'image.jpg';
    });
  }

  Future<void> _logout() async {
    await widget.tokenStorage.clear();
    widget.onLogout();
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
        throw Exception('Selecciona una categoría');
      }
      if (img == null || imgName == null) {
        throw Exception('Selecciona una imagen');
      }

      await widget.api.createRegister(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        title: _titleController.text.trim(),
        description: _descriptionController.text.trim(),
        category: cat,
        latitude: _latController.text.trim(),
        longitude: _lngController.text.trim(),
        imageBytes: img,
        imageFilename: imgName,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Registro creado')));
      _titleController.clear();
      _descriptionController.clear();
      setState(() {
        _imageBytes = null;
        _imageFilename = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Crear registro'),
        actions: [
          TextButton(
            onPressed: _loading ? null : _logout,
            child: const Text('Salir'),
          ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: ListView(
            children: [
              if (_error != null) ...[
                Text(_error!, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 12),
              ],
              TextField(
                controller: _titleController,
                decoration: const InputDecoration(labelText: 'Título'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _descriptionController,
                decoration: const InputDecoration(labelText: 'Descripción'),
                maxLines: 4,
              ),
              const SizedBox(height: 12),
              if (_loadingCategories)
                const LinearProgressIndicator()
              else
                DropdownButtonFormField<String>(
                  key: ValueKey(_selectedCategory),
                  initialValue: _selectedCategory,
                  items: _categories
                      .map((c) => DropdownMenuItem<String>(
                            value: c.key,
                            child: Text(c.label.isNotEmpty ? c.label : c.key),
                          ))
                      .toList(growable: false),
                  onChanged: _loading ? null : (v) => setState(() => _selectedCategory = v),
                  decoration: const InputDecoration(labelText: 'Categoría'),
                ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _latController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Lat'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _lngController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Lng'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: _loading ? null : _pickImage,
                child: Text(_imageFilename == null ? 'Elegir imagen' : 'Imagen: $_imageFilename'),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _loading ? null : _submit,
                child: _loading
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('Crear'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
