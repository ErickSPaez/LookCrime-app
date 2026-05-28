import 'package:dio_cache_interceptor/dio_cache_interceptor.dart';

Future<CacheStore> createMapCacheStoreImpl() async => MemCacheStore();
