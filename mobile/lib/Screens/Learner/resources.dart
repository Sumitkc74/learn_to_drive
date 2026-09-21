import 'package:first_app/Services/session.dart';
import 'package:first_app/Screens/AuthScreen/login.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/safe_links.dart';
import 'package:first_app/utils/pdf_reader.dart';
import 'common.dart';

class FlashcardsPage extends StatefulWidget {
  const FlashcardsPage({super.key});
  @override
  State<FlashcardsPage> createState() => _FlashcardsPageState();
}

class _FlashcardsPageState extends State<FlashcardsPage> {
  final List<dynamic> cards = [];
  int index = 0;
  int? page = 1;
  bool busy = false, revealed = false;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    if (busy || page == null) return;
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final r = await LearnerApi.call(
          'learner/library/traffic-sign?flashcards=1&page=$page');
      if (!mounted) return;
      setState(() {
        cards.addAll(r['data']);
        page = r['next_page'];
      });
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    }
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text('Traffic sign flashcards'.tr)),
        body: ListView(padding: const EdgeInsets.all(20), children: [
          if (cards.isNotEmpty) ...[
            Text('${index + 1}'),
            learnerImage(cards[index]['media_url']),
            ElevatedButton(
                onPressed: () => setState(() => revealed = !revealed),
                child: Text((revealed ? 'Hide answer' : 'Reveal answer').tr)),
            if (revealed) ...[
              Text(cards[index]['title']),
              Text(cards[index]['description'] ?? '')
            ],
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              TextButton(
                  onPressed: index == 0
                      ? null
                      : () => setState(() {
                            index--;
                            revealed = false;
                          }),
                  child: Text('Previous'.tr)),
              TextButton(
                  onPressed: busy || (index + 1 == cards.length && page == null)
                      ? null
                      : () async {
                          if (index + 1 == cards.length) await load();
                          if (mounted && index + 1 < cards.length) {
                            setState(() {
                              index++;
                              revealed = false;
                            });
                          }
                        },
                  child: Text('Next'.tr)),
            ]),
          ],
          if (busy) const Center(child: CircularProgressIndicator()),
          if (error != null)
            TextButton(onPressed: load, child: Text('Retry'.tr)),
          if (!busy && error == null && cards.isEmpty)
            Text('No resources match this selection.'.tr),
        ]),
      );
}

class ResourceListPage extends StatefulWidget {
  const ResourceListPage(
      {super.key, required this.type, required this.title, this.saved = false});
  final String type, title;
  final bool saved;
  @override
  State<ResourceListPage> createState() => _ResourceListPageState();
}

class _ResourceListPageState extends State<ResourceListPage> {
  final search = TextEditingController();
  String language = '';
  final List<dynamic> items = [];
  int? page = 1;
  bool busy = false;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
  }

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  Future<void> load({bool reset = false}) async {
    if (busy) return;
    if (reset) {
      items.clear();
      page = 1;
    }
    if (page == null) return;
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final query = Uri(queryParameters: {
        'page': '$page',
        'q': search.text,
        'language': language
      }).query;
      final result = await LearnerApi.call(widget.saved
          ? 'learner/saved?page=$page'
          : 'learner/library/${widget.type}?$query');
      if (!mounted) return;
      setState(() {
        items.addAll(result['data']);
        page = result['next_page'];
      });
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    }
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(widget.title.tr)),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        if (!widget.saved)
          TextField(
              controller: search,
              decoration: InputDecoration(
                  labelText: 'Search'.tr,
                  suffixIcon: IconButton(
                      onPressed: () => load(reset: true),
                      icon: const Icon(Icons.search))),
              onSubmitted: (_) => load(reset: true)),
        if (['question-bank', 'exam-information'].contains(widget.type))
          DropdownButton<String>(
              value: language,
              isExpanded: true,
              items: ['', 'English', 'Nepali']
                  .map((v) => DropdownMenuItem(
                      value: v,
                      child: Text((v.isEmpty ? 'All languages' : v).tr)))
                  .toList(),
              onChanged: busy
                  ? null
                  : (v) {
                      setState(() => language = v!);
                      load(reset: true);
                    }),
        for (final entry in items)
          Builder(builder: (context) {
            final item = widget.saved ? entry['resource'] : entry;
            return Card(
                child: ListTile(
                    title: Text(item?['title']?.toString() ??
                        'Resource unavailable'.tr),
                    subtitle: Text(item?['language'] ?? ''),
                    onTap: item == null
                        ? null
                        : () async {
                            await Get.to(() => ResourcePage(
                                resource: Map<String, dynamic>.from(item)));
                            if (mounted) await load(reset: true);
                          },
                    trailing: widget.saved
                        ? IconButton(
                            icon: const Icon(Icons.bookmark_remove),
                            tooltip: 'Remove from saved'.tr,
                            onPressed: () => learnerAction(() async {
                                  await LearnerApi.call(
                                      'learner/saved/${entry['bookmark_id']}',
                                      method: 'DELETE');
                                  await load(reset: true);
                                }))
                        : const Icon(Icons.chevron_right)));
          }),
        if (error != null) Text(error!),
        if (busy) const Center(child: CircularProgressIndicator()),
        if (!busy && items.isEmpty)
          Text('No resources match this selection.'.tr),
        if (!busy && page != null)
          TextButton(
              onPressed: load,
              child: Text((error == null ? 'Load more' : 'Retry').tr)),
      ]));
}

