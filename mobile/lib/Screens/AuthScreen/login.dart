import 'package:first_app/Screens/Learner/home_account.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/auth_controller.dart';
import 'package:first_app/Screens/AuthScreen/forgot_password.dart';
import 'package:first_app/Screens/AuthScreen/register.dart';
import 'package:first_app/utils/widgets/button_widget.dart';
import 'package:first_app/utils/widgets/text_field_widget.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  // ignore: library_private_types_in_public_api
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  String email = '';
  String password = '';
  bool passwordVisible = false;
  final loginController = Get.put(AuthController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          Container(
              padding: const EdgeInsets.only(left: 35, top: 120),
              child: Text('welcome-to-app'.tr,
                  style: const TextStyle(
                    // color: Colors.white,
                    fontSize: 34,
                  ))),
          SingleChildScrollView(
            child: Container(
                padding: EdgeInsets.only(
                    top: MediaQuery.of(context).size.height * 0.35,
                    right: 35,
                    left: 35),
                child: Column(
                  children: [
                    CustomTextFieldWidget(
                      label: 'email'.tr,
                      icon: Icons.email,
                      onChanged: (value) {
                        email = value;
                      },
                    ),
                    const SizedBox(
                      height: 25,
                    ),
                    CustomPasswordFieldWidget(
                      label: 'password'.tr,
                      onChanged: (value) {
                        password = value;
                      },
                    ),
                    CustomTextButtonWidget(
                      label: '${'forgot-password'.tr}?',
                      onPressed: () {
                        Get.to(() => const ForgotPasswordScreen());
                      },
                      mainAxisAlignment: MainAxisAlignment.end,
                      size: 14,
                    ),
                    const SizedBox(height: 30),
                    OutlinedButton.icon(
                        onPressed: () => Get.to(() => const BrowserLoginPage()),
                        icon: const Icon(Icons.account_circle_outlined),
                        label: Text('Sign in with Google'.tr)),
                    TextButton(
                        onPressed: () => Get.to(libraryHome),
                        child: Text('Explore as guest'.tr)),
                    TextButton(
                        onPressed: () => Get.to(() => premiumPage()),
                        child: Text('Free and Premium plans'.tr)),
                    CustomFilledButtonWidget(
                        label: 'login'.tr,
                        onPressed: () => loginController.login(
                            email: email, password: password),
                        margin: 10),
                    CustomTextButtonWidget(
                      label: 'create-new-account'.tr,
                      onPressed: () {
                        Get.to(() => const RegisterScreen());
                      },
                      mainAxisAlignment: MainAxisAlignment.center,
                      size: 18,
                    ),
                  ],
                )),
          ),
        ],
      ),
    );
  }
}
