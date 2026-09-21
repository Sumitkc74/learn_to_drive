import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:first_app/Controllers/setting_controller.dart';
import 'package:first_app/Controllers/exam_paper_controller.dart';
import 'package:first_app/models/exam_paper_model.dart';
import 'package:first_app/utils/widgets/screens_app_bar.dart';
import 'package:first_app/utils/widgets/button_widget.dart';
import 'package:first_app/utils/pdf_reader.dart';

class ReadingMaterialsPage extends StatefulWidget {
  const ReadingMaterialsPage({super.key});

  @override
  State<ReadingMaterialsPage> createState() => _ReadingMaterialsPageState();
}

class _ReadingMaterialsPageState extends State<ReadingMaterialsPage> {
  final readingMaterialController = Get.put(ExamPaperController());
  final settingsController = Get.put(SettingsController());
  var chosenLanguage = 'English'.obs;

  @override
  Widget build(BuildContext context) {
    return  Scaffold(
      appBar: ScreensAppBar(
        title: 'reading-materials'.tr, 
        onPressed: () => Get.back(),
      ),
      
      body:  Obx(
        () => (readingMaterialController.loading.value)
        ? const Center(child: CircularProgressIndicator())
        : 
        SingleChildScrollView(
          child: Column(
            children: [
              Row( 
                children : [ 
                  Expanded ( 
                    child : PdfLanguageButtonWidget(
                      buttonLanguage: 'English',
                      chosenLanguage: chosenLanguage.value, 
                      onPressed: (){
                        chosenLanguage.value = 'English';
                      } ,
                    )
                  ),
                  Expanded ( 
                    child : PdfLanguageButtonWidget(
                      buttonLanguage: 'Nepali',
                      chosenLanguage: chosenLanguage.value, 
                      onPressed: (){
                        chosenLanguage.value = 'Nepali';
                      },
                    )
                  ),
                ]
              ),
              SizedBox(
                height: Get.height,
                child: GridView.builder(
                  itemCount: readingMaterialController.examPapers.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    mainAxisExtent: 100,
                    crossAxisCount: 1,
                    crossAxisSpacing: 10.0,
                    mainAxisSpacing: 0.0,
                  ),
                  itemBuilder: (context, index) {
                    ExamPaper examPaper = readingMaterialController.examPapers[index];
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: SizedBox(
                        height: 50,
                        width: 200,
                        child: Row(
                          children: [
                            ListView.builder(
                            scrollDirection: Axis.horizontal,
                              shrinkWrap: true,
                              itemCount: 1,
                              itemBuilder: (context,index){
                               Media media = examPaper.media![(chosenLanguage.value == 'English') ? 0 : 1];
                                return IconButton(
                                  onPressed: (){
                                    Get.to(() => PdfReaderScreen(
                                      title: settingsController.checkLanguage(
                                        examPaper.name??'', 
                                        examPaper.nepaliName??''), 
                                      url: media.originalUrl??""
                                    ));
                                  }, 
                                  icon: const Icon(Icons.picture_as_pdf), 
                                  color: Colors.red, 
                                  iconSize: 50,
                                );
                              }
                            ), 
                            Text(
                              settingsController.checkLanguage(
                                examPaper.name??'', 
                                examPaper.nepaliName??''
                              ),
                              style: const TextStyle(fontSize: 20),
                            ),
                          ],
                        ),
                      ),
                    );
                  }
                ),
              ),
            ],
          ),
        )
      )
    );
  }
}