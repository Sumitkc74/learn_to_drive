import 'dart:developer';
import 'package:get/get.dart';
import 'package:first_app/models/notice_model.dart';
import 'package:first_app/Services/Repo/notice_repo.dart';

class NoticeController extends GetxController {
  RxList<Notice> notices = RxList();
  RxInt unReadCount = RxInt(0);
  RxBool loading = false.obs;
  @override
  void onInit() {
    getAllNotices();
    super.onInit();
  }

  getAllNotices() async {
    loading.value = true;
    await NoticeRepo.getNotice(
      onSuccess: (notice) {
        loading.value = false;
        notices.addAll(notice);
        unReadCount.value += notice.length;
      },
      onError: ((message) {
        loading.value = false;
        log("error ");
      }),
    );
  }
}
