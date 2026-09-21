import 'package:first_app/Screens/SettingsScreens/user_history_page.dart';
import 'package:first_app/Services/globals.dart';
import 'package:get_storage/get_storage.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Controllers/auth_controller.dart';
import 'package:first_app/Services/learner_api.dart';
import 'package:first_app/Services/safe_links.dart';
import 'package:first_app/models/access_token_model.dart';
import 'package:first_app/models/current_user_model.dart';
import 'package:first_app/Services/session.dart';
import 'package:first_app/Screens/navigator.dart';
import 'common.dart';
import 'practice.dart';
import 'resources.dart';
import 'support_premium.dart';

class BrowserLoginPage extends StatefulWidget {
  const BrowserLoginPage({super.key});
  @override
  State<BrowserLoginPage> createState() => _BrowserLoginPageState();
}

class _BrowserLoginPageState extends State<BrowserLoginPage>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && flow != null && !busy) finish();
  }

  Map<String, dynamic>? flow;
  bool busy = false;
  Future<void> start() async {
    setState(() => busy = true);
    await learnerAction(() async {
      final r = await LearnerApi.call('mobile/login/start', method: 'POST');
      if (!mounted) return;
      setState(() => flow = Map<String, dynamic>.from(r['data']));
      await SafeLinks.open(flow!['url']);
    });
    if (mounted) setState(() => busy = false);
  }

  Future<void> finish() async {
    if (busy || flow == null) return;
    setState(() => busy = true);
    await learnerAction(() async {
      final r = await LearnerApi.call('mobile/login/exchange',
          method: 'POST',
          data: {'id': flow!['id'], 'verifier': flow!['verifier']});
      Session.clear();
      currentUser = CurrentUser.fromJson(r['user']);
      accessToken = AccessToken.fromJson(r['token']);
      Get.offAll(() => const NavigationPage());
    });
    if (mounted) setState(() => busy = false);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text('Sign in with Google'.tr)),
      body: ListView(padding: const EdgeInsets.all(20), children: [
        Text(
            'Continue securely in your browser. Choose Google or your existing account, then approve the matching code.'
                .tr),
        if (flow != null) ...[
          SelectableText(flow!['code'], style: const TextStyle(fontSize: 32)),
          Text('Return here after approving the request.'.tr),
          ElevatedButton(
              onPressed: busy ? null : finish,
              child: Text('Complete sign-in'.tr))
        ],
        OutlinedButton(
            onPressed: busy ? null : start,
            child:
                Text((flow == null ? 'Open secure sign-in' : 'Start again').tr))
      ]));
}

