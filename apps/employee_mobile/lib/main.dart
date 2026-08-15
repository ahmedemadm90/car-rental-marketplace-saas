import 'package:flutter/material.dart';

import 'features/operations/presentation/employee_workspace_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const EmployeeApp());
}

final class EmployeeApp extends StatelessWidget {
  const EmployeeApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'VoyagerRent Operations',
        debugShowCheckedModeBanner: false,
        themeMode: ThemeMode.system,
        theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0F766E), brightness: Brightness.light), useMaterial3: true),
        darkTheme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF2DD4BF), brightness: Brightness.dark), useMaterial3: true),
        home: const EmployeeWorkspaceScreen(),
      );
}
