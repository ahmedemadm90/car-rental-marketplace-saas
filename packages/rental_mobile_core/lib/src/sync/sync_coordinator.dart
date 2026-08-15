import 'package:connectivity_plus/connectivity_plus.dart';

import '../api/api_client.dart';
import '../offline/encrypted_operation_store.dart';

final class SyncCoordinator {
  SyncCoordinator({required ApiClient apiClient, required EncryptedOperationStore operationStore, Connectivity? connectivity})
      : _apiClient = apiClient,
        _operationStore = operationStore,
        _connectivity = connectivity ?? Connectivity();

  final ApiClient _apiClient;
  final EncryptedOperationStore _operationStore;
  final Connectivity _connectivity;

  Future<bool> syncPending() async {
    final states = await _connectivity.checkConnectivity();
    if (states.contains(ConnectivityResult.none)) return false;

    for (final operation in await _operationStore.pending()) {
      try {
        await _operationStore.markAttempted(operation.id);
        await _apiClient.postJson(
          '/api/v1/mobile/sync/${operation.type}',
          body: operation.payload,
          headers: {'Idempotency-Key': operation.id},
        );
        await _operationStore.markSucceeded(operation.id);
      } on ApiFailure catch (error) {
        if (error.statusCode != null && error.statusCode! >= 400 && error.statusCode! < 500 && error.statusCode != 429) {
          return false;
        }
        return false;
      }
    }
    return true;
  }
}