Widget accountPage() => LearnerDataPage(
    title: 'Account',
    path: 'learner/account',
    builder: (result, refresh) {
      final data = result['data'];
      final user = data['user'];
      return [
        Text(user['name'], style: const TextStyle(fontSize: 24)),
        Text(user['email']),
        for (final field in {
          'name': 'Name',
          'email': 'Email',
          'phoneNumber': 'Phone number'
        }.entries)
          learnerTile(field.value, () async {
            await Get.to(() => LearnerFormPage(
                title: 'Edit ${field.value}',
                path: 'learner/settings/${field.key}',
                method: 'PATCH',
                fields: {
                  field.key: field.value,
                  if (field.key == 'email')
                    'current_password': 'Current password'
                },
                initial: Map<String, dynamic>.from(user)));
            refresh();
          }, subtitle: user[field.key]?.toString()),
        learnerTile(
            'Change password',
            () => Get.to(() => const LearnerFormPage(
                    title: 'Change password',
                    path: 'auth/password',
                    method: 'PUT',
                    fields: {
                      'current_password': 'Current password',
                      'new_password': 'New password',
                      'new_password_confirmation': 'Confirm password'
                    }))),
        if (user['email_verified_at'] == null)
          learnerTile(
              'Verify email',
              () => learnerAction(() async {
                    final r = await LearnerApi.call('learner/verify-email',
                        method: 'POST');
                    Get.snackbar('Check your email'.tr, r['message']);
                  })),
        learnerTile('Two-step verification', () async {
          if (data['mfa_enabled']) {
            await Get.to(() => const LearnerFormPage(
                    title: 'Disable two-step verification',
                    path: 'learner/mfa',
                    method: 'DELETE',
                    fields: {
                      'current_password': 'Current password',
                      'code': 'Authenticator or recovery code'
                    }));
            refresh();
            return;
          }
          final setup = await Get.to(() => const LearnerFormPage(
              title: 'Set up two-step verification',
              path: 'learner/mfa/setup',
              fields: {'current_password': 'Current password'}));
          if (setup == null) return;
          await Get.dialog(AlertDialog(
              title: Text('Authenticator setup key'.tr),
              content: SelectableText(setup['data']['secret']),
              actions: [
                TextButton(
                    onPressed: () => Get.back(),
                    child: Text('I saved the key'.tr))
              ]));
          final enabled = await Get.to(() => const LearnerFormPage(
                  title: 'Enable two-step verification',
                  path: 'learner/mfa/enable',
                  fields: {
                    'current_password': 'Current password',
                    'code': 'Authenticator code'
                  }));
          if (enabled != null) {
            await Get.dialog(AlertDialog(
                title: Text('Save your recovery codes'.tr),
                content: SelectableText(
                    (enabled['data']['recovery_codes'] as List).join('\n')),
                actions: [
                  TextButton(
                      onPressed: () => Get.back(), child: Text('Done'.tr))
                ]));
          }
          refresh();
        }, subtitle: (data['mfa_enabled'] ? 'Enabled' : 'Not enabled').tr),
        learnerTile('Google account connection', () => website('settings')),
        learnerTile('Premium', () async {
          await Get.to(premiumPage);
          refresh();
        }),
        learnerTile(
            'Practice archive', () => Get.to(() => UserHistoryScreen())),
        learnerTile('Help & feedback', () => Get.to(supportPage)),
        SwitchListTile(
            title: Text('Dark mode'.tr),
            value: Get.isDarkMode,
            onChanged: (value) {
              GetStorage().write('learner_dark', value);
              Get.changeThemeMode(value ? ThemeMode.dark : ThemeMode.light);
            }),
        learnerTile(
            'Language',
            () =>
                Get.dialog(SimpleDialog(title: Text('Language'.tr), children: [
                  for (final entry in {'en': 'English', 'ne': 'नेपाली'}.entries)
                    SimpleDialogOption(
                        onPressed: () {
                          GetStorage().write('learner_language', entry.key);
                          Get.updateLocale(Locale(
                              entry.key, entry.key == 'ne' ? 'NP' : 'US'));
                          Get.back();
                          refresh();
                        },
                        child: Text(entry.value))
                ]))),
        learnerTile('Sign out', () => Get.put(AuthController()).logout()),
      ];
    });
Widget premiumPage() => LearnerDataPage(
    title: 'Free and Premium plans',
    path: 'learner/catalog',
    builder: (result, refresh) {
      final premium = result['data']['premium']['active'] == true;
      final plan = result['data']['premium'];
      return [
        Card(
            child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Registered (Free)'.tr,
                          style: const TextStyle(fontSize: 22)),
                      Text(
                          'Free: saved resources, progress, practice and support. Premium: study chatbot and personalized revision.'
                              .tr)
                    ]))),
        Card(
            child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                          (premium ? 'Premium is active' : 'Upgrade to Premium')
                              .tr,
                          style: const TextStyle(fontSize: 22)),
                      if ((plan['amount_paisa'] ?? 0) > 0)
                        Text(
                            'NPR ${(plan['amount_paisa'] / 100).toStringAsFixed(2)} / ${plan['days']} ${'days'.tr}')
                      else
                        Text(
                            'Pricing will be available when checkout is configured.'
                                .tr),
                      Text(
                          '${plan['daily_chat_limit']} ${'chat requests per Nepal day'.tr}'),
                    ]))),
        if (premium) ...[
          learnerTile('Study assistant', () => Get.to(() => const ChatPage())),
          learnerTile('Your revision modules', () => Get.to(revisionPage)),
          learnerTile('Personalized practice',
              () => Get.to(() => const PracticeSetupPage(personalized: true)))
        ],
        learnerTile(
            'Plans and secure checkout',
            () => Session.isAuthenticated
                ? website('premium')
                : SafeLinks.open(
                    Uri.parse(baseURL).resolve('../learn/premium').toString())),
        if (Session.isAuthenticated) ...[
          learnerTile('Payment history', () async {
            await Get.to(() => paymentHistory(1));
            refresh();
          }),
          if (!premium)
            learnerTile(
                'Request Premium access',
                () => learnerAction(() async {
                      final r = await LearnerApi.call('learner/premium/request',
                          method: 'POST');
                      Get.snackbar('Request submitted'.tr, r['message']);
                    })),
        ],
      ];
    });

