import 'package:first_app/Controllers/notice_controller.dart';
import 'package:flutter/material.dart';
import 'package:first_app/utils/colors.dart';
import 'package:get/get.dart';

import 'notification_button_widget.dart';

class MainScreensAppBar extends StatelessWidget implements PreferredSizeWidget {
   MainScreensAppBar({
    Key? key,
    required this.title,
    this.height,
  }) : super(key: key);

  final String title;
  final double? height;
  final noticeController = Get.put(NoticeController());

//   @override
//   Widget build(BuildContext context) {
//     return Container(height: height ?? 180,
//     decoration: const BoxDecoration(
//       color: AppColors.primaryYellow,
//       boxShadow: [
//         BoxShadow(
//           color: AppColors.shadowBlack,
//           blurRadius: 5,
//         ),
//       ],
//     ),
//     child: Row(
//       children: [
//         Text(
//           title,
//             style: const TextStyle(
//             fontWeight: FontWeight.bold,
//             fontSize: 20,
//             color: Colors.black, 
//           ),
//         ),
//         const Spacer(),
//         NotificationBellButton(notificationCount: RxInt(1)),
//       ],
//     ),
//   );
// }
@override
Widget build(BuildContext context) {
  return Container(
    height: height ?? 120,
    decoration: const BoxDecoration(
      color: AppColors.primaryYellow,
      boxShadow: [
        BoxShadow(
          color: AppColors.shadowBlack,
          blurRadius: 5,
        ),
      ],
    ),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 8.0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              const SizedBox(width: 25, height: 50,),
              Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 20,
                  color: Colors.black, 
                ),
              ),
            ],
          ),
        ),
        NotificationBellButton(),
      ],
    ),
  );
}




  @override
  Size get preferredSize => Size.fromHeight(height ?? 80);
}

class ScreensAppBar extends StatelessWidget implements PreferredSizeWidget {
  const ScreensAppBar({
    Key? key,
    required this.title,
    this.onPressed,
    this.action,
    this.automaticallyImplyLeading = true,
    this.height,
  }) : super(key: key);

  final String title;
  final VoidCallback? onPressed;
  final Widget? action;
  final bool automaticallyImplyLeading;
  final double? height;

  @override
  Widget build(BuildContext context) {
    return AppBar(
      automaticallyImplyLeading: automaticallyImplyLeading,
      leading: automaticallyImplyLeading 
      ?  IconButton(
        onPressed: onPressed,
        icon: const Icon(Icons.arrow_back),
      )
      : null ,
      title: Text(
        title,
        style: const TextStyle(
          fontWeight: FontWeight.bold,
          fontSize: 20,
          color: Colors.black, 
        ),
      ),
      toolbarHeight: height ?? 80,
      foregroundColor: AppColors.shadowBlack,
      backgroundColor: AppColors.primaryYellow,
      shadowColor: AppColors.shadowBlack,
      actions: action != null ? [action!] : null,
    );
  }
  @override
  Size get preferredSize => Size.fromHeight(height ?? 80);
}