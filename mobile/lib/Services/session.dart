import 'package:first_app/models/access_token_model.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/models/question_model.dart';

/// Sessions remain in memory. Never restore authentication from plain storage.
class Session {
  static void Function()? onClear;
  static bool get isAuthenticated =>
      (accessToken.accessToken?.isNotEmpty ?? false) && currentUser.id != null;

  static void clear() {
    accessToken = AccessToken();
    currentUser = CurrentUser();
    attemptedQuestions.clear();
    onClear?.call();
  }
}
