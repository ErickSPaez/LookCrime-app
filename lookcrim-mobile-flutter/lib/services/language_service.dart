import 'package:flutter/widgets.dart';

import '../storage/lang_storage.dart';

class LanguageService {
  LanguageService._();

  static final LanguageService instance = LanguageService._();

  final LangStorage _storage = LangStorage();

  // Notifier that widgets can listen to.
  final ValueNotifier<Locale> localeNotifier = ValueNotifier(
    const Locale('en'),
  );

  List<Locale> get supportedLocales => const [Locale('en'), Locale('pt')];

  Future<void> init() async {
    final saved = await _storage.readLang();
    if (saved != null && saved.trim().isNotEmpty) {
      localeNotifier.value = Locale(saved);
      return;
    }

    // Use platform locale if supported
    final platform = WidgetsBinding.instance.platformDispatcher.locale;
    final code = platform.languageCode.toLowerCase();
    if (code.startsWith('pt')) {
      localeNotifier.value = const Locale('pt');
    } else {
      localeNotifier.value = const Locale('en');
    }
  }

  Future<void> setLocale(Locale locale) async {
    localeNotifier.value = locale;
    await _storage.writeLang(locale.languageCode);
  }

  Locale get currentLocale => localeNotifier.value;
}
