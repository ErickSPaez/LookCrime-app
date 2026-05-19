import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';
import '../utils/user_friendly_error.dart';
import '../services/language_service.dart';
import '../utils/app_localizations.dart';

class ProfileScreen extends StatefulWidget {
  final LookCrimeApi api;
  final TokenStorage tokenStorage;
  final String authorizationHeaderValue;
  final VoidCallback onLogout;

  const ProfileScreen({
    super.key,
    required this.api,
    required this.tokenStorage,
    required this.authorizationHeaderValue,
    required this.onLogout,
  });

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<Map<String, String>> _userFuture;
  late final VoidCallback _localeListener;

  static const Color _red = Color(0xFF820000);
  static const Color _darkText = Color(0xFF09051C);
  static const Color _cardBg = Color(0xFFF3E9E9);

  bool _savingName = false;
  bool _sendingEmailChange = false;
  bool _savingPassword = false;

  @override
  void initState() {
    super.initState();
    _localeListener = () {
      if (mounted) setState(() {});
    };
    LanguageService.instance.localeNotifier.addListener(_localeListener);
    _userFuture = _fetchUserData();
  }

  @override
  void dispose() {
    LanguageService.instance.localeNotifier.removeListener(_localeListener);
    super.dispose();
  }

  Future<Map<String, String>> _fetchUserData() async {
    final res = await widget.api.getMe(
      authorizationHeaderValue: widget.authorizationHeaderValue,
    );

    final user = res.user;

    return {
      'name': _extractString(user, [
        'name',
        'full_name',
        'fullname',
        'username',
        'user_name',
      ]),
      'password': '************',
      'email': _extractString(user, ['email', 'mail']),
      'role': _extractRole(user, res.permissions),
      'city': _extractCity(user),
    };
  }

  Future<void> _reloadUser() async {
    setState(() {
      _userFuture = _fetchUserData();
    });
  }

  String _extractString(Map<String, dynamic> user, List<String> keys) {
    for (final key in keys) {
      final value = user[key];

      if (value is String && value.trim().isNotEmpty) {
        return value.trim();
      }

      if (value is num) {
        return value.toString();
      }
    }

    return 'N/A';
  }

  String _extractRole(Map<String, dynamic> user, List<String> permissions) {
    final directRole = _extractString(user, [
      'role',
      'Role',
      'role_name',
      'roleName',
      'user_role',
      'userRole',
      'type',
      'profile',
      'occupation',
      'profession',
      'job',
    ]);

    if (directRole != 'N/A') {
      return _capitalizeFirst(directRole);
    }

    final role = user['role'];

    if (role is Map) {
      final map = Map<String, dynamic>.from(role);
      final name = _extractString(map, ['name', 'label', 'title', 'role']);

      if (name != 'N/A') {
        return _capitalizeFirst(name);
      }
    }

    final roles = user['roles'];

    if (roles is List && roles.isNotEmpty) {
      final firstRole = roles.first;

      if (firstRole is String && firstRole.trim().isNotEmpty) {
        return _capitalizeFirst(firstRole.trim());
      }

      if (firstRole is Map) {
        final map = Map<String, dynamic>.from(firstRole);
        final name = _extractString(map, ['name', 'label', 'title', 'role']);

        if (name != 'N/A') {
          return _capitalizeFirst(name);
        }
      }
    }

    return 'N/A';
  }

  String _extractCity(Map<String, dynamic> user) {
    final cityName = user['city_name'];

    if (cityName is String && cityName.trim().isNotEmpty) {
      return cityName.trim();
    }

    final city = user['city'];

    if (city is String && city.trim().isNotEmpty) {
      return city.trim();
    }

    if (city is Map) {
      final map = Map<String, dynamic>.from(city);
      final name = map['name'];

      if (name is String && name.trim().isNotEmpty) {
        return name.trim();
      }
    }

    return 'N/A';
  }

  String _capitalizeFirst(String value) {
    final cleaned = value.trim();

    if (cleaned.isEmpty) return cleaned;

    return cleaned[0].toUpperCase() + cleaned.substring(1);
  }

