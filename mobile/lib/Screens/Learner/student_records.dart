import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'common.dart';

Widget schoolStudentsPage(int school, {int page = 1}) => LearnerDataPage(
  title: 'Student exams and licences',
  path: 'school-centre/$school/students?page=$page',
  builder: (response, refresh) {
    final data = response['data'];
    return [
      for (final member in data['data'])
        learnerTile(
          member['user']['name'],
          () => Get.to(() => studentRecordsPage(school, member['user_id'])),
          subtitle: member['user']['email'],
        ),
      if ((data['data'] as List).isEmpty)
        Text('No students available for your school role.'.tr),
      if (data['current_page'] < data['last_page'])
        learnerTile(
          'More students',
          () => Get.to(() => schoolStudentsPage(school, page: page + 1)),
        ),
    ];
  },
);

Widget studentRecordsPage(
  int school,
  int student, {
  int page = 1,
}) => LearnerDataPage(
  title: 'Student exams and licences',
  path: 'school-centre/$school/students/$student?page=$page',
  builder: (response, refresh) {
    final data = response['data'];
    final exams = data['exams'];
    return [
      Text(data['student']['name'], style: const TextStyle(fontSize: 24)),
      Text(
        'These are school-maintained records, not a government licence verification. Notes are visible to the student.'
            .tr,
      ),
      learnerTile(
        'Readiness and instructor feedback',
        () => Get.to(() => readinessPage(school, student)),
      ),
      if (data['canEdit'] == true) ...[
        learnerTile(
          'Add exam attempt',
          () => editStudentRecord(school, student, 'exam', data, refresh),
        ),
        learnerTile(
          'Add previous licence category',
          () => editStudentRecord(school, student, 'licence', data, refresh),
        ),
      ],
      Text('Exam attempts'.tr, style: const TextStyle(fontSize: 20)),
      if ((exams['data'] as List).isEmpty)
        Text('No exam attempts recorded yet.'.tr),
      for (final exam in exams['data'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${exam['category']} · ${exam['exam_type'].toString().tr}',
                ),
                Text('${'Exam date'.tr}: ${exam['exam_date']}'),
                Text(exam['result'].toString().tr),
                if (exam['centre'] != null)
                  Text('${'Exam centre'.tr}: ${exam['centre']}'),
                if (exam['result_date'] != null)
                  Text('${'Result date'.tr}: ${exam['result_date']}'),
                if (exam['notes'] != null) Text(exam['notes']),
                if (data['canEdit'] == true)
                  TextButton(
                    onPressed: () => editStudentRecord(
                      school,
                      student,
                      'exam',
                      data,
                      refresh,
                      record: Map<String, dynamic>.from(exam),
                    ),
                    child: Text('Edit exam record'.tr),
                  ),
              ],
            ),
          ),
        ),
      if (exams['current_page'] < exams['last_page'])
        learnerTile(
          'More exam attempts',
          () =>
              Get.to(() => studentRecordsPage(school, student, page: page + 1)),
        ),
      Text('Previous licences'.tr, style: const TextStyle(fontSize: 20)),
      if ((data['licences'] as List).isEmpty)
        Text(
          'No previous licence categories recorded. This does not confirm that the student has no licence.'
              .tr,
        ),
      for (final licence in data['licences'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${'Licence category'.tr}: ${licence['category']}'),
                if (licence['issued_on'] != null)
                  Text('${'Issued on'.tr}: ${licence['issued_on']}'),
                if (licence['expires_on'] != null)
                  Text('${'Expires on'.tr}: ${licence['expires_on']}'),
                if (licence['notes'] != null) Text(licence['notes']),
                if (data['canEdit'] == true)
                  TextButton(
                    onPressed: () => editStudentRecord(
                      school,
                      student,
                      'licence',
                      data,
                      refresh,
                      record: Map<String, dynamic>.from(licence),
                    ),
                    child: Text('Edit licence record'.tr),
                  ),
              ],
            ),
          ),
        ),
    ];
  },
);

