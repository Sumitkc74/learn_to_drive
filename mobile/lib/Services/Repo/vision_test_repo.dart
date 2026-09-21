import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/globals.dart';
import '../../models/vision_test.dart';

class VisionTestRepo {
  static Future<void> getVisionTest(
      {required Function(List<VisionTest>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(visionTestAPI);

      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );
      var data = json.decode(response.body);

      if (data['status']) {
        onSuccess(visionTestFromJson(data['data']['visionTests']));
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
