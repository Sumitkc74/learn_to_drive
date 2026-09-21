import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'globals.dart';
import 'session.dart';
import 'package:first_app/models/access_token_model.dart';

/// Only the configured API receives credentials; redirects are never followed.
class ApiClient extends http.BaseClient {
  ApiClient({http.Client? inner, Uri? origin, bool? allowHttp})
      : _inner = inner ?? http.Client(),
        _origin = origin ?? Uri.parse(baseURL),
        _allowHttp = kDebugMode && (allowHttp ?? true);

  final http.Client _inner;
  final Uri _origin;
  final bool _allowHttp;
  void Function()? onUnauthorized;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    final uri = request.url;
    if (uri.origin != _origin.origin ||
        !uri.path.startsWith(
            _origin.path.endsWith('/') ? _origin.path : '${_origin.path}/') ||
        uri.userInfo.isNotEmpty ||
        uri.hasFragment ||
        (uri.scheme != 'https' && !(_allowHttp && uri.scheme == 'http'))) {
      throw http.ClientException('A secure API connection is required.');
    }
    request.followRedirects = false;
    // Public authentication requests must not carry a previous user's token.
    final publicAuth = [loginAPI, registerAPI, '${baseURL}auth/forgot-password']
        .contains(uri.toString());
    request.headers
        .removeWhere((key, _) => key.toLowerCase() == 'authorization');
    final token = accessToken.accessToken;
    if (!publicAuth && token != null && token.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    final response =
        await _inner.send(request).timeout(const Duration(seconds: 20));
    final bytes = await readLimitedResponse(response, 5 * 1024 * 1024);
    if (!publicAuth && token != accessToken.accessToken) {
      throw http.ClientException('The session has changed. Sign in again.');
    }
    if (response.statusCode == 401 &&
        !publicAuth &&
        token == accessToken.accessToken) {
      Session.clear();
      onUnauthorized?.call();
    }
    return http.StreamedResponse(
      Stream.value(bytes),
      response.statusCode,
      headers: response.headers,
      request: response.request,
      reasonPhrase: response.reasonPhrase,
      isRedirect: response.isRedirect,
      persistentConnection: response.persistentConnection,
    );
  }

  @override
  void close() => _inner.close();
}

final apiClient = ApiClient();

/// The subscription is cancelled on overflow; the total read is time bounded.
Future<List<int>> readLimitedResponse(
    http.StreamedResponse response, int maxBytes) async {
  final result = Completer<List<int>>();
  final bytes = <int>[];
  late StreamSubscription<List<int>> subscription;
  Timer? timer;
  void fail(Object error) {
    if (result.isCompleted) return;
    result.completeError(error);
    subscription.cancel();
    timer?.cancel();
  }

  subscription = response.stream.listen(
      (chunk) {
        if (bytes.length + chunk.length > maxBytes) {
          fail(http.ClientException('The response is too large.'));
        } else if (!result.isCompleted) {
          bytes.addAll(chunk);
        }
      },
      onError: (Object error) => fail(error),
      onDone: () {
        timer?.cancel();
        if (!result.isCompleted) result.complete(bytes);
      });
  timer = Timer(const Duration(seconds: 20),
      () => fail(TimeoutException('Response timed out.')));
  return result.future;
}
