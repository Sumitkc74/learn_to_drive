import 'dart:convert';
import 'package:first_app/Services/session.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import 'package:first_app/models/access_token_model.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/Services/Repo/auth_services_repo.dart';
import 'package:first_app/Screens/navigator.dart';
import 'package:first_app/Screens/AuthScreen/login.dart';

class AuthController extends GetxController {
  // Do not display HTML error pages or raw exception details from the transport.
  Future<http.Response> _request(
      Future<http.Response> Function() action) async {
    try {
      final response = await action();
      final data = jsonDecode(response.body);
      if (data is! Map) throw const FormatException();
      if (response.statusCode >= 500) throw const FormatException();
      data['message'] ??= 'Please check your details and try again.';
      return http.Response(jsonEncode(data), response.statusCode);
    } catch (_) {
      return http.Response(
          jsonEncode({
            'status': false,
            'message':
                'Unable to complete the request. Check your connection and try again.',
          }),
          503);
    }
  }

  bool _validateSession(dynamic data) {
    if (data is! Map ||
        data['user'] is! Map ||
        data['user']['id'] is! int ||
        data['token'] is! Map ||
        data['token']['access_token'] is! String ||
        (data['token']['access_token'] as String).isEmpty) {
      Get.snackbar(
          'Sign-in failed', 'The server returned an invalid sign-in response.');
      return false;
    }
    return true;
  }

  var loading = false.obs;

  Future<void> login(
      {required String email,
      required String password,
      String? mfaCode}) async {
    if (email.isNotEmpty && password.isNotEmpty) {
      http.Response response = await _request(
          () => AuthServicesRepo.login(email, password, mfaCode: mfaCode));
      Map responseMap = jsonDecode(response.body);
      if (responseMap['mfa_required'] == true) {
        final codeController = TextEditingController();
        final code = await Get.dialog<String>(AlertDialog(
            title: Text('Two-step verification'.tr),
            content: TextField(
                controller: codeController,
                decoration: InputDecoration(
                    labelText: 'Authenticator or recovery code'.tr)),
            actions: [
              TextButton(onPressed: () => Get.back(), child: Text('Cancel'.tr)),
              TextButton(
                  onPressed: () => Get.back(result: codeController.text.trim()),
                  child: Text('Continue'.tr))
            ]));
        codeController.dispose();
        if (code != null && code.isNotEmpty) {
          await login(email: email, password: password, mfaCode: code);
        }
        return;
      }
      if (response.statusCode == 200 && responseMap['status'] != false) {
        if (!_validateSession(responseMap)) return;
        Session.clear();
        currentUser = CurrentUser.fromJson(responseMap['user']);
        accessToken = AccessToken.fromJson(responseMap['token']);
        Get.offAll(() => const NavigationPage());
        Get.snackbar("Success", responseMap["message"],
            backgroundColor: Colors.green);
      } else {
        Get.snackbar("Failed", responseMap["message"]);
      }
    } else {
      Get.snackbar("Failed", "Enter all the required fields");
    }
  }

  Future<void> register(
      {required String firstName,
      required String lastName,
      required String email,
      required String phoneNumber,
      required String password,
      required String confirmPassword}) async {
    bool emailValid = RegExp(
            r"^[a-zA-Z0-9.a-zA-Z0-9.!#$%&'*+-/=?^_`{|}~]+@[a-zA-Z0-9]+\.[a-zA-Z]+")
        .hasMatch(email);
    if (emailValid) {
      if (password == confirmPassword) {
        String name = "$firstName $lastName";
        final http.Response response = await _request(() =>
            AuthServicesRepo.register(name, email, phoneNumber, password));
        Map responseMap = jsonDecode(response.body);
        if (response.statusCode == 200 && responseMap['status'] != false) {
          if (!_validateSession(responseMap['data'])) return;
          Session.clear();
          currentUser = CurrentUser.fromJson(responseMap['data']['user']);
          accessToken = AccessToken.fromJson(responseMap['data']['token']);
          Get.offAll(() => const NavigationPage());
          Get.snackbar("Registration Successful", responseMap["message"],
              backgroundColor: Colors.green);
        } else {
          Get.snackbar("Failed", responseMap["message"]);
        }
      } else {
        Get.snackbar("Failed", 'Enter same password to confirm');
      }
    } else {
      Get.snackbar("Invalid Email", 'Enter a valid email address');
    }
  }

  Future<void> logout() async {
    final response = await _request(() => AuthServicesRepo.logout());
    Session.clear();
    Get.offAll(() => const LoginScreen());
    Get.snackbar(
        'Signed out',
        response.statusCode == 200
            ? 'You have been signed out.'
            : 'Signed out on this device. Server sign-out could not be confirmed.');
  }

  Future<void> changePassword(
      {required String currentPassword,
      required String newPassword,
      required String confirmNewPassword}) async {
    if (currentPassword.isNotEmpty &&
        newPassword.isNotEmpty &&
        confirmNewPassword.isNotEmpty) {
      if (newPassword == confirmNewPassword) {
        if (newPassword != currentPassword) {
          http.Response response = await _request(() =>
              AuthServicesRepo.changePassword(currentPassword, newPassword));
          Map responseMap = jsonDecode(response.body);
          if (response.statusCode == 200 && responseMap['status'] != false) {
            Get.back();
            Get.snackbar("Success", responseMap["message"],
                backgroundColor: Colors.green);
          } else {
            Get.snackbar("Failed", responseMap["message"]);
          }
        } else {
          Get.snackbar(
              "Failed", 'Current password and new password cannot be same');
        }
      } else {
        Get.snackbar(
            "Failed", 'Please enter same password in confirm password');
      }
    } else {
      Get.snackbar("Failed", 'Enter all the required fields');
    }
  }

  Future<void> updateProfile(
      {required String name,
      required String email,
      required String phoneNumber,
      required String profileImage}) async {
    if (name.isNotEmpty &&
        email.isNotEmpty &&
        phoneNumber.isNotEmpty &&
        profileImage.isNotEmpty) {
      http.Response response = await _request(() =>
          AuthServicesRepo.updateProfile(
              name, email, phoneNumber, profileImage));
      Map responseMap = jsonDecode(response.body);
      if (response.statusCode == 200 && responseMap['status'] != false) {
        Get.back();
        Get.snackbar("Success", responseMap["message"],
            backgroundColor: Colors.green);
      } else {
        Get.snackbar("Failed", responseMap["message"]);
      }
    } else {
      Get.snackbar("Failed", 'Enter all the required fields');
    }
  }

  Future<void> forgotPassword({required String email}) async {
    if (email.isNotEmpty) {
      http.Response response =
          await _request(() => AuthServicesRepo.resetPassword(email));
      Map responseMap = jsonDecode(response.body);
      if (response.statusCode == 200 && responseMap['status'] != false) {
        Get.offAll(() => const LoginScreen());
        Get.snackbar('Check your email',
            'If your account is eligible, follow the reset instructions sent to your email.');
      } else {
        Get.snackbar('Failed', responseMap["message"]);
      }
    } else {
      Get.snackbar('Failed', 'Enter your email address');
    }
  }
}
