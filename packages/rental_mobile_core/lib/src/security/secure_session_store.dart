import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../api/api_client.dart';

final class SecureSessionStore implements TokenStore {
  SecureSessionStore({FlutterSecureStorage? storage}) : _storage = storage ?? const FlutterSecureStorage();

  static const _accessTokenKey = 'voyagerrent.access_token';
  static const _userKey = 'voyagerrent.user';
  static const _biometricEnabledKey = 'voyagerrent.biometric_enabled';

  final FlutterSecureStorage _storage;

  @override
  Future<String?> readAccessToken() => _storage.read(key: _accessTokenKey);

  Future<Map<String, dynamic>?> readUser() async {
    final value = await _storage.read(key: _userKey);
    return value == null ? null : jsonDecode(value) as Map<String, dynamic>;
  }

  Future<void> persistAuthenticatedSession({required String accessToken, required Map<String, dynamic> user}) async {
    await _storage.write(key: _accessTokenKey, value: accessToken);
    await _storage.write(key: _userKey, value: jsonEncode(user));
  }

  Future<bool> biometricEnabled() async => (await _storage.read(key: _biometricEnabledKey)) == 'true';

  Future<void> setBiometricEnabled(bool enabled) => _storage.write(key: _biometricEnabledKey, value: '$enabled');

  @override
  Future<void> clearSession() async {
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _userKey);
  }
}
