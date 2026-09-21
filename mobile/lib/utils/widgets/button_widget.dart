import 'package:flutter/material.dart';
import 'package:get/get.dart';

class CustomTextButtonWidget extends StatelessWidget {
  const CustomTextButtonWidget({
    Key? key,
    required this.label,
    required this.onPressed,
    required this.size,
    this.mainAxisAlignment,
  }) : super(key: key);

  final double size;
  final String label;
  final MainAxisAlignment? mainAxisAlignment;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: mainAxisAlignment ?? MainAxisAlignment.start,
      children: [
        TextButton(
          onPressed: onPressed, 
          child: Text(
            label,
            style: TextStyle(
              decoration: TextDecoration.underline,
              color: const Color(0xFFFFDE17),
              fontSize: size,
            )
          )
        )
      ],
    );
  }
}


class CustomFilledButtonWidget extends StatelessWidget {
  const CustomFilledButtonWidget({
    Key? key,
    required this.label,
    required this.onPressed,
    required this.margin,
  }) : super(key: key);

  final double margin;
  final String label;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.all(margin),
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFFFDE17),
        borderRadius: BorderRadius.circular(10),
      ),
      child:(
        TextButton(
          onPressed: onPressed,
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 18,
              color: Colors.black,
            )
          )
        )
      ),
    );
  }
}

class QuestionOptionButtonWidget extends StatelessWidget {
  const QuestionOptionButtonWidget({
    Key? key,
    required this.option,
    required this.onPressed,
  }) : super(key: key);

  final String option;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return MaterialButton(
      color: Colors.blue,
      minWidth: double.infinity,
      onPressed: onPressed,
      child: Text(
        option, style: 
        const TextStyle(fontSize: 15),
      )
    );
  }
}

class ChooseLanguageWidget extends StatelessWidget {
  const ChooseLanguageWidget({
    Key? key,
    required this.language,
    required this.onTap,
    required this.iconVisible,
  }) : super(key: key);

  final String language;
  final bool iconVisible;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: SizedBox(
        height: 48,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Expanded(
              child: Align(
                alignment: Alignment.center,
                child: Text(
                  language,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
            Visibility(
              visible: iconVisible, 
              child: Icon(
                Icons.check,
                size: 28,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
          ]
        ),
      ),
    );
  }
}

class PdfLanguageButtonWidget extends StatelessWidget {
  const PdfLanguageButtonWidget({
    Key? key,
    required this.buttonLanguage,
    required this.chosenLanguage,
    required this.onPressed,
  }) : super(key: key);

  final String buttonLanguage;
  final String chosenLanguage;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return RawMaterialButton( 
      onPressed : onPressed,
      fillColor : chosenLanguage == buttonLanguage ? Colors.blue : Colors.white, 
      textStyle: TextStyle(
        color: chosenLanguage == buttonLanguage ? Colors.white : Colors.black,
        fontSize: 18,
      ),
      shape : RoundedRectangleBorder( 
        borderRadius: BorderRadius.circular(0),
        side : const BorderSide (color : Colors.blue), 
      ),
      child : Text( 
        buttonLanguage == 'English' ? 'english'.tr : 'nepali'.tr,
      ),  
    );
  }
}

