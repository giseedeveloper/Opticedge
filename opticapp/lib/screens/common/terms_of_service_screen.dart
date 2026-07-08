import 'package:flutter/material.dart';

class TermsOfServiceScreen extends StatelessWidget {
  const TermsOfServiceScreen({super.key});

  static const String effectiveDate = 'July 8, 2026';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final titleStyle = theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700);
    final bodyStyle = theme.textTheme.bodyMedium?.copyWith(height: 1.5, color: Colors.grey.shade800);
    final subtitleStyle = theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Terms of Service'),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        children: [
          Text(
            'Terms of Service',
            style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 6),
          Text('Effective date: $effectiveDate', style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey.shade600)),
          const SizedBox(height: 20),
          Text('Welcome to OpticEdge Africa Limited ("OpticEdgeAfrica", "we", "our", or "us").', style: bodyStyle),
          const SizedBox(height: 12),
          Text(
            'These Terms of Service ("Terms") govern your access to and use of the OpticEdge Africa '
            'Limited website, mobile application, and software platform (collectively, the "Platform"). By '
            'registering for an account or using the Platform, you agree to be bound by these Terms.',
            style: bodyStyle,
          ),
          const SizedBox(height: 12),
          Text(
            'If you do not agree with these Terms, you must not access or use the Platform.',
            style: bodyStyle,
          ),
          _section(
            title: '1. About the Platform',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'OpticEdge Africa Limited provides a cloud-based platform that enables businesses involved in '
                  'mobile device financing and sales to manage their operations digitally.',
              'The Platform includes features that may allow subscribers to:',
            ],
            bullets: const [
              'Manage inventory and stock',
              'Register and manage agents',
              'Manage team leaders and managers',
              'Track financing applications',
              'Monitor customer portfolios',
              'View reports and analytics',
              'Manage branch operations',
              'Coordinate business workflows',
              'Access the Platform through both web and mobile applications',
            ],
            closing: 'The specific features available may depend on your subscription plan.',
          ),
          _section(
            title: '2. Eligibility',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const ['To use the Platform, you must:'],
            bullets: const [
              'Be at least 18 years of age.',
              'Have the legal authority to act on behalf of your organization if registering a business account.',
              'Provide accurate and complete information during registration.',
              'Keep your account information up to date.',
            ],
          ),
          _section(
            title: '3. User Accounts',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Each user is responsible for maintaining the confidentiality of their account credentials.',
              'You agree to:',
            ],
            bullets: const [
              'Keep your password secure.',
              'Not share your login credentials.',
              'Notify us immediately of any unauthorized use of your account.',
              'Be responsible for all activities performed under your account.',
            ],
            closing:
                'OpticEdge Africa Limited is not responsible for losses resulting from unauthorized access '
                'caused by your failure to safeguard your credentials.',
          ),
          _section(
            title: '4. Subscription Services',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Certain features of the Platform require an active subscription.',
              'Subscribers agree that:',
            ],
            bullets: const [
              'Subscription fees are payable according to the selected plan.',
              'Fees are subject to applicable taxes.',
              'Failure to pay may result in suspension or termination of services.',
              'Subscription plans and pricing may change with reasonable notice.',
            ],
            closing: 'Unless otherwise agreed in writing, subscription fees are non-refundable.',
          ),
          _section(
            title: '5. Acceptable Use',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'You agree to use the Platform only for lawful business purposes.',
              'You must not:',
            ],
            bullets: const [
              'Use the Platform for fraudulent or illegal activities.',
              'Attempt to gain unauthorized access to our systems.',
              'Upload malicious software or harmful code.',
              'Interfere with the operation or security of the Platform.',
              'Circumvent security measures.',
              'Reverse engineer, decompile, or attempt to extract the Platform\'s source code except where permitted by applicable law.',
              'Use the Platform to violate the rights of others.',
            ],
            closing: 'Violation of these Terms may result in suspension or termination of your account.',
          ),
          _section(
            title: '6. Customer Data',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Subscribers retain ownership of the data they upload to the Platform.',
              'By using the Platform, you grant OpticEdge Africa Limited permission to process, store, back '
                  'up, and transmit your data solely for the purpose of providing the services.',
              'Subscribers are responsible for ensuring they have the legal right to collect and process the '
                  'information they upload.',
            ],
          ),
          _section(
            title: '7. Privacy',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Your use of the Platform is also governed by our Privacy Policy.',
              'By using the Platform, you acknowledge that your information will be handled in accordance '
                  'with our Privacy Policy.',
            ],
            child: Padding(
              padding: const EdgeInsets.only(top: 8),
              child: TextButton(
                onPressed: () => Navigator.pushNamed(context, '/privacy'),
                child: const Text('View Privacy Policy'),
              ),
            ),
          ),
          _section(
            title: '8. Intellectual Property',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'The Platform, including its software, logos, branding, content, graphics, databases, and '
                  'technology, is owned by OpticEdge Africa Limited or its licensors and is protected by applicable '
                  'intellectual property laws.',
              'These Terms do not grant ownership of any intellectual property rights.',
              'You may not copy, modify, distribute, reproduce, sell, or create derivative works from the '
                  'Platform without our prior written permission.',
            ],
          ),
          _section(
            title: '9. Service Availability',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'We strive to provide reliable services but do not guarantee uninterrupted or error-free operation.',
              'The Platform may occasionally be unavailable due to:',
            ],
            bullets: const [
              'Scheduled maintenance',
              'Software updates',
              'Network interruptions',
              'Security incidents',
              'Circumstances beyond our reasonable control',
            ],
            closing: 'We will make reasonable efforts to minimize service disruptions.',
          ),
          _section(
            title: '10. Third-Party Services',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'The Platform may integrate with third-party services or providers.',
              'OpticEdge Africa Limited is not responsible for the availability, functionality, or policies of '
                  'third-party services.',
              'Use of third-party services is subject to their respective terms and policies.',
            ],
          ),
          _section(
            title: '11. Limitation of Liability',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'To the fullest extent permitted by law, OpticEdge Africa Limited shall not be liable for:',
            ],
            bullets: const [
              'Indirect or consequential losses',
              'Loss of profits',
              'Loss of business opportunities',
              'Loss of data',
              'Business interruption',
              'Special or incidental damages',
            ],
            closing:
                'Our total liability arising from the use of the Platform shall not exceed the amount paid by the '
                'subscriber for the services during the twelve (12) months preceding the event giving rise to the '
                'claim.\n\nNothing in these Terms excludes liability where such exclusion is prohibited by law.',
          ),
          _section(
            title: '12. Indemnification',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'You agree to indemnify and hold harmless OpticEdgeAfrica, its employees, directors, affiliates, '
                  'and partners from any claims, losses, liabilities, damages, or expenses arising from:',
            ],
            bullets: const [
              'Your misuse of the Platform.',
              'Your violation of these Terms.',
              'Your violation of applicable laws or the rights of any third party.',
            ],
          ),
          _section(
            title: '13. Suspension and Termination',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const ['We may suspend or terminate your access if:'],
            bullets: const [
              'You breach these Terms.',
              'You fail to pay subscription fees.',
              'Your activities threaten the security or integrity of the Platform.',
              'We are required to do so by law.',
            ],
            closing:
                'You may terminate your account at any time by contacting us.\n\n'
                'Termination does not relieve you of any outstanding payment obligations.',
          ),
          _section(
            title: '14. Confidentiality',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'Both parties agree to protect confidential business information obtained through the use of the Platform.',
              'Neither party shall disclose confidential information except where required by law or with prior written consent.',
            ],
          ),
          _section(
            title: '15. Modifications to the Platform',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'We may improve, modify, update, or discontinue features of the Platform from time to time.',
              'Where practical, we will provide reasonable notice of significant changes.',
            ],
          ),
          _section(
            title: '16. Changes to These Terms',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'We may revise these Terms periodically.',
              'The updated version will be published on our website with the revised Effective Date.',
              'Continued use of the Platform after changes become effective constitutes acceptance of the updated Terms.',
            ],
          ),
          _section(
            title: '17. Governing Law',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'These Terms shall be governed by and interpreted in accordance with the laws of the United '
                  'Republic of Tanzania, without regard to its conflict of law principles.',
              'Any disputes arising from these Terms shall be subject to the jurisdiction of the competent '
                  'courts of Tanzania unless otherwise agreed in writing.',
            ],
          ),
          _section(
            title: '18. Contact Information',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'For questions regarding these Terms, please contact us:',
              'OpticEdgeAfrica',
              'Website: https://opticedgeafrica.net',
              'Email: support@opticedgeafrica.net',
              'Email: legal@opticedgeafrica.net',
            ],
          ),
          _section(
            title: '19. Entire Agreement',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'These Terms, together with our Privacy Policy and any additional agreements entered into '
                  'between you and OpticEdgeAfrica, constitute the entire agreement governing your use of the '
                  'Platform and supersede all prior understandings relating to the Platform.',
            ],
          ),
          _section(
            title: '20. Acceptance of Terms',
            titleStyle: titleStyle,
            bodyStyle: bodyStyle,
            subtitleStyle: subtitleStyle,
            paragraphs: const [
              'By creating an account, subscribing to the Platform, accessing the website, or using the mobile '
                  'application, you acknowledge that you have read, understood, and agree to be bound by these '
                  'Terms of Service.',
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'By accessing or using the OpticEdge Africa Limited Platform, you acknowledge that you have '
            'read and understood these Terms of Service.',
            style: bodyStyle?.copyWith(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}

Widget _section({
  required String title,
  required TextStyle? titleStyle,
  required TextStyle? bodyStyle,
  required TextStyle? subtitleStyle,
  List<String> paragraphs = const [],
  List<String> bullets = const [],
  String? closing,
  Widget? child,
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
        if (bullets.isNotEmpty) ..._bulletList(bullets, bodyStyle),
        if (closing != null) ...[
          const SizedBox(height: 8),
          Text(closing, style: bodyStyle),
        ],
        if (child != null) child,
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
