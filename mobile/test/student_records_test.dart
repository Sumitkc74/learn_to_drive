import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Screens/Learner/student_records.dart';
import 'package:first_app/Screens/Learner/common.dart';

void main() {
  testWidgets(
    'native record form submits selected outcome with original version',
    (tester) async {
      Map<String, dynamic>? submitted;
      LearnerApi.client = MockClient((request) async {
        expect(request.method, 'POST');
        expect(
          request.url.path.endsWith('/school-centre/2/students/8/exam'),
          isTrue,
        );
        submitted = jsonDecode(request.body) as Map<String, dynamic>;
        return http.Response(jsonEncode({'message': 'Saved.'}), 200);
      });
      await tester.pumpWidget(
        GetMaterialApp(
          home: Scaffold(
            body: TextButton(
              onPressed: () => Get.to(
                () => const LearnerFormPage(
                  title: 'Edit exam record',
                  path: 'school-centre/2/students/8/exam',
                  fields: {
                    'category': 'Licence category',
                    'result': 'Result',
                    'result_date': 'Result date',
                  },
                  choices: {
                    'result': {'Scheduled': 'Scheduled', 'Passed': 'Passed'},
                  },
                  initial: {'category': 'B', 'result': 'Scheduled'},
                  extra: {'id': 5, 'version': 3},
                ),
              ),
              child: const Text('Edit'),
            ),
          ),
        ),
      );
      await tester.tap(find.text('Edit'));
      await tester.pumpAndSettle();
      await tester.tap(find.byType(DropdownButtonFormField<String>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Passed').last);
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField).last, '2026-10-01');
      await tester.tap(find.text('Continue'));
      await tester.pumpAndSettle();
      await tester.pump(const Duration(seconds: 4));
      await tester.pumpAndSettle();
      expect(submitted, {
        'id': 5,
        'version': 3,
        'category': 'B',
        'result': 'Passed',
        'result_date': '2026-10-01',
      });
      await tester.pumpWidget(const SizedBox.shrink());
      await tester.pumpAndSettle();
    },
  );
  testWidgets(
    'readiness respects withheld practice and hides student editing',
    (tester) async {
      LearnerApi.client = MockClient(
        (request) async => http.Response(
          jsonEncode({
            'data': {
              'student': {'name': 'Student One'},
              'attendance': {'Present': 2},
              'practice_count': null,
              'canEdit': false,
              'exams': [],
              'feedback': {'data': [], 'current_page': 1, 'last_page': 1},
            },
          }),
          200,
        ),
      );
      await tester.pumpWidget(GetMaterialApp(home: readinessPage(2, 8)));
      await tester.pumpAndSettle();
      expect(find.text('Completed practices: Not shared'), findsOneWidget);
      expect(find.text('Present: 2'), findsOneWidget);
      expect(find.text('Record instructor feedback'), findsNothing);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  tearDown(() {
    LearnerApi.client = apiClient;
    Get.reset();
  });
  testWidgets(
    'student sees results and previous category without edit access',
    (tester) async {
      LearnerApi.client = MockClient((request) async {
        expect(
          request.url.path.endsWith('/school-centre/2/students/8'),
          isTrue,
        );
        return http.Response(
          jsonEncode({
            'data': {
              'student': {'name': 'Student One'},
              'canEdit': false,
              'exams': {
                'current_page': 1,
                'last_page': 1,
                'data': [
                  {
                    'category': 'B',
                    'exam_type': 'Written',
                    'exam_date': '2026-10-01',
                    'result': 'Passed',
                    'result_date': '2026-10-02',
                    'centre': 'Test office',
                    'notes': null,
                  },
                ],
              },
              'licences': [
                {
                  'category': 'A',
                  'issued_on': '2020-01-01',
                  'expires_on': null,
                  'notes': null,
                },
              ],
            },
          }),
          200,
        );
      });
      await tester.pumpWidget(GetMaterialApp(home: studentRecordsPage(2, 8)));
      await tester.pumpAndSettle();
      expect(find.text('Passed'), findsOneWidget);
      expect(find.text('Licence category: A'), findsOneWidget);
      expect(find.text('Manage student records'), findsNothing);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
