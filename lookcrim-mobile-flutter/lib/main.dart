import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'api/lookcrime_api.dart';
import 'config/app_config.dart';
import 'screens/list_registers_screen.dart';
import 'screens/login_screen.dart';
import 'storage/token_storage.dart';
import 'services/language_service.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  final LookCrimeApi _api = LookCrimeApi.fromConfig();
  final TokenStorage _tokenStorage = TokenStorage();

  bool _loading = true;
  String? _authorizationHeaderValue;

  @override
  void initState() {
    super.initState();
    _loadAuth();
    LanguageService.instance.init().then((_) {
      LanguageService.instance.localeNotifier.addListener(() {
        if (!mounted) return;
        setState(() {});
      });
      setState(() {});
    });
  }

  Future<void> _loadAuth() async {
    final header = await _tokenStorage.readAuthorizationHeaderValue();
    if (!mounted) return;
    setState(() {
      _authorizationHeaderValue = header;
      _loading = false;
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
      home: _loading
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
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
                          authorizationHeaderValue: _authorizationHeaderValue!,
                          onLogout: _onLoggedOut,
                        ))),
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
            'API_BASE_URL is not configured.\n\n'
            'Example:\n'
            'flutter run --dart-define=API_BASE_URL=https://YOUR-CLOUD-RUN-URL',
          ),
        ),
      ),
    );
  }
}