Future<void> editStudentRecord(
  int school,
  int student,
  String kind,
  dynamic data,
  VoidCallback refresh, {
  Map<String, dynamic>? record,
}) async {
  final exam = kind == 'exam';
  await Get.to(
    () => LearnerFormPage(
      title: record == null
          ? (exam ? 'Add exam attempt' : 'Add previous licence category')
          : (exam ? 'Edit exam record' : 'Edit licence record'),
      path: 'school-centre/$school/students/$student/$kind',
      fields: {
        'category': 'Licence category',
        if (exam) ...{
          'exam_type': 'Exam type',
          'exam_date': 'Exam date',
          'centre': 'Exam centre',
          'result': 'Result',
          'result_date': 'Result date',
        } else ...{
          'issued_on': 'Issued on',
          'expires_on': 'Expires on',
        },
        'notes': 'Notes visible to student',
      },
      choices: exam
          ? {
              'exam_type': {
                for (final v in data['types']) v.toString(): v.toString(),
              },
              'result': {
                for (final v in data['results']) v.toString(): v.toString(),
              },
            }
          : {},
      initial: record ?? {},
      extra: record == null
          ? {}
          : {'id': record['id'], 'version': record['version']},
    ),
  );
  refresh();
}

Widget readinessPage(
  int school,
  int student, {
  int page = 1,
}) => LearnerDataPage(
  title: 'Readiness and instructor feedback',
  path: 'school-centre/$school/students/$student/readiness?page=$page',
  builder: (response, refresh) {
    final data = response['data'];
    return [
      Text(data['student']['name'], style: const TextStyle(fontSize: 24)),
      Text(
        'This is learning feedback, not an official exam eligibility decision.'
            .tr,
      ),
      for (final entry in (data['attendance'] as Map).entries)
        Text('${entry.key.toString().tr}: ${entry.value}'),
      Text(
        '${'Completed practices'.tr}: ${data['practice_count'] ?? 'Not shared'.tr}',
      ),
      if (data['canEdit'] == true && (data['sessions'] as List).isNotEmpty)
        learnerTile('Record instructor feedback', () async {
          await Get.to(
            () => LearnerFormPage(
              title: 'Record instructor feedback',
              path: 'school-centre/$school/students/$student/feedback',
              fields: const {
                'session_id': 'Completed class',
                'skill': 'Skill practised',
                'assessment': 'Assessment',
                'notes': 'Feedback and recommended next lesson',
              },
              choices: {
                'session_id': {
                  for (final s in data['sessions'])
                    s['id'].toString():
                        '${s['batch_name']} · ${s['starts_at']} UTC',
                },
                'assessment': {
                  for (final a in data['assessments'])
                    a.toString(): a.toString(),
                },
              },
            ),
          );
          refresh();
        }),
      for (final exam in data['exams'])
        ListTile(
          title: Text(
            '${exam['category']} · ${exam['exam_type'].toString().tr}',
          ),
          subtitle: Text(
            '${exam['exam_date']} · ${exam['result'].toString().tr}',
          ),
        ),
      for (final entry in data['feedback']['data'])
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(entry['skill']),
                Text(entry['assessment'].toString().tr),
                Text(entry['notes']),
                Text(entry['instructor_name']),
              ],
            ),
          ),
        ),
      if (data['feedback']['current_page'] < data['feedback']['last_page'])
        learnerTile(
          'More feedback',
          () => Get.to(() => readinessPage(school, student, page: page + 1)),
        ),
    ];
  },
);

Widget schoolReportPage(
  int school, {
  String? from,
  String? to,
}) => LearnerDataPage(
  title: 'School reports',
  path:
      'school-centre/$school/report${from == null ? '' : '?from=${Uri.encodeComponent(from)}&to=${Uri.encodeComponent(to ?? from)}'}',
  builder: (response, refresh) {
    final data = response['data'];
    return [
      Text(data['school']['name']),
      Text('${data['from']} — ${data['to']}'),
      Text(
        'Exam dates, class dates and lesson dates determine activity totals. Payment totals use invoice creation dates and current status.'
            .tr,
      ),
      learnerTile('Change report dates', () async {
        final context = Get.context;
        if (context == null) return;
        final range = await showDateRangePicker(
          context: context,
          firstDate: DateTime(2000),
          lastDate: DateTime.now().add(const Duration(days: 366)),
        );
        if (range != null) {
          Get.to(
            () => schoolReportPage(
              school,
              from: range.start.toIso8601String().substring(0, 10),
              to: range.end.toIso8601String().substring(0, 10),
            ),
          );
        }
      }),
      for (final e in (data['metrics'] as Map).entries)
        ListTile(
          title: Text(e.key.toString().tr),
          subtitle: Text(e.value.toString()),
        ),
    ];
  },
);
