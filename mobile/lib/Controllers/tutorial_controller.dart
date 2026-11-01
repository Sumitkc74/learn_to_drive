import 'dart:developer';
import 'package:get/get.dart';
import 'package:learn_to_drive/models/tutorial_model.dart';
import 'package:learn_to_drive/Services/Repo/tutorial_repo.dart';

class TutorialController extends GetxController {
  final tutorials = <Tutorial>[];
  RxBool loading = false.obs;
  @override
  void onInit() {
    getAllTutorials();
    super.onInit();
  }

  getAllTutorials() async {
    loading.value = true;
    await TutorialRepo.getTutorial(
      onSuccess: (tutorial) {
        loading.value = false;
        tutorials.addAll(tutorial);
      },
      onError: ((message) {
        loading.value = false;
        log("error ");
      }),
    );
  }
}
