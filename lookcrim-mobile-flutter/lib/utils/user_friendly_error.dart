import '../api/lookcrime_api.dart';

String userFriendlyErrorMessage(
  Object error, {
  String fallback = 'Something went wrong. Please try again.',
  String? operation,
}) {
  if (error is ApiException) {
    return _apiExceptionMessage(
      error,
      fallback: fallback,
      operation: operation,
    );
  }

  final raw = error.toString();
  final normalized = raw.toLowerCase();

  if (_looksLikeConnectionError(normalized)) {
    return 'Could not connect to the server. Please try again.';
  }

  if (_looksLikeFormatError(normalized)) {
    return 'There was a problem processing the response. Please try again later.';
  }

  if (normalized.startsWith('exception: ')) {
    final stripped = raw.substring('Exception: '.length).trim();
    if (stripped.isNotEmpty && !_looksTechnical(stripped.toLowerCase())) {
      return stripped;
    }
  }

  return fallback;
}

String _apiExceptionMessage(
  ApiException error, {
  required String fallback,
  String? operation,
}) {
  final statusCode = error.statusCode;
  final message = error.message.trim();
  final normalized = message.toLowerCase();

  if (statusCode == 401 || statusCode == 403) {
    return 'You do not have permission to continue.';
  }

  if (statusCode == 404) {
    return 'The requested information was not found.';
  }

  if (statusCode == 422) {
    switch (operation) {
      case 'login':
        return 'Incorrect email or password. Please try again.';
      case 'forgotPassword':
        return 'We could not send the link. Check your email and try again.';
      case 'createRegister':
        return 'Please review the report details and try again.';
      case 'profile':
        return 'We could not save the changes. Please try again.';
      default:
        break;
    }

    if (normalized.contains('email') && normalized.contains('password')) {
      return 'Incorrect email or password. Please try again.';
    }

    return fallback;
  }

  if (statusCode != null && statusCode >= 500) {
    return 'The server encountered a problem. Please try again later.';
  }

  if (_looksTechnical(normalized)) {
    return fallback;
  }

  return fallback;
}

bool _looksLikeConnectionError(String value) {
  return value.contains('socketexception') ||
      value.contains('timeoutexception') ||
      value.contains('failed host lookup') ||
      value.contains('connection refused') ||
      value.contains('connection timed out') ||
      value.contains('network is unreachable') ||
      value.contains('xhr error');
}

bool _looksLikeFormatError(String value) {
  return value.contains('formatexception') ||
      value.contains('json') ||
      value.contains('malformed');
}

bool _looksTechnical(String value) {
  return value.contains('statuscode') ||
      value.contains('apiexception') ||
      value.contains('body:') ||
      value.contains('stack trace') ||
      value.contains('json') ||
      value.contains('http ');
}
