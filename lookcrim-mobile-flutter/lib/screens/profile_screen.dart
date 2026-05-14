import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';

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

  static const Color _red = Color(0xFF820000);
  static const Color _darkText = Color(0xFF09051C);
  static const Color _cardBg = Color(0xFFF3E9E9);

  @override
  void initState() {
    super.initState();
    _userFuture = _fetchUserData();
  }

  Future<Map<String, String>> _fetchUserData() async {
    final res = await widget.api.getMe(
      authorizationHeaderValue: widget.authorizationHeaderValue,
    );

    final user = res.user;

    debugPrint('USER FROM /api/v1/me: $user');
    debugPrint('PERMISSIONS FROM /api/v1/me: ${res.permissions}');

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

    if (permissions.contains('admin') || permissions.contains('Admin')) {
      return 'Admin';
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

  Future<void> _logout(BuildContext context) async {
    await widget.tokenStorage.clear();

    if (!mounted) return;

    widget.onLogout();

    Navigator.of(context).popUntil((route) => route.isFirst);
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
                        'Error cargando usuario:\n${snapshot.error}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.red, fontSize: 14),
                      ),
                    ),
                  );
                }

                if (!snapshot.hasData) {
                  return const Center(child: Text('No data available'));
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

                      const SizedBox(height: 40),

                      _buildEditButton(),

                      const SizedBox(height: 20),

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
            'Profile',
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
      padding: const EdgeInsets.fromLTRB(14, 8, 14, 48),
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
              'Personal Info',
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
                _buildInfoRow('User Name', userData['name'] ?? 'N/A'),
                _buildDividerSpace(),
                _buildInfoRow(
                  'Password',
                  userData['password'] ?? '************',
                ),
                _buildDividerSpace(),
                _buildInfoRow('Email', userData['email'] ?? 'N/A'),
                _buildDividerSpace(),
                _buildInfoRow('Role', userData['role'] ?? 'N/A'),
                _buildDividerSpace(),
                _buildInfoRow('City', userData['city'] ?? 'N/A'),
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

  Widget _buildInfoRow(String label, String value) {
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
        const SizedBox(width: 16),
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
      ],
    );
  }

  Widget _buildEditButton() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 0),
      child: SizedBox(
        height: 44,
        child: ElevatedButton.icon(
          onPressed: () {
            // Luego aquí conectamos la pantalla de Edit Profile.
          },
          icon: const Icon(Icons.edit, size: 18, color: Colors.white),
          label: Text(
            'Edit profile',
            style: GoogleFonts.poppins(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          style: ElevatedButton.styleFrom(
            backgroundColor: _red,
            foregroundColor: Colors.white,
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(5),
            ),
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
              title: const Text('Logout Account'),
              content: const Text('Are you sure you want to logout?'),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(dialogContext).pop(false);
                  },
                  child: const Text('Cancel'),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.of(dialogContext).pop(true);
                  },
                  child: const Text('Logout'),
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
        'Log out account',
        style: GoogleFonts.poppins(
          fontSize: 16,
          fontWeight: FontWeight.w500,
          color: const Color(0xFFD00022),
        ),
      ),
    );
  }
}
