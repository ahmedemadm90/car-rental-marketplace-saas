import 'package:dio/dio.dart';
import 'package:uuid/uuid.dart';

abstract interface class TokenStore {
  Future<String?> readAccessToken();
  Future<void> clearSession();
}

final class ApiFailure implements Exception {
  const ApiFailure({required this.statusCode, required this.message, this.details});

  final int? statusCode;
  final String message;
  final Map<String, dynamic>? details;

  @override
  String toString() => 'ApiFailure($statusCode): $message';
}

final class ApiClient {
  ApiClient({required String baseUrl, required TokenStore tokenStore, void Function()? onUnauthorized})
      : _tokenStore = tokenStore,
        _onUnauthorized = onUnauthorized,
        _dio = Dio(BaseOptions(
          baseUrl: baseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 30),
          sendTimeout: const Duration(seconds: 30),
          headers: const {'Accept': 'application/json', 'Content-Type': 'application/json'},
        )) {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _tokenStore.readAccessToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        options.headers['X-Request-Id'] = const Uuid().v7();
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 401) {
          await _tokenStore.clearSession();
          _onUnauthorized?.call();
        }
        handler.next(error);
      },
    ));
  }

  final Dio _dio;
  final TokenStore _tokenStore;
  final void Function()? _onUnauthorized;

  Future<Map<String, dynamic>> getJson(String path, {Map<String, dynamic>? queryParameters}) =>
      _perform(() => _dio.get<Map<String, dynamic>>(path, queryParameters: queryParameters));

  Future<Map<String, dynamic>> postJson(String path, {Map<String, dynamic>? body, Map<String, dynamic>? headers}) =>
      _perform(() => _dio.post<Map<String, dynamic>>(path, data: body, options: Options(headers: headers)));

  Future<Map<String, dynamic>> _perform(Future<Response<Map<String, dynamic>>> Function() request) async {
    try {
      final response = await request();
      return response.data ?? const <String, dynamic>{};
    } on DioException catch (error) {
      final data = error.response?.data;
      final map = data is Map<String, dynamic> ? data : null;
      throw ApiFailure(
        statusCode: error.response?.statusCode,
        message: map?['detail']?.toString() ?? map?['message']?.toString() ?? error.message ?? 'Network request failed.',
        details: map,
      );
    }
  }
}
