import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:learn_to_drive/Services/learner_api.dart';
import 'common.dart';
import 'student_records.dart';

Future<void> schoolAction(
  String path,
  Map<String, dynamic> data,
  VoidCallback refresh,
) => learnerAction(() async {
  await LearnerApi.call(path, method: 'POST', data: data);
  refresh();
});

Widget schoolCentrePage() => LearnerDataPage(
  title: 'School centre',
  path: 'school-centre',
  builder: (result, refresh) {
    final data = result['data'];
    return [
      learnerTile('Find driving schools', () => website('schools')),
      learnerTile('Book a private driving lesson', () => website('lessons')),
      learnerTile(
        'My driving bookings',
        () => website('lessons.bookings'),
        subtitle: 'Bookings, payments and refunds'.tr,
      ),
      for (final membership in data['schools'])
        learnerTile(
          membership['school']['name'],
          membership['school']['status'] == 'Approved'
              ? () => Get.to(() => schoolPage(membership['driving_school_id']))
              : null,
          subtitle:
              '${membership['role'].toString().tr} · ${membership['school']['status'].toString().tr}',
        ),
      if ((data['schools'] as List).isEmpty)
        Text('You have not joined a school yet.'.tr),
      for (final invitation in data['invitations'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(invitation['name']),
                Text(invitation['role'].toString().tr),
                Wrap(
                  spacing: 12,
                  children: [
                    for (final decision in ['accept', 'decline'])
                      TextButton(
                        onPressed: () => schoolAction(
                          'school-centre/invitations/${invitation['id']}',
                          {'decision': decision},
                          refresh,
                        ),
                        child: Text(
                          (decision == 'accept' ? 'Accept' : 'Decline').tr,
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ),
      learnerTile('Apply as a driving school', () async {
        await Get.to(
          () => const LearnerFormPage(
            title: 'Apply as a driving school',
            path: 'school-centre/apply',
            fields: {
              'name': 'School name',
              'address': 'Address',
              'phone': 'Phone number',
              'email': 'Email',
            },
          ),
        );
        refresh();
      }),
      Text('School notifications'.tr, style: const TextStyle(fontSize: 20)),
      TextButton(
        onPressed: () => schoolAction('school-centre/read', {}, refresh),
        child: Text('Mark all as read'.tr),
      ),
      for (final notice in data['notifications']['data'])
        ListTile(
          leading: Icon(
            notice['read_at'] == null
                ? Icons.notifications_active_outlined
                : Icons.notifications_none,
          ),
          title: Text(notice['message'].toString().tr),
        ),
      if (data['notifications']['next_page_url'] != null)
        learnerTile('Older notifications', () => website('school.centre')),
    ];
  },
);

Widget schoolPage(int id, {int page = 1}) => LearnerDataPage(
  title: 'My school',
  path: 'school-centre/$id?session_page=$page',
  builder: (result, refresh) {
    final data = result['data'];
    final member = data['member'];
    final student = member['role'] == 'Student';
    return [
      Text(data['school']['name'], style: const TextStyle(fontSize: 24)),
      Text(member['role'].toString().tr),
      if (['Owner', 'Manager'].contains(member['role']))
        learnerTile('School reports', () => Get.to(() => schoolReportPage(id))),
      learnerTile(
        'Student exams and licences',
        () => Get.to(() => schoolStudentsPage(id)),
      ),
      if (!student)
        learnerTile(
          'Manage school',
          () => website('school.centre'),
          subtitle: 'Courses, invitations and attendance'.tr,
        ),
      if (member['role'] == 'Instructor')
        learnerTile(
          'My vehicles and lesson times',
          () => website('instructor.lessons'),
        ),
      if (student)
        SwitchListTile(
          title: Text('Share progress'.tr),
          subtitle: Text(
            'Share only your completed practice count with school staff. Your answers and chats stay private.'
                .tr,
          ),
          value:
              member['share_progress'] == true || member['share_progress'] == 1,
          onChanged: (value) => schoolAction('school-centre/$id/consent', {
            'share_progress': value,
          }, refresh),
        ),
      Text('Courses and batches'.tr, style: const TextStyle(fontSize: 20)),
      for (final course in data['courses'])
        ListTile(
          title: Text(course['title']),
          subtitle: Text(course['description']),
        ),
      for (final batch in data['batches'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${batch['name']} · ${batch['course_title']}'),
                Text(batch['instructor_name']),
                Text('${batch['starts_on']} — ${batch['ends_on']}'),
                Text('${'Capacity'.tr}: ${batch['capacity']}'),
                if (student && batch['closed'] != true && batch['closed'] != 1)
                  if ((data['roster'] as List).any(
                    (m) => m['school_batch_id'] == batch['id'],
                  ))
                    Text('Joined'.tr)
                  else
                    TextButton(
                      onPressed: () => schoolAction('school-centre/$id/join', {
                        'batch_id': batch['id'],
                      }, refresh),
                      child: Text('Join batch'.tr),
                    ),
              ],
            ),
          ),
        ),
      Text(
        'Class schedule and attendance'.tr,
        style: const TextStyle(fontSize: 20),
      ),
      for (final session in data['sessions'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(session['batch_name']),
                Text(
                  '${schoolTime(session['starts_at'])} · ${'Nepal time'.tr}',
                ),
                Text(session['location']),
                if (session['cancelled'] == true || session['cancelled'] == 1)
                  Text('Cancelled'.tr),
                for (final row in data['attendance'])
                  if (row['school_class_session_id'] == session['id'])
                    Text(
                      '${schoolLearner(data['roster'], row['user_id'])}: ${row['status'].toString().tr}',
                    ),
              ],
            ),
          ),
        ),
      if (data['session_pages']['current_page'] <
          data['session_pages']['last_page'])
        learnerTile(
          'More classes',
          () => Get.to(() => schoolPage(id, page: page + 1)),
        ),
      if (!student) ...[
        Text('Shared learning progress'.tr),
        for (final row in data['progress'])
          ListTile(
            title: Text(row['name']),
            subtitle: Text(
              '${row['completed_attempts']} ${'completed practices'.tr}',
            ),
          ),
      ],
    ];
  },
);

String schoolLearner(dynamic roster, dynamic id) {
  for (final member in roster) {
    if (member['user_id'] == id) return member['name'];
  }
  return 'Learner'.tr;
}

String schoolTime(String value) {
  final utc = DateTime.tryParse('${value.replaceFirst(' ', 'T')}Z');
  if (utc == null) return value;
  final nepal = utc.add(const Duration(hours: 5, minutes: 45));
  return nepal.toIso8601String().substring(0, 16).replaceFirst('T', ' ');
}
