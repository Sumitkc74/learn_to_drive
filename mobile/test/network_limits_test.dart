import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:first_app/Services/api_client.dart';
import 'package:first_app/Services/safe_links.dart';

void main() {
  test('rejects chunked response over the byte limit', () async {
    final response = http.StreamedResponse(
        Stream.fromIterable([
          [1, 2],
          [3, 4]
        ]),
        200);
    await expectLater(
        readLimitedResponse(response, 3), throwsA(isA<http.ClientException>()));
  });
  test('accepts response exactly at the byte limit', () async {
    final response = http.StreamedResponse(Stream.value([1, 2, 3]), 200);
    expect(await readLimitedResponse(response, 3), [1, 2, 3]);
  });
  test('blocks unsafe schemes, deceptive domains and private destinations', () {
    for (final value in [
      'javascript:alert(1)',
      'file:///etc/passwd',
      'https://dotm.gov.np.evil.test/a',
      'https://user:pass@dotm.gov.np/a',
      'https://127.0.0.1/a',
      'http://dotm.gov.np/a',
      'https://dotm.gov.np:8443/a'
    ]) {
      expect(SafeLinks.parse(value), isNull, reason: value);
    }
    expect(SafeLinks.parse('https://dotm.gov.np/notices'), isNotNull);
    expect(
        SafeLinks.parse('https://www.youtube.com/watch?v=example'), isNotNull);
    expect(
        SafeLinks.parse('https://dotm.gov.np/file.pdf', media: true), isNull);
  });
}
