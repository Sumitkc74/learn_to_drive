import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/models/notice_model.dart';
import 'package:first_app/Services/globals.dart';

class NoticeRepo {
  static Future<void> getNotice(
      {required Function(List<Notice>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(noticeAPI);

      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );
      var data = json.decode(response.body);

      if (data['status']) {
        onSuccess(noticeFromJson(data['data']['notices']));
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
