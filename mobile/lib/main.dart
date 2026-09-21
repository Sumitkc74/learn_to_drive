import 'dart:convert';
import 'package:flutter/services.dart';
import 'package:get_storage/get_storage.dart';
import 'package:first_app/Screens/splashscreen.dart';
import 'package:first_app/Services/locale_string.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/storage_helper.dart';
import 'package:first_app/Services/session.dart';
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Screens/AuthScreen/login.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await StorageHelper.removeLegacySession();
  learnerNepali = Map<String, String>.from(
      jsonDecode(await rootBundle.loadString('assets/i18n/ne.json')));
  final language = GetStorage().read('learner_language') ?? 'en';
  final dark = GetStorage().read('learner_dark');
  Session.onClear = () {
    Get.deleteAll(force: true);
  };
  apiClient.onUnauthorized = () {
    Get.offAll(() => const LoginScreen());
  };
  runApp(GetMaterialApp(
    title: 'Learn to Drive',
    debugShowCheckedModeBanner: false,
    themeMode: dark == null
        ? ThemeMode.system
        : (dark ? ThemeMode.dark : ThemeMode.light),
    theme: ThemeData(
        colorScheme: const ColorScheme.light(
            primary: Color(0xFFFACC15),
            onPrimary: Colors.black,
            secondary: Color(0xFF30343B))),
    darkTheme: ThemeData(
        colorScheme: const ColorScheme.dark(
            primary: Color(0xFFFACC15),
            onPrimary: Colors.black,
            secondary: Color(0xFFFACC15))),
    translations: LocaleString(),
    locale: Locale(language, language == 'ne' ? 'NP' : 'US'),
    home: const SplashScreen(),
  ));
}
