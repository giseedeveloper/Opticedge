import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:provider/provider.dart';
import '../api/auth_api.dart';
import '../api/client.dart';
import '../providers/notifications_provider.dart';
import '../providers/pending_request_counts_provider.dart';
import '../api/guest_api.dart';
import '../services/website_google_auth_service.dart';
import '../theme/app_theme.dart';
import '../utils/auth_navigation.dart';

const Color _authBgTop = Color(0xFFFFF8F2);
const Color _authBgBottom = Color(0xFFF3F4F6);
const Color _authTitle = Color(0xFF1A1D21);
const Color _authMuted = Color(0xFF6B7280);
const String _kAppIconAsset = 'assets/icons/app_icon.png';
const String _kGoogleLogoAsset = 'assets/icons/google_logo.svg';

enum _AuthView { signIn, signUp, signUpAgent, forgot }

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _signInFormKey = GlobalKey<FormState>();
  final _signUpFormKey = GlobalKey<FormState>();
  final _agentFormKey = GlobalKey<FormState>();
  final _forgotFormKey = GlobalKey<FormState>();

  final _signInEmailController = TextEditingController();
  final _signInPasswordController = TextEditingController();

  final _signUpNameController = TextEditingController();
  final _signUpEmailController = TextEditingController();
  final _signUpPasswordController = TextEditingController();

  final _agentNameController = TextEditingController();
  final _agentEmailController = TextEditingController();
  final _agentPhoneController = TextEditingController();
  final _agentPasswordController = TextEditingController();

  final _forgotEmailController = TextEditingController();

  _AuthView _view = _AuthView.signIn;
  bool _loading = false;
  String? _error;
  bool _obscureSignInPassword = true;
  bool _obscureSignUpPassword = true;
  bool _obscureAgentPassword = true;

  Color _brandPrimary(BuildContext context) => Theme.of(context).colorScheme.primary;

  Color _brandPrimaryPressed(BuildContext context) =>
      Color.lerp(_brandPrimary(context), Colors.black, 0.12)!;

  String _friendlyAuthError(Object e, {bool isLogin = false}) {
    final msg = e.toString().replaceFirst('Exception: ', '').toLowerCase();
    if (msg.contains('401') ||
        msg.contains('invalid') ||
        msg.contains('credential') ||
        msg.contains('unauthorized')) {
      return isLogin
          ? 'Incorrect email or password. Please try again.'
          : 'Could not complete sign-in. Check your details and try again.';
    }
    if (msg.contains('network') ||
        msg.contains('socket') ||
        msg.contains('connection') ||
        msg.contains('failed host') ||
        msg.contains('host lookup')) {
      return 'Unable to connect. Check your internet connection and try again.';
    }
    if (msg.contains('timeout') || msg.contains('timed out')) {
      return 'Request timed out. Please try again.';
    }
    if (msg.contains('google') || msg.contains('404') || msg.contains('not found')) {
      return 'Google sign-in is not available right now. Try email and password instead.';
    }
    return isLogin ? 'Sign in failed. Please try again.' : 'Something went wrong. Please try again.';
  }

  Future<void> _completeAuthSuccess() async {
    if (!mounted) return;
    context.read<NotificationsProvider>().refreshSilently();
    context.read<PendingRequestCountsProvider>().refreshSilently();
    await navigateForStoredUser(context);
  }

  Future<void> _submitSignIn() async {
    setState(() {
      _error = null;
      _loading = true;
    });
    try {
      await login(_signInEmailController.text.trim(), _signInPasswordController.text);
      if (!mounted) return;
      await _completeAuthSuccess();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = _friendlyAuthError(e, isLogin: true);
        _loading = false;
      });
    }
  }

  Future<String?> _resolveGoogleAuthUrl() async {
    try {
      final config = await getPublicAuthConfig(mobile: true);
      final apiUrl = config['google_auth_url']?.toString().trim();
      if (apiUrl != null && apiUrl.isNotEmpty) {
        return apiUrl;
      }
    } catch (_) {}

    final webBase = await resolveWebBaseUrl();
    if (webBase.isEmpty) return null;
    return '$webBase/auth/google?mobile=1';
  }

  Future<void> _submitGoogleSignIn() async {
    final authUrl = await _resolveGoogleAuthUrl();
    if (authUrl == null || authUrl.isEmpty) {
      _snack('Google Sign-In is not configured on this server.');
      return;
    }

    setState(() {
      _error = null;
      _loading = true;
    });

    try {
      final token = await WebsiteGoogleAuthService.signInViaWebsite(authUrl);
      if (!mounted) return;
      if (token == null) {
        setState(() => _loading = false);
        return;
      }
      await completeGoogleWebAuth(token);
      if (!mounted) return;
      await _completeAuthSuccess();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = _friendlyAuthError(e);
        _loading = false;
      });
    }
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  void dispose() {
    _signInEmailController.dispose();
    _signInPasswordController.dispose();
    _signUpNameController.dispose();
    _signUpEmailController.dispose();
    _signUpPasswordController.dispose();
    _agentNameController.dispose();
    _agentEmailController.dispose();
    _agentPhoneController.dispose();
    _agentPasswordController.dispose();
    _forgotEmailController.dispose();
    super.dispose();
  }

  InputDecoration _fieldDecoration({
    required String hint,
    required Widget prefixIcon,
    Widget? suffixIcon,
  }) {
    const borderRadius = BorderRadius.all(Radius.circular(12));
    final primary = _brandPrimary(context);
    return InputDecoration(
      hintText: hint,
      prefixIcon: prefixIcon,
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: const OutlineInputBorder(borderRadius: borderRadius),
      enabledBorder: OutlineInputBorder(
        borderRadius: borderRadius,
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: borderRadius,
        borderSide: BorderSide(color: primary, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: borderRadius,
        borderSide: BorderSide(color: Colors.red.shade400),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: borderRadius,
        borderSide: BorderSide(color: Colors.red.shade600, width: 2),
      ),
    );
  }

  Widget _heroImage() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: _brandPrimary(context).withValues(alpha: 0.12),
            blurRadius: 20,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Image.asset(
        _kAppIconAsset,
        height: 72,
        width: 72,
        fit: BoxFit.contain,
        errorBuilder: (context, error, stackTrace) =>
            Icon(Icons.broken_image_outlined, size: 56, color: Colors.grey.shade400),
      ),
    );
  }

  Widget _linkButton({required String text, required VoidCallback onTap}) {
    return TextButton(
      onPressed: onTap,
      style: TextButton.styleFrom(
        foregroundColor: _brandPrimary(context),
        padding: EdgeInsets.zero,
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      ),
      child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600)),
    );
  }

  Widget _primaryButton({required String label, required VoidCallback? onPressed}) {
    final primary = _brandPrimary(context);
    final pressed = _brandPrimaryPressed(context);
    return FilledButton(
      onPressed: onPressed,
      style: FilledButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        disabledBackgroundColor: primary.withValues(alpha: 0.5),
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ).copyWith(
        overlayColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.pressed)) return pressed;
          return null;
        }),
      ),
      child: _loading
          ? const SizedBox(
              height: 24,
              width: 24,
              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
            )
          : Text(label),
    );
  }

  Widget _orDivider() {
    return Row(
      children: [
        Expanded(child: Divider(color: Colors.grey.shade300)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Text('or', style: TextStyle(color: _authMuted, fontSize: 13)),
        ),
        Expanded(child: Divider(color: Colors.grey.shade300)),
      ],
    );
  }

  Widget _authHeader({required String title, required String subtitle}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
                color: _authTitle,
              ),
        ),
        const SizedBox(height: 6),
        Text(
          subtitle,
          style: TextStyle(color: _authMuted, fontSize: 14, height: 1.35),
        ),
      ],
    );
  }

  Widget _errorBanner(String message) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.errorContainer.withValues(alpha: 0.35),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: Theme.of(context).colorScheme.error.withValues(alpha: 0.2),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.error_outline_rounded,
            size: 20,
            color: Theme.of(context).colorScheme.error,
          ),
          const SizedBox(width: 10),
          Expanded(child: Text(message, style: errorStyle())),
        ],
      ),
    );
  }

  Widget _googleLogo() {
    return SvgPicture.asset(
      _kGoogleLogoAsset,
      width: 20,
      height: 20,
      fit: BoxFit.contain,
      semanticsLabel: 'Google',
    );
  }

  Widget _googleSignInButton() {
    return OutlinedButton(
      onPressed: _loading ? null : _submitGoogleSignIn,
      style: OutlinedButton.styleFrom(
        foregroundColor: _authTitle,
        backgroundColor: Colors.white,
        minimumSize: const Size.fromHeight(52),
        side: BorderSide(color: Colors.grey.shade300),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          _googleLogo(),
          const SizedBox(width: 12),
          const Text('Continue with Google'),
        ],
      ),
    );
  }

  void _openPrivacyPolicy() {
    Navigator.pushNamed(context, '/privacy');
  }

  void _openTermsOfService() {
    Navigator.pushNamed(context, '/terms');
  }

  Widget _legalBlurb() {
    final linkStyle = TextStyle(
      color: _brandPrimary(context),
      fontWeight: FontWeight.w600,
      fontSize: 12,
    );
    return Text.rich(
      TextSpan(
        style: TextStyle(color: _authMuted, fontSize: 12, height: 1.4),
        children: [
          const TextSpan(text: 'By continuing, you agree to our '),
          WidgetSpan(
            alignment: PlaceholderAlignment.baseline,
            baseline: TextBaseline.alphabetic,
            child: GestureDetector(
              onTap: _openTermsOfService,
              child: Text('Terms & Conditions', style: linkStyle),
            ),
          ),
          const TextSpan(text: ' and '),
          WidgetSpan(
            alignment: PlaceholderAlignment.baseline,
            baseline: TextBaseline.alphabetic,
            child: GestureDetector(
              onTap: _openPrivacyPolicy,
              child: Text('Privacy Policy', style: linkStyle),
            ),
          ),
          const TextSpan(text: '.'),
        ],
      ),
    );
  }

  Widget _signInBody() {
    return Form(
      key: _signInFormKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _authHeader(
            title: 'Welcome back',
            subtitle: 'Sign in to continue to Opticedge',
          ),
          const SizedBox(height: 20),
          if (_error != null) ...[
            _errorBanner(_error!),
            const SizedBox(height: 18),
          ],
          TextFormField(
            controller: _signInEmailController,
            keyboardType: TextInputType.emailAddress,
            autofillHints: const [AutofillHints.email],
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Email address',
              prefixIcon: const Icon(Icons.mail_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your email' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _signInPasswordController,
            obscureText: _obscureSignInPassword,
            autofillHints: const [AutofillHints.password],
            textInputAction: TextInputAction.done,
            onFieldSubmitted: (_) {
              if (_signInFormKey.currentState!.validate()) _submitSignIn();
            },
            decoration: _fieldDecoration(
              hint: 'Password',
              prefixIcon: const Icon(Icons.lock_outline_rounded, size: 22, color: _authMuted),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscureSignInPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  color: _authMuted,
                ),
                onPressed: () => setState(() => _obscureSignInPassword = !_obscureSignInPassword),
              ),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your password' : null,
          ),
          Align(
            alignment: Alignment.centerRight,
            child: Padding(
              padding: const EdgeInsets.only(top: 6),
              child: _linkButton(
                text: 'Forgot password?',
                onTap: () => setState(() {
                  _view = _AuthView.forgot;
                  _error = null;
                }),
              ),
            ),
          ),
          const SizedBox(height: 20),
          _primaryButton(
            label: 'Sign in',
            onPressed: _loading
                ? null
                : () {
                    if (_signInFormKey.currentState!.validate()) _submitSignIn();
                  },
          ),
          const SizedBox(height: 18),
          _orDivider(),
          const SizedBox(height: 18),
          _googleSignInButton(),
          const SizedBox(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text("Don't have an account? ", style: TextStyle(color: _authMuted, fontSize: 14)),
              _linkButton(
                text: 'Sign up',
                onTap: () => setState(() {
                  _view = _AuthView.signUpAgent;
                  _error = null;
                }),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _signUpBody() {
    return Form(
      key: _signUpFormKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _authHeader(
            title: 'Customer sign up',
            subtitle: 'Create your account to shop with Opticedge',
          ),
          const SizedBox(height: 20),
          if (_error != null) ...[
            _errorBanner(_error!),
            const SizedBox(height: 18),
          ],
          TextFormField(
            controller: _signUpNameController,
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Full name',
              prefixIcon: const Icon(Icons.person_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your name' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _signUpEmailController,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Email address',
              prefixIcon: const Icon(Icons.mail_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your email' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _signUpPasswordController,
            obscureText: _obscureSignUpPassword,
            textInputAction: TextInputAction.done,
            decoration: _fieldDecoration(
              hint: 'Password',
              prefixIcon: const Icon(Icons.lock_outline_rounded, size: 22, color: _authMuted),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscureSignUpPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  color: _authMuted,
                ),
                onPressed: () => setState(() => _obscureSignUpPassword = !_obscureSignUpPassword),
              ),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your password' : null,
          ),
          const SizedBox(height: 16),
          _legalBlurb(),
          const SizedBox(height: 20),
          _primaryButton(
            label: 'Create Account',
            onPressed: _loading
                ? null
                : () async {
                    if (!_signUpFormKey.currentState!.validate()) return;
                    setState(() {
                      _loading = true;
                      _error = null;
                    });
                    try {
                      await registerCustomer(
                        name: _signUpNameController.text.trim(),
                        email: _signUpEmailController.text.trim(),
                        password: _signUpPasswordController.text,
                        passwordConfirmation: _signUpPasswordController.text,
                      );
                      if (!mounted) return;
                      Navigator.pushReplacementNamed(context, '/shop/dashboard');
                    } catch (e) {
                      if (!mounted) return;
                      setState(() {
                        _error = _friendlyAuthError(e);
                        _loading = false;
                      });
                    }
                  },
          ),
          const SizedBox(height: 18),
          _orDivider(),
          const SizedBox(height: 18),
          _googleSignInButton(),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('Already have an account? ', style: TextStyle(color: _authMuted, fontSize: 14)),
              _linkButton(
                text: 'Sign in',
                onTap: () => setState(() => _view = _AuthView.signIn),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _agentSignUpBody() {
    return Form(
      key: _agentFormKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _authHeader(
            title: 'Create your account',
            subtitle: 'Enter your details to get started. Your manager will activate your account.',
          ),
          const SizedBox(height: 20),
          if (_error != null) ...[
            _errorBanner(_error!),
            const SizedBox(height: 18),
          ],
          TextFormField(
            controller: _agentNameController,
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Full name',
              prefixIcon: const Icon(Icons.person_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your name' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _agentEmailController,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Email address',
              prefixIcon: const Icon(Icons.mail_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your email' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _agentPhoneController,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: _fieldDecoration(
              hint: 'Phone (optional)',
              prefixIcon: const Icon(Icons.phone_outlined, size: 22, color: _authMuted),
            ),
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _agentPasswordController,
            obscureText: _obscureAgentPassword,
            textInputAction: TextInputAction.done,
            decoration: _fieldDecoration(
              hint: 'Password',
              prefixIcon: const Icon(Icons.lock_outline_rounded, size: 22, color: _authMuted),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscureAgentPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  color: _authMuted,
                ),
                onPressed: () => setState(() => _obscureAgentPassword = !_obscureAgentPassword),
              ),
            ),
            validator: (v) => v == null || v.length < 8 ? 'Password must be at least 8 characters' : null,
          ),
          const SizedBox(height: 20),
          _primaryButton(
            label: 'Create account',
            onPressed: _loading
                ? null
                : () async {
                    if (!_agentFormKey.currentState!.validate()) return;
                    setState(() {
                      _loading = true;
                      _error = null;
                    });
                    try {
                      final result = await registerGuest(
                        name: _agentNameController.text.trim(),
                        email: _agentEmailController.text.trim(),
                        password: _agentPasswordController.text,
                        passwordConfirmation: _agentPasswordController.text,
                        phone: _agentPhoneController.text.trim(),
                      );
                      if (!mounted) return;
                      final token = result['token'] as String?;
                      if (token != null) {
                        await _completeAuthSuccess();
                      } else {
                        _snack(result['message']?.toString() ?? 'Account created. Sign in with your email and password.');
                        setState(() {
                          _view = _AuthView.signIn;
                          _loading = false;
                        });
                      }
                    } catch (e) {
                      if (!mounted) return;
                      setState(() {
                        _error = _friendlyAuthError(e);
                        _loading = false;
                      });
                    }
                  },
          ),
          const SizedBox(height: 18),
          _orDivider(),
          const SizedBox(height: 18),
          _googleSignInButton(),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('Already have an account? ', style: TextStyle(color: _authMuted, fontSize: 14)),
              _linkButton(
                text: 'Sign in',
                onTap: () => setState(() {
                  _view = _AuthView.signIn;
                  _error = null;
                }),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _forgotBody() {
    return Form(
      key: _forgotFormKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _authHeader(
            title: 'Forgot password',
            subtitle: 'Enter the email linked to your account and we will send you a reset link.',
          ),
          const SizedBox(height: 20),
          TextFormField(
            controller: _forgotEmailController,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.done,
            decoration: _fieldDecoration(
              hint: 'Email address',
              prefixIcon: const Icon(Icons.mail_outline_rounded, size: 22, color: _authMuted),
            ),
            validator: (v) => v == null || v.isEmpty ? 'Enter your email' : null,
          ),
          const SizedBox(height: 20),
          _primaryButton(
            label: 'Send reset link',
            onPressed: _loading
                ? null
                : () async {
                    if (!_forgotFormKey.currentState!.validate()) return;
                    setState(() => _loading = true);
                    try {
                      final msg = await forgotPassword(_forgotEmailController.text.trim());
                      if (!mounted) return;
                      _snack(msg);
                      setState(() => _view = _AuthView.signIn);
                    } catch (e) {
                      if (!mounted) return;
                      _snack(_friendlyAuthError(e));
                    } finally {
                      if (mounted) setState(() => _loading = false);
                    }
                  },
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text('Remember your password? ', style: TextStyle(color: _authMuted, fontSize: 14)),
              _linkButton(
                text: 'Sign in',
                onTap: () => setState(() => _view = _AuthView.signIn),
              ),
            ],
          ),
        ],
      ),
    );
  }

  bool get _showHeroLogo {
    switch (_view) {
      case _AuthView.signUp:
      case _AuthView.signUpAgent:
        return false;
      case _AuthView.signIn:
      case _AuthView.forgot:
        return true;
    }
  }

  @override
  Widget build(BuildContext context) {
    Widget body;
    switch (_view) {
      case _AuthView.signIn:
        body = _signInBody();
        break;
      case _AuthView.signUp:
        body = _signUpBody();
        break;
      case _AuthView.signUpAgent:
        body = _agentSignUpBody();
        break;
      case _AuthView.forgot:
        body = _forgotBody();
        break;
    }

    return Scaffold(
      body: Stack(
        children: [
          Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [_authBgTop, _authBgBottom],
              ),
            ),
          ),
          Positioned(
            top: -60,
            right: -40,
            child: IgnorePointer(
              child: Container(
                width: 180,
                height: 180,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _brandPrimary(context).withValues(alpha: 0.08),
                ),
              ),
            ),
          ),
          Positioned(
            bottom: 40,
            left: -70,
            child: IgnorePointer(
              child: Container(
                width: 160,
                height: 160,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withValues(alpha: 0.55),
                ),
              ),
            ),
          ),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 400),
                  child: Column(
                    children: [
                      if (_showHeroLogo) ...[
                        const SizedBox(height: 12),
                        _heroImage(),
                        const SizedBox(height: 24),
                      ] else
                        const SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.fromLTRB(24, 28, 24, 28),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 28,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: body,
                      ),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
