import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:first_app/Screens/splashscreen.dart';

void main() {
  testWidgets('closing splash cancels pending navigation and animation',
      (tester) async {
    await tester.pumpWidget(const MaterialApp(home: SplashScreen()));
    await tester.pump(const Duration(milliseconds: 100));
    await tester.pumpWidget(const MaterialApp(home: Text('Next screen')));
    await tester.pump(const Duration(seconds: 5));
    expect(find.text('Next screen'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
