import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api/lookcrime_api.dart';
import '../utils/user_friendly_error.dart';
import '../services/language_service.dart';
import '../utils/app_localizations.dart';

class ForgotPasswordScreen extends StatefulWidget {
  final LookCrimeApi api;

  const ForgotPasswordScreen({super.key, required this.api});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  late final VoidCallback _localeListener;

  bool _loading = false;
  String? _error;
  String? _success;

  static const Color _primaryRed = Color(0xFF7A0E0E);
  static const Color _linkRed = Color(0xFFE0003A);
  static const Color _cardTint = Color(0xFFF2E7E7);

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
    super.dispose();
  }

  InputDecoration _fieldDecoration({required String hint}) {
    return InputDecoration(
      hintText: hint,
      isDense: true,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      hintStyle: TextStyle(
        color: Colors.black.withValues(alpha: 0.55),
        fontWeight: FontWeight.w400,
        fontSize: 13,
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

  Future<void> _submit() async {
    final ok = _formKey.currentState?.validate() ?? false;
    if (!ok) return;

    setState(() {
      _loading = true;
      _error = null;
      _success = null;
    });

    try {
      await widget.api.forgotPassword(email: _emailController.text.trim());

      if (!mounted) return;

      setState(() {
        _success = AppLocalizations.t('forgot_sent');
      });
    } catch (e) {
      debugPrint('Forgot password failed: $e');
      if (!mounted) return;

      setState(() {
        _error = userFriendlyErrorMessage(
          e,
          fallback: AppLocalizations.t('forgot_fail'),
          operation: 'forgotPassword',
        );
      });
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final height = MediaQuery.sizeOf(context).height;
    final isCompact = height <= 700;
    final contentMaxWidth = width >= 430 ? 390.0 : double.infinity;
    final topPadding = MediaQuery.paddingOf(context).top;

    final logoTopSpacing = isCompact ? 38.0 : 54.0;
    final logoHeight = isCompact ? 108.0 : 128.0;
    final bgHeight = topPadding + 210;

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
            child: Align(
              alignment: Alignment.topLeft,
              child: Padding(
                padding: const EdgeInsets.only(left: 22, top: 18),
                child: InkWell(
                  onTap: _loading ? null : () => Navigator.of(context).pop(),
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: _primaryRed,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.chevron_left,
                      color: Colors.white,
                      size: 30,
                    ),
                  ),
                ),
              ),
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
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          SizedBox(height: logoTopSpacing),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            clipBehavior: Clip.antiAlias,
                            child: Container(
                              width: isCompact ? 245 : 270,
                              height: logoHeight,
                              color: _primaryRed,
                              child: Image.asset(
                                'assets/images/logo.png',
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) {
                                  return Center(
                                    child: Text(
                                      AppLocalizations.t('app_name'),
                                      style: GoogleFonts.poppins(
                                        color: Colors.white,
                                        fontSize: 30,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                          ),

                          SizedBox(height: isCompact ? 22 : 30),

                          Text(
                            AppLocalizations.t('forgot_password'),
                            textAlign: TextAlign.center,
                            style: GoogleFonts.poppins(
                              fontSize: 19,
                              fontWeight: FontWeight.w600,
                              color: Colors.black,
                            ),
                          ),

                          const SizedBox(height: 10),

                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 22),
                            child: Text(
                              AppLocalizations.t('forgot_description'),
                              textAlign: TextAlign.center,
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w400,
                                color: Colors.black.withValues(alpha: 0.72),
                                height: 1.45,
                              ),
                            ),
                          ),

                          SizedBox(height: isCompact ? 44 : 58),

                          if (_error != null) ...[
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                              ),
                              child: Text(
                                _error!,
                                textAlign: TextAlign.center,
                                style: GoogleFonts.poppins(
                                  color: _linkRed,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                          ],

                          if (_success != null) ...[
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                              ),
                              child: Text(
                                _success!,
                                textAlign: TextAlign.center,
                                style: GoogleFonts.poppins(
                                  color: const Color(0xFF067A46),
                                  fontWeight: FontWeight.w600,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                          ],

                          Container(
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: _cardTint,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            padding: const EdgeInsets.fromLTRB(18, 18, 18, 28),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  AppLocalizations.t('email_address'),
                                  style: GoogleFonts.poppins(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.black,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                TextFormField(
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
                                      return AppLocalizations.t('valid_email');
                                    }

                                    return null;
                                  },
                                  style: GoogleFonts.poppins(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),

                          SizedBox(height: isCompact ? 68 : 86),

                          SizedBox(
                            width: double.infinity,
                            height: 48,
                            child: ElevatedButton(
                              onPressed: _loading ? null : _submit,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _primaryRed,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(7),
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
                                  : Text(
                                      AppLocalizations.t('forgot_submit'),
                                      style: GoogleFonts.poppins(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                            ),
                          ),

                          const SizedBox(height: 32),
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
