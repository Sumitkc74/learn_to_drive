import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'api_client.dart';
import 'globals.dart';

class SafeLinks {
  static Future<void> open(String url) async {
    final uri = parse(url);
    if (uri == null) {
      Get.snackbar(
          'Link blocked', 'This link is not an approved secure destination.');
      return;
    }
    try {
      if (await launchUrl(uri, mode: LaunchMode.externalApplication)) return;
    } catch (_) {
      // Do not expose URLs or platform exception details in logs.
    }
    Get.snackbar(
        'Link unavailable', 'Unable to open this link. Please try again.');
  }

  static Uri? parse(String value, {bool media = false}) {
    final uri = Uri.tryParse(value);
    final api = Uri.parse(baseURL);
    if (uri == null ||
        (uri.scheme != 'https' && uri.scheme != 'http') ||
        !uri.hasAuthority ||
        uri.userInfo.isNotEmpty ||
        uri.host.isEmpty ||
        uri.hasFragment) return null;
    final own = uri.origin == api.origin;
    if (uri.scheme != 'https' && !(kDebugMode && own && uri.scheme == 'http')) {
      return null;
    }
    if (media) return own ? uri : null;
    final host = uri.host.toLowerCase();
    final trusted = own ||
        host == 'dotm.gov.np' ||
        host.endsWith('.dotm.gov.np') ||
        host == 'youtube.com' ||
        host == 'www.youtube.com' ||
        host == 'youtu.be';
    return trusted && (own || uri.port == 443) ? uri : null;
  }

  static Future<Uint8List> pdf(String url) async {
    final uri = parse(url, media: true);
    if (uri == null) throw const FormatException('Untrusted document URL.');
    final client = http.Client();
    try {
      final request = http.Request('GET', uri)..followRedirects = false;
      // Public library documents never receive a bearer token.
      final response =
          await client.send(request).timeout(const Duration(seconds: 20));
      if (response.statusCode != 200) {
        throw const FormatException('Document unavailable.');
      }
      final bytes = await readLimitedResponse(response, 20 * 1024 * 1024);
      if (bytes.length < 5 || String.fromCharCodes(bytes.take(5)) != '%PDF-') {
        throw const FormatException('Invalid PDF document.');
      }
      return Uint8List.fromList(bytes);
    } finally {
      client.close();
    }
  }
}