class ResourcePage extends StatefulWidget {
  const ResourcePage({super.key, required this.resource});
  final Map<String, dynamic> resource;
  @override
  State<ResourcePage> createState() => _ResourcePageState();
}

class _ResourcePageState extends State<ResourcePage> {
  bool revealed = false, saving = false;
  int? bookmarkId;
  @override
  void initState() {
    super.initState();
    bookmarkId = widget.resource['bookmark_id'];
  }

  @override
  Widget build(BuildContext context) {
    final r = widget.resource;
    final type = r['type'];
    final reveal = ['traffic-sign', 'vision-test'].contains(type);
    final media = r['media_url']?.toString() ?? '';
    return Scaffold(
        appBar: AppBar(
            title: Text(
                (reveal ? 'Look, think, reveal' : r['title']).toString().tr)),
        body: ListView(padding: const EdgeInsets.all(20), children: [
          if (reveal && SafeLinks.parse(media, media: true) != null)
            Image.network(media,
                height: 250,
                fit: BoxFit.contain,
                errorBuilder: (_, __, ___) => Text('Image unavailable'.tr)),
          if (reveal)
            ElevatedButton(
                onPressed: () => setState(() => revealed = !revealed),
                child: Text((revealed ? 'Hide answer' : 'Reveal answer').tr)),
          if (!reveal || revealed) ...[
            Text(r['title'] ?? '',
                style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 16),
            Text(r['description'] ?? '')
          ],
          if (type == 'vision-test')
            Padding(
                padding: const EdgeInsets.all(12),
                child: Text(
                    'This is a learning exercise, not a medical assessment.'
                        .tr)),
          if (['question-bank', 'exam-information'].contains(type) &&
              media.isNotEmpty)
            ElevatedButton(
                onPressed: () => Get.to(
                    () => PdfReaderScreen(title: r['title'], url: media)),
                child: Text('Open PDF'.tr)),
          if (r['external_url'] != null)
            TextButton(
                onPressed: () => SafeLinks.open(r['external_url']),
                child: Text('Open source'.tr)),
          if (!Session.isAuthenticated)
            TextButton(
                onPressed: () => Get.to(() => const LoginScreen()),
                child: Text('Sign in to save resources'.tr)),
          if (Session.isAuthenticated)
            TextButton.icon(
                icon: Icon(bookmarkId == null
                    ? Icons.bookmark_add
                    : Icons.bookmark_remove),
                label: Text(
                    (bookmarkId == null ? 'Save resource' : 'Remove from saved')
                        .tr),
                onPressed: saving
                    ? null
                    : () async {
                        setState(() => saving = true);
                        await learnerAction(() async {
                          if (bookmarkId == null) {
                            final result = await LearnerApi.call(
                                'learner/saved/$type/${r['id']}',
                                method: 'POST');
                            if (mounted) {
                              setState(() =>
                                  bookmarkId = result['data']['bookmark_id']);
                            }
                          } else {
                            await LearnerApi.call('learner/saved/$bookmarkId',
                                method: 'DELETE');
                            if (mounted) setState(() => bookmarkId = null);
                          }
                        });
                        if (mounted) setState(() => saving = false);
                      }),
          if (Session.isAuthenticated)
            TextButton.icon(
                icon: const Icon(Icons.flag_outlined),
                label: Text('Report a problem'.tr),
                onPressed: () => Get.to(() => LearnerFormPage(
                    title: 'Report a problem',
                    path: 'content/$type/${r['id']}/report',
                    fields: const {'message': 'Describe the problem'}))),
        ]));
  }
}
