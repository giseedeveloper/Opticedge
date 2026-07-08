import 'package:flutter/material.dart';

import '../shared/vendor_requests_content.dart';
import 'team_leader_scaffold.dart';

class TeamLeaderVendorRequestsScreen extends StatelessWidget {
  const TeamLeaderVendorRequestsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const TeamLeaderScaffold(
      title: 'Vendor requests',
      body: VendorRequestsContent(),
    );
  }
}
