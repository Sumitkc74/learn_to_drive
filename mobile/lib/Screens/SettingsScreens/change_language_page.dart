import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/setting_controller.dart';
import 'package:first_app/utils/widgets/button_widget.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class ChangeLanguageScreen extends StatelessWidget {
  ChangeLanguageScreen({super.key});
  final settingsController = Get.put(SettingsController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ScreensAppBar(
        title: 'change-language'.tr,
        onPressed: (){
          Get.back();
        }
      ),

      body: SafeArea(
        child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.only(top: 40),
              child: Text(
                'available-language'.tr,
                style: const TextStyle(
                  fontWeight: FontWeight.w400,
                  fontSize: 20,
                ),
              ),
            ),
            const SizedBox(height: 40,),
            const Divider(
              color: Colors.grey,
            ),

            ChooseLanguageWidget(
              language: 'english'.tr, 
              onTap: () {
                var locale = const Locale('en','US');
                Get.updateLocale(locale);
                settingsController.isEnglish = true;
              }, 
              iconVisible: settingsController.isEnglish
            ),
            const Divider(
              color: Colors.grey,
            ),

            ChooseLanguageWidget(
              language: 'nepali'.tr, 
              onTap: () {
                var locale = const Locale('ne','NP');
                Get.updateLocale(locale);
                settingsController.isEnglish = false;
              }, 
              iconVisible: !settingsController.isEnglish
            ),
            const Divider(
              color: Colors.grey
            ),
        ] 
      )))
    );
  }
}