Widget learningHome() => LearnerDataPage(
    title: 'My learning',
    path: 'learner/account',
    actions: [
      IconButton(
          onPressed: () => website('account'),
          icon: const Icon(Icons.open_in_browser),
          tooltip: 'Continue on website'.tr)
    ],
    builder: (result, refresh) {
      final data = result['data'];
      final p = data['progress'];
      return [
        Text('${'Welcome'.tr}, ${data['user']['name']}',
            style: const TextStyle(fontSize: 24)),
        Text(
            '${'Sessions'.tr}: ${p['sessions']}  |  ${'Answered questions'.tr}: ${p['answered']}'),
        Text(
            '${'Study streak'.tr}: ${p['streak']}  •  ${'Accuracy'.tr}: ${p['accuracy'] ?? 0}%'),
        Wrap(spacing: 8, children: [
          for (final day in p['week'])
            Chip(
                avatar: Icon(
                    day['done'] ? Icons.check_circle : Icons.circle_outlined),
                label: Text(day['label']))
        ]),
        Text('Recommended next step'.tr, style: const TextStyle(fontSize: 20)),
        learnerTile(data['next_step']['label'], () async {
          final step = data['next_step'];
          if (step['action'] == 'resume') {
            await Get.to(() => PracticeAttemptPage(id: step['attempt_id']));
          } else if (step['action'] == 'flashcards') {
            await Get.to(() => const FlashcardsPage());
          } else {
            await Get.to(
                () => PracticeSetupPage(topic: step['category'] ?? ''));
          }
          refresh();
        },
            subtitle: data['next_step']['reason']
                .toString()
                .tr
                .replaceAll(':topic', data['next_step']['topic'] ?? '')),
        learnerTile(
            'Saved resources',
            () => Get.to(() => const ResourceListPage(
                type: '', title: 'Saved resources', saved: true))),
        if (data['premium'])
          learnerTile('Your revision modules', () => Get.to(revisionPage)),
        Text('Your practice sessions'.tr, style: const TextStyle(fontSize: 20)),
        for (final a in data['attempts'])
          learnerTile(
              attemptLabel(a),
              a['status'] == 'expired'
                  ? null
                  : () async {
                      await Get.to(() => PracticeAttemptPage(id: a['id']));
                      refresh();
                    },
              subtitle: a['completed_at'] == null
                  ? '${a['expires_at']}'
                  : '${a['score']} / ${a['total']}'),
        if (data['next_page'] != null)
          learnerTile('Older sessions',
              () => Get.to(() => sessionHistory(data['next_page']))),
      ];
    });
Widget sessionHistory(int page) => LearnerDataPage(
    title: 'Practice history',
    path: 'learner/account?page=$page',
    builder: (r, refresh) => [
          for (final a in r['data']['attempts'])
            learnerTile(
                attemptLabel(a),
                a['status'] == 'expired'
                    ? null
                    : () async {
                        await Get.to(() => PracticeAttemptPage(id: a['id']));
                        refresh();
                      },
                subtitle: a['completed_at'] == null
                    ? '${a['expires_at']}'
                    : '${a['score']} / ${a['total']}'),
          if (r['data']['next_page'] != null)
            learnerTile('Older sessions',
                () => Get.to(() => sessionHistory(r['data']['next_page']))),
        ]);
Widget libraryHome() => LearnerDataPage(
    title: 'Library',
    path: 'learner/catalog',
    builder: (r, refresh) => [
          for (final type in r['data']['types'])
            learnerTile(
                type['title'],
                () => Get.to(() => ResourceListPage(
                    type: type['type'], title: type['title']))),
        ]);

Widget paymentHistory(int page) => LearnerDataPage(
    title: 'Payment history',
    path: 'learner/payments?page=$page',
    builder: (r, refresh) => [
          if ((r['data'] as List).isEmpty) Text('No payments yet.'.tr),
          for (final payment in r['data'])
            Card(
                child: ListTile(
                    title: Text(
                        '${payment['gateway']} | NPR ${(payment['amount_paisa'] / 100).toStringAsFixed(2)}'),
                    subtitle:
                        Text('${payment['status']}\n${payment['created_at']}'),
                    trailing: TextButton(
                        onPressed: () => learnerAction(() async {
                              final result = await LearnerApi.call(
                                  'learner/payments/${payment['id']}/verify',
                                  method: 'POST');
                              Get.snackbar(
                                  'Payment status'.tr, result['message']);
                              refresh();
                            }),
                        child: Text('Check status'.tr)))),
          if (r['next_page'] != null)
            learnerTile('Older payments',
                () => Get.to(() => paymentHistory(r['next_page']))),
        ]);

String attemptLabel(dynamic a) => a['status'] == 'expired'
    ? 'Session expired'
    : a['status'] == 'results_pending'
        ? 'Check results'
        : a['completed_at'] == null
            ? 'Resume practice'
            : 'Review answers';
