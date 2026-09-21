import 'dart:typed_data';
import 'package:first_app/Services/safe_links.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';

class PdfReaderScreen extends StatefulWidget {
  final String title;
  final String url;

  const PdfReaderScreen({
    Key? key,
    required this.title,
    required this.url,
  }) : super(key: key);

  @override
  State<PdfReaderScreen> createState() => _PdfReaderScreenState();
}

class _PdfReaderScreenState extends State<PdfReaderScreen> {
  late final Future<Uint8List> document = SafeLinks.pdf(widget.url);

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        appBar: ScreensAppBar(
            title: widget.title,
            onPressed: () {
              Get.back();
            },
            action: IconButton(
              icon: const Icon(
                Icons.download,
                color: Colors.black,
              ),
              onPressed: () {
                SafeLinks.open(widget.url);
              },
            )),
        body: FutureBuilder<Uint8List>(
            future: document,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Center(
                    child: Text('This document could not be loaded securely.'));
              }
              if (!snapshot.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              return SfPdfViewer.memory(snapshot.data!);
            }),
      ),
    );
  }
}
