import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/utils/widgets/home_widget.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';
import 'package:first_app/Screens/HomeScreens/exam_information_page.dart';
import 'package:first_app/Screens/HomeScreens/reading_materials_page.dart';
import 'package:first_app/Screens/HomeScreens/traffic_sign_page.dart';
import 'package:first_app/Screens/HomeScreens/vision_test_page.dart';
import 'package:first_app/Screens/HomeScreens/mock_exam_page.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: MainScreensAppBar(
        title: '${'welcome'.tr}, ${currentUser.name}',
      ),
      
      // backgroundColor: AppColors.secondaryBlack,
      body: Stack(
        children: [
          SingleChildScrollView(
            child:Container( 
              padding: const EdgeInsets.all(30),
              child: Center(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 20,),
                      
                    HomeBoxWidget(
                      label: "traffic-signs".tr, 
                      onTap: (() => Get.to(() => TrafficSignsPage()))
                    ),
                    const SizedBox(height: 30,),

                    HomeBoxWidget(
                      label: "reading-materials".tr, 
                      onTap: (() => Get.to(() => const ReadingMaterialsPage()))
                    ),
                    const SizedBox(height: 30,),

                    HomeBoxWidget(
                      label: "mock-exam".tr, 
                      onTap: (() => Get.to(() => const MockExam()))
                    ),
                    const SizedBox(height: 30,),

                    HomeBoxWidget(
                      label: "exam-trial-information".tr, 
                      onTap: (() => Get.to(() => const ExamInformationPage()))
                    ),
                    const SizedBox(height: 30,),

                    HomeBoxWidget(
                      label: "vision-tests".tr, 
                      onTap: (() => Get.to(() => VisionTestPage()))
                    ),
                    const SizedBox(height: 30,),
                  ],
                ),
              ),
            ),
          ),
        ]
      ),
    );
  }
}