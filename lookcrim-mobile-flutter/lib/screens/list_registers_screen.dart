import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';
import 'create_register_screen.dart';
import 'edit_register_screen.dart';
import 'pending_registers_screen.dart';
import 'profile_screen.dart';
import 'read_register_screen.dart';
import '../utils/user_friendly_error.dart';
import '../services/language_service.dart';
import '../services/offline_sync_service.dart';
import '../services/notification_service.dart';
import '../utils/app_localizations.dart';

typedef RegisterItem = ({
  int id,
  int? localId,
  int? userId,
  bool isPending,
  String title,
  String description,
  String? address,
  String? imageUrl,
  Uint8List? imageBytes,
  String? createdAt,
});

class ListRegistersScreen extends StatefulWidget {
  final LookCrimeApi api;
  final TokenStorage tokenStorage;
  final String authorizationHeaderValue;
  final VoidCallback onLogout;

  const ListRegistersScreen({
    super.key,
    required this.api,
    required this.tokenStorage,
    required this.authorizationHeaderValue,
    required this.onLogout,
  });

  @override
  State<ListRegistersScreen> createState() => _ListRegistersScreenState();
}

class _ListRegistersScreenState extends State<ListRegistersScreen> {
  final _searchController = TextEditingController();
  final _searchFocusNode = FocusNode();
  late final VoidCallback _localeListener;
  Timer? _searchDebounce;
  int _loadRequestId = 0;

