import 'package:flutter_test/flutter_test.dart';
import 'package:voyagerrent_employee/main.dart';

void main() {
  testWidgets('employee application renders operational navigation', (tester) async {
    await tester.pumpWidget(const EmployeeApp());

    expect(find.text('Today’s tasks'), findsOneWidget);
    expect(find.text('Tasks'), findsOneWidget);
    expect(find.text('Fleet'), findsOneWidget);
    expect(find.text('Sync'), findsOneWidget);
  });
}
