import 'package:learn_to_drive/Services/session.dart';
import 'package:learn_to_drive/Screens/navigator.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:learn_to_drive/Screens/AuthScreen/login.dart';
import 'package:learn_to_drive/utils/colors.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  late final Timer _navigationTimer;

  @override
  void initState() {
    super.initState();
    _navigationTimer = Timer(const Duration(seconds: 3), () {
      if (mounted) {
        Get.off(
          () => Session.isAuthenticated
              ? const NavigationPage()
              : const LoginScreen(),
        );
      }
    });
  }

  @override
  void dispose() {
    _navigationTimer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.secondaryBlack,
      body: Center(
        child: TweenAnimationBuilder<double>(
          tween: Tween(begin: 0, end: 250),
          duration: const Duration(seconds: 1),
          curve: Curves.easeOut,
          builder: (context, size, child) =>
              SizedBox(width: size, height: size, child: child),
          child: Image.asset('assets/images/logo.png'),
        ),
      ),
    );
  }
}
