import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/Controllers/auth_controller.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';
import 'package:first_app/utils/widgets/button_widget.dart';
import 'package:first_app/utils/widgets/text_field_widget.dart';

class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {

  String _currentPassword = '';
  String _newPassword= '';
  String _confirmNewPassword = '';
  final authController = Get.put(AuthController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ScreensAppBar(
        title: 'change-password'.tr, 
        onPressed: (){
          Get.back();
        }
      ),
      
      body: Stack(
        children: [
          SingleChildScrollView(
            child:Container(
              padding: EdgeInsets.only(
                top: MediaQuery.of(context).size.height * 0.1, 
                right: 35, 
                left: 35
              ),

              child: Column(
                children: [
                  CustomPasswordFieldWidget(
                    label: 'current-password'.tr,
                    onChanged: (value) {
                      _currentPassword = value;
                    },
                  ),
                  const SizedBox(height: 25,),

                  CustomPasswordFieldWidget(
                    label: 'new-password'.tr,
                    onChanged: (value) {
                      _newPassword = value;
                    },
                  ),
                  const SizedBox(height: 25,),

                  CustomPasswordFieldWidget(
                    label: 'confirm-password'.tr,
                    onChanged: (value) {
                      _confirmNewPassword = value;
                    },
                  ),
                  const SizedBox(height: 30,),

                  CustomFilledButtonWidget(
                    label: 'change-password'.tr,
                    onPressed: () =>  authController.changePassword( 
                      currentPassword: _currentPassword, 
                      newPassword: _newPassword, 
                      confirmNewPassword: _confirmNewPassword
                    ), 
                    margin: 0,
                  ),
                ],
              )
            ),
          ),
        ], 
      ),
    );
  }
}
