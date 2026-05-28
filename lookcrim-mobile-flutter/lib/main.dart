import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'api/lookcrime_api.dart';
import 'dart:async';
import 'config/app_config.dart';
import 'screens/list_registers_screen.dart';
import 'screens/login_screen.dart';
import 'storage/token_storage.dart';
import 'services/language_service.dart';
import 'services/map_tile_cache_service.dart';
import 'services/map_view_preset_service.dart';
import 'services/offline_sync_service.dart';
import 'utils/app_localizations.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  late final LookCrimeApi _api;
  final TokenStorage _tokenStorage = TokenStorage();
  late final VoidCallback _localeListener;
  bool _localeListenerAttached = false;

  bool _loading = true;
  String? _authorizationHeaderValue;
  bool _offlineBlocked = false;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (!mounted) return;
      setState(() {});
    };
    _bootstrap();
  }

  @override
  void dispose() {
    if (_localeListenerAttached) {
      LanguageService.instance.localeNotifier.removeListener(_localeListener);
    }
    super.dispose();
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _offlineBlocked = false;
    });

    await AppConfig.init();
    _api = LookCrimeApi.fromConfig();

    await LanguageService.instance.init();
    await MapViewPresetService.instance.init();
    await MapTileCacheService.instance.init();
    await OfflineSyncService.instance.init();
    if (!_localeListenerAttached) {
      LanguageService.instance.localeNotifier.addListener(_localeListener);
      _localeListenerAttached = true;
    }

    final header = await _tokenStorage.readAuthorizationHeaderValue();
    final online = await OfflineSyncService.instance.isOnline();
    var offlineBlocked = false;

    // If we have credentials and are online, prefetch recent registers in background
    if (header != null && online) {
      unawaited(
        OfflineSyncService.instance.fetchAndCacheRecentRegisters(
          api: _api,
          authorizationHeaderValue: header,
        ),
      );
    }

    if (!online) {
      final hasOfflineMinimum = await OfflineSyncService.instance
          .hasOfflineMinimum(
            lang: LanguageService.instance.currentLocale.languageCode,
          );
      offlineBlocked = header == null || !hasOfflineMinimum;
    }

    if (!mounted) return;
    setState(() {
      _authorizationHeaderValue = header;
      _loading = false;
      _offlineBlocked = offlineBlocked;
    });
  }

  void _onLoggedIn(String authorizationHeaderValue) {
    setState(() => _authorizationHeaderValue = authorizationHeaderValue);
  }

  void _onLoggedOut() {
    setState(() => _authorizationHeaderValue = null);
  }

  @override
  Widget build(BuildContext context) {
    final baseTheme = ThemeData(
      colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF7A0E0E)),
      useMaterial3: true,
    );

    final Widget home = _loading
        ? const Scaffold(body: Center(child: CircularProgressIndicator()))
        : (_offlineBlocked
              ? _OfflineBlockedScreen(onRetry: _bootstrap)
              : (AppConfig.apiBaseUrl.trim().isEmpty
                    ? const _MissingConfigScreen()
                    : (_authorizationHeaderValue == null
                          ? LoginScreen(
                              api: _api,
                              tokenStorage: _tokenStorage,
                              onLoggedIn: _onLoggedIn,
                            )
                          : ListRegistersScreen(
                              api: _api,
                              tokenStorage: _tokenStorage,
                              authorizationHeaderValue:
                                  _authorizationHeaderValue!,
                              onLogout: _onLoggedOut,
                            ))));

    return MaterialApp(
      title: 'LookCrime Mobile',
      locale: LanguageService.instance.currentLocale,
      localizationsDelegates: [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: LanguageService.instance.supportedLocales,
      theme: baseTheme.copyWith(
        textTheme: GoogleFonts.poppinsTextTheme(baseTheme.textTheme),
      ),
      home: home,
    );
  }
}

class _OfflineBlockedScreen extends StatelessWidget {
  final Future<void> Function() onRetry;

  const _OfflineBlockedScreen({required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(AppLocalizations.t('offline_blocked_title'))),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off, size: 56, color: Color(0xFF820000)),
              const SizedBox(height: 18),
              Text(
                AppLocalizations.t('offline_blocked_message'),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: () => onRetry(),
                child: Text(AppLocalizations.t('ok')),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MissingConfigScreen extends StatelessWidget {
  const _MissingConfigScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Missing config')),
      body: const SafeArea(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'API base URL is not configured.\n\n'
            'Set it in assets/config/app_config.json using:\n'
            '{"apiBaseUrl": "https://YOUR-CLOUD-RUN-URL"}\n\n'
            'Or use:\n'
            'flutter run --dart-define=API_BASE_URL=https://YOUR-CLOUD-RUN-URL',
          ),
        ),
      ),
    );
  }
}
