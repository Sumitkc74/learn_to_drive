import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/user_history_controller.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/models/user_history_model.dart';
import 'package:first_app/Screens/SettingsScreens/individual_user_history_page.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class UserHistoryScreen extends StatelessWidget {
  UserHistoryScreen({super.key});

  final userHistoryController = Get.put(GetUserHistoryController());

  @override
  Widget build(BuildContext context) {
    final currentUserHistories = userHistoryController.userHistories
        .where((history) => history.userId == currentUser.id);
    return Scaffold(
      appBar: ScreensAppBar(
          title: 'Practice archive'.tr,
          onPressed: () {
            Get.back();
          }),
      body: Obx(
        () => (userHistoryController.loading.value)
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
                            itemCount: currentUserHistories.length + 1,
                            itemBuilder: (context, index) {
                              if (index == currentUserHistories.length) {
                                return userHistoryController.hasMore.value
                                    ? TextButton(
                                        onPressed: () => userHistoryController
                                            .getUserHistories(),
                                        child: Text('Load more'.tr))
                                    : const SizedBox.shrink();
                              }
                              UserHistory userHistory =
                                  currentUserHistories.toList()[index];
                              return Padding(
                                  padding: const EdgeInsets.only(top: 10),
                                  child: GestureDetector(
                                    onTap: () {
                                      Get.to(() => IndividualUserHistory(
                                          userHistory: userHistory,
                                          index: currentUserHistories.length -
                                              index -
                                              1));
                                    },
                                    child: Card(
                                      elevation: 4,
                                      child: ListTile(
                                        leading: const Icon(
                                          Icons.question_answer,
                                          size: 30,
                                        ),
                                        title: Text(
                                          "${'Practice session'.tr} ${currentUserHistories.length - index}",
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 18),
                                        ),
                                        subtitle: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.end,
                                          children: [
                                            const SizedBox(
                                              height: 10,
                                            ),
                                            Text(
                                              userHistory.updatedAt ?? "",
                                              style:
                                                  const TextStyle(fontSize: 18),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ));
                            }),
                      ),
                    ],
                  ),
                ),
              ),
      ),
    );
  }
}
