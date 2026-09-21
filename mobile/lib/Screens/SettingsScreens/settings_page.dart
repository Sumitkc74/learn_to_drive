import 'package:flutter/material.dart';
import 'package:first_app/Screens/Learner/home_account.dart';

/// Compatibility entry point for older routes; account has one implementation.
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});
  @override
  Widget build(BuildContext context) => accountPage();
}
