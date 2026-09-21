import 'package:flutter/material.dart';
import 'package:get/get.dart';

class HomeBoxWidget extends StatelessWidget {
  const HomeBoxWidget({
    Key? key,
    required this.label,
    this.onTap,
  }) : super(key: key);

  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Material(
          borderRadius: BorderRadius.circular(10),
          color: const Color(0xFF3C3C3C),
          child: InkWell(
            borderRadius: BorderRadius.circular(10),
            onTap: onTap,
            splashColor: Colors.grey.withOpacity(0.1),
            child: SizedBox(
              height: 120,
              width: Get.width/1.2,
              child: Center(
                child: Wrap( 
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  spacing: 10.0,
                  children: [
                    Text(
                      label,
                      style: const TextStyle(color: Colors.white, fontSize: 25),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}