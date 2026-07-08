import 'package:flutter/material.dart';

import '../shared/vendor_requests_content.dart';
import 'regional_manager_scaffold.dart';

class RegionalManagerVendorRequestsScreen extends StatelessWidget {
  const RegionalManagerVendorRequestsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const RegionalManagerScaffold(
      title: 'Vendor requests',
      body: VendorRequestsContent(),
    );
  }
}
