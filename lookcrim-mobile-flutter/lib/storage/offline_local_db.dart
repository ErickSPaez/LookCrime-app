import 'dart:convert';

import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

class OfflineLocalDb {
  OfflineLocalDb._();

  static final OfflineLocalDb instance = OfflineLocalDb._();

  static const _dbName = 'lookcrime_offline.db';
  static const _dbVersion = 2;

  Database? _database;

  Future<Database> get _db async {
    final existing = _database;
    if (existing != null && existing.isOpen) return existing;

    final databasesPath = await getDatabasesPath();
    final dbPath = p.join(databasesPath, _dbName);

    _database = await openDatabase(
      dbPath,
      version: _dbVersion,
      onCreate: _onCreate,
      onUpgrade: _onUpgrade,
    );

    return _database!;
  }

  Future<void> _onCreate(Database db, int version) async {
    await db.execute('''
      CREATE TABLE cached_categories (
        lang TEXT NOT NULL,
        category_key TEXT NOT NULL,
        label TEXT NOT NULL,
        updated_at INTEGER NOT NULL,
        PRIMARY KEY (lang, category_key)
      )
    ''');

    await db.execute('''
      CREATE TABLE cached_user_context (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        user_id INTEGER,
        city_center_lat REAL,
        city_center_lng REAL,
        city_radius_m INTEGER,
        city_name TEXT,
        permissions_json TEXT NOT NULL,
        updated_at INTEGER NOT NULL
      )
    ''');

    await db.execute('''
      CREATE TABLE cached_registers (
        id INTEGER PRIMARY KEY,
        payload_json TEXT NOT NULL,
        search_text TEXT NOT NULL,
        created_at TEXT,
        cached_at INTEGER NOT NULL
      )
    ''');

    await db.execute('''
      CREATE TABLE pending_registers (
        local_id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        category TEXT NOT NULL,
        latitude TEXT NOT NULL,
        longitude TEXT NOT NULL,
        address TEXT NOT NULL,
        image_filename TEXT NOT NULL,
        image_base64 TEXT NOT NULL,
        search_text TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        last_error TEXT
      )
    ''');
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      await db.execute(
        'ALTER TABLE cached_user_context ADD COLUMN user_id INTEGER',
      );
    }
  }

  Future<void> saveCategories(
    String lang,
    List<({String key, String label})> categories,
  ) async {
    final db = await _db;
    final batch = db.batch();
    final now = DateTime.now().millisecondsSinceEpoch;

    batch.delete('cached_categories', where: 'lang = ?', whereArgs: [lang]);

    for (final category in categories) {
      batch.insert('cached_categories', {
        'lang': lang,
        'category_key': category.key.trim(),
        'label': category.label.trim(),
        'updated_at': now,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }

    await batch.commit(noResult: true);
  }

  Future<List<({String key, String label})>> loadCategories(String lang) async {
    final db = await _db;
    final rows = await db.query(
      'cached_categories',
      where: 'lang = ?',
      whereArgs: [lang],
      orderBy: 'label COLLATE NOCASE ASC',
    );

    return rows
        .map(
          (row) => (
            key: (row['category_key'] as String?) ?? '',
            label: (row['label'] as String?) ?? '',
          ),
        )
        .where((category) => category.key.trim().isNotEmpty)
        .toList(growable: false);
  }

  Future<bool> hasCategories(String lang) async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS count FROM cached_categories WHERE lang = ?',
      [lang],
    );
    final count = rows.first['count'];
    return (count as int?) != null && (count as int) > 0;
  }

  Future<void> saveUserContext({
    required int? userId,
    required double? cityCenterLat,
    required double? cityCenterLng,
    required int? cityRadiusMeters,
    required String? cityName,
    required List<String> permissions,
  }) async {
    final db = await _db;
    await db.insert('cached_user_context', {
      'id': 1,
      'user_id': userId,
      'city_center_lat': cityCenterLat,
      'city_center_lng': cityCenterLng,
      'city_radius_m': cityRadiusMeters,
      'city_name': cityName,
      'permissions_json': jsonEncode(permissions),
      'updated_at': DateTime.now().millisecondsSinceEpoch,
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<Map<String, dynamic>?> loadUserContext() async {
    final db = await _db;
    final rows = await db.query(
      'cached_user_context',
      where: 'id = 1',
      limit: 1,
    );

    if (rows.isEmpty) return null;
    return rows.first;
  }

  Future<bool> hasUserContext() async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS count FROM cached_user_context WHERE id = 1',
    );
    final count = rows.first['count'];
    return (count as int?) != null && (count as int) > 0;
  }

  Future<void> replaceRegisters(List<Map<String, dynamic>> items) async {
    final db = await _db;
    final cutoff = DateTime.now().subtract(const Duration(days: 31));
    final batch = db.batch();

    batch.delete('cached_registers');

    for (final item in items) {
      final createdAtRaw = item['created_at']?.toString();
      final createdAt = DateTime.tryParse(createdAtRaw ?? '');
      if (createdAt != null && createdAt.isBefore(cutoff)) {
        continue;
      }

      final payload = Map<String, dynamic>.from(item);
      final searchText = _buildSearchText(payload);
      final id = (payload['id'] as num?)?.toInt();

      if (id == null || id <= 0) continue;

      batch.insert('cached_registers', {
        'id': id,
        'payload_json': jsonEncode(payload),
        'search_text': searchText,
        'created_at': createdAtRaw,
        'cached_at': DateTime.now().millisecondsSinceEpoch,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }

    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> loadRegisters({String? query}) async {
    final db = await _db;
    final normalizedQuery = query?.trim().toLowerCase() ?? '';

    final rows = normalizedQuery.isEmpty
        ? await db.query(
            'cached_registers',
            orderBy: 'cached_at DESC, created_at DESC',
          )
        : await db.query(
            'cached_registers',
            where: 'LOWER(search_text) LIKE ?',
            whereArgs: ['%$normalizedQuery%'],
            orderBy: 'cached_at DESC, created_at DESC',
          );

    return rows
        .map((row) {
          final payloadRaw = row['payload_json'] as String?;
          if (payloadRaw == null || payloadRaw.trim().isEmpty) {
            return <String, dynamic>{};
          }

          final decoded = jsonDecode(payloadRaw);
          if (decoded is Map) {
            final payload = Map<String, dynamic>.from(decoded);
            payload['is_pending'] = false;
            return payload;
          }

          return <String, dynamic>{};
        })
        .where((item) => item.isNotEmpty)
        .toList(growable: false);
  }

  Future<int> addPendingRegister({
    required String title,
    required String description,
    required String category,
    required String latitude,
    required String longitude,
    required String address,
    required String imageFilename,
    required String imageBase64,
  }) async {
    final db = await _db;
    final id = await db.insert('pending_registers', {
      'title': title,
      'description': description,
      'category': category,
      'latitude': latitude,
      'longitude': longitude,
      'address': address,
      'image_filename': imageFilename,
      'image_base64': imageBase64,
      'search_text': _buildSearchText({
        'title': title,
        'description': description,
        'category': category,
        'latitude': latitude,
        'longitude': longitude,
        'address': address,
      }),
      'created_at': DateTime.now().millisecondsSinceEpoch,
    }, conflictAlgorithm: ConflictAlgorithm.replace);

    // enforce a maximum number of pending items to avoid unbounded growth
    await _enforcePendingLimit();

    return id;
  }

  Future<void> _enforcePendingLimit({int maxPending = 200}) async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS count FROM pending_registers',
    );
    final count = (rows.first['count'] as int?) ?? 0;
    if (count <= maxPending) return;

    final toRemove = count - maxPending;
    final oldRows = await db.query(
      'pending_registers',
      orderBy: 'created_at ASC',
      limit: toRemove,
    );

    final ids = oldRows
        .map((r) => r['local_id'] as int?)
        .where((v) => v != null)
        .map((v) => v as int)
        .toList(growable: false);

    if (ids.isNotEmpty) {
      final whereClause =
          'local_id IN (${List.filled(ids.length, '?').join(',')})';
      await db.delete('pending_registers', where: whereClause, whereArgs: ids);
    }
  }

  Future<List<Map<String, dynamic>>> loadPendingRegisters() async {
    final db = await _db;
    final rows = await db.query(
      'pending_registers',
      orderBy: 'created_at DESC',
    );

    return rows
        .map((row) {
          return {
            'local_id': row['local_id'],
            'title': row['title'],
            'description': row['description'],
            'category': row['category'],
            'latitude': row['latitude'],
            'longitude': row['longitude'],
            'address': row['address'],
            'image_filename': row['image_filename'],
            'image_base64': row['image_base64'],
            'created_at': row['created_at'],
            'is_pending': true,
          };
        })
        .toList(growable: false);
  }

  Future<void> updatePendingRegisterAddress({
    required int localId,
    required String address,
    required String searchText,
  }) async {
    final db = await _db;
    await db.update(
      'pending_registers',
      {'address': address, 'search_text': searchText},
      where: 'local_id = ?',
      whereArgs: [localId],
    );
  }

  Future<void> removePendingRegister(int localId) async {
    final db = await _db;
    await db.delete(
      'pending_registers',
      where: 'local_id = ?',
      whereArgs: [localId],
    );
  }

  Future<int> pendingCount() async {
    final db = await _db;
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS count FROM pending_registers',
    );
    return (rows.first['count'] as int?) ?? 0;
  }

  Future<void> clearAll() async {
    final db = await _db;
    await db.delete('cached_categories');
    await db.delete('cached_user_context');
    await db.delete('cached_registers');
    await db.delete('pending_registers');
  }

  String _buildSearchText(Map<String, dynamic> payload) {
    final parts = <String>[
      payload['title']?.toString() ?? '',
      payload['description']?.toString() ?? '',
      payload['category']?.toString() ?? '',
      payload['author_name']?.toString() ?? '',
      payload['address']?.toString() ?? '',
      payload['created_at']?.toString() ?? '',
    ];

    return parts
        .where((part) => part.trim().isNotEmpty)
        .join(' ')
        .toLowerCase();
  }
}
