import 'package:flutter/material.dart';

import '../shared/contract_termination_content.dart';
import 'team_leader_scaffold.dart';

class TeamLeaderContractTerminationScreen extends StatelessWidget {
  const TeamLeaderContractTerminationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const TeamLeaderScaffold(
      title: 'End contract',
      body: ContractTerminationContent(apiPrefix: 'team-leader'),
    );
  }
}
