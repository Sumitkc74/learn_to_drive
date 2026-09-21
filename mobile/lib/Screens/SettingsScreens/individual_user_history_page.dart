import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/models/user_history_model.dart';
import 'package:first_app/Controllers/user_history_controller.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class IndividualUserHistory extends StatelessWidget {
  final UserHistory userHistory;
  final int? index;

  IndividualUserHistory({
    required this.userHistory,
    this.index,
    Key? key,
  }) : super(key: key);

  final userHistoryController = Get.put(UserHistoryController());

  @override
  Widget build(BuildContext context) {
    final individualUserHistory =
        userHistoryController.decodeAllLists(userHistory);
    return Scaffold(
      appBar: ScreensAppBar(
        title: '${"mock-exam-history".tr} ${index! + 1}',
        onPressed: () {
          Get.back();
        },
      ),
      body: SingleChildScrollView(
        child: Center(
          child: Column(
            children: [
              ListView.builder(
                  physics: const BouncingScrollPhysics(),
                  shrinkWrap: true,
                  itemCount: individualUserHistory['questions']!.length,
                  itemBuilder: (context, index) {
                    return Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Column(
                        children: [
                          ListTile(
                            title: Text(
                              '${index + 1}. ${individualUserHistory['questions']![index]}',
                              style: const TextStyle(fontSize: 20),
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                    '${'A'.tr}.  ${individualUserHistory['option1']![index]}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'B'.tr}.  ${individualUserHistory['option2']![index]}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'C'.tr}.  ${individualUserHistory['option3']![index]}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'D'.tr}.  ${individualUserHistory['option4']![index]}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'correct-option'.tr} : ${individualUserHistory['correct_options']![index].tr}',
                                    style: const TextStyle(fontSize: 16)),
                                Text(
                                    '${'selected-option'.tr} : ${individualUserHistory['selected_options']![index].tr}',
                                    style: const TextStyle(fontSize: 16)),
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
