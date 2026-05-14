import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../api/lookcrime_api.dart';
import '../storage/token_storage.dart';
import 'create_register_screen.dart';
import 'profile_screen.dart';

typedef RegisterItem = ({
  int id,
  String title,
  String description,
  String? address,
  String? imageUrl,
  String? createdAt,
});

class ListRegistersScreen extends StatefulWidget {
  final LookCrimeApi api;
  final TokenStorage tokenStorage;
  final String authorizationHeaderValue;
  final VoidCallback onLogout;

  const ListRegistersScreen({
    super.key,
    required this.api,
    required this.tokenStorage,
    required this.authorizationHeaderValue,
    required this.onLogout,
  });

  @override
  State<ListRegistersScreen> createState() => _ListRegistersScreenState();
}

class _ListRegistersScreenState extends State<ListRegistersScreen> {
  final _searchController = TextEditingController();

  bool _loading = false;
  String? _error;
  List<RegisterItem> _items = const [];
  String? _cityLabel;

  @override
  void initState() {
    super.initState();
    _loadUserCity();
    _loadRegisters();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadRegisters({String? query, bool showLoader = true}) async {
    if (showLoader) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    try {
      final res = await widget.api.getRegisters(
        authorizationHeaderValue: widget.authorizationHeaderValue,
        query: query ?? _searchController.text,
      );

      final items = res.items.map(_mapRegisterItem).toList(growable: false);

      if (!mounted) return;

      setState(() {
        _items = items;
      });

      _precacheImages(items);
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _error = e.toString();
      });
    } finally {
      if (mounted && showLoader) {
        setState(() {
          _loading = false;
        });
      }
    }
  }

  void _precacheImages(List<RegisterItem> items) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;

      for (final item in items.take(4)) {
        final url = item.imageUrl;

        if (url == null || url.trim().isEmpty) continue;

        precacheImage(CachedNetworkImageProvider(url), context);
      }
    });
  }

  Future<void> _loadUserCity() async {
    try {
      final res = await widget.api.getMe(
        authorizationHeaderValue: widget.authorizationHeaderValue,
      );

      final user = res.user;
      final label = _extractCityLabel(user);

      if (!mounted) return;

      setState(() {
        _cityLabel = label;
      });
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _cityLabel = null;
      });
    }
  }

  String? _extractCityLabel(Map<String, dynamic> user) {
    final cityName = user['city_name'];

    if (cityName is String && cityName.trim().isNotEmpty) {
      return cityName.trim().toUpperCase();
    }

    final city = user['city'];

    if (city is Map) {
      final map = Map<String, dynamic>.from(city);
      final name = map['name'];

      if (name is String && name.trim().isNotEmpty) {
        return name.trim().toUpperCase();
      }
    }

    return null;
  }

  RegisterItem _mapRegisterItem(Map<String, dynamic> raw) {
    final description = _sanitizeDescription(
      (raw['description'] as String?) ?? '',
    );

    final rawImageUrl = raw['image_url'];

    String? imageUrl;

    if (rawImageUrl is String && rawImageUrl.trim().isNotEmpty) {
      imageUrl = rawImageUrl.trim();
    }

    return (
      id: (raw['id'] as int?) ?? 0,
      title: (raw['title'] as String?) ?? 'Untitled',
      description: description,
      address: (raw['address'] as String?)?.trim().isEmpty == true
          ? null
          : raw['address'] as String?,
      imageUrl: (raw['image_url'] as String?)?.trim().isEmpty == true
          ? null
          : raw['image_url'] as String?,
      createdAt: raw['created_at'] as String?,
    );
  }

  String _sanitizeDescription(String value) {
    if (value.isEmpty) return '';

    var cleaned = value.replaceAll(RegExp(r'<[^>]*>'), ' ');

    cleaned = cleaned
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>');

    cleaned = cleaned.replaceAll(RegExp(r'\s+'), ' ').trim();

    return cleaned;
  }

  Future<void> _logout() async {
    await widget.tokenStorage.clear();

    if (!mounted) return;

    widget.onLogout();
  }

  Future<void> _openCreateRegister() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => CreateRegisterScreen(
          api: widget.api,
          authorizationHeaderValue: widget.authorizationHeaderValue,
        ),
      ),
    );

    if (created == true && mounted) {
      await _loadRegisters(showLoader: false);
    }
  }

  void _openProfile() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProfileScreen(
          api: widget.api,
          tokenStorage: widget.tokenStorage,
          authorizationHeaderValue: widget.authorizationHeaderValue,
          onLogout: widget.onLogout,
        ),
      ),
    );
  }

  void _showUserMenu() {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                ListTile(
                  leading: const Icon(Icons.person),
                  title: const Text('Profile'),
                  onTap: () {
                    Navigator.of(context).pop();
                    _openProfile();
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.logout),
                  title: const Text('Cerrar sesión'),
                  onTap: () {
                    Navigator.of(context).pop();
                    _logout();
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final red = const Color(0xFF770101);
    final lightRed = const Color(0xFFFFE8E8);
    final mutedText = const Color(0xFF4B5563);

    return Scaffold(
      backgroundColor: const Color(0xFFFFFEFE),
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: Align(
                alignment: Alignment.topCenter,
                child: Opacity(
                  opacity: 0.35,
                  child: Image.asset(
                    'assets/images/bg_mapv1.png',
                    fit: BoxFit.cover,
                    height: 180,
                    width: double.infinity,
                  ),
                ),
              ),
            ),

            Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: lightRed,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Text(
                          'ENG',
                          style: theme.textTheme.labelMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                            color: red,
                          ),
                        ),
                      ),

                      const Spacer(),

                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_outlined,
                            color: Color(0xFF09051C),
                            size: 16,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _cityLabel ?? '',
                            style: theme.textTheme.labelSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                              color: const Color(0xFF09051C),
                            ),
                          ),
                        ],
                      ),

                      const Spacer(),

                      GestureDetector(
                        onTap: _openProfile,
                        child: Container(
                          width: 34,
                          height: 34,
                          decoration: BoxDecoration(
                            color: red,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.person,
                            color: Colors.white,
                            size: 20,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  child: Container(
                    decoration: BoxDecoration(
                      color: red,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      textInputAction: TextInputAction.search,
                      onSubmitted: (_) {
                        _loadRegisters(showLoader: true);
                      },
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        hintText: 'Search',
                        hintStyle: const TextStyle(color: Colors.white70),
                        prefixIcon: const Icon(
                          Icons.search,
                          color: Colors.white,
                        ),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(
                          vertical: 12,
                        ),
                        suffixIcon: IconButton(
                          onPressed: _loading
                              ? null
                              : () {
                                  _loadRegisters(showLoader: true);
                                },
                          icon: const Icon(
                            Icons.arrow_forward,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),

                Expanded(
                  child: RefreshIndicator(
                    onRefresh: () => _loadRegisters(showLoader: false),
                    child: _loading
                        ? const Center(child: CircularProgressIndicator())
                        : ListView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 90),
                            itemCount: _items.length + (_error != null ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (_error != null && index == 0) {
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: Text(
                                    _error!,
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: Colors.red,
                                    ),
                                  ),
                                );
                              }

                              final item =
                                  _items[index - (_error != null ? 1 : 0)];

                              return _RegisterCard(
                                title: item.title,
                                address: item.address,
                                imageUrl: item.imageUrl,
                                mutedText: mutedText,
                              );
                            },
                          ),
                  ),
                ),
              ],
            ),

            Positioned(
              left: 16,
              right: 16,
              bottom: 16,
              child: SizedBox(
                height: 48,
                child: FilledButton.icon(
                  onPressed: _openCreateRegister,
                  style: FilledButton.styleFrom(
                    backgroundColor: red,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  icon: const Icon(Icons.add, color: Colors.white),
                  label: const Text(
                    'Create New Register',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RegisterCard extends StatelessWidget {
  final String title;
  final String? address;
  final String? imageUrl;
  final Color mutedText;

  const _RegisterCard({
    required this.title,
    required this.address,
    required this.imageUrl,
    required this.mutedText,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            child: AspectRatio(
              aspectRatio: 16 / 10,
              child: imageUrl == null
                  ? Container(
                      color: const Color(0xFFD5D5D5),
                      child: const Icon(
                        Icons.photo,
                        size: 48,
                        color: Colors.white70,
                      ),
                    )
                  : Image.network(
                      imageUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) {
                        return Container(
                          color: const Color(0xFFD5D5D5),
                          child: const Icon(
                            Icons.broken_image,
                            size: 40,
                            color: Colors.white70,
                          ),
                        );
                      },
                    ),
            ),
          ),

          Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(
                            Icons.location_on,
                            size: 14,
                            color: Color(0xFFB34242),
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              (address != null && address!.trim().isNotEmpty)
                                  ? address!.trim()
                                  : 'Location not available',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: mutedText,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.more_vert),
                  onPressed: _showOptionsDisabled,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showOptionsDisabled() {
    // Functionality disabled for now.
  }
}