  void _showMessage(String message, {bool isError = false}) {
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red.shade700 : _red,
      ),
    );
  }

  Future<void> _logout(BuildContext context) async {
    await widget.tokenStorage.clear();

    if (!mounted) return;

    widget.onLogout();

    Navigator.of(context).popUntil((route) => route.isFirst);
  }

  Future<void> _openEditNameSheet(String currentName) async {
    final controller = TextEditingController(
      text: currentName == 'N/A' ? '' : currentName,
    );

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Future<void> saveName() async {
              final newName = controller.text.trim();

              if (newName.isEmpty) {
                _showMessage(
                  AppLocalizations.t('name_required'),
                  isError: true,
                );
                return;
              }

              setSheetState(() {
                _savingName = true;
              });

              try {
                await widget.api.updateMeName(
                  authorizationHeaderValue: widget.authorizationHeaderValue,
                  name: newName,
                );

                if (!mounted) return;

                Navigator.of(sheetContext).pop();
                await _reloadUser();
                _showMessage(AppLocalizations.t('name_updated'));
              } catch (e) {
                _showMessage(
                  userFriendlyErrorMessage(
                    e,
                    fallback: AppLocalizations.t('name_save_fail'),
                    operation: 'profile',
                  ),
                  isError: true,
                );
              } finally {
                if (mounted) {
                  setState(() {
                    _savingName = false;
                  });
                }
              }
            }

            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 8,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSheetTitle(AppLocalizations.t('edit_name')),
                  const SizedBox(height: 16),
                  _buildDisabledField(
                    label: AppLocalizations.t('current_name'),
                    value: currentName,
                  ),
                  const SizedBox(height: 14),
                  _buildTextField(
                    controller: controller,
                    label: AppLocalizations.t('new_name'),
                    textInputAction: TextInputAction.done,
                    onSubmitted: (_) => saveName(),
                  ),
                  const SizedBox(height: 20),
                  _buildSheetButton(
                    label: AppLocalizations.t('save_edit'),
                    loading: _savingName,
                    onPressed: saveName,
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _openChangeEmailSheet(String currentEmail) async {
    final passwordController = TextEditingController();
    final emailController = TextEditingController();

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Future<void> sendVerification() async {
              final currentPassword = passwordController.text;
              final newEmail = emailController.text.trim();

              if (currentPassword.isEmpty || newEmail.isEmpty) {
                _showMessage(
                  AppLocalizations.t('fill_all_fields'),
                  isError: true,
                );
                return;
              }

              setSheetState(() {
                _sendingEmailChange = true;
              });

              try {
                await widget.api.requestEmailChange(
                  authorizationHeaderValue: widget.authorizationHeaderValue,
                  currentPassword: currentPassword,
                  newEmail: newEmail,
                );

                if (!mounted) return;

                Navigator.of(sheetContext).pop();
                _showMessage(AppLocalizations.t('verification_sent'));
              } catch (e) {
                _showMessage(
                  userFriendlyErrorMessage(
                    e,
                    fallback: AppLocalizations.t('verification_failed'),
                    operation: 'profile',
                  ),
                  isError: true,
                );
              } finally {
                if (mounted) {
                  setState(() {
                    _sendingEmailChange = false;
                  });
                }
              }
            }

            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 8,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSheetTitle(AppLocalizations.t('change_email')),
                  const SizedBox(height: 16),
                  _buildDisabledField(
                    label: AppLocalizations.t('current_email'),
                    value: currentEmail,
                  ),
                  const SizedBox(height: 14),
                  _buildTextField(
                    controller: passwordController,
                    label: AppLocalizations.t('current_password'),
                    obscureText: true,
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 14),
                  _buildTextField(
                    controller: emailController,
                    label: AppLocalizations.t('new_email'),
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.done,
                    onSubmitted: (_) => sendVerification(),
                  ),
                  const SizedBox(height: 20),
                  _buildSheetButton(
                    label: AppLocalizations.t('send_confirmation_email'),
                    loading: _sendingEmailChange,
                    onPressed: sendVerification,
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _openChangePasswordSheet() async {
    final currentPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Future<void> savePassword() async {
              final currentPassword = currentPasswordController.text;
              final newPassword = newPasswordController.text;
              final confirmPassword = confirmPasswordController.text;

              if (currentPassword.isEmpty ||
                  newPassword.isEmpty ||
                  confirmPassword.isEmpty) {
                _showMessage(
                  AppLocalizations.t('fill_all_fields'),
                  isError: true,
                );
                return;
              }

              if (newPassword != confirmPassword) {
                _showMessage(
                  AppLocalizations.t('passwords_no_match'),
                  isError: true,
                );
                return;
              }

              if (newPassword.length < 8) {
                _showMessage(
                  AppLocalizations.t('password_min_8'),
                  isError: true,
                );
                return;
              }

              setSheetState(() {
                _savingPassword = true;
              });

              try {
                await widget.api.updatePassword(
                  authorizationHeaderValue: widget.authorizationHeaderValue,
                  currentPassword: currentPassword,
                  newPassword: newPassword,
                  confirmPassword: confirmPassword,
                );

                if (!mounted) return;

                Navigator.of(sheetContext).pop();
                _showMessage(AppLocalizations.t('password_changed'));
              } catch (e) {
                _showMessage(
                  userFriendlyErrorMessage(
                    e,
                    fallback: AppLocalizations.t('password_change_failed'),
                    operation: 'profile',
                  ),
                  isError: true,
                );
              } finally {
                if (mounted) {
                  setState(() {
                    _savingPassword = false;
                  });
                }
              }
            }

            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 8,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSheetTitle(AppLocalizations.t('change_password')),
                  const SizedBox(height: 16),
                  _buildTextField(
                    controller: currentPasswordController,
                    label: AppLocalizations.t('current_password'),
                    obscureText: true,
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 14),
                  _buildTextField(
                    controller: newPasswordController,
                    label: AppLocalizations.t('new_password'),
                    obscureText: true,
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 14),
                  _buildTextField(
                    controller: confirmPasswordController,
                    label: AppLocalizations.t('confirm_password'),
                    obscureText: true,
                    textInputAction: TextInputAction.done,
                    onSubmitted: (_) => savePassword(),
                  ),
                  const SizedBox(height: 20),
                  _buildSheetButton(
                    label: AppLocalizations.t('save_password'),
                    loading: _savingPassword,
                    onPressed: savePassword,
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFFEFE),
      body: SafeArea(
        child: Stack(
          children: [
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Opacity(
                opacity: 0.35,
                child: Image.asset(
                  'assets/images/bg_mapv1.png',
                  fit: BoxFit.cover,
                  height: 205,
                  width: double.infinity,
                ),
              ),
            ),
            FutureBuilder<Map<String, String>>(
              future: _userFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Text(
                        '${AppLocalizations.t('error_loading_user')}${snapshot.error}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.red, fontSize: 14),
                      ),
                    ),
                  );
                }

                if (!snapshot.hasData) {
                  return Center(
                    child: Text(AppLocalizations.t('no_data_available')),
                  );
                }

                final userData = snapshot.data!;

                return SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(8, 34, 8, 28),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildHeader(context),
                      const SizedBox(height: 28),
                      _buildAvatar(),
                      const SizedBox(height: 28),
                      _buildInfoCard(userData),
                      const SizedBox(height: 24),
                      _buildLanguageTile(),
                      const SizedBox(height: 12),
                      _buildLogoutButton(context),
                    ],
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return SizedBox(
      height: 56,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: Padding(
              padding: const EdgeInsets.only(left: 24),
              child: InkWell(
                onTap: () {
                  Navigator.of(context).pop();
                },
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: _red,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.chevron_left,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
              ),
            ),
          ),
          Text(
            AppLocalizations.t('profile'),
            style: GoogleFonts.poppins(
              fontSize: 26,
              fontWeight: FontWeight.w700,
              color: Colors.black,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAvatar() {
    return Center(
      child: Container(
        width: 106,
        height: 106,
        decoration: const BoxDecoration(
          color: Color(0xFFFFE3E3),
          shape: BoxShape.circle,
        ),
        child: Center(
          child: Container(
            width: 82,
            height: 82,
            decoration: const BoxDecoration(
              color: _red,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.person, color: Colors.white, size: 54),
          ),
        ),
      ),
    );
  }

  Widget _buildInfoCard(Map<String, String> userData) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 0),
      padding: const EdgeInsets.fromLTRB(14, 8, 14, 36),
      decoration: BoxDecoration(
        color: _cardBg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 0, bottom: 16),
            child: Text(
              AppLocalizations.t('personal_info'),
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Colors.black,
              ),
            ),
          ),
          Container(
            padding: const EdgeInsets.fromLTRB(16, 18, 16, 18),
            decoration: BoxDecoration(
              color: Colors.transparent,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.75),
                width: 1,
              ),
            ),
            child: Column(
              children: [
                _buildInfoRow(
                  label: AppLocalizations.t('user_name'),
                  value: userData['name'] ?? AppLocalizations.t('na'),
                  actionIcon: Icons.edit,
                  onActionTap: () {
                    _openEditNameSheet(
                      userData['name'] ?? AppLocalizations.t('na'),
                    );
                  },
                ),
                _buildDividerSpace(),
                _buildInfoRow(
                  label: AppLocalizations.t('password'),
                  value: userData['password'] ?? '************',
                  actionIcon: Icons.edit,
                  onActionTap: _openChangePasswordSheet,
                ),
                _buildDividerSpace(),
                _buildInfoRow(
                  label: AppLocalizations.t('email'),
                  value: userData['email'] ?? AppLocalizations.t('na'),
                  actionIcon: Icons.edit,
                  onActionTap: () {
                    _openChangeEmailSheet(
                      userData['email'] ?? AppLocalizations.t('na'),
                    );
                  },
                ),
                _buildDividerSpace(),
                _buildInfoRow(
                  label: AppLocalizations.t('role'),
                  value: userData['role'] ?? AppLocalizations.t('na'),
                  actionIcon: Icons.lock_outline,
                ),
                _buildDividerSpace(),
                _buildInfoRow(
                  label: AppLocalizations.t('city'),
                  value: userData['city'] ?? AppLocalizations.t('na'),
                  actionIcon: Icons.lock_outline,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDividerSpace() {
    return const SizedBox(height: 24);
  }

  Widget _buildInfoRow({
    required String label,
    required String value,
    IconData? actionIcon,
    VoidCallback? onActionTap,
  }) {
    final isEditable = onActionTap != null;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Expanded(
          flex: 4,
          child: Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 15,
              fontWeight: FontWeight.w500,
              color: const Color(0xFF3E3E3E),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          flex: 5,
          child: Text(
            value,
            textAlign: TextAlign.right,
            overflow: TextOverflow.ellipsis,
            maxLines: 2,
            style: GoogleFonts.poppins(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: Colors.black,
            ),
          ),
        ),
        const SizedBox(width: 8),
        SizedBox(
          width: 28,
          height: 28,
          child: IconButton(
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            onPressed: onActionTap,
            icon: Icon(
              actionIcon,
              size: isEditable ? 18 : 17,
              color: isEditable ? _red : const Color(0xFF8A8A8A),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSheetTitle(String title) {
    return Text(
      title,
      style: GoogleFonts.poppins(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: _darkText,
      ),
    );
  }

  Widget _buildDisabledField({required String label, required String value}) {
    return TextFormField(
      enabled: false,
      initialValue: value,
      style: GoogleFonts.poppins(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        color: Colors.black,
      ),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.poppins(color: const Color(0xFF6B7280)),
        filled: true,
        fillColor: const Color(0xFFF3F4F6),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    bool obscureText = false,
    TextInputType? keyboardType,
    TextInputAction? textInputAction,
    ValueChanged<String>? onSubmitted,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      onSubmitted: onSubmitted,
      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.poppins(color: const Color(0xFF6B7280)),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: _red, width: 1.4),
        ),
      ),
    );
  }

  Widget _buildSheetButton({
    required String label,
    required bool loading,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      width: double.infinity,
      height: 46,
      child: ElevatedButton(
        onPressed: loading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: _red,
          foregroundColor: Colors.white,
          disabledBackgroundColor: _red.withValues(alpha: 0.45),
          elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
        child: loading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
      ),
    );
  }

  Widget _buildLogoutButton(BuildContext context) {
    return TextButton(
      onPressed: () async {
        final shouldLogout = await showDialog<bool>(
          context: context,
          builder: (dialogContext) {
            return AlertDialog(
              title: Text(AppLocalizations.t('logout_account')),
              content: Text(AppLocalizations.t('logout_confirm')),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(dialogContext).pop(false);
                  },
                  child: Text(AppLocalizations.t('cancel')),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.of(dialogContext).pop(true);
                  },
                  child: Text(AppLocalizations.t('logout')),
                ),
              ],
            );
          },
        );

        if (shouldLogout == true && context.mounted) {
          await _logout(context);
        }
      },
      child: Text(
        AppLocalizations.t('logout_account_short'),
        style: GoogleFonts.poppins(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: const Color(0xFFD00022),
        ),
      ),
    );
  }

  Widget _buildLanguageTile() {
    final code = LanguageService.instance.currentLocale.languageCode;
    final langLabel = code == 'pt'
        ? AppLocalizations.t('portuguese_pt')
        : AppLocalizations.t('english');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: _cardBg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                AppLocalizations.t('app_language'),
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                langLabel,
                style: GoogleFonts.poppins(fontSize: 13, color: Colors.black54),
              ),
            ],
          ),
          TextButton(
            onPressed: () async {
              final chosen = await showDialog<String>(
                context: context,
                builder: (dctx) {
                  return SimpleDialog(
                    title: Text(AppLocalizations.t('app_language')),
                    children: [
                      SimpleDialogOption(
                        onPressed: () => Navigator.of(dctx).pop('en'),
                        child: Text(AppLocalizations.t('english')),
                      ),
                      SimpleDialogOption(
                        onPressed: () => Navigator.of(dctx).pop('pt'),
                        child: Text(AppLocalizations.t('portuguese_pt')),
                      ),
                    ],
                  );
                },
              );

              if (chosen != null) {
                await LanguageService.instance.setLocale(Locale(chosen));
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      '${AppLocalizations.t('app_language')}: ${chosen.toUpperCase()}',
                    ),
                  ),
                );
                setState(() {});
              }
            },
            child: Text(AppLocalizations.t('edit')),
          ),
        ],
      ),
    );
  }
}
