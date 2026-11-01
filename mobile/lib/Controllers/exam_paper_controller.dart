import 'dart:developer';
import 'package:get/get.dart';
import 'package:learn_to_drive/Services/Repo/exam_paper_repo.dart';
import 'package:learn_to_drive/models/exam_paper_model.dart';

class ExamPaperController extends GetxController {
  RxList<ExamPaper> examPapers = RxList();
  RxBool loading = false.obs;
  @override
  void onInit() {
    getAllExamPapers();
    super.onInit();
  }
  
  getAllExamPapers() async {
    loading.value = true;
    await ExamPaperRepo.getExamPaper(
      onSuccess: (examPaper) {
        loading.value = false;
        examPapers.addAll(examPaper);
      },
      onError: ((message) {
        loading.value = false;
        log("error ");
      }),
    );
  }
}
