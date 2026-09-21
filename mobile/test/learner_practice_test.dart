import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Screens/Learner/practice.dart';

void main() {
  tearDown(() {
    LearnerApi.client = apiClient;
    Get.reset();
  });
  testWidgets(
      'native practice hides answers, saves a draft, then submits for server grading',
      (tester) async {
    final requests = <http.Request>[];
    final attempt = <String, dynamic>{
      'id': 7,
      'answers': <String, dynamic>{},
      'score': null,
      'completed_at': null,
      'expires_at':
          DateTime.now().add(const Duration(hours: 1)).toIso8601String(),
      'questions': [
        {
          'id': 3,
          'text': 'Choose a safe action',
          'options': {'A': 'Stop', 'B': 'Speed'}
        }
      ]
    };
    LearnerApi.client = MockClient((request) async {
      requests.add(request);
      if (request.method == 'PATCH') {
        return http.Response('{"message":"Answers saved."}', 200);
      }
      if (request.method == 'POST') {
        expect(jsonDecode(request.body)['answers'], {'3': 'A'});
        attempt['completed_at'] = DateTime.now().toIso8601String();
        attempt['score'] = 1;
        attempt['questions'][0]['correct'] = 'A';
        attempt['questions'][0]['explanation'] = 'Wait until safe.';
      }
      return http.Response(jsonEncode({'data': attempt}), 200);
    });
    await tester
        .pumpWidget(const GetMaterialApp(home: PracticeAttemptPage(id: 7)));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 50));
    expect(find.text('Wait until safe.'), findsNothing);
    await tester.tap(find.text('A. Stop'));
    await tester.pump(const Duration(milliseconds: 800));
    await tester.pump();
    expect(requests.where((r) => r.method == 'PATCH').length, 1);
    await tester.ensureVisible(find.text('Submit answers'));
    await tester.tap(find.text('Submit answers'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 50));
    expect(find.text('Wait until safe.'), findsOneWidget);
    expect(find.text('1 / 1'), findsOneWidget);
    await tester.pumpWidget(const SizedBox.shrink());
  });
}
