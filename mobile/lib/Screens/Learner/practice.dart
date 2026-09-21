import 'dart:async';
import 'package:get_storage/get_storage.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/learner_api.dart';
import 'common.dart';
import 'resources.dart';

class PracticeSetupPage extends StatefulWidget {
  const PracticeSetupPage(
      {super.key,
      this.personalized = false,
      this.mock = false,
      this.revision = false,
      this.topic = ''});
  final bool personalized, mock, revision;
  final String topic;
  @override
  State<PracticeSetupPage> createState() => _PracticeSetupPageState();
}

class _PracticeSetupPageState extends State<PracticeSetupPage> {
  int count = 5;
  String category = '', language = Get.locale?.languageCode ?? 'en';
  bool busy = false;
  @override
  void initState() {
    super.initState();
    final saved = GetStorage().read('practice_language');
    if (saved == 'en' || saved == 'ne') language = saved;
    category = widget.topic;
    if (widget.mock) count = 25;
  }

  @override
  Widget build(BuildContext context) => LearnerDataPage(
      title: widget.mock
          ? '30-minute mock exam'
          : (widget.personalized ? 'Personalized practice' : 'Practice'),
      path: 'learner/catalog',
      builder: (result, refresh) => [
            Text('Set up your session'.tr,
                style: Theme.of(context).textTheme.headlineSmall),
            DropdownButtonFormField<int>(
                value: count,
                decoration: InputDecoration(labelText: 'Questions'.tr),
                items: (widget.mock ? [25] : [5, 10, 20])
                    .map((v) => DropdownMenuItem(value: v, child: Text('$v')))
                    .toList(),
                onChanged: (v) => setState(() => count = v!)),
            DropdownButtonFormField<String>(
                value: language,
                decoration: InputDecoration(labelText: 'Question language'.tr),
                items: const [
                  DropdownMenuItem(value: 'en', child: Text('English')),
                  DropdownMenuItem(value: 'ne', child: Text('नेपाली'))
                ],
                onChanged: (v) {
                  setState(() => language = v!);
                  GetStorage().write('practice_language', v);
                }),
            DropdownButtonFormField<String>(
                value: category,
                decoration: InputDecoration(labelText: 'Topic'.tr),
                items: [
                  DropdownMenuItem(value: '', child: Text('All topics'.tr)),
                  for (final v in result['data']['categories'])
                    DropdownMenuItem(
                        value: v.toString(), child: Text(v.toString()))
                ],
                onChanged: (v) => setState(() => category = v!)),
            ElevatedButton(
                onPressed: busy
                    ? null
                    : () async {
                        setState(() => busy = true);
                        await learnerAction(() async {
                          final r = await LearnerApi.call('learner/practice',
                              method: 'POST',
                              data: {
                                'count': count,
                                'category': category,
                                'language': language,
                                if (widget.personalized) 'mode': 'personalized',
                                if (widget.mock) 'mode': 'mock',
                                if (widget.revision) 'mode': 'revision'
                              });
                          await Get.to(
                              () => PracticeAttemptPage(id: r['data']['id']));
                        });
                        if (mounted) setState(() => busy = false);
                      },
                child: Text('Start practice'.tr)),
            if (!widget.mock)
              learnerTile('30-minute mock exam',
                  () => Get.to(() => const PracticeSetupPage(mock: true))),
            learnerTile('Traffic sign flashcards',
                () => Get.to(() => const FlashcardsPage())),
            learnerTile(
                'Vision practice',
                () => Get.to(() => const ResourceListPage(
                    type: 'vision-test', title: 'Vision practice'))),
          ]);
}

class PracticeAttemptPage extends StatefulWidget {
  const PracticeAttemptPage({super.key, required this.id});
  final int id;
  @override
  State<PracticeAttemptPage> createState() => _PracticeAttemptPageState();
}

