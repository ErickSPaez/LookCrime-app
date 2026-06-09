import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api/lookcrime_api.dart';
import '../services/language_service.dart';
import '../services/offline_sync_service.dart';
import '../utils/app_localizations.dart';

class PendingRegistersScreen extends StatefulWidget {
  final LookCrimeApi api;
  final String authorizationHeaderValue;

  const PendingRegistersScreen({
    super.key,
    required this.api,
    required this.authorizationHeaderValue,
  });

  @override
  State<PendingRegistersScreen> createState() => _PendingRegistersScreenState();
}

class _PendingRegistersScreenState extends State<PendingRegistersScreen> {
  static const Color _red = Color(0xFF820000);
  static const Color _background = Color(0xFFFFFAFA);
  static const Color _cardBg = Color(0xFFF8EDEE);
  static const Color _pending = Color(0xFFB45A00);

  late final VoidCallback _localeListener;
  bool _loading = true;
  bool _syncing = false;
  String? _message;
  List<Map<String, dynamic>> _items = const [];

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _loadPending();
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    super.dispose();
  }

  Future<void> _loadPending() async {
    setState(() {
      _loading = true;
      _message = null;
    });

    try {
      final items = await OfflineSyncService.instance.loadPendingRegisters();
      if (!mounted) return;
      setState(() => _items = items);
    } catch (_) {
      if (!mounted) return;
      setState(() => _message = AppLocalizations.t('offline_db_error'));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _syncAll() async {
    if (_syncing) return;

    setState(() {
      _syncing = true;
      _message = null;
    });

    try {
      final synced = await OfflineSyncService.instance.syncPendingRegisters(
        api: widget.api,
        authorizationHeaderValue: widget.authorizationHeaderValue,
      );
      if (!mounted) return;
      setState(() {
        _message = synced > 0
            ? AppLocalizations.t('pending_synced_success')
            : AppLocalizations.t('pending_synced_nothing');
      });
      await _loadPending();
    } catch (_) {
      if (!mounted) return;
      setState(() => _message = AppLocalizations.t('pending_register_sync_failed'));
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  Future<void> _retryOne(int localId) async {
    if (_syncing) return;

    setState(() {
      _syncing = true;
      _message = null;
    });

    try {
      final ok = await OfflineSyncService.instance.syncSinglePending(
        api: widget.api,
        authorizationHeaderValue: widget.authorizationHeaderValue,
        localId: localId,
      );
      if (!mounted) return;
      setState(() {
        _message = ok
            ? AppLocalizations.t('pending_synced_success_single')
            : AppLocalizations.t('pending_synced_failed_single');
      });
      await _loadPending();
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  Future<void> _cancelOne(int localId) async {
    if (_syncing) return;

    await OfflineSyncService.instance.cancelPending(localId);
    if (!mounted) return;
    setState(() => _message = AppLocalizations.t('pending_cancelled'));
    await _loadPending();
  }

  Uint8List? _imageBytes(Map<String, dynamic> item) {
    final raw = item['image_base64'];
    if (raw is! String || raw.trim().isEmpty) return null;

    try {
      return base64Decode(raw);
    } catch (_) {
      return null;
    }
  }

  String _title(Map<String, dynamic> item) {
    final value = item['title']?.toString().trim() ?? '';
    return value.isEmpty ? AppLocalizations.t('untitled') : value;
  }

  String _address(Map<String, dynamic> item) {
    final value = item['address']?.toString().trim() ?? '';
    return value.isEmpty
        ? AppLocalizations.t('location_not_available')
        : value;
  }

  String _status(Map<String, dynamic> item) {
    if (!OfflineSyncService.instance.onlineNotifier.value) {
      return AppLocalizations.t('pending_status_waiting_internet');
    }

    final address = item['address']?.toString() ?? '';
    if (OfflineSyncService.instance.addressNeedsResolution(address)) {
      return AppLocalizations.t('pending_status_resolving_address');
    }
    return AppLocalizations.t('pending_status_waiting_upload');
  }

  Widget _buildHeader() {
    return Padding(
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
          Text(
            AppLocalizations.t('pending_registers'),
            style: GoogleFonts.poppins(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: Colors.black,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessage() {
    final message = _message;
    if (message == null || message.trim().isEmpty) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: _cardBg,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          message,
          style: GoogleFonts.poppins(fontSize: 13, color: Colors.black87),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: Color(0xFFFFECEC),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.pending_actions,
                color: _red,
                size: 32,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              AppLocalizations.t('no_pending_registers'),
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              AppLocalizations.t('no_pending_registers_message'),
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(fontSize: 13, color: Colors.black54),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPendingCard(Map<String, dynamic> item) {
    final localId = item['local_id'] as int?;
    final imageBytes = _imageBytes(item);

    return Card(
      color: _cardBg,
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 14),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
            child: AspectRatio(
              aspectRatio: 16 / 8,
              child: imageBytes == null
                  ? Container(
                      color: const Color(0xFFD5D5D5),
                      child: const Icon(
                        Icons.photo,
                        color: Colors.white70,
                        size: 44,
                      ),
                    )
                  : Image.memory(imageBytes, fit: BoxFit.cover),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        _title(item),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.poppins(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: Colors.black,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: _pending,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        AppLocalizations.t('pending_short'),
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.location_on, color: _red, size: 16),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        _address(item),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.poppins(
                          fontSize: 13,
                          color: Colors.black54,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  _status(item),
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _pending,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: localId == null || _syncing
                            ? null
                            : () => _cancelOne(localId),
                        icon: const Icon(Icons.close, size: 18),
                        label: Text(AppLocalizations.t('cancel')),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: localId == null || _syncing
                            ? null
                            : () => _retryOne(localId),
                        style: FilledButton.styleFrom(backgroundColor: _red),
                        icon: _syncing
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.cloud_upload_outlined, size: 18),
                        label: Text(AppLocalizations.t('retry_upload')),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _background,
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
                _buildHeader(),
                _buildMessage(),
                if (_items.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                    child: SizedBox(
                      width: double.infinity,
                      height: 46,
                      child: FilledButton.icon(
                        onPressed: _syncing ? null : _syncAll,
                        style: FilledButton.styleFrom(
                          backgroundColor: _red,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        icon: _syncing
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.cloud_upload_outlined),
                        label: Text(AppLocalizations.t('upload_registers')),
                      ),
                    ),
                  ),
                Expanded(
                  child: _loading
                      ? const Center(child: CircularProgressIndicator())
                      : _items.isEmpty
                          ? _buildEmptyState()
                          : RefreshIndicator(
                              onRefresh: _loadPending,
                              child: ListView.builder(
                                padding: const EdgeInsets.only(bottom: 24),
                                itemCount: _items.length,
                                itemBuilder: (context, index) =>
                                    _buildPendingCard(_items[index]),
                              ),
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
