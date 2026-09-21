import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/globals.dart';
import 'package:first_app/models/question_model.dart';

class QuestionRepo {
  static Future<void> getQuestion(
      {required Function(List<Question>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(questionAPI);

      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );

      var data = json.decode(response.body);

      if (data['status']) {
        onSuccess(questionFromJson(data['data']['questions']));
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
