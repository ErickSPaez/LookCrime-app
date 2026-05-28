import 'package:dio_cache_interceptor/dio_cache_interceptor.dart';

import 'map_cache_store_web.dart'
    if (dart.library.io) 'map_cache_store_io.dart';

Future<CacheStore> createMapCacheStore() => createMapCacheStoreImpl();
