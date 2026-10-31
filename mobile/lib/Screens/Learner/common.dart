import 'package:get_storage/get_storage.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/safe_links.dart';

Widget learnerImage(dynamic url) {
  if (url is! String || SafeLinks.parse(url, media: true) == null) {
    return const SizedBox.shrink();
  }
  return Image.network(
    url,
    height: 220,
    fit: BoxFit.contain,
    errorBuilder: (_, __, ___) => Text('Image unavailable'.tr),
  );
}

Future<void> learnerAction(Future<void> Function() action) async {
  try {
    await action();
  } catch (error) {
    Get.snackbar(
      'Unable to continue'.tr,
      error is LearnerApiError ? error.message.tr : 'Please try again.'.tr,
    );
  }
}

class LearnerDataPage extends StatefulWidget {
  const LearnerDataPage({
    super.key,
    required this.title,
    required this.path,
    required this.builder,
    this.actions,
  });
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
    appBar: AppBar(
      title: Text(widget.title.tr),
      actions: [
        IconButton(
          onPressed: refresh,
          icon: const Icon(Icons.refresh),
          tooltip: 'Refresh'.tr,
        ),
        ...?widget.actions,
      ],
    ),
    body: FutureBuilder<Map<String, dynamic>>(
      future: result,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(snapshot.error.toString().tr),
                    TextButton(onPressed: refresh, child: Text('Retry'.tr)),
                  ],
                ),
              ),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        return SafeArea(
          top: false,
          child: Align(
            alignment: Alignment.topCenter,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 1000),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: widget.builder(snapshot.data!, refresh),
              ),
            ),
          ),
        );
      },
    ),
  );
}

Widget learnerTile(
  String title,
  VoidCallback? onTap, {
  String? subtitle,
  IconData icon = Icons.chevron_right,
}) => Card(
  child: ListTile(
    title: Text(title.tr),
    subtitle: subtitle == null ? null : Text(subtitle),
    enabled: onTap != null,
    trailing: onTap == null ? null : Icon(icon),
    onTap: onTap,
  ),
);

class LearnerFormPage extends StatefulWidget {
  const LearnerFormPage({
    super.key,
    required this.title,
    required this.path,
    required this.fields,
    this.method = 'POST',
    this.initial = const {},
    this.extra = const {},
    this.choices = const {},
  });
  final String title, path, method;
  final Map<String, String> fields;
  final Map<String, dynamic> initial, extra;
  final Map<String, Map<String, String>> choices;
  @override
  State<LearnerFormPage> createState() => _LearnerFormPageState();
}

class _LearnerFormPageState extends State<LearnerFormPage> {
  static const dateFields = {
    'exam_date',
    'result_date',
    'issued_on',
    'expires_on',
    'from',
    'to',
  };
  final Map<String, TextEditingController> controllers = {};
  bool busy = false;
  @override
  void initState() {
    super.initState();
    for (final key in widget.fields.keys) {
      controllers[key] = TextEditingController(
        text:
            widget.initial[key]?.toString() ??
            (widget.choices[key]?.keys.firstOrNull ?? ''),
      );
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
      final result = await LearnerApi.call(
        widget.path,
        method: widget.method,
        data: {
          ...widget.extra,
          ...controllers.map((key, c) => MapEntry(key, c.text)),
        },
      );
      if (!mounted) return;
      Get.back(result: result);
      Get.snackbar('Success'.tr, (result['message'] ?? 'Saved.').toString().tr);
    });
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.title.tr)),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        for (final field in widget.fields.entries)
          Padding(
            padding: const EdgeInsets.only(bottom: 18),
            child: widget.choices.containsKey(field.key)
                ? DropdownButtonFormField<String>(
                    initialValue:
                        widget.choices[field.key]!.containsKey(
                          controllers[field.key]!.text,
                        )
                        ? controllers[field.key]!.text
                        : null,
                    isExpanded: true,
                    decoration: InputDecoration(labelText: field.value.tr),
                    items: widget.choices[field.key]!.entries
                        .map(
                          (e) => DropdownMenuItem(
                            value: e.key,
                            child: Text(
                              e.value.tr,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: busy
                        ? null
                        : (value) => controllers[field.key]!.text = value ?? '',
                  )
                : TextField(
                    controller: controllers[field.key],
                    enabled: !busy,
                    obscureText: field.key.contains('password'),
                    autocorrect: !field.key.contains('password'),
                    enableSuggestions: !field.key.contains('password'),
                    maxLines: ['message', 'notes'].contains(field.key) ? 5 : 1,
                    decoration: InputDecoration(
                      labelText: field.value.tr,
                      suffixIcon: dateFields.contains(field.key)
                          ? IconButton(
                              tooltip: 'Choose date'.tr,
                              icon: const Icon(Icons.calendar_month),
                              onPressed: busy
                                  ? null
                                  : () async {
                                      final entered = DateTime.tryParse(
                                        controllers[field.key]!.text,
                                      );
                                      final selected = await showDatePicker(
                                        context: context,
                                        initialDate:
                                            entered != null &&
                                                !entered.isBefore(
                                                  DateTime(1900),
                                                ) &&
                                                !entered.isAfter(DateTime(2200))
                                            ? entered
                                            : null,
                                        firstDate: DateTime(1900),
                                        lastDate: DateTime(2200),
                                      );
                                      if (selected != null && mounted) {
                                        controllers[field.key]!.text = selected
                                            .toIso8601String()
                                            .substring(0, 10);
                                      }
                                    },
                            )
                          : null,
                      hintText:
                          [
                            'exam_date',
                            'result_date',
                            'issued_on',
                            'expires_on',
                            'from',
                            'to',
                          ].contains(field.key)
                          ? 'YYYY-MM-DD'
                          : null,
                      border: const OutlineInputBorder(),
                    ),
                  ),
          ),
        ElevatedButton(
          onPressed: busy ? null : submit,
          child: Text((busy ? 'Saving...' : 'Continue').tr),
        ),
      ],
    ),
  );
}

Future<void> website(String destination, {int? attemptId}) => learnerAction(
  () async {
    final language = Get.locale?.languageCode ?? 'en';
    final result = await LearnerApi.call(
      'mobile/handoff',
      method: 'POST',
      data: {
        'destination': destination,
        'language': language,
        'practice_language': GetStorage().read('practice_language') ?? language,
        if (attemptId != null) 'attempt_id': attemptId,
      },
    );
    await SafeLinks.open(result['data']['url']);
  },
);
