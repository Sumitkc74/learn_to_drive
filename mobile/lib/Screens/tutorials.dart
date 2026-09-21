import 'package:first_app/Services/safe_links.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:first_app/Controllers/notice_controller.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/tutorial_controller.dart';
import 'package:first_app/models/tutorial_model.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';
// import 'package:youtube_player_flutter/youtube_player_flutter.dart';

class TutorialsScreen extends StatelessWidget {
  TutorialsScreen({super.key});

 final tutorialController = Get.put(TutorialController());
  final noticeController = Get.put(NoticeController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: MainScreensAppBar(title: 'tutorials'.tr),

      body: Obx(
        () => (tutorialController.loading.value)
        ? const Center(child: CircularProgressIndicator())
        : Container(
          margin: const EdgeInsets.only(top: 30,),
          padding: const EdgeInsets.symmetric(horizontal: 15),
          child: ListView.builder(
            itemCount: tutorialController.tutorials.length,
            itemBuilder: (context, index) {
              Tutorial tutorial = tutorialController.tutorials[index];
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
                        itemCount: tutorial.media!.length,
                        itemBuilder: (context,index){
                          Media media= tutorial.media![index];
                          return Center(
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 0),
                              height: Get.height / 3,
                              width: Get.width / 1.1,
                              decoration: BoxDecoration(
                                color: Colors.grey.shade600,
                                borderRadius: BorderRadius.circular(9),
                                boxShadow: [
                                  BoxShadow(
                                    offset: const Offset(4, 4),
                                    blurRadius: 9,
                                    color: const Color(0xFF494949).withOpacity(0.1),
                                  ),
                                ],
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  InkWell(
                                    onTap: () async {
                                      SafeLinks.open(tutorial.videoLink??"");
                                    },
                                    child: Container(
                                      decoration:
                                          BoxDecoration(borderRadius: BorderRadius.circular(9)),
                                      width: Get.width / 1.1,
                                      height: 175,
                                      child: ClipRRect(
                                      borderRadius: const BorderRadius.only(
                                          topLeft: Radius.circular(10),
                                          topRight: Radius.circular(10)),
                                      child: CachedNetworkImage(
                                        placeholder: (context, url) =>
                                            const Center(child: CircularProgressIndicator()),
                                        fit: BoxFit.cover,
                                        imageUrl: media.originalUrl ?? "",
                                        errorWidget: (context, url, error) => Image.asset(
                                          'assets/images/logo.png',
                                          height: 87,
                                          fit: BoxFit.cover,
                                            ),
                                            height: 87,
                                          ),
                                        ),
                                      ),
                                    ),
                                  const SizedBox(
                                    height: 5,
                                  ),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Padding(
                                        padding: const EdgeInsets.only(left: 4),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            SizedBox(
                                              height: 40,
                                              width: Get.width / 1.15,
                                              child: Text(
                                                tutorial.title??'',
                                                textAlign: TextAlign.center,
                                                style: const TextStyle(
                                                  fontSize: 20,
                                                  fontWeight: FontWeight.w500,
                                                ),
                                              ),
                                            ),
                                            SizedBox(
                                              height: 20,
                                              width: Get.width / 1.15,
                                              child: Text(
                                                tutorial.description??'',
                                                textAlign: TextAlign.center,
                                                style: const TextStyle(
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.w500,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  )
                                ],
                              ),
                            ),
                          );
                        }
                      )
                    ),
                    const SizedBox(height: 15,)
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