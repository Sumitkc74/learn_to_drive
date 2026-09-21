import 'dart:developer';
import 'package:get/get.dart';
import 'package:first_app/Services/Repo/traffic_sign_repo.dart';
import 'package:first_app/models/traffic_sign.dart';

class TrafficSignController extends GetxController {
  RxList<TrafficSign> trafficSigns = RxList();
  RxBool loading = false.obs;
  @override
  void onInit() {
    getAllTrafficSigns();
    super.onInit();
  }

  getAllTrafficSigns() async {
    loading.value = true;
    await TrafficSignRepo.getTrafficSign(
      onSuccess: (trafficSign) {
        loading.value = false;
        trafficSigns.addAll(trafficSign);
      },
      onError: ((message) {
        loading.value = false;
        log("error ");
      }),
    );
  }
}
