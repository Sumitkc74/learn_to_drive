import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/globals.dart';
import '../../models/traffic_sign.dart';

class TrafficSignRepo {
  static Future<void> getTrafficSign(
      {required Function(List<TrafficSign>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(trafficSignAPI);
      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );
      var data = json.decode(response.body);

      if (data['status']) {
        onSuccess(trafficSignFromJson(data['data']['trafficSigns']));
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
