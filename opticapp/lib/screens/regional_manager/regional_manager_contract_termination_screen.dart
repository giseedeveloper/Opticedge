import 'package:flutter/material.dart';

import '../shared/contract_termination_content.dart';
import 'regional_manager_scaffold.dart';

class RegionalManagerContractTerminationScreen extends StatelessWidget {
  const RegionalManagerContractTerminationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const RegionalManagerScaffold(
      title: 'End contract',
      body: ContractTerminationContent(apiPrefix: 'regional-manager'),
    );
  }
}
