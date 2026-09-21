import 'package:first_app/Services/learner_api.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:first_app/Screens/Learner/home_account.dart';
import 'package:first_app/Screens/Learner/practice.dart';
import 'package:first_app/Screens/Learner/support_premium.dart';

class NavigationPage extends StatefulWidget {
  const NavigationPage({super.key});
  @override
  State<NavigationPage> createState() => _NavigationState();
}

class _NavigationState extends State<NavigationPage>
    with WidgetsBindingObserver {
  int selected = 0;
  bool premium = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    refreshPremium();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) refreshPremium();
  }

  Future<void> refreshPremium() async {
    try {
      final r = await LearnerApi.call('learner/account');
      if (mounted) setState(() => premium = r['data']['premium'] == true);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: KeyedSubtree(
            key: ValueKey(selected),
            child: [
              learningHome,
              libraryHome,
              () => const PracticeSetupPage(),
              accountPage
            ][selected]()),
        floatingActionButton: !premium
            ? null
            : FloatingActionButton(
                onPressed: () => Get.to(() => const ChatPage()),
                tooltip: 'Premium study assistant'.tr,
                child: const Icon(Icons.chat_bubble_outline)),
        bottomNavigationBar: BottomNavigationBar(
            type: BottomNavigationBarType.fixed,
            currentIndex: selected,
            onTap: (index) {
              setState(() => selected = index);
              refreshPremium();
            },
            items: [
              BottomNavigationBarItem(
                  icon: const Icon(Icons.home_outlined),
                  label: 'My learning'.tr),
              BottomNavigationBarItem(
                  icon: const Icon(Icons.menu_book_outlined),
                  label: 'Library'.tr),
              BottomNavigationBarItem(
                  icon: const Icon(Icons.quiz_outlined), label: 'Practice'.tr),
              BottomNavigationBarItem(
                  icon: const Icon(Icons.person_outline), label: 'Account'.tr),
            ]),
      );
}
