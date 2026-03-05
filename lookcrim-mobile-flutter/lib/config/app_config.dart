class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://lookcrime-staging-862536847304.europe-southwest1.run.app',
  );
}
