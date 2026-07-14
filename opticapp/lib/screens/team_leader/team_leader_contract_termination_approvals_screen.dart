import 'package:flutter/material.dart';

import '../shared/major_contract_termination_approvals_content.dart';
import 'team_leader_scaffold.dart';

class TeamLeaderContractTerminationApprovalsScreen extends StatelessWidget {
  const TeamLeaderContractTerminationApprovalsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const TeamLeaderScaffold(
      title: 'Exit approvals',
      body: MajorContractTerminationApprovalsContent(apiPrefix: 'team-leader'),
    );
  }
}
