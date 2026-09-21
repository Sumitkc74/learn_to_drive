import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/globals.dart';

class AuthServicesRepo {
  static Future<http.Response> register(
      String name, String email, String phoneNumber, String password) async {
    Map<String, dynamic> data = {
      "name": name,
      "email": email,
      "phoneNumber": phoneNumber,
      "password": password,
      "password_confirmation": password,
    };

    var body = json.encode(data);
    var url = Uri.parse(registerAPI);
    http.Response response = await apiClient.post(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }

  static Future<http.Response> login(String email, String password,
      {String? mfaCode}) async {
    Map data = {
      "email": email,
      "password": password,
      if (mfaCode != null) "mfa_code": mfaCode,
    };
    var body = json.encode(data);
    var url = Uri.parse(loginAPI);
    http.Response response = await apiClient.post(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }

  static Future<http.Response> changePassword(
      String currentPassword, String newPassword) async {
    Map data = {
      "current_password": currentPassword,
      "new_password": newPassword,
      "new_password_confirmation": newPassword,
    };
    var body = json.encode(data);
    var url = Uri.parse(changePasswordAPI);
    http.Response response = await apiClient.put(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }

  static Future<http.Response> updateProfile(String name, String email,
      String phoneNumber, String profileImage) async {
    Map data = {
      "name": name,
      "email": email,
      "phoneNumber": phoneNumber,
      "profileImage": profileImage,
    };
    var body = json.encode(data);
    var url = Uri.parse(updateProfileAPI);
    http.Response response = await apiClient.post(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }

  static Future<http.Response> resetPassword(String email) async {
    Map data = {
      "email": email,
    };
    var body = json.encode(data);
    var url = Uri.parse('${baseURL}auth/forgot-password');
    http.Response response = await apiClient.post(
      url,
      headers: headers,
      body: body,
    );
    return response;
  }

  static Future<http.Response> logout() => apiClient.post(
        Uri.parse(logoutAPI),
        headers: headers,
      );
}
