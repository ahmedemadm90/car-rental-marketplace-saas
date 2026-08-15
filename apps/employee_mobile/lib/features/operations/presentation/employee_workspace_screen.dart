import 'package:flutter/material.dart';

final class EmployeeWorkspaceScreen extends StatefulWidget {
  const EmployeeWorkspaceScreen({super.key});

  @override
  State<EmployeeWorkspaceScreen> createState() => _EmployeeWorkspaceScreenState();
}

final class _EmployeeWorkspaceScreenState extends State<EmployeeWorkspaceScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    const pages = [_TasksPage(), _FleetPage(), _SyncPage(), _ProfilePage()];
    return Scaffold(
      appBar: AppBar(title: const Text('Operations'), actions: [IconButton(onPressed: () {}, icon: const Icon(Icons.qr_code_scanner), tooltip: 'Scan vehicle or reservation QR code'), IconButton(onPressed: () {}, icon: const Icon(Icons.notifications_outlined), tooltip: 'Notifications')]),
      body: SafeArea(child: pages[_tab]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (index) => setState(() => _tab = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.task_alt_outlined), selectedIcon: Icon(Icons.task_alt), label: 'Tasks'),
          NavigationDestination(icon: Icon(Icons.directions_car_outlined), selectedIcon: Icon(Icons.directions_car), label: 'Fleet'),
          NavigationDestination(icon: Icon(Icons.sync_outlined), selectedIcon: Icon(Icons.sync), label: 'Sync'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}

final class _TasksPage extends StatelessWidget {
  const _TasksPage();

  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(20), children: [Text('Today’s tasks', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 8), const Text('Prioritized branch handovers and inspections remain usable offline.'), const SizedBox(height: 20), const _OperationTask(status: 'Pickup due', title: 'Reservation VR-8R29PT', detail: '10:30 · Central Branch · Premium SUV'), const _OperationTask(status: 'Return due', title: 'Reservation VR-1D72MK', detail: '12:15 · Airport Branch · Economy Sedan'), const _OperationTask(status: 'Inspection', title: 'Vehicle FLEET-482', detail: 'Complete condition review and odometer capture')]);
}

final class _FleetPage extends StatelessWidget {
  const _FleetPage();

  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(20), children: [Text('Fleet operations', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 16), const ListTile(leading: Icon(Icons.qr_code_2), title: Text('Scan vehicle QR'), subtitle: Text('Retrieve current status and assigned work')), const ListTile(leading: Icon(Icons.camera_alt_outlined), title: Text('Capture inspection evidence'), subtitle: Text('Record timestamped photos and condition checklist')), const ListTile(leading: Icon(Icons.report_problem_outlined), title: Text('Report damage'), subtitle: Text('Document severity, location, photos, and cost estimate')), const ListTile(leading: Icon(Icons.build_outlined), title: Text('Maintenance queue'), subtitle: Text('Review service blocks and vehicle availability'))]);
}

final class _SyncPage extends StatelessWidget {
  const _SyncPage();

  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_done_outlined, size: 56), const SizedBox(height: 16), Text('Offline synchronization', style: Theme.of(context).textTheme.titleLarge), const SizedBox(height: 8), const Text('Queued handovers, inspections, photos, and damage reports are encrypted locally and replayed with idempotent operation IDs when a connection becomes available.', textAlign: TextAlign.center)])));
}

final class _ProfilePage extends StatelessWidget {
  const _ProfilePage();

  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(20), children: [Text('Employee profile', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 16), const ListTile(leading: Icon(Icons.fingerprint), title: Text('Biometric unlock'), subtitle: Text('Require biometrics before restoring an active session')), const ListTile(leading: Icon(Icons.location_on_outlined), title: Text('Location permissions'), subtitle: Text('Used only for handover and inspection evidence')), const ListTile(leading: Icon(Icons.lock_outline), title: Text('Secure device'), subtitle: Text('Sign out and revoke this device if it is no longer assigned to you'))]);
}

final class _OperationTask extends StatelessWidget {
  const _OperationTask({required this.status, required this.title, required this.detail});
  final String status;
  final String title;
  final String detail;

  @override
  Widget build(BuildContext context) => Card(child: ListTile(leading: const CircleAvatar(child: Icon(Icons.directions_car)), title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)), subtitle: Text(detail), trailing: Chip(label: Text(status))));
}
