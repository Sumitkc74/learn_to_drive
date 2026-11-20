import 'dart:io';
import 'package:get/get.dart';
import 'package:flutter/material.dart';
import 'package:get_storage/get_storage.dart';
import 'package:image_picker/image_picker.dart';
import '../Services/globals.dart';
import '../Services/learner_api.dart';
import '../Services/safe_links.dart';
import '../Services/session.dart';

class SettingsController extends GetxController {
  bool isEnglish = true;
  final picker = ImagePicker();
  Rxn<File> image = Rxn<File>();
  var isDarkModeOn = true.obs;

  void toggleDarkMode(bool value) {
    isDarkModeOn.value = value;
    Get.changeThemeMode(isDarkModeOn.value ? ThemeMode.dark : ThemeMode.light);
  }

  // Opens the website Premium checkout (Khalti and eSewa) in the browser.
  // Signed-in users go through the authenticated handoff so the web
  // checkout already knows them; guests open the public Premium page.
  // There is no native Khalti/eSewa SDK in the app — the verified web
  // checkout is the payment path, and it returns here via verification.
  Future<void> payWithKhalti(context) async {
    if (!Session.isAuthenticated) {
      await SafeLinks.open(
          Uri.parse(baseURL).resolve('../learn/premium').toString());
      return;
    }
    try {
      final language = Get.locale?.languageCode ?? 'en';
      final result = await LearnerApi.call(
        'mobile/handoff',
        method: 'POST',
        data: {
          'destination': 'premium',
          'language': language,
          'practice_language':
              GetStorage().read('practice_language') ?? language,
        },
      );
      await SafeLinks.open(result['data']['url']);
    } catch (_) {
      Get.snackbar('Premium payments',
          'Could not open the website checkout. Please try again.');
    }
  }

  checkLanguage(String english, String nepali) {
    if (isEnglish) {
      return english;
    } else {
      return nepali;
    }
  }

  void pickImage() async {
    final pickedImage = await picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 40,
        maxHeight: 500,
        maxWidth: 500);
    if (pickedImage != null) {
      image.value = File(pickedImage.path);
    }
  }
}
