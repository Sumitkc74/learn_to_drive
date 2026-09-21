import 'package:get_storage/get_storage.dart';
import 'storage_keys.dart';

class StorageHelper {
  // Keep this migration so existing installs discard legacy plain-text sessions.
  static Future<void> removeLegacySession() async {
    await GetStorage.init();
    final storage = GetStorage();
    await storage.remove(StorageKey.accessToken);
    await storage.remove(StorageKey.user);
  }
}
