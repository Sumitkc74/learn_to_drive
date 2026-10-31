import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:first_app/Services/session.dart';
import 'package:first_app/models/access_token_model.dart';
import 'package:first_app/models/current_user_model.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });
  test('encrypted session restores and sign-out removes it', () async {
    currentUser = CurrentUser(id: 7, name: 'Learner');
    accessToken = AccessToken(
      accessToken: 'test-only',
      expiresAt: DateTime.now().add(const Duration(hours: 1)).toIso8601String(),
    );
    await Session.persist();
    currentUser = CurrentUser();
    accessToken = AccessToken();
    await Session.restore();
    expect(Session.isAuthenticated, isTrue);
    expect(currentUser.id, 7);
    Session.clear();
    await Session.restore();
    expect(Session.isAuthenticated, isFalse);
  });
  test('expired stored sessions are not restored', () async {
    currentUser = CurrentUser(id: 7);
    accessToken = AccessToken(
      accessToken: 'test-only',
      expiresAt: DateTime.now()
          .subtract(const Duration(hours: 1))
          .toIso8601String(),
    );
    await Session.persist();
    currentUser = CurrentUser();
    accessToken = AccessToken();
    await Session.restore();
    expect(Session.isAuthenticated, isFalse);
  });
}
