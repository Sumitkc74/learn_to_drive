import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Screens/Learner/common.dart';

void main() {
  tearDown(() {
    LearnerApi.client = apiClient;
    Get.reset();
  });
  for (final size in [
    const Size(320, 568),
    const Size(740, 360),
    const Size(1024, 768),
  ]) {
    testWidgets('learner content fits $size with enlarged bilingual text', (
      tester,
    ) async {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      LearnerApi.client = MockClient(
        (_) async => http.Response('{"data":{}}', 200),
      );
      var tapped = false;
      await tester.pumpWidget(
        GetMaterialApp(
          builder: (context, child) => MediaQuery(
            data: MediaQuery.of(
              context,
            ).copyWith(textScaler: const TextScaler.linear(2)),
            child: child!,
          ),
          home: LearnerDataPage(
            title: 'Student exams and licences',
            path: 'school-centre',
            builder: (_, refresh) => [
              Text('Long school and student information ' * 8),
              Text('विद्यार्थीका परीक्षा र अनुमतिपत्र ' * 8),
              learnerTile(
                'View exam information and previous licence categories',
                () => tapped = true,
                subtitle: 'student.with.a.long.email.address@example.com',
              ),
            ],
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      await tester.scrollUntilVisible(
        find.text('View exam information and previous licence categories'),
        150,
      );
      await tester.pumpAndSettle();
      await tester.tap(
        find.text('View exam information and previous licence categories'),
      );
      await tester.pumpAndSettle();
      expect(tapped, isTrue);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
}
