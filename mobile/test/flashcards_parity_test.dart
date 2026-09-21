import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Screens/Learner/resources.dart';

void main() {
  tearDown(() {
    LearnerApi.client = apiClient;
    Get.reset();
  });
  testWidgets('flashcards hide names and reset reveal on the next card',
      (tester) async {
    LearnerApi.client = MockClient((r) async {
      expect(r.url.queryParameters['flashcards'], '1');
      return http.Response(
          jsonEncode({
            'data': [
              {
                'title': 'Stop sign',
                'description': 'Stop here',
                'media_url': null
              },
              {'title': 'Give way', 'description': 'Yield', 'media_url': null},
            ],
            'next_page': null
          }),
          200);
    });
    await tester.pumpWidget(const GetMaterialApp(home: FlashcardsPage()));
    await tester.pumpAndSettle();
    expect(find.text('Stop sign'), findsNothing);
    await tester.tap(find.text('Reveal answer'));
    await tester.pump();
    expect(find.text('Stop sign'), findsOneWidget);
    await tester.tap(find.text('Next'));
    await tester.pump();
    expect(find.text('Give way'), findsNothing);
    expect(find.text('Stop sign'), findsNothing);
    await tester.tap(find.text('Reveal answer'));
    await tester.pump();
    expect(find.text('Give way'), findsOneWidget);
  });
}
