import 'package:flutter/material.dart';

import 'api/lookcrime_api.dart';
import 'config/app_config.dart';
import 'screens/create_register_screen.dart';
import 'screens/login_screen.dart';
import 'storage/token_storage.dart';

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
    return MaterialApp(
      title: 'LookCrime Mobile',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.red),
        useMaterial3: true,
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
                  : CreateRegisterScreen(
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
      appBar: AppBar(title: const Text('Config requerida')),
      body: const SafeArea(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'Falta configurar API_BASE_URL.\n\n'
            'Ejemplo:\n'
            'flutter run --dart-define=API_BASE_URL=https://TU-CLOUD-RUN-URL',
          ),
        ),
      ),
    );
  }
}
