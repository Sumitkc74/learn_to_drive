import 'dart:convert';
import 'dart:developer';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import 'package:first_app/models/question_model.dart';
import 'package:first_app/models/user_history_model.dart';
import 'package:first_app/Services/Repo/user_history_repo.dart';
import 'package:first_app/Screens/navigator.dart';

class UserHistoryController extends GetxController {
  Map<String, List<String>> buildHistorySubmission() => {
        'ids': attemptedQuestions
            .map((question) => question.id.toString())
            .toList(),
        'selectOption': attemptedQuestions
            .map((question) => question.selectOption ?? '')
            .toList(),
      };

  Map<String, List<String>> decodeAllLists(UserHistory userHistory) {
    return {
      'questions': List<String>.from(
          json.decode(userHistory.attemptedQuestions ?? '[]')),
      'option1': List<String>.from(json.decode(userHistory.optionA ?? '[]')),
      'option2': List<String>.from(json.decode(userHistory.optionB ?? '[]')),
      'option3': List<String>.from(json.decode(userHistory.optionC ?? '[]')),
      'option4': List<String>.from(json.decode(userHistory.optionD ?? '[]')),
      'correct_options':
          List<String>.from(json.decode(userHistory.correctOptions ?? '[]')),
      'selected_options':
          List<String>.from(json.decode(userHistory.selectedOptions ?? '[]')),
    };
  }

  Future<void> recordUserHistory(
      {required Map<String, List<String>> attemptedQuestions}) async {
    try {
      http.Response response =
          await UserHistoryRepo.recordHistory(attemptedQuestions);
      Map responseMap = jsonDecode(response.body);
      if (response.statusCode == 200) {
        Get.off(() => const NavigationPage());
        Get.snackbar("Success", responseMap["message"],
            backgroundColor: Colors.green);
      } else {
        Get.snackbar(
            "Failed", responseMap["message"] ?? "Unable to save history.",
            backgroundColor: Colors.red);
      }
    } catch (_) {
      Get.snackbar('Not saved',
          'Unable to save history. Check your connection and retry.');
    }
  }
}

class GetUserHistoryController extends GetxController {
  final userHistories = <UserHistory>[].obs;
  final hasMore = true.obs;
  int page = 1;
  RxBool loading = false.obs;
  @override
  void onInit() {
    getUserHistories();
    super.onInit();
  }

  getUserHistories() async {
    if (loading.value || !hasMore.value) return;
    loading.value = true;
    await GetUserHistoryRepo.getUserHistory(
      page: page,
      onPage: (more) {
        hasMore.value = more;
        page++;
      },
      onSuccess: (userHistory) {
        loading.value = false;
        userHistories.addAll(userHistory);
      },
      onError: ((message) {
        loading.value = false;
        log("error ");
      }),
    );
  }
}
