import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:first_app/utils/widgets/button_widget.dart';

void main() {
  testWidgets('Study action button responds to a learner tap', (tester) async {
    var activated = false;
    await tester.pumpWidget(MaterialApp(home: Scaffold(body:
      CustomFilledButtonWidget(label: 'Start practice', margin: 8,
        onPressed: () { activated = true; }),
    )));
    await tester.tap(find.text('Start practice'));
    expect(activated, isTrue);
  });
}
