import 'package:flutter/material.dart';

import '../agent/agent_scaffold.dart';
import '../shared/vendor_requests_content.dart';

class AgentVendorRequestsScreen extends StatelessWidget {
  const AgentVendorRequestsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const AgentScaffold(
      title: 'Vendor requests',
      body: VendorRequestsContent(),
    );
  }
}
