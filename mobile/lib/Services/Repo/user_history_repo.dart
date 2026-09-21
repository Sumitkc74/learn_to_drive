import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';

import 'package:first_app/models/user_history_model.dart';
import 'package:first_app/Services/globals.dart';

class UserHistoryRepo {
  static Future<http.Response> recordHistory(
      Map<String, List<String>> questionsList) async {
    final ids = questionsList['ids'] ?? [];
    final selected = questionsList['selectOption'] ?? [];
    if (ids.length != selected.length || ids.isEmpty || ids.length > 100) {
      return http.Response('{"message":"Start a new practice set."}', 422);
    }
    final data = {
      'answers': List.generate(
          ids.length,
          (index) => {
                'question_id': int.tryParse(ids[index]),
                'selected_option':
                    selected[index].isEmpty ? null : selected[index],
              })
    };
    var body = json.encode(data);
    var url = Uri.parse(userHistoryAPI);
    http.Response response = await apiClient.post(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }
}

class GetUserHistoryRepo {
  static Future<void> getUserHistory(
      {int page = 1,
      required Function(bool) onPage,
      required Function(List<UserHistory>) onSuccess,
      required Function(String message) onError}) async {
    try {
      var url = Uri.parse(userHistoryAPI)
          .replace(queryParameters: {'page': page.toString()});

      http.Response response = await apiClient.get(
        url,
        headers: headers,
      );
      var data = json.decode(response.body);

      if (response.statusCode == 200 && data['status'] == true) {
        final pagination = data['data']['pagination'];
        onPage(pagination['current_page'] < pagination['last_page']);
        onSuccess(userHistoryFromJson(data['data']['userHistories']));
      } else {
        onError('Unable to load history. Try again.');
      }
    } catch (e) {
      onError('Sorry something went wrong. Please try again');
    }
  }
}
