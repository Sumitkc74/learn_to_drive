import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:learn_to_drive/Services/learner_api.dart';
import 'package:learn_to_drive/Services/api_client.dart';
import 'package:learn_to_drive/Screens/Learner/schools.dart';

void main() {
  tearDown(() {
    LearnerApi.client = apiClient;
    Get.reset();
  });
  test('school times consistently display Nepal time', () {
    expect(schoolTime('2026-10-07 10:00:00'), '2026-10-07 15:45');
    expect(schoolTime('2026-10-07 23:00:00'), '2026-10-08 04:45');
  });
  testWidgets('learner accepts invitation then refreshes membership', (
    tester,
  ) async {
    var accepted = false;
    LearnerApi.client = MockClient((request) async {
      if (request.method == 'POST') {
        expect(
          request.url.path.endsWith('/school-centre/invitations/7'),
          isTrue,
        );
        expect(jsonDecode(request.body), {'decision': 'accept'});
        accepted = true;
        return http.Response('{"message":"Saved"}', 200);
      }
      return http.Response(
        jsonEncode({
          'data': {
            'schools': accepted
                ? [
                    {
                      'driving_school_id': 1,
                      'role': 'Student',
                      'school': {'name': 'Road Academy', 'status': 'Approved'},
                    },
                  ]
                : [],
            'invitations': accepted
                ? []
                : [
                    {'id': 7, 'name': 'Road Academy', 'role': 'Student'},
                  ],
            'notifications': {'data': [], 'next_page_url': null},
          },
        }),
        200,
      );
    });
    await tester.pumpWidget(GetMaterialApp(home: schoolCentrePage()));
    await tester.pumpAndSettle();
    expect(find.text('Accept'), findsOneWidget);
    await tester.tap(find.text('Accept'));
    await tester.pumpAndSettle();
    expect(accepted, isTrue);
    expect(find.text('Accept'), findsNothing);
    expect(find.text('Road Academy'), findsOneWidget);
    await tester.pumpWidget(const SizedBox.shrink());
  });
}
