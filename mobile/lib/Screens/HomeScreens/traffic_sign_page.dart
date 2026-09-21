import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/models/traffic_sign.dart';
import 'package:first_app/Controllers/traffic_sign_controller.dart';
import 'package:first_app/Controllers/setting_controller.dart';
import 'package:first_app/utils/flip_image.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class TrafficSignsPage extends StatelessWidget {
  TrafficSignsPage({super.key});

  final trafficSignController = Get.put(TrafficSignController());

  final settingsController = Get.put(SettingsController());

  @override
  Widget build(BuildContext context) {
    return  Scaffold(
      appBar: ScreensAppBar(
        title: 'traffic-signs'.tr,
        onPressed: () => Get.back(),
      ),
      
      body:  Obx(
        () => (trafficSignController.loading.value)
        ? const Center(child: CircularProgressIndicator())
        : Container(
          margin: const EdgeInsets.only(top: 30,),
          padding: const EdgeInsets.symmetric(horizontal: 15),
          child: GridView.builder(
            itemCount: trafficSignController.trafficSigns.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              mainAxisExtent: 150,
              crossAxisCount: 2,
              crossAxisSpacing: 20.0,
              mainAxisSpacing: 20.0,
            ),
            itemBuilder: (context, index) {
              TrafficSign trafficSign = trafficSignController.trafficSigns[index];
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
                        itemCount: trafficSign.media!.length,
                        itemBuilder: (context,index){
                          Media media= trafficSign.media![index];
                          return FlippableImage(
                            imageUrl: media.originalUrl ?? "",
                            name: settingsController.checkLanguage(trafficSign.name??"", trafficSign.nepaliSignName??""),
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