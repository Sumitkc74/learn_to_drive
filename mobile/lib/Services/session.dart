import 'package:learn_to_drive/models/access_token_model.dart';
import 'package:learn_to_drive/models/current_user_model.dart';
import 'package:learn_to_drive/models/question_model.dart';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'globals.dart';

/// Persist only in platform encrypted storage, scoped to the API origin.
class Session {
  static const vault = FlutterSecureStorage();
  static Future<void> _writes = Future.value();
  static String get _key => 'learner-session:${Uri.parse(baseURL).origin}';
  static Future<void> persist() {
    if (kIsWeb) return Future.value();
    final value = jsonEncode({
      'token': accessToken.toJson(),
      'user': currentUser.toJson(),
    });
    _writes = _writes
        .then((_) => vault.write(key: _key, value: value))
        .catchError((Object _) {});
    return _writes;
  }

  static Future<void> restore() async {
    if (kIsWeb) return;
    await _writes;
    try {
      final raw = await vault.read(key: _key);
      if (raw == null) return;
      final data = jsonDecode(raw);
      final token = AccessToken.fromJson(
        Map<String, dynamic>.from(data['token']),
      );
      final expires = DateTime.tryParse(token.expiresAt ?? '');
      if (expires == null || !expires.isAfter(DateTime.now())) {
        clear();
        return;
      }
      accessToken = token;
      currentUser = CurrentUser.fromJson(
        Map<String, dynamic>.from(data['user']),
      );
    } catch (_) {
      clear();
    }
  }

  static void Function()? onClear;
  static bool get isAuthenticated =>
      (accessToken.accessToken?.isNotEmpty ?? false) && currentUser.id != null;

  static void clear() {
    accessToken = AccessToken();
    currentUser = CurrentUser();
    attemptedQuestions.clear();
    onClear?.call();
    if (!kIsWeb) {
      _writes = _writes
          .then((_) => vault.delete(key: _key))
          .catchError((Object _) {});
    }
  }
}
