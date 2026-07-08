import 'package:flutter/material.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  static const String effectiveDate = 'July 8, 2026';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final titleStyle = theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700);
    final bodyStyle = theme.textTheme.bodyMedium?.copyWith(height: 1.5, color: Colors.grey.shade800);
    final subtitleStyle = theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Privacy Policy'),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        children: [
          Text(
            'Privacy Policy',
            style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 6),
          Text('Effective date: $effectiveDate', style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey.shade600)),
          const SizedBox(height: 20),
          Text(
            'OpticEdge Africa Limited ("OpticEdgeAfrica", "we", "our", or "us") is committed to protecting your '
            'privacy and safeguarding the information entrusted to us. This Privacy Policy explains how we '
            'collect, use, disclose, and protect personal information when you use our website, mobile '
            'application, and software platform (collectively, the "Services").',
            style: bodyStyle,
          ),
          const SizedBox(height: 12),
          Text(
            'By accessing or using our Services, you agree to the collection and use of information in '
            'accordance with this Privacy Policy.',
            style: bodyStyle,
          ),
          _section(
            title: '1. About Our Services',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'OpticEdge Africa Limited provides a cloud-based platform that enables businesses involved in '
                  'mobile device financing and retail operations to manage their inventory, sales, financing '
                  'activities, agents, team leaders, managers, and related business operations through a '
                  'centralized system.',
              'This Privacy Policy applies to:',
            ],
            bullets: const [
              'Our website',
              'Our mobile application',
              'Our web platform',
              'Any services offered by OpticEdgeAfrica',
            ],
          ),
          _section(
            title: '2. Information We Collect',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Depending on how you use our Services, we may collect the following information.',
            ],
            subsections: const [
              _PolicySubsection(
                title: 'Business Information',
                intro: 'When an organization registers for our Services, we may collect:',
                bullets: [
                  'Company name',
                  'Business registration information',
                  'Business address',
                  'Contact details',
                  'Billing information',
                ],
              ),
              _PolicySubsection(
                title: 'Account Information',
                intro: 'When users create accounts, we may collect:',
                bullets: [
                  'Full name',
                  'Email address',
                  'Phone number',
                  'Job title',
                  'Username',
                  'Password (stored securely in encrypted form)',
                ],
              ),
              _PolicySubsection(
                title: 'User Activity',
                intro: 'We may collect information relating to how users interact with the platform, including:',
                bullets: [
                  'Login history',
                  'Actions performed within the platform',
                  'Device information',
                  'Browser information',
                  'IP address',
                  'Operating system',
                  'Application version',
                ],
              ),
              _PolicySubsection(
                title: 'Customer and Business Data',
                intro:
                    'Our platform allows subscribed organizations to manage their business operations. Information '
                    'entered into the platform by our customers may include:',
                bullets: [
                  'Customer records',
                  'Sales information',
                  'Financing records',
                  'Inventory information',
                  'Agent information',
                  'Team leader information',
                  'Branch information',
                  'Transaction history',
                  'Reports and analytics',
                ],
                closing:
                    'This information remains under the control of the organization using our platform.',
              ),
            ],
          ),
          _section(
            title: '3. How We Use Your Information',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const ['We use collected information to:'],
            bullets: const [
              'Provide and maintain our Services',
              'Create and manage user accounts',
              'Authenticate users',
              'Improve platform performance',
              'Deliver customer support',
              'Generate reports and analytics',
              'Communicate important service updates',
              'Process subscriptions and billing',
              'Detect fraud and unauthorized activity',
              'Maintain platform security',
              'Comply with legal obligations',
            ],
          ),
          _section(
            title: '4. Information Shared by Our Customers',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Businesses using our platform are responsible for ensuring they have obtained the necessary '
                  'permissions to collect and process the information they enter into our Services.',
              'OpticEdge Africa Limited processes such information solely for the purpose of providing the '
                  'Services to our customers.',
            ],
          ),
          _section(
            title: '5. Information Sharing',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'We do not sell personal information.',
              'We may share information only when necessary with:',
            ],
            bullets: const [
              'Trusted cloud hosting providers',
              'Payment processors',
              'Customer support providers',
              'Professional advisers',
              'Government or regulatory authorities where legally required',
            ],
            closing: 'All third-party service providers are expected to maintain appropriate security standards.',
          ),
          _section(
            title: '6. Data Security',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Protecting your information is important to us.',
              'We implement reasonable administrative, technical, and organizational safeguards designed to '
                  'protect information against unauthorized access, disclosure, alteration, or destruction.',
              'These measures include:',
            ],
            bullets: const [
              'Secure encrypted connections (HTTPS)',
              'Access controls',
              'Authentication mechanisms',
              'Regular software updates',
              'Activity logging',
              'Secure cloud infrastructure',
            ],
            closing: 'While we strive to protect your information, no system can guarantee absolute security.',
          ),
          _section(
            title: '7. Data Retention',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const ['We retain information only for as long as necessary to:'],
            bullets: const [
              'Provide our Services',
              'Meet legal obligations',
              'Resolve disputes',
              'Enforce our agreements',
            ],
            closing:
                'When information is no longer required, it will be securely deleted or anonymized where appropriate.',
          ),
          _section(
            title: '8. Your Rights',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Depending on applicable laws, you may have the right to:',
            ],
            bullets: const [
              'Access your personal information',
              'Correct inaccurate information',
              'Request deletion of your information',
              'Restrict certain processing activities',
              'Receive a copy of your information',
              'Withdraw consent where applicable',
            ],
            closing: 'Requests may be submitted using the contact information below.',
          ),
          _section(
            title: "9. Children's Privacy",
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Our Services are intended for business use and are not directed toward individuals under the age of 18.',
              'We do not knowingly collect personal information from children.',
            ],
          ),
          _section(
            title: '10. Cookies and Similar Technologies',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const ['Our website and platform may use cookies and similar technologies to:'],
            bullets: const [
              'Keep users signed in',
              'Improve user experience',
              'Measure platform performance',
              'Enhance security',
            ],
            closing: 'Users may control cookies through their browser settings.',
          ),
          _section(
            title: '11. Third-Party Services',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Our Services may contain links to third-party websites or services.',
              'We are not responsible for the privacy practices or content of third-party websites. '
                  'Users should review the privacy policies of those services independently.',
            ],
          ),
          _section(
            title: '12. International Data Transfers',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Your information may be stored or processed in countries other than your own where our '
                  'service providers operate appropriate infrastructure.',
              'Where applicable, we take reasonable steps to ensure appropriate safeguards are in place to '
                  'protect personal information.',
            ],
          ),
          _section(
            title: '13. Changes to This Privacy Policy',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'We may update this Privacy Policy from time to time.',
              'When significant changes are made, we will revise the Effective Date and, where appropriate, '
                  'notify users through our website or platform.',
              'Continued use of the Services after changes become effective constitutes acceptance of the '
                  'updated Privacy Policy.',
            ],
          ),
          _section(
            title: '14. Contact Us',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'If you have questions about this Privacy Policy or how your information is handled, please '
                  'contact us through the contact information available on our website.',
              'Website: https://opticedgeafrica.net',
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'By accessing or using the OpticEdge Africa Limited Services, you acknowledge that you have '
            'read and understood this Privacy Policy.',
            style: bodyStyle?.copyWith(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}

class _PolicySubsection {
  const _PolicySubsection({
    required this.title,
    required this.intro,
    required this.bullets,
    this.closing,
  });

  final String title;
  final String intro;
  final List<String> bullets;
  final String? closing;
}

Widget _section({
  required String title,
  required TextStyle? titleStyle,
  required TextStyle? bodyStyle,
  required TextStyle? subtitleStyle,
  List<String> paragraphs = const [],
  List<String> bullets = const [],
  List<_PolicySubsection> subsections = const [],
  String? closing,
}) {
  return Padding(
    padding: const EdgeInsets.only(top: 24),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: titleStyle),
        const SizedBox(height: 10),
        for (final paragraph in paragraphs) ...[
          Text(paragraph, style: bodyStyle),
          const SizedBox(height: 10),
        ],
        for (final subsection in subsections) ...[
          Text(subsection.title, style: subtitleStyle),
          const SizedBox(height: 6),
          Text(subsection.intro, style: bodyStyle),
          const SizedBox(height: 8),
          ..._bulletList(subsection.bullets, bodyStyle),
          if (subsection.closing != null) ...[
            const SizedBox(height: 8),
            Text(subsection.closing!, style: bodyStyle),
          ],
          const SizedBox(height: 12),
        ],
        if (bullets.isNotEmpty) ..._bulletList(bullets, bodyStyle),
        if (closing != null) ...[
          const SizedBox(height: 8),
          Text(closing, style: bodyStyle),
        ],
      ],
    ),
  );
}

List<Widget> _bulletList(List<String> items, TextStyle? bodyStyle) {
  return items
      .map(
        (item) => Padding(
          padding: const EdgeInsets.only(bottom: 6, left: 4),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('•  ', style: bodyStyle),
              Expanded(child: Text(item, style: bodyStyle)),
            ],
          ),
        ),
      )
      .toList();
}
