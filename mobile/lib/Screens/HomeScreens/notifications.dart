import 'package:first_app/Services/safe_links.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/models/notice_model.dart';
import 'package:first_app/Controllers/notice_controller.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';


class NotificationsScreen extends StatelessWidget {
  NotificationsScreen({super.key});

  final noticeController = Get.put(NoticeController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ScreensAppBar(
        title: 'notifications'.tr, 
        onPressed: () => Get.back(),
      ),
      
      body: Obx(
        () => (noticeController.loading.value)
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
          child: Center(
            child: Column(
              children: [
                SizedBox(
                  height: Get.height,
                  width: Get.width, 
                  child: ListView.builder(
                    shrinkWrap: true,
                    itemCount: noticeController.notices.length,
                    itemBuilder: (context,index){ 
                      Notice notice = noticeController.notices[noticeController.notices.length - 1 - index];
                      return  Padding(
                        padding: const EdgeInsets.only(top: 10),
                        child: GestureDetector(
                          onTap: () {
                            SafeLinks.open(notice.link??'');
                          },
                          child: Card(
                            elevation: 4,
                            child: ListTile(
                              leading: const Icon(Icons.campaign,size: 26,),
                              title: Text(
                                notice.title??"",
                                style: const TextStyle(
                                  fontWeight:FontWeight.bold,fontSize: 18
                                ),
                              ),
                              subtitle: Text(
                                notice.description??"",
                                style: const TextStyle(
                                  fontSize: 18
                                ),
                              ),
                            ),
                          ),
                        ),
                      );
                    }
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}