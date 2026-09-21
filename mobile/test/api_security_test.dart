import 'dart:async';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/globals.dart';
import 'package:first_app/Services/session.dart';
import 'package:first_app/models/access_token_model.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/models/question_model.dart';

void main() {
  setUp(() {
    Session.clear();
    accessToken = AccessToken(accessToken: 'test-token');
    currentUser = CurrentUser(id: 1, email: 'learner@example.test');
  });
  tearDown(Session.clear);

  test('rejects HTTP without debug permission and foreign API targets',
      () async {
    var calls = 0;
    final client = ApiClient(
        allowHttp: false,
        inner: MockClient((_) async {
          calls++;
          return http.Response('{}', 200);
        }));
    for (final url in [baseURL, 'https://untrusted.example/api/']) {
      await expectLater(
          client.get(Uri.parse(url)), throwsA(isA<http.ClientException>()));
    }
    expect(calls, 0);
    client.close();
  });

  test('blocks credentials in URL and paths outside configured API', () async {
    final client = ApiClient(
        origin: Uri.parse('https://example.test/api/'),
        inner: MockClient((_) async => throw StateError('Must not send')));
    for (final url in [
      'https://user:password@example.test/api/',
      'https://example.test/private',
      'https://example.test/api-evil/'
    ]) {
      await expectLater(
          client.get(Uri.parse(url)), throwsA(isA<http.ClientException>()));
    }
    client.close();
  });

  test('never follows redirects or sends old token during login', () async {
    final client = ApiClient(inner: MockClient((request) async {
      expect(request.followRedirects, false);
      expect(
          request.headers.keys.where((k) => k.toLowerCase() == 'authorization'),
          isEmpty);
      return http.Response('', 302,
          headers: {'location': 'https://untrusted.example'});
    }));
    expect(
        (await client.post(Uri.parse(loginAPI), headers: headers)).statusCode,
        302);
    client.close();
  });

  test('unauthorized response clears private session and practice data',
      () async {
    attemptedQuestions.add(Question(id: 1, selectOption: 'A'));
    var redirected = false;
    final client = ApiClient(inner: MockClient((request) async {
      expect(request.headers['Authorization'], 'Bearer test-token');
      return http.Response('{}', 401);
    }));
    client.onUnauthorized = () {
      redirected = true;
    };
    await client.get(Uri.parse(questionAPI));
    expect(accessToken.accessToken, isNull);
    expect(currentUser.id, isNull);
    expect(attemptedQuestions, isEmpty);
    expect(Session.isAuthenticated, false);
    expect(redirected, true);
    client.close();
  });

  test('rejects an old account response arriving after session changes',
      () async {
    final pending = Completer<http.Response>();
    final sent = Completer<void>();
    final client = ApiClient(inner: MockClient((_) {
      sent.complete();
      return pending.future;
    }));
    final response = client.get(Uri.parse(userHistoryAPI));
    await sent.future;
    Session.clear();
    accessToken = AccessToken(accessToken: 'another-token');
    pending.complete(http.Response('{"private":"old account data"}', 200));
    await expectLater(response, throwsA(isA<http.ClientException>()));
    expect(accessToken.accessToken, 'another-token');
    client.close();
  });
}
