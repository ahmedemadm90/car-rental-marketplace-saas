import 'package:flutter_test/flutter_test.dart';
import 'package:voyagerrent_customer/app/customer_app.dart';

void main() {
  testWidgets('customer application renders primary journey navigation', (tester) async {
    await tester.pumpWidget(const CustomerApp());

    expect(find.text('Find the right drive'), findsOneWidget);
    expect(find.text('Explore'), findsOneWidget);
    expect(find.text('Trips'), findsOneWidget);
    expect(find.text('Wallet'), findsOneWidget);
  });
}
