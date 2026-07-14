import 'package:flutter/material.dart';

import '../shared/major_contract_termination_approvals_content.dart';
import 'regional_manager_scaffold.dart';

class RegionalManagerContractTerminationApprovalsScreen extends StatelessWidget {
  const RegionalManagerContractTerminationApprovalsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const RegionalManagerScaffold(
      title: 'Exit approvals',
      body: MajorContractTerminationApprovalsContent(apiPrefix: 'regional-manager'),
    );
  }
}
