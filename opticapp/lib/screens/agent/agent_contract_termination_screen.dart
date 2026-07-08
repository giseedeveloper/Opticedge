import 'package:flutter/material.dart';

import '../agent/agent_scaffold.dart';
import '../shared/contract_termination_content.dart';

class AgentContractTerminationScreen extends StatelessWidget {
  const AgentContractTerminationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const AgentScaffold(
      title: 'End contract',
      body: ContractTerminationContent(apiPrefix: 'agent'),
    );
  }
}
