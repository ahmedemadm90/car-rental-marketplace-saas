import 'dart:convert';

import 'package:path/path.dart' as path;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite_sqlcipher/sqflite.dart';
import 'package:uuid/uuid.dart';

final class OfflineOperation {
  const OfflineOperation({
    required this.id,
    required this.type,
    required this.payload,
    required this.createdAt,
    required this.attempts,
  });

  final String id;
  final String type;
  final Map<String, dynamic> payload;
  final DateTime createdAt;
  final int attempts;
}

final class EncryptedOperationStore {
  EncryptedOperationStore({required String encryptionKey}) : _encryptionKey = encryptionKey;

  final String _encryptionKey;
  Database? _database;

  Future<void> open() async {
    if (_database != null) return;
    final directory = await getApplicationDocumentsDirectory();
    _database = await openDatabase(
      path.join(directory.path, 'voyagerrent.offline.db'),
      password: _encryptionKey,
      version: 1,
      onCreate: (database, version) async {
        await database.execute('''
          CREATE TABLE pending_operations (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            payload TEXT NOT NULL,
            created_at INTEGER NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0
          )
        ''');
      },
    );
  }

  Future<OfflineOperation> enqueue(String type, Map<String, dynamic> payload) async {
    await open();
    final operation = OfflineOperation(
      id: const Uuid().v7(),
      type: type,
      payload: payload,
      createdAt: DateTime.now().toUtc(),
      attempts: 0,
    );
    await _database!.insert('pending_operations', {
      'id': operation.id,
      'type': operation.type,
      'payload': jsonEncode(operation.payload),
      'created_at': operation.createdAt.millisecondsSinceEpoch,
      'attempts': operation.attempts,
    });
    return operation;
  }

  Future<List<OfflineOperation>> pending() async {
    await open();
    final rows = await _database!.query('pending_operations', orderBy: 'created_at ASC');
    return rows
        .map((row) => OfflineOperation(
              id: row['id']! as String,
              type: row['type']! as String,
              payload: jsonDecode(row['payload']! as String) as Map<String, dynamic>,
              createdAt: DateTime.fromMillisecondsSinceEpoch(row['created_at']! as int, isUtc: true),
              attempts: row['attempts']! as int,
            ))
        .toList(growable: false);
  }

  Future<void> markSucceeded(String operationId) async {
    await open();
    await _database!.delete('pending_operations', where: 'id = ?', whereArgs: [operationId]);
  }

  Future<void> markAttempted(String operationId) async {
    await open();
    await _database!.rawUpdate('UPDATE pending_operations SET attempts = attempts + 1 WHERE id = ?', [operationId]);
  }

  Future<void> close() async {
    await _database?.close();
    _database = null;
  }
}
