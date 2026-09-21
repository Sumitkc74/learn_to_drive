import 'dart:convert';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'api_client.dart';
import 'globals.dart';

class LearnerApi {
  static http.Client client = apiClient;
  static Future<Map<String, dynamic>> call(String path,
      {String method = 'GET', Map<String, dynamic>? data}) async {
    final request = http.Request(method, Uri.parse('$baseURL$path'));
    request.headers.addAll(headers);
    request.headers['X-Learner-Language'] = Get.locale?.languageCode ?? 'en';
    if (data != null) request.body = jsonEncode(data);
    try {
      final response =
          await http.Response.fromStream(await client.send(request));
      final body = jsonDecode(response.body);
      if (body is! Map<String, dynamic>) throw const FormatException();
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw LearnerApiError(response.statusCode < 500
            ? (body['message']?.toString() ?? 'Please check your details.')
            : 'The service is unavailable. Please try again.');
      }
      return body;
    } on LearnerApiError {
      rethrow;
    } catch (_) {
      throw LearnerApiError('Unable to connect. Please try again.');
    }
  }
}

class LearnerApiError implements Exception {
  LearnerApiError(this.message);
  final String message;
  @override
  String toString() => message;
}
