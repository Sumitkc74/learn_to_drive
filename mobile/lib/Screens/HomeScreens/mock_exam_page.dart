import 'package:flutter/material.dart';
import 'package:learn_to_drive/Screens/Learner/practice.dart';

/// Legacy entry point delegates to the server-graded practice flow.
class MockExam extends StatelessWidget {
  const MockExam({super.key});

  @override
  Widget build(BuildContext context) => const PracticeSetupPage(mock: true);
}