  bool _loading = false;
  String? _error;
  List<RegisterItem> _items = const [];
  String? _cityLabel;
  int? _currentUserId;
  List<String> _currentPermissions = const [];
  String _activeQuery = '';
  int _pendingCount = 0;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) {
        setState(() {});
      }
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _loadUserContext();
    _refreshPendingCount();
    _loadRegisters();

    // listen connectivity and pending count changes to auto-sync
    OfflineSyncService.instance.onlineNotifier.addListener(_onOnlineChanged);
    OfflineSyncService.instance.pendingCountNotifier.addListener(
      _onPendingCountChanged,
    );
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    _searchFocusNode.dispose();
    _searchController.dispose();
    OfflineSyncService.instance.onlineNotifier.removeListener(_onOnlineChanged);
    OfflineSyncService.instance.pendingCountNotifier.removeListener(
      _onPendingCountChanged,
    );
    super.dispose();
  }

  void _onPendingCountChanged() {
    if (!mounted) return;
    setState(() {
      _pendingCount = OfflineSyncService.instance.pendingCountNotifier.value;
    });
  }

  void _onOnlineChanged() async {
    if (!mounted) return;
    final online = OfflineSyncService.instance.onlineNotifier.value;
    if (online && _pendingCount > 0) {
      _showMessage(AppLocalizations.t('pending_register_sync_started'));
      await _manualSync();
      _showMessage(AppLocalizations.t('pending_register_sync_done'));
    }
  }

  Future<void> _loadRegisters({String? query, bool showLoader = true}) async {
    final requestId = ++_loadRequestId;

    if (showLoader) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    try {
      final normalizedQuery = (query ?? _searchController.text).trim();
      final online = await OfflineSyncService.instance.isOnline();
      List<Map<String, dynamic>> rawItems = const [];

      if (online) {
        await OfflineSyncService.instance.syncPendingRegisters(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
        );
        if (normalizedQuery.isEmpty) {
          // When online, prefer server results for the main list so user sees
          // all accessible registers. Also kick off a background job to cache
          // recent registers for offline use.
          final res = await widget.api.getRegisters(
            authorizationHeaderValue: widget.authorizationHeaderValue,
            page: 1,
            perPage: 100,
          );

          // Load pending items from local DB so they appear on top.
          final cachedLocal = await OfflineSyncService.instance
              .loadCachedRegisters();
          final pendingMatches = cachedLocal
              .where((item) => item['is_pending'] == true)
              .toList(growable: false);

          rawItems = [...pendingMatches, ...res.items];

          // Background: fetch remaining pages from server and append to list
          // so the user sees all accessible records when online. Also keep
          // refreshing the recent-month cache for offline use.
          Future(() async {
            try {
              final perPage = res.perPage;
              final total = res.total;
              final lastPage = (total + perPage - 1) ~/ perPage;

              // Start by caching recent registers (31 days) in background.
              try {
                await OfflineSyncService.instance.fetchAndCacheRecentRegisters(
                  api: widget.api,
                  authorizationHeaderValue: widget.authorizationHeaderValue,
                  perPage: perPage,
                  daysBack: 31,
                );
              } catch (_) {
                // ignore
              }

              // Fetch remaining pages (2..lastPage) and append them to UI as they arrive.
              for (var page = res.page + 1; page <= lastPage; page++) {
                try {
                  final pageRes = await widget.api.getRegisters(
                    authorizationHeaderValue: widget.authorizationHeaderValue,
                    page: page,
                    perPage: perPage,
                  );

                  if (pageRes.items.isEmpty) break;

                  if (!mounted || requestId != _loadRequestId) break;

                  // Map and append without disturbing pending items at top.
                  final newItems = pageRes.items
                      .map(_mapRegisterItem)
                      .toList(growable: false);
                  setState(() {
                    // Ensure we keep pending items at the top. Remove any duplicates by id.
                    final existingIds = _items.map((e) => e.id).toSet();
                    final additions = newItems
                        .where((it) => !existingIds.contains(it.id))
                        .toList(growable: false);
                    _items = [..._items, ...additions];
                  });
                } catch (_) {
                  // stop on errors fetching subsequent pages
                  break;
                }
              }
            } catch (_) {
              // swallow background errors
            }
          });
        } else {
          final res = await widget.api.getRegisters(
            authorizationHeaderValue: widget.authorizationHeaderValue,
            query: normalizedQuery,
          );
          final cachedMatches = await OfflineSyncService.instance
              .loadCachedRegisters(query: normalizedQuery);
          final pendingMatches = cachedMatches
              .where((item) => item['is_pending'] == true)
              .toList(growable: false);
          rawItems = [...pendingMatches, ...res.items];
        }
      } else {
        rawItems = await OfflineSyncService.instance.loadCachedRegisters(
          query: normalizedQuery.isEmpty ? null : normalizedQuery,
        );
      }

      final items = rawItems.map(_mapRegisterItem).toList(growable: false);

      if (!mounted || requestId != _loadRequestId) return;

      setState(() {
        _items = items;
        _error = items.isEmpty && !online
            ? AppLocalizations.t('offline_missing_register_cache')
            : null;
      });

      _precacheImages(items);
    } catch (e) {
      debugPrint('Load registers failed: $e');
      if (!mounted || requestId != _loadRequestId) return;

      final online = await OfflineSyncService.instance.isOnline();
      if (!mounted || requestId != _loadRequestId) return;

      if (!online) {
        final cachedItems = await OfflineSyncService.instance
            .loadCachedRegisters(query: query);
        if (!mounted || requestId != _loadRequestId) return;

        final items = cachedItems.map(_mapRegisterItem).toList(growable: false);
        setState(() {
          _items = items;
          _error = items.isEmpty
              ? AppLocalizations.t('offline_missing_register_cache')
              : null;
        });
        return;
      }

      setState(() {
        _error = userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('load_reports_fail'),
        );
      });
    } finally {
      if (mounted && requestId == _loadRequestId && showLoader) {
        setState(() {
          _loading = false;
        });
      }
      if (mounted && requestId == _loadRequestId) await _refreshPendingCount();
    }
  }

  Future<void> _refreshPendingCount() async {
    try {
      final count = await OfflineSyncService.instance.pendingCount();
      if (!mounted) return;
      setState(() {
        _pendingCount = count;
      });
    } catch (_) {
      // ignore
    }
  }

  Future<void> _manualSync() async {
    if (_loading) return;
    setState(() {
      _loading = true;
    });

    try {
      final synced = await OfflineSyncService.instance.syncPendingRegisters(
        api: widget.api,
        authorizationHeaderValue: widget.authorizationHeaderValue,
      );

      if (!mounted) return;
      if (synced > 0) {
        _showMessage(AppLocalizations.t('pending_synced_success'));
      } else {
        _showMessage(AppLocalizations.t('pending_synced_nothing'));
      }

      await _loadRegisters(showLoader: false);
    } catch (e) {
      _showMessage(userFriendlyErrorMessage(e));
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
        await _refreshPendingCount();
      }
    }
  }

  Future<void> _retryPending(RegisterItem item) async {
    if (item.localId == null) return;

    setState(() {
      _loading = true;
    });

    try {
      final ok = await OfflineSyncService.instance.syncSinglePending(
        api: widget.api,
        authorizationHeaderValue: widget.authorizationHeaderValue,
        localId: item.localId!,
      );

      if (!mounted) return;
      if (ok) {
        _showMessage(AppLocalizations.t('pending_synced_success_single'));
      } else {
        _showMessage(AppLocalizations.t('pending_synced_failed_single'));
      }

      await _loadRegisters(showLoader: false);
    } catch (e) {
      _showMessage(userFriendlyErrorMessage(e));
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
        await _refreshPendingCount();
      }
    }
  }

  Future<void> _cancelPending(RegisterItem item) async {
    if (item.localId == null) return;

    setState(() {
      _loading = true;
    });

    try {
      await OfflineSyncService.instance.cancelPending(item.localId!);
      if (!mounted) return;
      _showMessage(AppLocalizations.t('pending_cancelled'));
      await _loadRegisters(showLoader: false);
    } catch (e) {
      _showMessage(userFriendlyErrorMessage(e));
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
        await _refreshPendingCount();
      }
    }
  }

  void _dismissSearchKeyboard() {
    FocusScope.of(context).unfocus();
  }

  void _clearSearch({bool keepFocus = false}) {
    _searchDebounce?.cancel();
    if (_searchController.text.isNotEmpty) {
      _searchController.clear();
    }

    setState(() {
      _activeQuery = '';
    });

    _loadRegisters(query: null, showLoader: true);

    if (!keepFocus) {
      FocusScope.of(context).unfocus();
    }
  }

  void _scheduleAutoSearch(String value) {
    _searchDebounce?.cancel();

    final query = value.trim();
    setState(() {});

    if (query == _activeQuery) return;

    _searchDebounce = Timer(const Duration(milliseconds: 300), () {
      if (!mounted) return;

      final latestQuery = _searchController.text.trim();
      if (latestQuery == _activeQuery) return;

      setState(() {
        _activeQuery = latestQuery;
      });

      _loadRegisters(
        query: latestQuery.isEmpty ? null : latestQuery,
        showLoader: false,
      );
    });
  }

  void _precacheImages(List<RegisterItem> items) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;

      for (final item in items.take(4)) {
        final url = item.imageUrl;

        if (url == null || url.trim().isEmpty) continue;

        precacheImage(CachedNetworkImageProvider(url), context);
      }
    });
  }

  Future<void> _loadUserContext() async {
    try {
      final ctx = await OfflineSyncService.instance.getUserContext(
        remoteLoader: () => widget.api.getMe(
          authorizationHeaderValue: widget.authorizationHeaderValue,
        ),
      );
      final label = ctx.cityName?.trim().isNotEmpty == true
          ? ctx.cityName!.trim().toUpperCase()
          : null;

      if (!mounted) return;

      setState(() {
        _cityLabel = label;
        _currentUserId = ctx.userId;
        _currentPermissions = ctx.permissions;
      });
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _cityLabel = null;
        _currentUserId = null;
        _currentPermissions = const [];
      });
    }
  }

  RegisterItem _mapRegisterItem(Map<String, dynamic> raw) {
    final description = _sanitizeDescription(
      (raw['description'] as String?) ?? '',
    );

    final rawImageUrl = raw['image_url'];
    final isPending = raw['is_pending'] == true;

    String? imageUrl;

    if (!isPending && rawImageUrl is String && rawImageUrl.trim().isNotEmpty) {
      imageUrl = rawImageUrl.trim();
    }

    return (
      id:
          (raw['id'] as num?)?.toInt() ??
          ((raw['local_id'] as num?)?.toInt() ?? 0),
      localId: (raw['local_id'] as num?)?.toInt(),
      userId: (raw['user_id'] as num?)?.toInt(),
      isPending: isPending,
      title: (raw['title'] as String?) ?? AppLocalizations.t('untitled'),
      description: description,
      address: (raw['address'] as String?)?.trim().isEmpty == true
          ? null
          : raw['address'] as String?,
      imageUrl: imageUrl,
      imageBytes: isPending ? _decodePendingImage(raw['image_base64']) : null,
      createdAt: raw['created_at'] as String?,
    );
  }

  Uint8List? _decodePendingImage(dynamic rawBase64) {
    if (rawBase64 is! String || rawBase64.trim().isEmpty) return null;

    try {
      return base64Decode(rawBase64.trim());
    } catch (_) {
      return null;
    }
  }

  String _sanitizeDescription(String value) {
    if (value.isEmpty) return '';

    var cleaned = value.replaceAll(RegExp(r'<[^>]*>'), ' ');

    cleaned = cleaned
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>');

    cleaned = cleaned.replaceAll(RegExp(r'\s+'), ' ').trim();

    return cleaned;
  }

  void _showMessage(String message) {
    if (!mounted) return;

    NotificationService.instance.showTemporary(message);
  }

  Future<void> _openCreateRegister() async {
    final navigator = Navigator.of(context);
    final online = await OfflineSyncService.instance.isOnline();
    if (!online) {
      final lang = LanguageService.instance.currentLocale.languageCode;
      final ready = await OfflineSyncService.instance.canOpenOfflineApp(
        lang: lang,
      );
      if (!ready) {
        final missing = await OfflineSyncService.instance.offlineMissingReasons(
          lang: lang,
        );

        final parts = <String>[];
        if (missing.contains('categories')) {
          parts.add(AppLocalizations.t('offline_missing_categories'));
        }
        if (missing.contains('user_context')) {
          parts.add(AppLocalizations.t('offline_missing_user_context'));
        }
        if (missing.contains('registers')) {
          parts.add(AppLocalizations.t('offline_missing_register_cache'));
        }
        if (missing.contains('db_error')) {
          parts.add(AppLocalizations.t('offline_db_error'));
        }

        final message = parts.isEmpty
            ? AppLocalizations.t('offline_blocked_message')
            : parts.join('\n');

        _showMessage(message);
        return;
      }
    }

    final created = await navigator.push<bool>(
      MaterialPageRoute(
        builder: (_) => CreateRegisterScreen(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
        ),
      ),
    );

    if (created == true && mounted) {
      await _loadRegisters(showLoader: false);
    }
  }

  Future<void> _openProfile() async {
    final navigator = Navigator.of(context);
    final online = await OfflineSyncService.instance.isOnline();
    if (!mounted) return;
    if (!online) {
      _showMessage(AppLocalizations.t('offline_only_profile_message'));
      return;
    }

    navigator.push(
      MaterialPageRoute(
        builder: (_) => ProfileScreen(
          api: widget.api,
          tokenStorage: widget.tokenStorage,
          authorizationHeaderValue: widget.authorizationHeaderValue,
          onLogout: widget.onLogout,
        ),
      ),
    );
  }

  Future<void> _openPendingRegisters() async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PendingRegistersScreen(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
        ),
      ),
    );

    if (!mounted) return;
    await _loadRegisters(showLoader: false);
    await _refreshPendingCount();
  }

  bool _canEditRegister(RegisterItem item) {
    if (item.isPending) return false;

    final canEditAny =
        _currentPermissions.contains('edit_any_registers') ||
        _currentPermissions.contains('edit_all_registers');
    final canEditOwn =
        _currentPermissions.contains('edit_own_registers') &&
        _currentUserId != null &&
        item.userId != null &&
        item.userId == _currentUserId;

    return canEditAny || canEditOwn;
  }

  bool _canDeleteRegister(RegisterItem item) {
    if (item.isPending) return false;

    final canDeleteAny =
        _currentPermissions.contains('delete_any_registers') ||
        _currentPermissions.contains('delete_registers');
    final canDeleteOwn =
        _currentPermissions.contains('delete_own_registers') &&
        _currentUserId != null &&
        item.userId != null &&
        item.userId == _currentUserId;

    return canDeleteAny || canDeleteOwn;
  }

  Future<void> _openEditRegister(RegisterItem item) async {
    if (item.isPending) return;

    final online = await OfflineSyncService.instance.isOnline();
    if (!online) {
      _showMessage(AppLocalizations.t('offline_only_profile_message'));
      return;
    }

    final canEdit = _canEditRegister(item);
    if (!canEdit) {
      _showMessage(AppLocalizations.t('no_permission_for_action'));
      return;
    }

    if (!mounted) return;
    final navigator = Navigator.of(context);
    final edited = await navigator.push<bool>(
      MaterialPageRoute(
        builder: (_) => EditRegisterScreen(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
          registerId: item.id,
        ),
      ),
    );

    if (edited == true && mounted) {
      await _loadRegisters(showLoader: false);
    }
  }

  Future<void> _confirmDeleteRegister(RegisterItem item) async {
    if (item.isPending) return;

    final online = await OfflineSyncService.instance.isOnline();
    if (!online) {
      _showMessage(AppLocalizations.t('offline_only_profile_message'));
      return;
    }

    final canDelete = _canDeleteRegister(item);
    if (!canDelete) {
      _showMessage(AppLocalizations.t('no_permission_for_action'));
      return;
    }

    if (!mounted) return;
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: Text(AppLocalizations.t('delete_register')),
          content: Text(AppLocalizations.t('delete_register_confirm')),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: Text(AppLocalizations.t('cancel')),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF820000),
              ),
              child: Text(AppLocalizations.t('delete')),
            ),
          ],
        );
      },
    );

    if (shouldDelete != true) return;

    try {
      await widget.api.deleteRegister(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        id: item.id,
      );

      if (!mounted) return;
      _showMessage(AppLocalizations.t('delete_register_success'));
      await _loadRegisters(showLoader: false);
    } catch (e) {
      if (!mounted) return;
      _showMessage(
        userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('delete_register_failed'),
          operation: 'deleteRegister',
        ),
      );
    }
  }

  Future<void> _openReadRegister(RegisterItem item) async {
    if (item.isPending) {
      _showMessage(AppLocalizations.t('pending_register_saved'));
      return;
    }

    final navigator = Navigator.of(context);
    final online = await OfflineSyncService.instance.isOnline();
    if (!mounted) return;
    if (!online) {
      _showMessage(AppLocalizations.t('offline_only_profile_message'));
      return;
    }

    navigator.push(
      MaterialPageRoute(
        builder: (_) => ReadRegisterScreen(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
          registerId: item.id,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final red = const Color(0xFF770101);
    final lightRed = const Color(0xFFFFE8E8);
    final mutedText = const Color(0xFF4B5563);

    return Scaffold(
      backgroundColor: const Color(0xFFFFFEFE),
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
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: () {
                          final code = LanguageService
                              .instance
                              .currentLocale
                              .languageCode
                              .toUpperCase();
                          final langLabel = code == 'PT'
                              ? AppLocalizations.t('portuguese_pt')
                              : AppLocalizations.t('english');
                          showDialog<void>(
                            context: context,
                            builder: (dctx) {
                              return AlertDialog(
                                title: Text(
                                  AppLocalizations.t('language_dialog_title'),
                                ),
                                content: Text(
                                  AppLocalizations.t(
                                    'language_dialog_message_profile',
                                    args: {'lang': langLabel},
                                  ),
                                ),
                                actions: [
                                  TextButton(
                                    onPressed: () {
                                      Navigator.of(dctx).pop();
                                      _openProfile();
                                    },
                                    child: Text(
                                      AppLocalizations.t('go_to_profile'),
                                    ),
                                  ),
                                  TextButton(
                                    onPressed: () async {
                                      Navigator.of(dctx).pop();
                                      final online = await OfflineSyncService
                                          .instance
                                          .isOnline();
                                      if (!online) {
                                        _showMessage(
                                          AppLocalizations.t(
                                            'offline_only_profile_message',
                                          ),
                                        );
                                      }
                                    },
                                    child: Text(AppLocalizations.t('ok')),
                                  ),
                                ],
                              );
                            },
                          );
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: lightRed,
                            borderRadius: BorderRadius.circular(24),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.public, color: red, size: 18),
                              const SizedBox(width: 8),
                              Text(
                                (LanguageService
                                            .instance
                                            .currentLocale
                                            .languageCode
                                            .toUpperCase() ==
                                        'PT')
                                    ? 'PT'
                                    : 'ENG',
                                style: theme.textTheme.labelMedium?.copyWith(
                                  fontWeight: FontWeight.w700,
                                  color: red,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      const Spacer(),

                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_outlined,
                            color: Color(0xFF09051C),
                            size: 16,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _cityLabel ?? '',
                            style: theme.textTheme.labelSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                              color: const Color(0xFF09051C),
                            ),
                          ),
                        ],
                      ),

                      const Spacer(),

                      Row(
                        children: [
                          IconButton(
                            tooltip: AppLocalizations.t('pending_registers'),
                            onPressed: _loading ? null : _openPendingRegisters,
                            icon: Icon(
                              Icons.pending_actions,
                              color: _pendingCount > 0
                                  ? const Color(0xFFB45A00)
                                  : Colors.black87,
                            ),
                          ),
                          if (_pendingCount > 0)
                            Container(
                              margin: const EdgeInsets.only(right: 8),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: const Color(0xFFB45A00),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                _pendingCount.toString(),
                                style: theme.textTheme.labelSmall?.copyWith(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),

                          GestureDetector(
                            onTap: _openProfile,
                            child: Container(
                              width: 34,
                              height: 34,
                              decoration: BoxDecoration(
                                color: red,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.person,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  child: Container(
                    decoration: BoxDecoration(
                      color: red,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      focusNode: _searchFocusNode,
                      textInputAction: TextInputAction.done,
                      onChanged: (value) {
                        _scheduleAutoSearch(value);
                      },
                      onSubmitted: (_) {
                        _dismissSearchKeyboard();
                      },
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        hintText: AppLocalizations.t('search'),
                        hintStyle: const TextStyle(color: Colors.white70),
                        prefixIcon: const Icon(
                          Icons.search,
                          color: Colors.white,
                        ),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(
                          vertical: 12,
                        ),
                        suffixIcon: _searchController.text.trim().isEmpty
                            ? null
                            : IconButton(
                                tooltip: AppLocalizations.t('cancel'),
                                onPressed: _loading
                                    ? null
                                    : () => _clearSearch(),
                                icon: const Icon(
                                  Icons.close,
                                  color: Colors.white,
                                ),
                              ),
                      ),
                    ),
                  ),
                ),

                // Inline banner area (below search)
                ValueListenableBuilder<AppBannerMessage?>(
                  valueListenable: NotificationService.instance.bannerNotifier,
                  builder: (context, banner, _) {
                    final message = banner?.text.trim() ?? '';
                    if (message.isEmpty) {
                      return const SizedBox.shrink();
                    }

                    return Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                      child: Container(
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8EDEE),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                message,
                                style: Theme.of(context).textTheme.bodyMedium
                                    ?.copyWith(color: Colors.black87),
                              ),
                            ),
                            if (banner?.dismissible == true)
                              IconButton(
                                onPressed: () =>
                                    NotificationService.instance.clearBanner(),
                                icon: const Icon(Icons.close),
                              ),
                          ],
                        ),
                      ),
                    );
                  },
                ),

                Expanded(
                  child: RefreshIndicator(
                    onRefresh: () => _loadRegisters(showLoader: false),
                    child: _loading
                        ? const Center(child: CircularProgressIndicator())
                        : ListView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 90),
                            itemCount: _items.length + (_error != null ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (_error != null && index == 0) {
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: Text(
                                    _error!,
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: Colors.red,
                                    ),
                                  ),
                                );
                              }

                              final item =
                                  _items[index - (_error != null ? 1 : 0)];

                              return _RegisterCard(
                                title: item.title,
                                address: item.address,
                                imageUrl: item.imageUrl,
                                imageBytes: item.imageBytes,
                                mutedText: mutedText,
                                isPending: item.isPending,
                                canEdit: _canEditRegister(item),
                                canDelete: _canDeleteRegister(item),
                                onTap: () => _openReadRegister(item),
                                onEdit: () => _openEditRegister(item),
                                onDelete: () => _confirmDeleteRegister(item),
                                onRetry: item.isPending
                                    ? () => _retryPending(item)
                                    : null,
                                onCancel: item.isPending
                                    ? () => _cancelPending(item)
                                    : null,
                              );
                            },
                          ),
                  ),
                ),
              ],
            ),

            Positioned(
              left: 16,
              right: 16,
              bottom: 16,
              child: SizedBox(
                height: 48,
                child: FilledButton.icon(
                  onPressed: _openCreateRegister,
                  style: FilledButton.styleFrom(
                    backgroundColor: red,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  icon: const Icon(Icons.add, color: Colors.white),
                  label: Text(
                    AppLocalizations.t('create_new_register'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RegisterCard extends StatelessWidget {
  final String title;
  final String? address;
  final String? imageUrl;
  final Uint8List? imageBytes;
  final Color mutedText;
  final bool isPending;
  final bool canEdit;
  final bool canDelete;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;
  final VoidCallback? onRetry;
  final VoidCallback? onCancel;

  const _RegisterCard({
    required this.title,
    required this.address,
    required this.imageUrl,
    required this.imageBytes,
    required this.mutedText,
    required this.isPending,
    required this.canEdit,
    required this.canDelete,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
    this.onRetry,
    this.onCancel,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClipRRect(
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(16),
              ),
              child: AspectRatio(
                aspectRatio: 16 / 10,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (imageUrl != null)
                      CachedNetworkImage(
                        imageUrl: imageUrl!,
                        fit: BoxFit.cover,
                        placeholder: (_, _) => Container(
                          color: const Color(0xFFD5D5D5),
                          child: const Center(
                            child: SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          ),
                        ),
                        errorWidget: (_, _, _) => Container(
                          color: const Color(0xFFD5D5D5),
                          child: const Icon(
                            Icons.broken_image,
                            size: 40,
                            color: Colors.white70,
                          ),
                        ),
                      )
                    else if (imageBytes != null)
                      Image.memory(
                        imageBytes!,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) {
                          return Container(
                            color: const Color(0xFFD5D5D5),
                            child: const Icon(
                              Icons.broken_image,
                              size: 40,
                              color: Colors.white70,
                            ),
                          );
                        },
                      )
                    else
                      Container(
                        color: const Color(0xFFD5D5D5),
                        child: const Icon(
                          Icons.photo,
                          size: 48,
                          color: Colors.white70,
                        ),
                      ),
                    if (isPending)
                      Container(
                        color: Colors.black.withValues(alpha: 0.25),
                        alignment: Alignment.topRight,
                        padding: const EdgeInsets.all(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFB45A00),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            'PENDING',
                            style: Theme.of(context).textTheme.labelSmall
                                ?.copyWith(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),

            Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(
                              Icons.location_on,
                              size: 14,
                              color: Color(0xFFB34242),
                            ),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                (address != null && address!.trim().isNotEmpty)
                                    ? address!.trim()
                                    : AppLocalizations.t(
                                        'location_not_available',
                                      ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: mutedText,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (isPending) ...[
                    IconButton(
                      icon: const Icon(Icons.refresh),
                      tooltip: AppLocalizations.t(
                        'pending_register_sync_started',
                      ),
                      onPressed: onRetry,
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      tooltip: AppLocalizations.t('cancel'),
                      onPressed: onCancel,
                    ),
                  ] else if (canEdit || canDelete)
                    PopupMenuButton<_CardAction>(
                      icon: const Icon(Icons.more_vert),
                      onSelected: (action) {
                        switch (action) {
                          case _CardAction.edit:
                            onEdit?.call();
                            break;
                          case _CardAction.delete:
                            onDelete?.call();
                            break;
                        }
                      },
                      itemBuilder: (context) => [
                        if (canEdit)
                          PopupMenuItem<_CardAction>(
                            value: _CardAction.edit,
                            child: Row(
                              children: [
                                const Icon(Icons.edit, size: 18),
                                const SizedBox(width: 10),
                                Text(AppLocalizations.t('edit')),
                              ],
                            ),
                          ),
                        if (canDelete)
                          PopupMenuItem<_CardAction>(
                            value: _CardAction.delete,
                            child: Row(
                              children: [
                                const Icon(Icons.delete, size: 18),
                                const SizedBox(width: 10),
                                Text(AppLocalizations.t('delete')),
                              ],
                            ),
                          ),
                      ],
                    )
                  else
                    IconButton(
                      icon: const Icon(Icons.more_vert),
                      onPressed: _showOptionsDisabled,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showOptionsDisabled() {
    // Functionality disabled for now.
  }
}

enum _CardAction { edit, delete }
