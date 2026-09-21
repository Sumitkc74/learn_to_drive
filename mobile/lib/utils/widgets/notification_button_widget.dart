import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/notice_controller.dart';
import 'package:first_app/Screens/HomeScreens/notifications.dart';

class NotificationBellButton extends StatelessWidget {  
  final noticeController = Get.put(NoticeController());
  NotificationBellButton({super.key});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        IconButton(
          icon: const Icon(
            Icons.notifications,
            size: 30,
            color: Colors.red
          ),
          padding: const EdgeInsets.only(top: 25),
          onPressed: () {
            noticeController.unReadCount.value = 0;
            Get.to(() => NotificationsScreen());
          },
        ),
        if (noticeController.unReadCount.value > 0)
        Positioned(
          right: 6,
          top: 4,
          child: Container(
            margin: const EdgeInsets.only(top: 14),
            padding: const EdgeInsets.all(2),
            decoration: BoxDecoration(
              color: Colors.red,
              borderRadius: BorderRadius.circular(10),
            ),
            constraints: const BoxConstraints(
              minWidth: 16,
              minHeight: 16,
            ),
            child: Text(
              '${noticeController.unReadCount.value}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
              ),
              textAlign: TextAlign.center,
            ),
          )
        ),
      ],
    );
  }
}