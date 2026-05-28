import 'dart:io';

import 'package:dio_cache_interceptor/dio_cache_interceptor.dart';
import 'package:http_cache_file_store/http_cache_file_store.dart';
import 'package:path_provider/path_provider.dart';

Future<CacheStore> createMapCacheStoreImpl() async {
  final directory = await getApplicationSupportDirectory();
  final cachePath = '${directory.path}${Platform.pathSeparator}map_tiles_cache';
  return FileCacheStore(cachePath);
}
