import 'package:flutter/material.dart';

final class CustomerHomeScreen extends StatefulWidget {
  const CustomerHomeScreen({super.key});

  @override
  State<CustomerHomeScreen> createState() => _CustomerHomeScreenState();
}

final class _CustomerHomeScreenState extends State<CustomerHomeScreen> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    const pages = [
      _MarketplacePage(),
      _TripsPage(),
      _WalletPage(),
      _AccountPage(),
    ];
    return Scaffold(
      body: SafeArea(child: pages[_selectedIndex]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) => setState(() => _selectedIndex = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.explore_outlined), selectedIcon: Icon(Icons.explore), label: 'Explore'),
          NavigationDestination(icon: Icon(Icons.directions_car_outlined), selectedIcon: Icon(Icons.directions_car), label: 'Trips'),
          NavigationDestination(icon: Icon(Icons.account_balance_wallet_outlined), selectedIcon: Icon(Icons.account_balance_wallet), label: 'Wallet'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Account'),
        ],
      ),
    );
  }
}

final class _MarketplacePage extends StatelessWidget {
  const _MarketplacePage();

  @override
  Widget build(BuildContext context) => ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('Find the right drive', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Text('Compare verified providers and reserve with transparent total pricing.', style: Theme.of(context).textTheme.bodyLarge),
          const SizedBox(height: 24),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                const ListTile(leading: Icon(Icons.location_on_outlined), title: Text('Pick-up location'), subtitle: Text('Select a branch or map location')),
                const Divider(),
                const ListTile(leading: Icon(Icons.calendar_month_outlined), title: Text('Journey dates'), subtitle: Text('Choose pick-up and return times')),
                const SizedBox(height: 12),
                FilledButton.icon(onPressed: () {}, icon: const Icon(Icons.search), label: const Text('Search available cars')),
              ]),
            ),
          ),
          const SizedBox(height: 28),
          Text('Why book with VoyagerRent', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          const _FeatureRow(icon: Icons.verified_user_outlined, title: 'Verified rental partners', body: 'Know who you are booking with before confirming.'),
          const _FeatureRow(icon: Icons.receipt_long_outlined, title: 'Clear total prices', body: 'See rates, taxes, fees, and deposits together.'),
          const _FeatureRow(icon: Icons.support_agent_outlined, title: 'Trip support in one place', body: 'Manage documents, updates, and conversations securely.'),
        ],
      );
}

final class _TripsPage extends StatelessWidget {
  const _TripsPage();

  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.directions_car_outlined, size: 56), const SizedBox(height: 16), Text('Your trips will appear here', style: Theme.of(context).textTheme.titleLarge), const SizedBox(height: 8), const Text('Confirmed reservations remain available offline with their key pickup details and signed agreement.')])));
}

final class _WalletPage extends StatelessWidget {
  const _WalletPage();

  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(20), children: [Text('Wallet', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 16), Card(child: ListTile(leading: const Icon(Icons.account_balance_wallet), title: const Text('Available credit'), trailing: Text('0.00', style: Theme.of(context).textTheme.titleLarge))), const SizedBox(height: 10), const Text('Credits, refunds, and payment receipts are recorded here as an immutable transaction history.')]);
}

final class _AccountPage extends StatelessWidget {
  const _AccountPage();

  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(20), children: [Text('Account & security', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 16), const ListTile(leading: Icon(Icons.badge_outlined), title: Text('Driving licence and identity'), subtitle: Text('Upload and review verification status')), const ListTile(leading: Icon(Icons.fingerprint), title: Text('Biometric unlock'), subtitle: Text('Protect local trip details on this device')), const ListTile(leading: Icon(Icons.notifications_outlined), title: Text('Notifications'), subtitle: Text('Manage booking and trip updates')), const ListTile(leading: Icon(Icons.language), title: Text('Language'), subtitle: Text('English / العربية'))]);
}

final class _FeatureRow extends StatelessWidget {
  const _FeatureRow({required this.icon, required this.title, required this.body});
  final IconData icon;
  final String title;
  final String body;

  @override
  Widget build(BuildContext context) => Padding(padding: const EdgeInsets.only(bottom: 16), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [CircleAvatar(child: Icon(icon)), const SizedBox(width: 12), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)), const SizedBox(height: 3), Text(body)]))]));
}
