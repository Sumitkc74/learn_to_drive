import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/Controllers/user_history_controller.dart';
import 'package:first_app/Screens/HomeScreens/mock_exam_page.dart';
import 'package:first_app/Screens/navigator.dart';
import 'package:first_app/models/question_model.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';
import 'package:first_app/utils/colors.dart';

class MockExamAnswer extends StatelessWidget {
  MockExamAnswer({Key? key}) : super(key: key);

  final userHistoryController = Get.put(UserHistoryController());

  Future<void> showResultDialog(context) async {
    showDialog<void>(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return AlertDialog(
            backgroundColor: AppColors.secondaryBlack,
            title: Text(
              "result".tr,
              style: const TextStyle(color: Colors.white),
            ),
            content: const Text(
              'Do you want to save the question history?',
              style: TextStyle(color: Colors.white),
            ),
            actions: <Widget>[
              TextButton(
                child: Text(
                  'no'.tr,
                  style: const TextStyle(color: Colors.white),
                ),
                onPressed: () => Get.to(() => const NavigationPage()),
              ),
              TextButton(
                child: Text(
                  'yes'.tr,
                  style: const TextStyle(color: Colors.white),
                ),
                onPressed: () async {
                  Map<String, List<String>> questionMap =
                      userHistoryController.buildHistorySubmission();
                  userHistoryController.recordUserHistory(
                      attemptedQuestions: questionMap);
                },
              )
            ],
          );
        });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ScreensAppBar(
        title: "mock-exam-answer".tr,
        onPressed: () {
          (currentUser.role == 'User')
              ? Get.off(const NavigationPage())
              : showResultDialog(context);
        },
        action: IconButton(
          onPressed: () {
            Get.off(() => const MockExam());
          },
          icon: const Icon(Icons.replay_outlined),
        ),
      ),
      body: SingleChildScrollView(
        child: Center(
          child: Column(
            children: [
              ListView.builder(
                  physics: const BouncingScrollPhysics(),
                  shrinkWrap: true,
                  itemCount: attemptedQuestions.length,
                  itemBuilder: (context, index) {
                    Question question = attemptedQuestions[index];
                    return Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Column(
                        children: [
                          ListTile(
                            title: Text(
                              '${index + 1}. ${question.question}',
                              style: const TextStyle(fontSize: 20),
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('${'A'.tr}.  ${question.option1}',
                                    style: const TextStyle(fontSize: 16)),
                                Text('${'B'.tr}.  ${question.option2}',
                                    style: const TextStyle(fontSize: 16)),
                                Text('${'C'.tr}.  ${question.option3}',
                                    style: const TextStyle(fontSize: 16)),
                                Text('${'D'.tr}.  ${question.option4}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'correct-option'.tr} : ${question.correctOption?.tr}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'selected-option'.tr} : ${question.selectOption?.tr}',
                                    style: const TextStyle(
                                      fontSize: 16,
                                      // color: (question.correctOption == question.selectOption) ? Colors.green : Colors.red,
                                    )),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
            ],
          ),
        ),
      ),
    );
  }
}
