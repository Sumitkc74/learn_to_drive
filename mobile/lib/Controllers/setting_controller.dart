import 'dart:io';
import 'package:get/get.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class SettingsController extends GetxController {
  bool isEnglish = true;
  final picker = ImagePicker();
  Rxn<File> image = Rxn<File>();
  var isDarkModeOn = true.obs;

  void toggleDarkMode(bool value) {
    isDarkModeOn.value = value;
    Get.changeThemeMode(isDarkModeOn.value ? ThemeMode.dark : ThemeMode.light);
  }

  payWithKhalti(context) {
    Get.snackbar('Premium payments',
        'Please use the Learn to Drive website for verified Premium payments.');
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
