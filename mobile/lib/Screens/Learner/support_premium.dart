import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/learner_api.dart';
import 'common.dart';
import 'practice.dart';

Widget supportPage() => LearnerDataPage(
    title: 'Help & feedback',
    path: 'support-tickets',
    builder: (result, refresh) => [
          for (final type in [
            'Complaint',
            'Feature request',
            'Content report',
            'Other'
          ])
            learnerTile(type, () async {
              await Get.to(() => LearnerFormPage(
                  title: type,
                  path: 'support-tickets',
                  fields: const {'subject': 'Subject', 'message': 'Message'},
                  extra: {'type': type}));
              refresh();
            }),
          for (final ticket in result['data'])
            learnerTile(
                ticket['subject'], () => Get.to(() => ticketPage(ticket['id'])),
                subtitle: ticket['status']),
          if (result['current_page'] < result['last_page'])
            learnerTile('Older requests',
                () => Get.to(() => olderTickets(result['current_page'] + 1))),
        ]);
Widget olderTickets(int page) => LearnerDataPage(
    title: 'Your requests',
    path: 'support-tickets?page=$page',
    builder: (result, refresh) => [
          for (final ticket in result['data'])
            learnerTile(
                ticket['subject'], () => Get.to(() => ticketPage(ticket['id'])),
                subtitle: ticket['status']),
          if (result['current_page'] < result['last_page'])
            learnerTile(
                'Older requests', () => Get.to(() => olderTickets(page + 1))),
        ]);
Widget ticketPage(int id) => LearnerDataPage(
    title: 'Your request',
    path: 'support-tickets/$id',
    builder: (result, refresh) => [
          Text(result['data']['subject'], style: const TextStyle(fontSize: 24)),
          Text(result['data']['message']),
          Text(result['data']['status']),
          for (final reply in result['data']['replies'])
            Card(
                child: ListTile(
                    title: Text(reply['message']),
                    subtitle: Text(reply['author_role']))),
          ElevatedButton(
              onPressed: () async {
                await Get.to(() => LearnerFormPage(
                    title: 'Reply',
                    path: 'support-tickets/$id/replies',
                    fields: const {'message': 'Message'}));
                refresh();
              },
              child: Text('Reply'.tr)),
        ]);

Widget revisionPage() => LearnerDataPage(
    title: 'Your revision modules',
    path: 'learner/revision',
    builder: (result, refresh) => [
          learnerTile('Personalized practice',
              () => Get.to(() => const PracticeSetupPage(personalized: true))),
          if ((result['data'] as List).isEmpty)
            Text('Complete a practice session to build your revision modules.'
                .tr),
          for (final category in (result['data'] as List)
              .map((q) => q['category'])
              .toSet()) ...[
            Text(category.toString().tr, style: const TextStyle(fontSize: 22)),
            for (final q in (result['data'] as List)
                .where((q) => q['category'] == category))
              Card(
                  child: ExpansionTile(title: Text(q['question']), children: [
                learnerImage(q['image_url']),
                Text(
                    '${'Correct answer'.tr}: ${q['correctOption']} - ${q['option${{
                  'A': 1,
                  'B': 2,
                  'C': 3,
                  'D': 4
                }[q['correctOption']]}']}'),
                Text(q['explanation'] ?? ''),
                TextButton(
                    onPressed: () =>
                        Get.to(() => ChatPage(questionId: q['id'])),
                    child: Text('Study assistant'.tr)),
                TextButton(
                    onPressed: () => Get.to(() => LearnerFormPage(
                        title: 'Report a problem',
                        path: 'content/question/${q['id']}/report',
                        fields: const {'message': 'Describe the problem'})),
                    child: Text('Report a problem'.tr)),
              ])),
            learnerTile(
                'Retry this topic',
                () => Get.to(() => PracticeSetupPage(
                    revision: true, topic: category.toString()))),
          ],
        ]);

class ChatPage extends StatefulWidget {
  const ChatPage({super.key, this.questionId});
  final int? questionId;
  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  final input = TextEditingController();
  final List<dynamic> turns = [];
  String? error;
  bool busy = false;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    try {
      final r = await LearnerApi.call('learner/chat');
      if (mounted) setState(() => turns.addAll(r['data']));
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    }
  }

  @override
  void dispose() {
    input.dispose();
    super.dispose();
  }

  Future<void> send() async {
    if (busy || input.text.trim().isEmpty) return;
    final message = input.text.trim();
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final r = await LearnerApi.call('learner/chat', method: 'POST', data: {
        'message': message,
        if (widget.questionId != null) 'question_id': widget.questionId
      });
      if (mounted) {
        setState(() {
          turns.add({'message': message, 'answer': r['answer']});
          input.clear();
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    }
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text('Study assistant'.tr)),
      body: Column(children: [
        Padding(
            padding: const EdgeInsets.all(12),
            child: Text(
                'Ask about this platform, driving rules, or your test preparation.'
                    .tr)),
        Expanded(
            child: ListView(padding: const EdgeInsets.all(16), children: [
          for (final turn in turns)
            Card(
                child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(turn['message'],
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(height: 8),
                          SelectableText(
                              turn['answer'] ?? 'No response available'.tr)
                        ]))),
          if (error != null) Text(error!)
        ])),
        SafeArea(
            child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Expanded(
                      child: TextField(
                          controller: input,
                          maxLength: 2000,
                          maxLines: null,
                          decoration:
                              InputDecoration(labelText: 'Your question'.tr),
                          onSubmitted: (_) => send())),
                  IconButton(
                      onPressed: busy ? null : send,
                      icon: busy
                          ? const CircularProgressIndicator()
                          : const Icon(Icons.send),
                      tooltip: 'Send'.tr)
                ]))),
      ]));
}
