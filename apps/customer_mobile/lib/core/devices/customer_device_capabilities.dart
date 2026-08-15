import 'dart:io';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:image_picker/image_picker.dart';
import 'package:local_auth/local_auth.dart';

final class CustomerDeviceCapabilities {
  CustomerDeviceCapabilities({LocalAuthentication? authentication, FirebaseMessaging? messaging, ImagePicker? imagePicker})
      : _authentication = authentication ?? LocalAuthentication(),
        _messaging = messaging ?? FirebaseMessaging.instance,
        _imagePicker = imagePicker ?? ImagePicker();

  final LocalAuthentication _authentication;
  final FirebaseMessaging _messaging;
  final ImagePicker _imagePicker;

  Future<bool> unlockWithBiometrics() async {
    if (!await _authentication.canCheckBiometrics || !await _authentication.isDeviceSupported()) return false;
    return _authentication.authenticate(
      localizedReason: 'Confirm your identity to open VoyagerRent trip details.',
      options: const AuthenticationOptions(biometricOnly: true, stickyAuth: true),
    );
  }

  Future<String?> registerPushNotifications() async {
    final settings = await _messaging.requestPermission(alert: true, badge: true, sound: true);
    if (settings.authorizationStatus == AuthorizationStatus.denied) return null;
    return _messaging.getToken();
  }

  Future<File?> captureDocument() async {
    final image = await _imagePicker.pickImage(source: ImageSource.camera, imageQuality: 88, requestFullMetadata: false);
    return image == null ? null : File(image.path);
  }
}
