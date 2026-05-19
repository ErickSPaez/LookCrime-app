import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';
import 'forgot_password_screen.dart';
import '../utils/user_friendly_error.dart';
import '../services/language_service.dart';
import '../utils/app_localizations.dart';

class LoginScreen extends StatefulWidget {
  final LookCrimeApi api;
  final TokenStorage tokenStorage;
  final void Function(String authorizationHeaderValue) onLoggedIn;

  const LoginScreen({
    super.key,
    required this.api,
    required this.tokenStorage,
    required this.onLoggedIn,
  });

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  late final VoidCallback _localeListener;

  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await widget.api.login(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      );

      await widget.tokenStorage.writeToken(
        tokenType: res.tokenType,
        token: res.token,
      );
      final header = await widget.tokenStorage.readAuthorizationHeaderValue();
      if (!mounted) return;
      if (header == null) {
        setState(() => _error = AppLocalizations.t('token_save_error'));
        return;
      }

      widget.onLoggedIn(header);
    } catch (e) {
      debugPrint('Login failed: $e');
      setState(
        () => _error = userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('incorrect_credentials'),
          operation: 'login',
        ),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Color get _primaryRed => const Color(0xFF7A0E0E);
  Color get _linkRed => const Color(0xFFE0003A);
  Color get _cardTint => const Color(0xFFF2E7E7);

  InputDecoration _fieldDecoration({required String hint}) {
    return InputDecoration(
      hintText: hint,
      isDense: true,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      hintStyle: TextStyle(
        color: Colors.black.withValues(alpha: 0.55),
        fontWeight: FontWeight.w400,
        fontSize: 12,
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.black.withValues(alpha: 0.08)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.black.withValues(alpha: 0.08)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: _primaryRed.withValues(alpha: 0.65)),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: _linkRed.withValues(alpha: 0.65)),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: _linkRed.withValues(alpha: 0.65)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final height = MediaQuery.sizeOf(context).height;
    final isCompact = height <= 700;
    final contentMaxWidth = width >= 430 ? 390.0 : double.infinity;
    final topPadding = MediaQuery.paddingOf(context).top;

    final logoTopSpacing = isCompact ? 10.0 : 18.0;
    final logoHeight = isCompact ? 108.0 : 128.0;
    final bgHeight = topPadding + logoTopSpacing + logoHeight;

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: Image.asset(
              'assets/images/bg_mapv1.png',
              width: double.infinity,
              height: bgHeight,
              fit: BoxFit.cover,
              alignment: Alignment.topCenter,
              errorBuilder: (context, error, stackTrace) {
                return Container(
                  width: double.infinity,
                  height: bgHeight,
                  color: const Color(0xFFF7F7F7),
                );
              },
            ),
          ),
          SafeArea(
            child: Center(
              child: ConstrainedBox(
                constraints: BoxConstraints(maxWidth: contentMaxWidth),
                child: Form(
                  key: _formKey,
                  child: SingleChildScrollView(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          SizedBox(height: logoTopSpacing),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            clipBehavior: Clip.antiAlias,
                            child: SizedBox(
                              width: isCompact ? 240 : 260,
                              height: logoHeight,
                              child: Image.asset(
                                'assets/images/logo.png',
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) {
                                  return Center(
                                    child: Text(
                                      AppLocalizations.t('app_name'),
                                      style: GoogleFonts.poppins(
                                        color: _primaryRed,
                                        fontSize: 28,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                          ),
                          SizedBox(height: isCompact ? 16 : 24),
                          Text(
                            AppLocalizations.t('welcome'),
                            style: Theme.of(context).textTheme.titleLarge
                                ?.copyWith(
                                  fontWeight: FontWeight.w800,
                                  color: Colors.black,
                                  height: 1.5,
                                ),
                            textAlign: TextAlign.center,
                          ),
                          SizedBox(height: isCompact ? 12 : 20),
                          Text(
                            AppLocalizations.t('login_to_continue'),
                            style: Theme.of(context).textTheme.titleSmall
                                ?.copyWith(
                                  fontWeight: FontWeight.w600,
                                  color: Colors.black.withValues(alpha: 0.75),
                                  height: 1.5,
                                ),
                            textAlign: TextAlign.center,
                          ),
                          SizedBox(height: isCompact ? 18 : 26),
                          if (_error != null) ...[
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 14,
                              ),
                              child: Align(
                                alignment: Alignment.centerLeft,
                                child: Text(
                                  _error!,
                                  style: TextStyle(
                                    color: _linkRed,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 10),
                          ],
                          Container(
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: _cardTint,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            padding: const EdgeInsets.fromLTRB(10, 10, 14, 10),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      AppLocalizations.t('access'),
                                      style: Theme.of(context)
                                          .textTheme
                                          .titleLarge
                                          ?.copyWith(
                                            fontWeight: FontWeight.w800,
                                            color: Colors.black,
                                            height: 1.4,
                                          ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.only(bottom: 2),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Padding(
                                  padding: const EdgeInsets.only(left: 10),
                                  child: Text(
                                    AppLocalizations.t('use_email_password'),
                                    style: Theme.of(context).textTheme.bodySmall
                                        ?.copyWith(
                                          color: Colors.black.withValues(
                                            alpha: 0.55,
                                          ),
                                          fontWeight: FontWeight.w400,
                                          height: 1.3,
                                        ),
                                  ),
                                ),
                                const SizedBox(height: 14),
                                Padding(
                                  padding: const EdgeInsets.only(left: 10),
                                  child: Text(
                                    AppLocalizations.t('email_address'),
                                    style: Theme.of(context)
                                        .textTheme
                                        .bodyMedium
                                        ?.copyWith(
                                          height: 1.5,
                                          fontWeight: FontWeight.w600,
                                        ),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Padding(
                                  padding: const EdgeInsets.only(
                                    left: 10,
                                    right: 0,
                                  ),
                                  child: TextFormField(
                                    controller: _emailController,
                                    enabled: !_loading,
                                    keyboardType: TextInputType.emailAddress,
                                    decoration: _fieldDecoration(
                                      hint: 'ekamcy@gmail.com',
                                    ),
                                    validator: (value) {
                                      final v = value?.trim() ?? '';
                                      if (v.isEmpty) {
                                        return AppLocalizations.t(
                                          'email_required',
                                        );
                                      }
                                      final emailOk = RegExp(
                                        r'^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$',
                                      ).hasMatch(v);
                                      if (!emailOk) {
                                        return AppLocalizations.t(
                                          'valid_email',
                                        );
                                      }
                                      return null;
                                    },
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 14),
                                Padding(
                                  padding: const EdgeInsets.only(left: 10),
                                  child: Text(
                                    AppLocalizations.t('password'),
                                    style: Theme.of(context)
                                        .textTheme
                                        .bodyMedium
                                        ?.copyWith(
                                          height: 1.5,
                                          fontWeight: FontWeight.w600,
                                        ),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Padding(
                                  padding: const EdgeInsets.only(
                                    left: 10,
                                    right: 0,
                                  ),
                                  child: TextFormField(
                                    controller: _passwordController,
                                    enabled: !_loading,
                                    obscureText: true,
                                    decoration: _fieldDecoration(
                                      hint: '••••••••••••',
                                    ),
                                    validator: (value) {
                                      final v = value ?? '';
                                      if (v.isEmpty) {
                                        return AppLocalizations.t(
                                          'password_required',
                                        );
                                      }
                                      if (v.length < 6) {
                                        return AppLocalizations.t(
                                          'password_min_length',
                                        );
                                      }
                                      return null;
                                    },
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          SizedBox(height: isCompact ? 22 : 36),
                          SizedBox(
                            width: double.infinity,
                            height: isCompact ? 46 : 48,
                            child: ElevatedButton(
                              onPressed: _loading
                                  ? null
                                  : () {
                                      final ok =
                                          _formKey.currentState?.validate() ??
                                          false;
                                      if (!ok) {
                                        return;
                                      }
                                      _submit();
                                    },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _primaryRed,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                              child: _loading
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(
                                          Icons.arrow_forward,
                                          size: 18,
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          AppLocalizations.t('login'),
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                      ],
                                    ),
                            ),
                          ),
                          SizedBox(height: isCompact ? 28 : 44),
                          GestureDetector(
                            onTap: _loading
                                ? null
                                : () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ForgotPasswordScreen(
                                          api: widget.api,
                                        ),
                                      ),
                                    );
                                  },
                            child: Text(
                              AppLocalizations.t('forgot_password'),
                              style: Theme.of(context).textTheme.bodySmall
                                  ?.copyWith(
                                    color: _linkRed,
                                    fontWeight: FontWeight.w600,
                                    height: 1.5,
                                  ),
                            ),
                          ),
                          const SizedBox(height: 12),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
