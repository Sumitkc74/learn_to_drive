import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/Controllers/vision_test_controller.dart';
import 'package:first_app/models/vision_test.dart';
import 'package:first_app/utils/flip_image.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class VisionTestPage extends StatelessWidget {
  VisionTestPage({super.key});

  final c = Get.put(VisionTestController());
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ScreensAppBar(
        title: 'vision-tests'.tr, 
        onPressed: (){
          Get.back();
        }
      ),
      
      body:  Obx(
        () => (c.loading.value)
        ? const Center(child: CircularProgressIndicator())
        : Container(
          margin: const EdgeInsets.only(top: 15),
          padding: const EdgeInsets.symmetric(horizontal: 15),
          child: GridView.builder(
            itemCount: c.visionTests.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              mainAxisExtent: 150,
              crossAxisCount: 2,
              crossAxisSpacing: 20.0,
              mainAxisSpacing: 20.0,
            ),
            itemBuilder: (context, index) {
              VisionTest visionTest = c.visionTests[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                  child: Column(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(10.0),
                      ),
                      child: ListView.builder(
                        shrinkWrap: true,
                        itemCount: visionTest.media!.length,
                        itemBuilder: (context,index){
                          Media media= visionTest.media![index];
                          return FlippableImage(
                            imageUrl: media.originalUrl ?? "",
                            name: visionTest.testNumber ?? "",
                          );
                        }
                      )
                    ),
                  ],
                )
              );
            }
          ),
        ),         
      ),
    );
  }
}