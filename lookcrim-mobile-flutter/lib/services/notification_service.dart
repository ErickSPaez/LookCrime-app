import 'package:flutter/foundation.dart';

class AppBannerMessage {
  final int id;
  final String text;
  final bool dismissible;

  const AppBannerMessage({
    required this.id,
    required this.text,
    required this.dismissible,
  });
}

class NotificationService {
  NotificationService._();

  static final NotificationService instance = NotificationService._();

  int _nextId = 0;

  /// Banner message shown in main list screen (placed below search).
  final ValueNotifier<AppBannerMessage?> bannerNotifier =
      ValueNotifier<AppBannerMessage?>(null);

  void showBanner(
    String message, {
    bool dismissible = true,
    Duration? duration,
  }) {
    final banner = AppBannerMessage(
      id: ++_nextId,
      text: message,
      dismissible: dismissible,
    );
    bannerNotifier.value = banner;

    if (duration == null) return;

    Future.delayed(duration, () {
      if (bannerNotifier.value?.id == banner.id) {
        clearBanner();
      }
    });
  }

  void showTemporary(
    String message, {
    Duration duration = const Duration(milliseconds: 2500),
  }) {
    showBanner(message, dismissible: false, duration: duration);
  }

  void clearBanner() {
    bannerNotifier.value = null;
  }
}
