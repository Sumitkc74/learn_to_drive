import 'package:flutter/material.dart';

class CustomPasswordFieldWidget extends StatefulWidget {
  final String label;
  final Function(String)? onChanged;
  const CustomPasswordFieldWidget({Key? key, required this.label, this.onChanged}) : super(key: key);

  @override
  State<CustomPasswordFieldWidget> createState() => _CustomPasswordFieldWidgetState();
}

class _CustomPasswordFieldWidgetState extends State<CustomPasswordFieldWidget> {
  bool _obscureText = true;

  @override
  Widget build(BuildContext context) {
    return TextField(
      obscureText: _obscureText,
      autocorrect: false,
      enableSuggestions: false,
      keyboardType: TextInputType.visiblePassword,
      onChanged: widget.onChanged,
      decoration: InputDecoration(
        filled: true, 
        prefixIcon: const Icon(Icons.lock),
        hintText: widget.label,
        suffixIcon: IconButton(
          icon: Icon(_obscureText ? Icons.visibility : Icons.visibility_off),
          onPressed: () {
            setState(() {
              _obscureText = !_obscureText;
            });
          },
        ),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }
}

class CustomTextFieldWidget extends StatelessWidget {
  final String label;
  final IconData? icon;
  final Function(String)? onChanged;

  const CustomTextFieldWidget({
    Key? key,
    required this.label,
    this.icon,
    this.onChanged,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return TextField(
      keyboardType: TextInputType.emailAddress,
      decoration: InputDecoration(
        filled: true,
        prefixIcon: Icon(icon),
        hintText: label,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
      onChanged: onChanged,
    );
  }
}