class _PracticeAttemptPageState extends State<PracticeAttemptPage>
    with WidgetsBindingObserver {
  Map<String, dynamic>? attempt;
  Map<String, dynamic> answers = {};
  String? error;
  String status = '';
  bool busy = false, dirty = false, finalizationRequested = false;
  Duration serverOffset = Duration.zero;
  Timer? timer, autosave;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    load();
    timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        setState(() {});
        if (attempt?['mode'] == 'mock' &&
            attempt?['completed_at'] == null &&
            DateTime.parse(attempt!['expires_at'])
                .isBefore(DateTime.now().add(serverOffset)) &&
            !busy &&
            !finalizationRequested) {
          busy = true;
          finalizationRequested = true;
          load().whenComplete(() {
            if (mounted) setState(() => busy = false);
          });
        }
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    timer?.cancel();
    autosave?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && !dirty && !busy) load();
  }

  Future<void> load() async {
    try {
      final r = await LearnerApi.call('learner/practice/${widget.id}');
      if (mounted) {
        setState(() {
          attempt = r['data'];
          if (attempt!['server_time'] != null) {
            serverOffset = DateTime.parse(attempt!['server_time'])
                .difference(DateTime.now());
          }
          answers = Map<String, dynamic>.from(attempt!['answers']);
          error = null;
          if (attempt!['completed_at'] != null) dirty = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = e.toString();
          status = e.toString();
        });
      }
    }
  }

  Future<void> save({bool submit = false}) async {
    if (busy) return;
    setState(() => busy = true);
    final sent = Map<String, dynamic>.from(answers);
    try {
      final r = await LearnerApi.call('learner/practice/${widget.id}',
          method: submit ? 'POST' : 'PATCH', data: {'answers': sent});
      if (!mounted) return;
      setState(() {
        dirty = false;
        status = 'Answers saved.';
        if (submit) attempt = r['data'];
      });
    } catch (e) {
      if (mounted) setState(() => status = e.toString());
    }
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) {
    if (attempt == null) {
      return Scaffold(
          appBar: AppBar(title: Text('Practice'.tr)),
          body: Center(
              child: error == null
                  ? const CircularProgressIndicator()
                  : TextButton(onPressed: load, child: Text(error!))));
    }
    final completed = attempt!['completed_at'] != null;
    final remaining = DateTime.parse(attempt!['expires_at'])
        .difference(DateTime.now().add(serverOffset));
    return WillPopScope(
        onWillPop: () async {
          if (dirty) {
            await save();
          }
          if (!dirty) return true;
          if (!mounted) return false;
          return await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                        title: Text('Leave without saving?'.tr),
                        actions: [
                          TextButton(
                              onPressed: () => Navigator.pop(context, false),
                              child: Text('Cancel'.tr)),
                          TextButton(
                              onPressed: () => Navigator.pop(context, true),
                              child: Text('Leave'.tr)),
                        ],
                      )) ??
              false;
        },
        child: Scaffold(
            appBar: AppBar(
                actions: [
                  IconButton(
                      icon: const Icon(Icons.open_in_browser),
                      tooltip: 'Continue on website'.tr,
                      onPressed: busy
                          ? null
                          : () async {
                              autosave?.cancel();
                              if (dirty) await save();
                              if (mounted && !dirty && !busy) {
                                await website('practice.attempt',
                                    attemptId: widget.id);
                              }
                            })
                ],
                title: Text(completed
                    ? '${attempt!['score']} / ${(attempt!['questions'] as List).length}'
                    : '${remaining.isNegative ? 0 : remaining.inMinutes}:${(remaining.inSeconds % 60).abs().toString().padLeft(2, '0')}')),
            body: ListView(padding: const EdgeInsets.all(16), children: [
              if (!completed && remaining.isNegative)
                Text('This session has expired. Start a new session.'.tr),
              if (!completed &&
                  remaining.isNegative &&
                  attempt!['mode'] == 'mock')
                TextButton(
                    onPressed: busy ? null : load,
                    child: Text('Check results'.tr)),
              for (final q in attempt!['questions'])
                Card(
                    child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              learnerImage(q['image_url']),
                              Text(q['text'],
                                  style:
                                      Theme.of(context).textTheme.titleLarge),
                              for (final option
                                  in (q['options'] as Map).entries)
                                RadioListTile<String>(
                                    value: option.key,
                                    groupValue: answers['${q['id']}'],
                                    title:
                                        Text('${option.key}. ${option.value}'),
                                    onChanged: completed ||
                                            remaining.isNegative ||
                                            busy
                                        ? null
                                        : (v) {
                                            setState(() {
                                              answers['${q['id']}'] = v;
                                              dirty = true;
                                              status = 'Unsaved changes';
                                            });
                                            autosave?.cancel();
                                            autosave = Timer(
                                                const Duration(
                                                    milliseconds: 700),
                                                save);
                                          }),
                              if (completed) ...[
                                Text('${'Correct answer'.tr}: ${q['correct']}'),
                                Text(q['explanation'] ?? '')
                              ],
                              TextButton(
                                  onPressed: () => Get.to(() => LearnerFormPage(
                                          title: 'Report a problem',
                                          path:
                                              'content/question/${q['id']}/report',
                                          fields: const {
                                            'message': 'Describe the problem'
                                          })),
                                  child: Text('Report a problem'.tr)),
                            ]))),
              if (status.isNotEmpty)
                Semantics(liveRegion: true, child: Text(status.tr)),
              if (!completed && !remaining.isNegative) ...[
                OutlinedButton(
                    onPressed: busy
                        ? null
                        : () async {
                            await save();
                            if (mounted && !dirty) Get.back();
                          },
                    child: Text('Save and continue later'.tr)),
                ElevatedButton(
                    onPressed: busy ? null : () => save(submit: true),
                    child: Text('Submit answers'.tr)),
              ],
            ])));
  }
}
