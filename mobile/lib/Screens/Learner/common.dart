import 'package:get_storage/get_storage.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/safe_links.dart';

Widget learnerImage(dynamic url) {
  if (url is! String || SafeLinks.parse(url, media: true) == null) {
    return const SizedBox.shrink();
  }
  return Image.network(url,
      height: 220,
      fit: BoxFit.contain,
      errorBuilder: (_, __, ___) => Text('Image unavailable'.tr));
}

Future<void> learnerAction(Future<void> Function() action) async {
  try {
    await action();
  } catch (error) {
    Get.snackbar('Unable to continue'.tr,
        error is LearnerApiError ? error.message.tr : 'Please try again.'.tr);
  }
}

class LearnerDataPage extends StatefulWidget {
  const LearnerDataPage(
      {super.key,
      required this.title,
      required this.path,
      required this.builder,
      this.actions});
  final String title, path;
  final List<Widget> Function(Map<String, dynamic>, VoidCallback) builder;
  final List<Widget>? actions;
  @override
  State<LearnerDataPage> createState() => _LearnerDataPageState();
}

class _LearnerDataPageState extends State<LearnerDataPage>
    with WidgetsBindingObserver {
  late Future<Map<String, dynamic>> result;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    refresh();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && mounted) refresh();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  void refresh() {
    setState(() {
      result = LearnerApi.call(widget.path);
    });
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.title.tr), actions: [
          IconButton(
              onPressed: refresh,
              icon: const Icon(Icons.refresh),
              tooltip: 'Refresh'.tr),
          ...?widget.actions
        ]),
        body: FutureBuilder<Map<String, dynamic>>(
            future: result,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return Center(
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                  Text(snapshot.error.toString().tr),
                  TextButton(onPressed: refresh, child: Text('Retry'.tr))
                ]));
              }
              if (!snapshot.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              return ListView(
                  padding: const EdgeInsets.all(16),
                  children: widget.builder(snapshot.data!, refresh));
            }),
      );
}

Widget learnerTile(String title, VoidCallback? onTap,
        {String? subtitle, IconData icon = Icons.chevron_right}) =>
    Card(
        child: ListTile(
            title: Text(title.tr),
            subtitle: subtitle == null ? null : Text(subtitle),
            enabled: onTap != null,
            trailing: onTap == null ? null : Icon(icon),
            onTap: onTap));

class LearnerFormPage extends StatefulWidget {
  const LearnerFormPage(
      {super.key,
      required this.title,
      required this.path,
      required this.fields,
      this.method = 'POST',
      this.initial = const {},
      this.extra = const {}});
  final String title, path, method;
  final Map<String, String> fields;
  final Map<String, dynamic> initial, extra;
  @override
  State<LearnerFormPage> createState() => _LearnerFormPageState();
}

class _LearnerFormPageState extends State<LearnerFormPage> {
  final Map<String, TextEditingController> controllers = {};
  bool busy = false;
  @override
  void initState() {
    super.initState();
    for (final key in widget.fields.keys) {
      controllers[key] =
          TextEditingController(text: widget.initial[key]?.toString() ?? '');
    }
  }

  @override
  void dispose() {
    for (final controller in controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> submit() async {
    if (busy) return;
    setState(() => busy = true);
    await learnerAction(() async {
      final result = await LearnerApi.call(widget.path,
          method: widget.method,
          data: {
            ...widget.extra,
            ...controllers.map((key, c) => MapEntry(key, c.text))
          });
      if (!mounted) return;
      Get.back(result: result);
      Get.snackbar('Success'.tr, (result['message'] ?? 'Saved.').toString().tr);
    });
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(widget.title.tr)),
      body: ListView(padding: const EdgeInsets.all(20), children: [
        for (final field in widget.fields.entries)
          Padding(
              padding: const EdgeInsets.only(bottom: 18),
              child: TextField(
                  controller: controllers[field.key],
                  obscureText: field.key.contains('password'),
                  autocorrect: !field.key.contains('password'),
                  enableSuggestions: !field.key.contains('password'),
                  maxLines: field.key == 'message' ? 5 : 1,
                  decoration: InputDecoration(
                      labelText: field.value.tr,
                      border: const OutlineInputBorder()))),
        ElevatedButton(
            onPressed: busy ? null : submit,
            child: Text((busy ? 'Saving...' : 'Continue').tr)),
      ]));
}

Future<void> website(String destination, {int? attemptId}) =>
    learnerAction(() async {
      final language = Get.locale?.languageCode ?? 'en';
      final result =
          await LearnerApi.call('mobile/handoff', method: 'POST', data: {
        'destination': destination,
        'language': language,
        'practice_language': GetStorage().read('practice_language') ?? language,
        if (attemptId != null) 'attempt_id': attemptId,
      });
      await SafeLinks.open(result['data']['url']);
    });
