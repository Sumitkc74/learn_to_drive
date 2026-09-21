import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/models/tutorial_model.dart';
import 'package:first_app/Services/globals.dart';

class TutorialRepo {
  static Future<void> getTutorial(
      {required Function(List<Tutorial>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(tutorialAPI);

      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );
      var data = json.decode(response.body);

      if (data['status']) {
        onSuccess(tutorialFromJson(data['data']['tutorials']));
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
