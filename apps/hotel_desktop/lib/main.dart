// ignore_for_file: use_build_context_synchronously
import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

void main() {
  runZonedGuarded(() {
    WidgetsFlutterBinding.ensureInitialized();

    // 1. Framework Error Handler
    FlutterError.onError = (FlutterErrorDetails details) {
      FlutterError.presentError(details);
      debugPrint('Global Flutter Framework Error caught: ${details.exception}');
    };

    // 2. Custom ErrorWidget.builder - NEVER render a blank white screen!
    ErrorWidget.builder = (FlutterErrorDetails details) {
      return GlobalAppErrorView(details: details);
    };

    // 3. Platform Async Error Handler
    PlatformDispatcher.instance.onError = (error, stack) {
      debugPrint('Global PlatformDispatcher Error caught: $error\n$stack');
      return true; // Mark as handled to prevent process crash
    };

    AppState.loadSavedConfig();
    runApp(const HotelDesktopApp());
  }, (error, stack) {
    debugPrint('Global Zone Unhandled Error: $error\n$stack');
  });
}

class AppLocale {
  static final ValueNotifier<String> current = ValueNotifier<String>('ar');

  static bool get isArabic => current.value == 'ar';
  static bool get isEnglish => current.value == 'en';
  static TextDirection get direction => isArabic ? TextDirection.rtl : TextDirection.ltr;

  static void setLocale(String lang) {
    if (current.value != lang) {
      current.value = lang;
    }
  }

  static String tr(String ar, String en) {
    return isArabic ? ar : en;
  }
}

class HotelDesktopApp extends StatelessWidget {
  const HotelDesktopApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: AppLocale.current,
      builder: (context, locale, child) {
        return MaterialApp(
          navigatorKey: AppState.navigatorKey,
          title: AppLocale.tr('منظومة إدارة الفنادق الذكية - محطة العمل المتكاملة', 'Smart Hotel Management System - Workstation'),
          debugShowCheckedModeBanner: false,
          locale: Locale(locale),
          theme: ThemeData(
            useMaterial3: true,
            fontFamily: 'Segoe UI',
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xFF1D4ED8),
              primary: const Color(0xFF1D4ED8),
              secondary: const Color(0xFF0F766E),
              surface: const Color(0xFFF8FAFC),
            ),
            scaffoldBackgroundColor: const Color(0xFFF1F5F9),
          ),
          home: Directionality(
            textDirection: AppLocale.direction,
            child: const AppRootScreen(),
          ),
        );
      },
    );
  }
}

// ============================================================================
// GLOBAL APPLICATION ERROR & RECOVERY VIEW (يمنع الشاشة البيضاء ويعرض الخيارات)
// ============================================================================
class GlobalAppErrorView extends StatefulWidget {
  final FlutterErrorDetails details;
  const GlobalAppErrorView({super.key, required this.details});

  @override
  State<GlobalAppErrorView> createState() => _GlobalAppErrorViewState();
}

class _GlobalAppErrorViewState extends State<GlobalAppErrorView> {
  bool _showDetails = false;

  @override
  Widget build(BuildContext context) {
    final errorStr = widget.details.exceptionAsString();
    final stackStr = widget.details.stack?.toString() ?? '';

    return Material(
      color: const Color(0xFFF1F5F9),
      child: Directionality(
        textDirection: TextDirection.rtl,
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Container(
              constraints: const BoxConstraints(maxWidth: 640),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
                border: Border.all(color: const Color(0xFFFECACA), width: 1.5),
              ),
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(Icons.warning_amber_rounded, color: Color(0xFFDC2626), size: 34),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text(
                              'تنبيه نظام: حدث استثناء غير متوقع في الواجهة',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'تم اعتراض الخطأ بنجاح لحماية البرنامج من التجمد أو الشاشة البيضاء.',
                              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 32),
                  const Text(
                    'تفاصيل الخطأ المسجل:',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF334155)),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF1F2),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFFDA4AF)),
                    ),
                    child: SelectableText(
                      errorStr,
                      style: const TextStyle(fontSize: 12, fontFamily: 'monospace', color: Color(0xFF9F1239), height: 1.4),
                    ),
                  ),
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: () => setState(() => _showDetails = !_showDetails),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(_showDetails ? Icons.expand_less : Icons.expand_more, size: 18, color: const Color(0xFF1D4ED8)),
                        const SizedBox(width: 4),
                        Text(
                          _showDetails ? 'إخفاء السجل التفصيلي (Stack Trace)' : 'عرض السجل التفصيلي (Stack Trace)',
                          style: const TextStyle(fontSize: 12, color: Color(0xFF1D4ED8), fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                  if (_showDetails && stackStr.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Container(
                      constraints: const BoxConstraints(maxHeight: 180),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0F172A),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: SingleChildScrollView(
                        child: SelectableText(
                          stackStr,
                          style: const TextStyle(fontSize: 10, fontFamily: 'monospace', color: Color(0xFF94A3B8)),
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        style: TextButton.styleFrom(
                          foregroundColor: const Color(0xFFDC2626),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        ),
                        onPressed: () => exit(0),
                        icon: const Icon(Icons.power_settings_new, size: 16),
                        label: const Text('إغلاق البرنامج'),
                      ),
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        ),
                        onPressed: () {
                          // Wait / Retry
                          setState(() {});
                        },
                        icon: const Icon(Icons.hourglass_empty, size: 16),
                        label: const Text('انتظار / إعادة المحاولة'),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1D4ED8),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                        ),
                        onPressed: () {
                          // OK / Dismiss
                          if (Navigator.canPop(context)) {
                            Navigator.pop(context);
                          } else {
                            setState(() {});
                          }
                        },
                        icon: const Icon(Icons.check, size: 16),
                        label: const Text('موافق (متابعة)'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class AppState {
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  static String safeString(dynamic val, [String fallback = '']) {
    if (val == null) return fallback;
    if (val is String) return val.trim().isEmpty ? fallback : val;
    if (val is Map) {
      return val['name']?.toString() ?? val['title']?.toString() ?? val['label']?.toString() ?? fallback;
    }
    return val.toString();
  }

  static double safeDouble(dynamic val, [double fallback = 0.0]) {
    if (val == null) return fallback;
    if (val is num) return val.toDouble();
    return double.tryParse(val.toString().replaceAll(',', '').trim()) ?? fallback;
  }

  static Map<String, dynamic> safeMap(dynamic val) {
    if (val is Map<String, dynamic>) return val;
    if (val is Map) return Map<String, dynamic>.from(val);
    return <String, dynamic>{};
  }

  static Future<void> showGlobalErrorDialog({
    required String title,
    required String message,
    VoidCallback? onRetry,
  }) async {
    final ctx = navigatorKey.currentContext;
    if (ctx == null) return;
    await showDialog(
      context: ctx,
      barrierDismissible: false,
      builder: (dialogCtx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'تم اعتراض التنبيه بنجاح لحماية البرنامج من التوقف أو ظهور شاشة بيضاء:',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
              const SizedBox(height: 10),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: SelectableText(
                  message,
                  style: const TextStyle(fontSize: 12, fontFamily: 'monospace', color: Color(0xFF991B1B)),
                ),
              ),
            ],
          ),
          actions: [
            TextButton.icon(
              onPressed: () => exit(0),
              icon: const Icon(Icons.power_settings_new, color: Color(0xFFDC2626), size: 16),
              label: const Text('إغلاق البرنامج', style: TextStyle(color: Color(0xFFDC2626))),
            ),
            OutlinedButton.icon(
              onPressed: () {
                Navigator.of(dialogCtx).pop();
                if (onRetry != null) onRetry();
              },
              icon: const Icon(Icons.hourglass_empty, size: 16),
              label: const Text('انتظار / إعادة المحاولة'),
            ),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1D4ED8),
                foregroundColor: Colors.white,
              ),
              onPressed: () => Navigator.of(dialogCtx).pop(),
              icon: const Icon(Icons.check, size: 16),
              label: const Text('موافق (تخطي ومتابعة)'),
            ),
          ],
        ),
      ),
    );
  }
  static const String defaultApiUrl = 'https://hotel.ahmadinsights.online/api';
  static String apiUrl = defaultApiUrl;
  static String? token;
  static Map<String, dynamic>? currentUser;

  static String get serverBaseUrl {
    final uri = Uri.tryParse(apiUrl);
    if (uri != null && uri.hasScheme && uri.hasAuthority) {
      return '${uri.scheme}://${uri.authority}';
    }
    return 'https://hotel.ahmadinsights.online';
  }

  static File _getConfigFile() {
    try {
      final exeDir = File(Platform.resolvedExecutable).parent.path;
      return File('$exeDir/server_config.txt');
    } catch (_) {
      return File('server_config.txt');
    }
  }

  static void loadSavedConfig() {
    try {
      final file = _getConfigFile();
      if (file.existsSync()) {
        final content = file.readAsStringSync().trim();
        if (content.isNotEmpty && (content.startsWith('http://') || content.startsWith('https://'))) {
          apiUrl = content;
          return;
        }
      }
    } catch (_) {}
    apiUrl = defaultApiUrl;
  }

  static void saveApiUrl(String newUrl) {
    apiUrl = newUrl.trim();
    try {
      final file = _getConfigFile();
      file.writeAsStringSync(apiUrl);
    } catch (_) {}
  }

  static File _getCredentialsFile() {
    try {
      final exeDir = File(Platform.resolvedExecutable).parent.path;
      return File('$exeDir/auth_credentials.json');
    } catch (_) {
      return File('auth_credentials.json');
    }
  }

  static Map<String, dynamic>? loadSavedCredentials() {
    try {
      final file = _getCredentialsFile();
      if (file.existsSync()) {
        final content = file.readAsStringSync().trim();
        if (content.isNotEmpty) {
          final data = jsonDecode(content);
          if (data is Map<String, dynamic>) {
            return data;
          }
        }
      }
    } catch (_) {}
    return null;
  }

  static void saveCredentials(String email, String password, bool remember) {
    try {
      final file = _getCredentialsFile();
      if (remember) {
        file.writeAsStringSync(jsonEncode({
          'email': email,
          'password': password,
          'remember_me': true,
        }));
      } else {
        if (file.existsSync()) {
          file.deleteSync();
        }
      }
    } catch (_) {}
  }

  static bool hasRole(String role) {
    return currentUser?['role'] == role;
  }

  static bool hasPermission(String perm) {
    final role = currentUser?['role'] ?? '';
    if (role == 'hotel_admin' || role == 'admin' || role == 'super_admin') {
      return true;
    }
    final perms = (currentUser?['permissions'] as List?)?.cast<String>() ?? [];
    return perms.contains(perm);
  }

  static bool isReadOnlyOwner() {
    return currentUser?['is_owner'] == true || currentUser?['role'] == 'hotel_owner';
  }
}

class AppRootScreen extends StatefulWidget {
  const AppRootScreen({super.key});

  @override
  State<AppRootScreen> createState() => _AppRootScreenState();
}

class _AppRootScreenState extends State<AppRootScreen> {
  bool _isLoggedIn = false;

  void _onLoginSuccess() {
    setState(() {
      _isLoggedIn = true;
    });
  }

  void _onLogout() {
    setState(() {
      AppState.token = null;
      AppState.currentUser = null;
      _isLoggedIn = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!_isLoggedIn) {
      return LoginScreen(onLoginSuccess: _onLoginSuccess);
    }
    return MainDashboardScreen(onLogout: _onLogout);
  }
}

// ============================================================================
// 1. LOGIN SCREEN (Strictly for Hotel Staff & Management only)
// ============================================================================
class LoginScreen extends StatefulWidget {
  final VoidCallback onLoginSuccess;
  const LoginScreen({super.key, required this.onLoginSuccess});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailCtrl = TextEditingController(text: 'ahmad19ali87@gmail.com');
  final _passCtrl = TextEditingController(text: r'Ah70m10a65d55$');
  bool _isLoading = false;
  String? _errorMessage;
  bool _rememberMe = true;

  @override
  void initState() {
    super.initState();
    final saved = AppState.loadSavedCredentials();
    if (saved != null) {
      if (saved['email'] != null) _emailCtrl.text = saved['email'];
      if (saved['password'] != null) _passCtrl.text = saved['password'];
      _rememberMe = saved['remember_me'] == true;
    }
  }

  Future<void> _submitLogin() async {
    final email = _emailCtrl.text.trim();
    final password = _passCtrl.text;

    if (email.isEmpty || password.isEmpty) {
      setState(() => _errorMessage = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await http.post(
        Uri.parse('${AppState.apiUrl}/auth/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': email,
          'password': password,
          'client_type': 'desktop', // Enforces staff-only restriction on server
        }),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['status'] == 'success') {
        AppState.token = data['token'];
        AppState.currentUser = data['user'];

        if (AppState.currentUser?['role'] == 'guest') {
          setState(() {
            _isLoading = false;
            _errorMessage = '⛔ عذراً: حسابات الزوار لا يمكنها فتح تطبيق الكومبيوتر. التطبيق مخصص لطاقم وموظفي الفنادق فقط.';
          });
          return;
        }

        AppState.saveCredentials(email, password, _rememberMe);
        widget.onLoginSuccess();
      } else {
        setState(() {
          _errorMessage = data['message'] ?? 'فشل تسجيل الدخول، يرجى التأكد من البيانات المدخلة.';
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'تعذر الاتصال بخادم النظام (${AppState.apiUrl}). تأكد من تشغيل السيرفر.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showServerSettings() {
    final ctrl = TextEditingController(text: AppState.apiUrl);
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('إعدادات عنوان السيرفر (API URL)'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('يمكنك تغيير رابط الخادم والاتصال بالسيرفر السحابي أو السيرفر المحلي:'),
              const SizedBox(height: 12),
              TextField(
                controller: ctrl,
                decoration: const InputDecoration(
                  labelText: 'رابط الخادم',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.link),
                ),
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  ActionChip(
                    avatar: const Icon(Icons.cloud_done, size: 16, color: Color(0xFF1D4ED8)),
                    label: const Text('السيرفر السحابي المباشر'),
                    onPressed: () {
                      ctrl.text = AppState.defaultApiUrl;
                    },
                  ),
                  ActionChip(
                    avatar: const Icon(Icons.computer, size: 16, color: Color(0xFF64748B)),
                    label: const Text('سيرفر محلي (Localhost)'),
                    onPressed: () {
                      ctrl.text = 'http://127.0.0.1:8000/api';
                    },
                  ),
                ],
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                AppState.saveApiUrl(ctrl.text.trim());
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('تم حفظ وتحديث عنوان الخادم إلى: ${AppState.apiUrl}')),
                );
              },
              child: const Text('حفظ'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 500),
            child: Card(
              elevation: 6,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              color: Colors.white,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 36, vertical: 42),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const SizedBox(width: 40),
                        Container(
                          width: 68,
                          height: 68,
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: const Color(0xFFBFDBFE)),
                          ),
                          child: const Center(
                            child: Text('🏨', style: TextStyle(fontSize: 34)),
                          ),
                        ),
                        IconButton(
                          onPressed: _showServerSettings,
                          icon: const Icon(Icons.settings, color: Color(0xFF64748B)),
                          tooltip: 'إعدادات عنوان السيرفر',
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    const Text(
                      'نظام إدارة وتشغيل الفنادق الذكي',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'محطة العمل المكتبي الشاملة (حجوزات، غرف، محاسبة، فواتير، رسائل)',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                    ),
                    const SizedBox(height: 24),

                    // User Notice
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFFCD34D)),
                      ),
                      child: const Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('📢', style: TextStyle(fontSize: 18)),
                          SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'للتسجيل لحساب جديد راجع المسؤول عن الموقع',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF78350F)),
                                ),
                                SizedBox(height: 3),
                                Text(
                                  'الدخول مسموح فقط لموظفي الفندق من رتبة مدير إلى أدنى رتبة وظيفية. حسابات الزوار لا يمكنها فتح هذا التطبيق.',
                                  style: TextStyle(fontSize: 11, color: Color(0xFF92400E)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    if (_errorMessage != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFFCA5A5)),
                        ),
                        child: Text(
                          _errorMessage!,
                          style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    TextField(
                      controller: _emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(
                        labelText: 'ادخل الايميل',
                        hintText: 'ادخل الايميل',
                        prefixIcon: const Icon(Icons.email_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _passCtrl,
                      obscureText: true,
                      decoration: InputDecoration(
                        labelText: 'كلمة المرور',
                        hintText: 'كلمة المرور',
                        prefixIcon: const Icon(Icons.lock_outline),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Checkbox(
                          value: _rememberMe,
                          activeColor: const Color(0xFF1D4ED8),
                          onChanged: (val) {
                            setState(() => _rememberMe = val ?? false);
                          },
                        ),
                        const SizedBox(width: 4),
                        const Text(
                          'حفظ بياناتي',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),

                    ElevatedButton(
                      onPressed: _isLoading ? null : _submitLogin,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 2,
                      ),
                      child: _isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Text('تسجيل الدخول للنظام', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'جميع البيانات والعمليات متزامنة لحظياً وبشكل كامل مع خادم الموقع.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 2. MAIN DASHBOARD SCREEN (Full Navigation & Permission Enforced Tabs)
class DesktopNavSubTab {
  final String id;
  final String titleAr;
  final String titleEn;
  final IconData icon;

  const DesktopNavSubTab({
    required this.id,
    required this.titleAr,
    required this.titleEn,
    required this.icon,
  });

  String get title => AppLocale.tr(titleAr, titleEn);
}

class DesktopNavCategory {
  final String id;
  final String titleAr;
  final String titleEn;
  final IconData icon;
  final List<DesktopNavSubTab> subTabs;

  const DesktopNavCategory({
    required this.id,
    required this.titleAr,
    required this.titleEn,
    required this.icon,
    this.subTabs = const [],
  });

  String get title => AppLocale.tr(titleAr, titleEn);
}

// ============================================================================
// 2. MAIN DASHBOARD SCREEN (Grouped Navigation & Top Sub-Tabs)
// ============================================================================
class MainDashboardScreen extends StatefulWidget {
  final VoidCallback onLogout;
  const MainDashboardScreen({super.key, required this.onLogout});

  @override
  State<MainDashboardScreen> createState() => _MainDashboardScreenState();
}

class _MainDashboardScreenState extends State<MainDashboardScreen> {
  String _selectedCategoryId = 'dashboard';
  final Map<String, String> _subTabSelection = {
    'reception': 'room_rack',
    'finance': 'invoices',
    'operations': 'cleaning',
    'settings': 'deletion_logs',
  };
  final ScrollController _overviewScrollController = ScrollController();
  bool _isLoading = true;
  Map<String, dynamic>? _dashboardData;
  Map<String, dynamic>? _subscriptionData;
  int _unreadNotificationsCount = 0;
  Timer? _notificationsTimer;

  @override
  void initState() {
    super.initState();
    _fetchDashboard();
    _fetchNotifications();
    _fetchSubscription();
    // Start 15s notification polling timer for real-time alerts
    _notificationsTimer = Timer.periodic(const Duration(seconds: 15), (timer) {
      if (mounted) {
        _fetchNotifications(silent: true);
      }
    });
  }

  @override
  void dispose() {
    _notificationsTimer?.cancel();
    _overviewScrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchSubscription() async {
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/subscription'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['status'] == 'success' && mounted) {
          setState(() {
            _subscriptionData = body;
          });
        }
      }
    } catch (_) {}
  }

  void _openSubscriptionDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => SubscriptionDetailsDialog(
        subscriptionData: _subscriptionData,
        onRefresh: _fetchSubscription,
      ),
    );
  }

  void _openSupportCenter(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => const SupportCenterDialog(),
    );
  }

  Future<void> _fetchDashboard() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/dashboard'),
        headers: {
          'Authorization': 'Bearer ${AppState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _dashboardData = body['data'];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _fetchNotifications({bool silent = false}) async {
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/notifications'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final newCount = (body['unread_count'] is num) ? (body['unread_count'] as num).toInt() : 0;
        final list = (body['data'] as List?) ?? [];
        if (mounted) {
          if (silent && newCount > _unreadNotificationsCount && list.isNotEmpty) {
            final latest = list.first;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Row(
                  children: [
                    const Icon(Icons.notifications_active, color: Colors.white, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'إشعار جديد: ${latest['title'] ?? ''} - ${latest['message'] ?? ''}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                backgroundColor: const Color(0xFF1D4ED8),
                duration: const Duration(seconds: 4),
                action: SnackBarAction(
                  label: 'عرض',
                  textColor: Colors.white,
                  onPressed: () => _openNotificationsCenter(context),
                ),
              ),
            );
          }
          setState(() {
            _unreadNotificationsCount = newCount;
          });
        }
      }
    } catch (_) {}
  }

  void _openNotificationsCenter(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => NotificationsCenterDialog(
        onNavigate: _navigateToSection,
      ),
    ).then((_) {
      _fetchNotifications(silent: true);
    });
  }

  void _openSettingsModal(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => SettingsAndLanguageDialog(
        onLogout: () {
          Navigator.pop(ctx);
          widget.onLogout();
        },
      ),
    ).then((_) {
      if (mounted) setState(() {});
    });
  }

  void _navigateToSection(String categoryId, [String? subTabId]) {
    _switchToTarget(subTabId ?? categoryId);
  }

  @override
  Widget build(BuildContext context) {
    final user = AppState.currentUser;
    final role = user?['role'] ?? '';
    final isOwner = AppState.isReadOnlyOwner();

    // Grouped categories matching website structure
    final availableCategories = <DesktopNavCategory>[
      const DesktopNavCategory(
        id: 'dashboard',
        titleAr: 'لوحة المؤشرات',
        titleEn: 'Dashboard',
        icon: Icons.dashboard_rounded,
      ),
    ];

    if (AppState.hasPermission('reception') || role == 'reception' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
      availableCategories.add(
        const DesktopNavCategory(
          id: 'reception',
          titleAr: 'الاستقبال والتسكين',
          titleEn: 'Reception & Rooms',
          icon: Icons.room_service_rounded,
          subTabs: [
            DesktopNavSubTab(id: 'room_rack', titleAr: 'لوحة الغرف التفاعلية', titleEn: 'Room Rack', icon: Icons.grid_view_rounded),
            DesktopNavSubTab(id: 'bookings', titleAr: 'الحجوزات', titleEn: 'Bookings', icon: Icons.book_online_rounded),
            DesktopNavSubTab(id: 'rooms', titleAr: 'إدارة الغرف', titleEn: 'Rooms Management', icon: Icons.meeting_room_rounded),
            DesktopNavSubTab(id: 'room_types', titleAr: 'أنواع الغرف', titleEn: 'Room Types', icon: Icons.category_rounded),
            DesktopNavSubTab(id: 'guests', titleAr: 'سجل النزلاء', titleEn: 'Guests Directory', icon: Icons.people_alt_rounded),
          ],
        ),
      );
    }

    if (AppState.hasPermission('accountant') || AppState.hasPermission('invoices') || role == 'accountant' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
      availableCategories.add(
        const DesktopNavCategory(
          id: 'finance',
          titleAr: 'المحاسبة والمالية',
          titleEn: 'Accounting & Finance',
          icon: Icons.account_balance_wallet_rounded,
          subTabs: [
            DesktopNavSubTab(id: 'invoices', titleAr: 'الفواتير والمدفوعات', titleEn: 'Invoices & Billing', icon: Icons.receipt_long_rounded),
            DesktopNavSubTab(id: 'accounts', titleAr: 'دفتر اليومية والصندوق', titleEn: 'Cash Book & Ledger', icon: Icons.menu_book_rounded),
            DesktopNavSubTab(id: 'reports', titleAr: 'التقارير الشاملة والإشغال', titleEn: 'Reports & Analytics', icon: Icons.analytics_rounded),
          ],
        ),
      );
    }

    if (AppState.hasPermission('cleaning') || AppState.hasPermission('maintenance') || role == 'cleaner' || role == 'maintenance' || role == 'reception' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
      final opSubTabs = <DesktopNavSubTab>[];
      if (AppState.hasPermission('cleaning') || role == 'cleaner' || role == 'reception' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
        opSubTabs.add(const DesktopNavSubTab(id: 'cleaning', titleAr: 'خدمات التنظيف', titleEn: 'Housekeeping', icon: Icons.cleaning_services_rounded));
      }
      if (AppState.hasPermission('maintenance') || role == 'maintenance' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
        opSubTabs.add(const DesktopNavSubTab(id: 'maintenance', titleAr: 'بلاغات الصيانة', titleEn: 'Maintenance', icon: Icons.build_rounded));
      }
      if (AppState.hasPermission('reception') || role == 'reception' || role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
        opSubTabs.add(const DesktopNavSubTab(id: 'complaints', titleAr: 'الشكاوى والمقترحات', titleEn: 'Guest Complaints', icon: Icons.feedback_rounded));
      }
      if (opSubTabs.isNotEmpty) {
        availableCategories.add(
          DesktopNavCategory(
            id: 'operations',
            titleAr: 'التشغيل والخدمات',
            titleEn: 'Operations & Services',
            icon: Icons.handyman_rounded,
            subTabs: opSubTabs,
          ),
        );
      }
    }

    if (role == 'hotel_admin' || role == 'admin' || role == 'super_admin' || isOwner) {
      availableCategories.add(
        const DesktopNavCategory(
          id: 'settings',
          titleAr: 'الرقابة والإعدادات',
          titleEn: 'Audit & Settings',
          icon: Icons.admin_panel_settings_rounded,
          subTabs: [
            DesktopNavSubTab(id: 'deletion_logs', titleAr: 'سجل المحذوفات والرقابة', titleEn: 'Audit & Deletion Logs', icon: Icons.delete_sweep_rounded),
            DesktopNavSubTab(id: 'employees', titleAr: 'الموظفون والصلاحيات', titleEn: 'Staff & Roles', icon: Icons.badge_rounded),
            DesktopNavSubTab(id: 'printer_scanner', titleAr: 'إعدادات الطباعة والسكنر', titleEn: 'Printers & Scanner', icon: Icons.print_rounded),
          ],
        ),
      );
    }

    // Messenger is available to all
    availableCategories.add(
      const DesktopNavCategory(
        id: 'messenger',
        titleAr: 'البريد والمحادثات',
        titleEn: 'Messenger & Chat',
        icon: Icons.forum_rounded,
      ),
    );

    // Guard selected category
    if (!availableCategories.any((c) => c.id == _selectedCategoryId)) {
      _selectedCategoryId = availableCategories.first.id;
    }

    final activeCategory = availableCategories.firstWhere((c) => c.id == _selectedCategoryId);
    String? currentSubTabId;
    if (activeCategory.subTabs.isNotEmpty) {
      currentSubTabId = _subTabSelection[activeCategory.id];
      if (currentSubTabId == null || !activeCategory.subTabs.any((st) => st.id == currentSubTabId)) {
        currentSubTabId = activeCategory.subTabs.first.id;
        _subTabSelection[activeCategory.id] = currentSubTabId;
      }
    }

    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          Container(
            width: 270,
            color: const Color(0xFF0F172A),
            child: Column(
              children: [
                // Hotel Header
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                  decoration: const BoxDecoration(
                    border: Border(bottom: BorderSide(color: Color(0xFF1E293B))),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1D4ED8),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Text('🏨', style: TextStyle(fontSize: 22)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?['hotel_name'] ?? AppLocale.tr('نظام الفندق', 'Hotel System'),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isOwner
                                  ? AppLocale.tr('👑 مالك الفندق (معاينة فقط)', '👑 Hotel Owner (View Only)')
                                  : AppLocale.tr('محطة العمل والتشغيل', 'Workstation & Operations'),
                              style: TextStyle(
                                color: isOwner ? const Color(0xFFFBBF24) : const Color(0xFF94A3B8),
                                fontSize: 11,
                                fontWeight: isOwner ? FontWeight.bold : FontWeight.normal,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Navigation Categories List
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
                    itemCount: availableCategories.length,
                    itemBuilder: (ctx, idx) {
                      final cat = availableCategories[idx];
                      final isSelected = _selectedCategoryId == cat.id;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: ListTile(
                          selected: isSelected,
                          selectedTileColor: const Color(0xFF1D4ED8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          leading: Icon(
                            cat.icon,
                            color: isSelected ? Colors.white : const Color(0xFF94A3B8),
                            size: 20,
                          ),
                          title: Text(
                            cat.title,
                            style: TextStyle(
                              color: isSelected ? Colors.white : const Color(0xFFCBD5E1),
                              fontSize: 13,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                            ),
                          ),
                          trailing: cat.subTabs.isNotEmpty
                              ? Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: isSelected ? Colors.white.withValues(alpha: 0.2) : const Color(0xFF1E293B),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    '${cat.subTabs.length}',
                                    style: TextStyle(
                                      color: isSelected ? Colors.white : const Color(0xFF94A3B8),
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                )
                              : null,
                          onTap: () {
                            setState(() => _selectedCategoryId = cat.id);
                          },
                        ),
                      );
                    },
                  ),
                ),

                // User Profile & Logout
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: const BoxDecoration(
                    color: Color(0xFF090D16),
                    border: Border(top: BorderSide(color: Color(0xFF1E293B))),
                  ),
                  child: Row(
                    children: [
                      CircleAvatar(
                        backgroundColor: const Color(0xFF1D4ED8),
                        radius: 18,
                        child: Text(
                          (user?['name'] ?? 'U').substring(0, 1),
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?['name'] ?? '',
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              '${AppLocale.tr("الدور: ", "Role: ")}${user?['role'] ?? ''}',
                              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: widget.onLogout,
                        icon: const Icon(Icons.logout, color: Color(0xFFEF4444), size: 20),
                        tooltip: AppLocale.tr('تسجيل الخروج', 'Logout'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Main Workspace Area
          Expanded(
            child: Column(
              children: [
                // Top Header Bar
                Container(
                  height: 64,
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(activeCategory.icon, color: const Color(0xFF1D4ED8), size: 22),
                          const SizedBox(width: 10),
                          Text(
                            activeCategory.title,
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                          if (isOwner) ...[
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEF3C7),
                                border: Border.all(color: const Color(0xFFFCD34D)),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                AppLocale.tr('👁️ وضع مالك الفندق: مخصص للاطلاع فقط', '👁️ Hotel Owner Mode: View Only'),
                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF92400E)),
                              ),
                            ),
                          ],
                        ],
                      ),
                      Row(
                        children: [
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF1D4ED8),
                              foregroundColor: Colors.white,
                              elevation: 0,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            icon: const Icon(Icons.qr_code_scanner, size: 18),
                            label: Text(AppLocale.tr('سكنر وبحث سريع', 'Quick Search & Scanner'), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            onPressed: () => _openScannerLookupDialog(context),
                          ),
                          const SizedBox(width: 8),
                          // Support Tickets Button
                          InkWell(
                            onTap: () => _openSupportCenter(context),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEFF6FF),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: const Color(0xFFBFDBFE)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: const [
                                  Icon(Icons.headset_mic_rounded, color: Color(0xFF1D4ED8), size: 17),
                                  SizedBox(width: 6),
                                  Text(
                                    'الدعم الفني 🎧',
                                    style: TextStyle(color: Color(0xFF1D4ED8), fontSize: 12, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),

                          // Subscription Status Pill Badge
                          if (_subscriptionData != null && _subscriptionData!['subscription'] != null) ...[
                            InkWell(
                              onTap: () => _openSubscriptionDialog(context),
                              borderRadius: BorderRadius.circular(10),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                                decoration: BoxDecoration(
                                  color: (_subscriptionData!['subscription']?['is_expired'] == true)
                                      ? const Color(0xFFFEE2E2)
                                      : (_subscriptionData!['subscription']?['is_expiring_soon'] == true)
                                          ? const Color(0xFFFEF3C7)
                                          : const Color(0xFFF0FDF4),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: (_subscriptionData!['subscription']?['is_expired'] == true)
                                        ? const Color(0xFFFCA5A5)
                                        : (_subscriptionData!['subscription']?['is_expiring_soon'] == true)
                                            ? const Color(0xFFFCD34D)
                                            : const Color(0xFF86EFAC),
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      (_subscriptionData!['subscription']?['is_expired'] == true)
                                          ? Icons.warning_rounded
                                          : (_subscriptionData!['subscription']?['is_expiring_soon'] == true)
                                              ? Icons.timer_outlined
                                              : Icons.verified_rounded,
                                      color: (_subscriptionData!['subscription']?['is_expired'] == true)
                                          ? const Color(0xFFDC2626)
                                          : (_subscriptionData!['subscription']?['is_expiring_soon'] == true)
                                              ? const Color(0xFFD97706)
                                              : const Color(0xFF16A34A),
                                      size: 17,
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      '${_subscriptionData!['subscription']?['plan_name'] ?? "الاشتراك"} • ${(_subscriptionData!['subscription']?['is_expired'] == true) ? "منتهي!" : "متبقي ${_subscriptionData!['subscription']?['remaining_days'] ?? 0} يوم"}',
                                      style: TextStyle(
                                        color: (_subscriptionData!['subscription']?['is_expired'] == true)
                                            ? const Color(0xFFDC2626)
                                            : (_subscriptionData!['subscription']?['is_expiring_soon'] == true)
                                                ? const Color(0xFF92400E)
                                                : const Color(0xFF166534),
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                          ],

                          // Interactive Notifications Center Button
                          InkWell(
                            onTap: () => _openNotificationsCenter(context),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                              decoration: BoxDecoration(
                                color: _unreadNotificationsCount > 0 ? const Color(0xFFFEE2E2) : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: _unreadNotificationsCount > 0 ? const Color(0xFFFCA5A5) : const Color(0xFFCBD5E1),
                                ),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    _unreadNotificationsCount > 0 ? Icons.notifications_active : Icons.notifications_outlined,
                                    color: _unreadNotificationsCount > 0 ? const Color(0xFFDC2626) : const Color(0xFF475569),
                                    size: 18,
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    _unreadNotificationsCount > 0
                                        ? '$_unreadNotificationsCount ${AppLocale.tr("إشعار جديد", "New")}'
                                        : AppLocale.tr('مركز الإشعارات', 'Notifications Center'),
                                    style: TextStyle(
                                      color: _unreadNotificationsCount > 0 ? const Color(0xFFDC2626) : const Color(0xFF475569),
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            onPressed: _fetchDashboard,
                            icon: const Icon(Icons.refresh, color: Color(0xFF64748B)),
                            tooltip: AppLocale.tr('تحديث بيانات الخادم', 'Refresh Server Data'),
                          ),
                          const SizedBox(width: 4),
                          IconButton(
                            onPressed: () => _openSettingsModal(context),
                            icon: const Icon(Icons.settings, color: Color(0xFF475569)),
                            tooltip: AppLocale.tr('الإعدادات واللغة', 'Settings & Language'),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                // Subscription Expiry Warning Banner
                if (_subscriptionData != null &&
                    _subscriptionData!['subscription'] != null &&
                    (_subscriptionData!['subscription']?['is_expired'] == true ||
                     _subscriptionData!['subscription']?['is_expiring_soon'] == true))
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    decoration: BoxDecoration(
                      color: (_subscriptionData!['subscription']?['is_expired'] == true)
                          ? const Color(0xFFDC2626)
                          : const Color(0xFFD97706),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Icon(
                          (_subscriptionData!['subscription']?['is_expired'] == true) ? Icons.error_outline : Icons.warning_amber_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            (_subscriptionData!['subscription']?['is_expired'] == true)
                                ? '⚠️ تنبيه عاجل: لقد انتهت فترة اشتراك فندقكم في النظام (${_subscriptionData!['subscription']?['plan_name']}). يرجى التواصل مع إدارة النظام وتجديد الاشتراك فوراً لضمان عدم توقف الخدمات والمزامنة.'
                                : '⚠️ تذكير بالاشتراك: اشتراككم في (${_subscriptionData!['subscription']?['plan_name']}) قارب على الانتهاء (متبقي ${_subscriptionData!['subscription']?['remaining_days']} يوماً فقط - تاريخ الانتهاء ${_subscriptionData!['subscription']?['end_date']}). يرجى المبادرة بالتجديد.',
                            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 12),
                        ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: (_subscriptionData!['subscription']?['is_expired'] == true) ? const Color(0xFFDC2626) : const Color(0xFFD97706),
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            elevation: 0,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: () => _openSubscriptionDialog(context),
                          child: const Text('تفاصيل وتجديد الاشتراك', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),

                // Top Sub-Tabs Navigation (if category has sub-tabs)
                if (activeCategory.subTabs.isNotEmpty)
                  Container(
                    height: 52,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 6),
                    decoration: const BoxDecoration(
                      color: Color(0xFFF8FAFC),
                      border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: activeCategory.subTabs.map((subTab) {
                          final isSelected = currentSubTabId == subTab.id;
                          return Padding(
                            padding: const EdgeInsets.only(left: 10),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(10),
                              onTap: () {
                                setState(() {
                                  _subTabSelection[activeCategory.id] = subTab.id;
                                });
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 150),
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
                                decoration: BoxDecoration(
                                  color: isSelected ? const Color(0xFF1D4ED8) : Colors.white,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: isSelected ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1),
                                  ),
                                  boxShadow: isSelected
                                      ? [BoxShadow(color: const Color(0xFF1D4ED8).withValues(alpha: 0.25), blurRadius: 6, offset: const Offset(0, 2))]
                                      : [],
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      subTab.icon,
                                      size: 16,
                                      color: isSelected ? Colors.white : const Color(0xFF475569),
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      subTab.title,
                                      style: TextStyle(
                                        color: isSelected ? Colors.white : const Color(0xFF1E293B),
                                        fontSize: 13,
                                        fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                  ),

                // Active Workspace Content
                Expanded(
                  child: _isLoading && _dashboardData == null
                      ? const Center(child: CircularProgressIndicator())
                      : _buildTabContent(_selectedCategoryId, currentSubTabId),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _switchToTarget(String target) {
    setState(() {
      switch (target) {
        case 'dashboard':
          _selectedCategoryId = 'dashboard';
          break;
        case 'room_rack':
          _selectedCategoryId = 'reception';
          _subTabSelection['reception'] = 'room_rack';
          break;
        case 'bookings':
          _selectedCategoryId = 'reception';
          _subTabSelection['reception'] = 'bookings';
          break;
        case 'rooms':
          _selectedCategoryId = 'reception';
          _subTabSelection['reception'] = 'rooms';
          break;
        case 'room_types':
          _selectedCategoryId = 'reception';
          _subTabSelection['reception'] = 'room_types';
          break;
        case 'guests':
          _selectedCategoryId = 'reception';
          _subTabSelection['reception'] = 'guests';
          break;
        case 'invoices':
          _selectedCategoryId = 'finance';
          _subTabSelection['finance'] = 'invoices';
          break;
        case 'accounts':
          _selectedCategoryId = 'finance';
          _subTabSelection['finance'] = 'accounts';
          break;
        case 'reports':
          _selectedCategoryId = 'finance';
          _subTabSelection['finance'] = 'reports';
          break;
        case 'cleaning':
          _selectedCategoryId = 'operations';
          _subTabSelection['operations'] = 'cleaning';
          break;
        case 'maintenance':
          _selectedCategoryId = 'operations';
          _subTabSelection['operations'] = 'maintenance';
          break;
        case 'complaints':
          _selectedCategoryId = 'operations';
          _subTabSelection['operations'] = 'complaints';
          break;
        case 'deletion_logs':
          _selectedCategoryId = 'settings';
          _subTabSelection['settings'] = 'deletion_logs';
          break;
        case 'employees':
          _selectedCategoryId = 'settings';
          _subTabSelection['settings'] = 'employees';
          break;
        case 'printer_scanner':
          _selectedCategoryId = 'settings';
          _subTabSelection['settings'] = 'printer_scanner';
          break;
        case 'messenger':
          _selectedCategoryId = 'messenger';
          break;
        default:
          _selectedCategoryId = 'dashboard';
      }
    });
  }

  void _openScannerLookupDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => const ScannerLookupDialog(),
    );
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  Widget _buildTabContent(String categoryId, String? subTabId) {
    if (categoryId == 'dashboard') {
      return _buildOverviewTab();
    }
    if (categoryId == 'messenger') {
      return const MessengerChatTab();
    }

    final target = subTabId ?? '';
    switch (target) {
      case 'room_rack':
        return RoomRackTab(
          onOpenBooking: () => _switchToTarget('bookings'),
          onOpenRooms: () => _switchToTarget('rooms'),
        );
      case 'bookings':
        return const BookingsTab();
      case 'rooms':
        return const RoomsTab();
      case 'room_types':
        return const RoomTypesTab();
      case 'guests':
        return const GuestsTab();
      case 'invoices':
        return const InvoicesTab();
      case 'accounts':
        return const AccountsTab();
      case 'reports':
        return const ReportsTab();
      case 'cleaning':
        return const CleaningTab();
      case 'maintenance':
        return const MaintenanceTab();
      case 'complaints':
        return const ComplaintsTab();
      case 'deletion_logs':
        return const DeletionLogsTab();
      case 'employees':
        return const EmployeesManagementTab();
      case 'printer_scanner':
        return const PrinterScannerTab();
      default:
        return _buildOverviewTab();
    }
  }

  Widget _buildOverviewTab() {
    final data = _dashboardData;
    if (data == null) {
      return const Center(child: Text('لا توجد بيانات متاحة حالياً.'));
    }

    final rooms = data['rooms'] ?? {};
    final todayStats = data['today_stats'] ?? {};
    final wallets = data['wallets'] ?? {};
    final sypWallet = wallets['syp'] ?? {};
    final usdWallet = wallets['usd'] ?? {};
    final deletionSummary = data['deletion_logs_summary'] ?? {};
    final recentDeletions = (deletionSummary['recent'] as List?) ?? [];
    final charts = data['charts'] ?? {};
    final recentComplaints = (data['recent_complaints'] as List?) ?? [];
    final recentInvoices = (data['recent_invoices'] as List?) ?? [];
    final recentBookings = (data['recent_bookings'] as List?) ?? [];

    final monthsLabels = ((charts['months_labels'] as List?)?.cast<String>()) ??
        ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    final sypRevenueList = ((charts['monthly_revenue_syp'] as List?)?.map((e) => (e as num).toDouble()).toList()) ??
        List.filled(12, 0.0);
    final usdRevenueList = ((charts['monthly_revenue_usd'] as List?)?.map((e) => (e as num).toDouble()).toList()) ??
        List.filled(12, 0.0);
    final bookingsList = ((charts['monthly_bookings'] as List?)?.map((e) => (e as num).toInt()).toList()) ??
        List.filled(12, 0);
    final occupancyRate = ((rooms['occupancy_rate'] as num?)?.toDouble()) ?? 0.0;

    return Scrollbar(
      controller: _overviewScrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _overviewScrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Quick Action Shortcuts Bar
            Container(
              margin: const EdgeInsets.only(bottom: 20),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  const Text('⚡ وصول سريع:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF475569))),
                  const SizedBox(width: 12),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    icon: const Icon(Icons.grid_view_rounded, size: 16),
                    label: const Text('لوحة الغرف وحالتها اللحظية', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    onPressed: () => _switchToTarget('room_rack'),
                  ),
                  const SizedBox(width: 8),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF1D4ED8),
                      side: const BorderSide(color: Color(0xFF1D4ED8)),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    icon: const Icon(Icons.book_online_rounded, size: 16),
                    label: const Text('إدارة الحجوزات', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    onPressed: () => _switchToTarget('bookings'),
                  ),
                  const SizedBox(width: 8),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF0F766E),
                      side: const BorderSide(color: Color(0xFF0F766E)),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    icon: const Icon(Icons.receipt_long_rounded, size: 16),
                    label: const Text('الفواتير والمدفوعات', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    onPressed: () => _switchToTarget('invoices'),
                  ),
                  const SizedBox(width: 8),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFFD97706),
                      side: const BorderSide(color: Color(0xFFD97706)),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    icon: const Icon(Icons.menu_book_rounded, size: 16),
                    label: const Text('دفتر اليومية والصندوق', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    onPressed: () => _switchToTarget('accounts'),
                  ),
                ],
              ),
            ),
            // 1. KPI Cards Row
            Row(
              children: [
                _buildKpiCard(
                  'الغرف المتاحة حالياً',
                  '${rooms['available'] ?? 0}',
                  'من إجمالي ${rooms['total'] ?? 0} غرفة',
                  Icons.hotel,
                  const Color(0xFF10B981),
              ),
              const SizedBox(width: 16),
              _buildKpiCard(
                'الغرف المشغولة',
                '${rooms['occupied'] ?? 0}',
                'نسبة الإشغال: $occupancyRate%',
                Icons.pie_chart,
                const Color(0xFF1D4ED8),
              ),
              const SizedBox(width: 16),
              _buildKpiCard(
                'حجوزات اليوم الجديدة',
                '${todayStats['today_bookings'] ?? 0}',
                'حركة الحجز الفندقي',
                Icons.calendar_today_outlined,
                const Color(0xFFD97706),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 10, offset: const Offset(0, 4)),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('إيرادات الفندق اليوم', style: TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.bold)),
                          Text('💰', style: TextStyle(fontSize: 18)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '🇸🇾 ${_formatNumber(todayStats['today_revenue_syp'] ?? 0)} ل.س',
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '🇺🇸 \$${_formatNumber(todayStats['today_revenue_usd'] ?? 0)} USD',
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
                      ),
                      const SizedBox(height: 4),
                      const Text('المبالغ المحصلة فعلياً', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // 2. Interactive Visual Charts Row
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Monthly Revenue Chart
              Expanded(
                flex: 5,
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Row(
                            children: [
                              Text('📊', style: TextStyle(fontSize: 18)),
                              SizedBox(width: 8),
                              Text('الإيرادات الشهرية (مفصولة بالعملتين)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                            ],
                          ),
                          Row(
                            children: [
                              Container(width: 10, height: 10, decoration: const BoxDecoration(color: Color(0xFF10B981), shape: BoxShape.circle)),
                              const SizedBox(width: 4),
                              const Text('ل.س', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                              const SizedBox(width: 10),
                              Container(width: 10, height: 10, decoration: const BoxDecoration(color: Color(0xFF2563EB), shape: BoxShape.circle)),
                              const SizedBox(width: 4),
                              const Text('USD', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        height: 220,
                        child: MonthlyRevenueBarChart(
                          sypValues: sypRevenueList,
                          usdValues: usdRevenueList,
                          months: monthsLabels,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),

              // Monthly Bookings Trend Chart
              Expanded(
                flex: 4,
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        children: [
                          Text('📈', style: TextStyle(fontSize: 18)),
                          SizedBox(width: 8),
                          Text('حركة الحجوزات الشهرية', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        height: 220,
                        child: MonthlyBookingsTrendChart(
                          bookings: bookingsList,
                          months: monthsLabels,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),

              // Occupancy Donut Chart
              Expanded(
                flex: 3,
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      const Row(
                        children: [
                          Text('🥧', style: TextStyle(fontSize: 18)),
                          SizedBox(width: 8),
                          Text('نسبة إشغال الفندق', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        ],
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 160,
                        width: 160,
                        child: OccupancyDonutChart(
                          occupancyRate: occupancyRate,
                          occupied: (rooms['occupied'] as num?)?.toInt() ?? 0,
                          available: (rooms['available'] as num?)?.toInt() ?? 0,
                          other: ((rooms['cleaning'] as num?)?.toInt() ?? 0) + ((rooms['maintenance'] as num?)?.toInt() ?? 0),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(8)),
                            child: Text('مشغول: ${rooms['occupied'] ?? 0}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8))),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(8)),
                            child: Text('متاح: ${rooms['available'] ?? 0}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // 3. Prominent Deletion Logs Section (سجل المحذوفات والرقابة الإدارية)
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFFCA5A5)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Red Top Border Indicator
                Container(
                  height: 4,
                  decoration: const BoxDecoration(
                    color: Color(0xFFDC2626),
                    borderRadius: BorderRadius.only(topLeft: Radius.circular(18), topRight: Radius.circular(18)),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Text('🗑️', style: TextStyle(fontSize: 20)),
                          ),
                          const SizedBox(width: 14),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('سجل المحذوفات والرقابة الإدارية', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFFB91C1C))),
                              const SizedBox(height: 2),
                              const Text('توثيق مباشر لجميع الفواتير والحجوزات التي تم حذفها مع أسباب الحذف والمسؤولين عنها.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                            ],
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFFCA5A5)),
                            ),
                            child: Text(
                              'المحذوفات: ${deletionSummary['count'] ?? 0} حركة',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFFB91C1C)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Text(
                              '🇸🇾 ${_formatNumber(deletionSummary['total_syp'] ?? 0)} ل.س  |  🇺🇸 \$${_formatNumber(deletionSummary['total_usd'] ?? 0)} USD',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1E293B)),
                            ),
                          ),
                          const SizedBox(width: 12),
                          OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFFDC2626),
                              side: const BorderSide(color: Color(0xFFDC2626)),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            onPressed: () => _switchToTarget('deletion_logs'),
                            icon: const Icon(Icons.arrow_forward, size: 16),
                            label: const Text('عرض كامل السجل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1, color: Color(0xFFFEE2E2)),
                if (recentDeletions.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(32),
                    child: Center(
                      child: Text('لا توجد عمليات حذف مسجلة. جميع الحركات المحذوفة ستظهر هنا فور تنفيذها مع أسبابها الموثقة.', style: TextStyle(color: Color(0xFF64748B), fontSize: 13)),
                    ),
                  )
                else
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: DataTable(
                      headingRowColor: WidgetStateProperty.all(const Color(0xFFFFF1F2)),
                      columns: const [
                        DataColumn(label: Text('نوع الحركة', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('رقم المرجع', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('النزيل / الغرفة', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('القيمة المحذوفة', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('سبب الحذف الموثق', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('الموظف المسؤول', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('تاريخ وتوقيت الحذف', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('معاينة النسخة', style: TextStyle(fontWeight: FontWeight.bold))),
                      ],
                      rows: recentDeletions.map((log) {
                        final isInvoice = log['record_type'] == 'invoice';
                        return DataRow(cells: [
                          DataCell(
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: isInvoice ? const Color(0xFFFEE2E2) : const Color(0xFFFEF3C7),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                isInvoice ? 'فاتورة' : 'حجز نزيل',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: isInvoice ? const Color(0xFFB91C1C) : const Color(0xFF92400E),
                                ),
                              ),
                            ),
                          ),
                          DataCell(Text(log['reference_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold))),
                          DataCell(Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(log['guest_name'] ?? '—', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                              if ((log['room_number'] ?? '').toString().isNotEmpty)
                                Text('غرفة ${log['room_number']}', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                            ],
                          )),
                          DataCell(Text(
                            '${_formatNumber(log['amount'])} ${log['currency']}',
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFDC2626)),
                          )),
                          DataCell(
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFF1F2),
                                borderRadius: BorderRadius.circular(6),
                                border: const Border(right: BorderSide(color: Color(0xFFDC2626), width: 3)),
                              ),
                              child: Text(log['reason'] ?? '', style: const TextStyle(fontSize: 11, color: Color(0xFF7F1D1D))),
                            ),
                          ),
                          DataCell(Text(log['user_name'] ?? 'مدير الفندق', style: const TextStyle(fontSize: 12))),
                          DataCell(Text(log['deleted_at'] ?? '', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)))),
                          DataCell(
                            OutlinedButton(
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                minimumSize: const Size(60, 30),
                              ),
                              onPressed: () {
                                showDialog(
                                  context: context,
                                  builder: (ctx) => SnapshotPreviewDialog(log: log),
                                );
                              },
                              child: const Text('معاينة', style: TextStyle(fontSize: 11)),
                            ),
                          ),
                        ]);
                      }).toList(),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // 4. Dual-Currency Financial Wallets Row
          Row(
            children: [
              // Syrian Pound Wallet
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('🇸🇾 محفظة الليرة السورية (SYP)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(6)),
                            child: const Text('ل.س', style: TextStyle(color: Color(0xFF059669), fontWeight: FontWeight.bold, fontSize: 11)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('الوارد (دخل)', style: TextStyle(color: Color(0xFF065F46), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text('+${_formatNumber(sypWallet['income'] ?? 0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF059669), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('الصادر (مصروف)', style: TextStyle(color: Color(0xFF991B1B), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text('-${_formatNumber(sypWallet['expense'] ?? 0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFDC2626), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('صافي الأرباح', style: TextStyle(color: Color(0xFF1E40AF), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text(_formatNumber(sypWallet['profit'] ?? 0), style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2563EB), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),

              // US Dollar Wallet
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('🇺🇸 محفظة الدولار الأمريكي (USD)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(6)),
                            child: const Text('\$ USD', style: TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold, fontSize: 11)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('الوارد (دخل)', style: TextStyle(color: Color(0xFF065F46), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text('+\$${_formatNumber(usdWallet['income'] ?? 0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF059669), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('الصادر (مصروف)', style: TextStyle(color: Color(0xFF991B1B), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text('-\$${_formatNumber(usdWallet['expense'] ?? 0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFDC2626), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('صافي الأرباح', style: TextStyle(color: Color(0xFF1E40AF), fontSize: 11)),
                                  const SizedBox(height: 4),
                                  Text('\$${_formatNumber(usdWallet['profit'] ?? 0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2563EB), fontSize: 14)),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // 5. Two-column tables: Recent Invoices & Recent Complaints
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Recent Invoices
              Expanded(
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(18),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('📑 أحدث الفواتير والمدفوعات', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                            TextButton(
                              onPressed: () => _switchToTarget('invoices'),
                              child: const Text('عرض الفواتير ⬅️', style: TextStyle(fontSize: 12)),
                            ),
                          ],
                        ),
                      ),
                      const Divider(height: 1),
                      if (recentInvoices.isEmpty)
                        const Padding(padding: EdgeInsets.all(24), child: Center(child: Text('لا توجد فواتير مسجلة مؤخراً.')))
                      else
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: recentInvoices.length,
                          separatorBuilder: (context, index) => const Divider(height: 1),
                          itemBuilder: (ctx, i) {
                            final inv = recentInvoices[i];
                            final isPaid = inv['status'] == 'paid';
                            return ListTile(
                              dense: true,
                              title: Text('${inv['invoice_number']} — ${inv['guest_name']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                              subtitle: Text(inv['date'] ?? '', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                              trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text('${_formatNumber(inv['total'])} ${inv['currency']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                  Text(
                                    isPaid ? 'مسددة' : 'غير مسددة',
                                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: isPaid ? const Color(0xFF059669) : const Color(0xFFDC2626)),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),

              // Recent Complaints
              Expanded(
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(18),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('📢 شكاوى النزلاء الجديدة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                            TextButton(
                              onPressed: () => _switchToTarget('complaints'),
                              child: const Text('إدارة الشكاوى ⬅️', style: TextStyle(fontSize: 12)),
                            ),
                          ],
                        ),
                      ),
                      const Divider(height: 1),
                      if (recentComplaints.isEmpty)
                        const Padding(padding: EdgeInsets.all(24), child: Center(child: Text('لا توجد شكاوى جديدة مسجلة حالياً.')))
                      else
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: recentComplaints.length,
                          separatorBuilder: (context, index) => const Divider(height: 1),
                          itemBuilder: (ctx, i) {
                            final comp = recentComplaints[i];
                            return ListTile(
                              dense: true,
                              title: Text(comp['title'] ?? 'شكوى', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                              subtitle: Text(comp['message'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                              trailing: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(6)),
                                child: const Text('جديدة', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF92400E))),
                              ),
                            );
                          },
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // 6. Recent Bookings Table
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.all(18),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('📋 أحدث الحجوزات المسجلة بالفندق', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      TextButton(
                        onPressed: () => _switchToTarget('bookings'),
                        child: const Text('إدارة كل الحجوزات ⬅️', style: TextStyle(fontSize: 12)),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),
                if (recentBookings.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(32),
                    child: Center(child: Text('لا توجد حجوزات مسجلة حديثاً.')),
                  )
                else
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: DataTable(
                      columns: const [
                        DataColumn(label: Text('رقم الحجز')),
                        DataColumn(label: Text('النزيل')),
                        DataColumn(label: Text('الغرفة')),
                        DataColumn(label: Text('تاريخ الوصول')),
                        DataColumn(label: Text('تاريخ المغادرة')),
                        DataColumn(label: Text('المبلغ الإجمالي')),
                        DataColumn(label: Text('الحالة')),
                      ],
                      rows: recentBookings.map((b) {
                        return DataRow(cells: [
                          DataCell(Text('#${b['id']}')),
                          DataCell(Text(b['guest_name'] ?? '—')),
                          DataCell(Text('غرفة ${b['room_number']}')),
                          DataCell(Text(b['check_in'] ?? '')),
                          DataCell(Text(b['check_out'] ?? '')),
                          DataCell(Text('${_formatNumber(b['total_price'])} ${b['currency']}')),
                          DataCell(Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: b['status'] == 'confirmed' ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              b['status'] == 'confirmed' ? 'مؤكد' : b['status'],
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: b['status'] == 'confirmed' ? const Color(0xFF166534) : const Color(0xFF475569),
                              ),
                            ),
                          )),
                        ]);
                      }).toList(),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

  Widget _buildKpiCard(String title, String value, String sub, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 10, offset: const Offset(0, 4)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(title, style: const TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.bold)),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
                  child: Icon(icon, color: color, size: 20),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
            const SizedBox(height: 4),
            Text(sub, style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// NATIVE PRINT & SAVE SERVICES (Windows Native File Dialog & Edge Print/PDF Engine)
// ============================================================================
class NativePrintAndSaveService {
  /// Detect installed printers on Windows via PowerShell
  static Future<List<String>> getConnectedPrinters() async {
    try {
      final res = await Process.run(
        'powershell',
        ['-NoProfile', '-Command', '[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; Get-Printer | Select-Object -ExpandProperty Name'],
        stdoutEncoding: utf8,
      ).timeout(const Duration(seconds: 4));
      if (res.exitCode == 0) {
        final lines = (res.stdout as String)
            .split(RegExp(r'[\r\n]+'))
            .map((s) => s.trim())
            .where((s) => s.isNotEmpty)
            .toList();
        if (lines.isNotEmpty) return lines;
      }
    } catch (_) {}
    return ['طابعة النظام الافتراضية (Default Printer)', 'Microsoft Print to PDF'];
  }

  /// Find available browser executable (Edge or Chrome on Windows)
  static String? _getBrowserExecutable() {
    final candidates = [
      r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
      r'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
      r'C:\Program Files\Google\Chrome\Application\chrome.exe',
      r'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    ];
    for (final p in candidates) {
      if (File(p).existsSync()) return p;
    }
    return null;
  }

  /// Open Native Windows Save File Dialog via PowerShell
  static Future<String?> showSaveFileDialog({
    required String defaultFileName,
    String filter = 'ملفات PDF (*.pdf)|*.pdf|ملفات HTML (*.html)|*.html|جميع الملفات (*.*)|*.*',
  }) async {
    try {
      final safeDefaultName = defaultFileName.replaceAll('"', '').replaceAll("'", '');
      final psScript = '''
[System.Reflection.Assembly]::LoadWithPartialName("System.Windows.Forms") | Out-Null
\$d = New-Object System.Windows.Forms.SaveFileDialog
\$d.Title = "اختر مكان حفظ الملف"
\$d.Filter = "$filter"
\$d.FileName = "$safeDefaultName"
\$d.AddExtension = \$true
\$d.DefaultExt = "pdf"
\$d.InitialDirectory = [Environment]::GetFolderPath("Desktop")
if (\$d.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    Write-Output \$d.FileName
}
''';
      final res = await Process.run(
        'powershell',
        ['-NoProfile', '-Command', psScript],
        stdoutEncoding: utf8,
      ).timeout(const Duration(seconds: 45));
      if (res.exitCode == 0) {
        var path = (res.stdout as String).trim();
        if (path.isNotEmpty) {
          if (!path.toLowerCase().endsWith('.pdf') && !path.toLowerCase().endsWith('.html')) {
            path = '$path.pdf';
          }
          return path;
        }
      }
    } catch (_) {}
    return null;
  }

  /// Save HTML as PDF (using headless Edge/Chrome) or HTML directly
  static Future<bool> saveDocument(String htmlContent, String targetPath) async {
    try {
      final targetFile = File(targetPath);
      await targetFile.parent.create(recursive: true);

      if (targetPath.toLowerCase().endsWith('.html')) {
        await targetFile.writeAsString(htmlContent, encoding: utf8);
        return true;
      }

      // If PDF, write temporary HTML file and convert using headless Edge or Chrome
      final tempDir = Directory.systemTemp;
      final tempHtmlFile = File('${tempDir.path}\\hotel_export_${DateTime.now().millisecondsSinceEpoch}.html');
      await tempHtmlFile.writeAsString(htmlContent, encoding: utf8);

      final browser = _getBrowserExecutable();
      if (browser != null) {
        final fileUri = 'file:///${tempHtmlFile.path.replaceAll('\\', '/')}';
        await Process.run(
          browser,
          ['--headless=new', '--disable-gpu', '--no-pdf-header-footer', '--print-to-pdf=$targetPath', fileUri],
        ).timeout(const Duration(seconds: 25));
      }

      // Verify file is created and has content
      if (await targetFile.exists() && (await targetFile.length()) > 0) {
        try { await tempHtmlFile.delete(); } catch (_) {}
        return true;
      }

      // Fallback: save as HTML with same base name if PDF headless failed
      if (!await targetFile.exists()) {
        final fallbackHtml = File('$targetPath.html');
        await fallbackHtml.writeAsString(htmlContent, encoding: utf8);
        return true;
      }
    } catch (_) {}
    return false;
  }

  /// Open file or containing folder in Windows Explorer
  static Future<void> openFile(String filePath) async {
    try {
      await Process.run('cmd', ['/c', 'start', '', filePath]);
    } catch (_) {}
  }

  /// Print document with native Windows print dialog showing connected printers (identical to website)
  static Future<void> printWithNativeDialog(String htmlContent) async {
    try {
      final tempDir = Directory.systemTemp;
      final tempHtmlFile = File('${tempDir.path}\\hotel_print_${DateTime.now().millisecondsSinceEpoch}.html');

      // Inject auto-trigger window.print() script
      final String fullHtml;
      if (htmlContent.contains('window.print()')) {
        fullHtml = htmlContent;
      } else {
        fullHtml = '''
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script>
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 400);
};
</script>
</head>
<body>
$htmlContent
</body>
</html>
''';
      }
      await tempHtmlFile.writeAsString(fullHtml, encoding: utf8);

      final browser = _getBrowserExecutable();
      final fileUri = 'file:///${tempHtmlFile.path.replaceAll('\\', '/')}';

      if (browser != null) {
        // Launch in app mode: clean dedicated window that pops up the system print dialog immediately
        await Process.start(
          browser,
          ['--app=$fileUri', '--new-window'],
          mode: ProcessStartMode.detached,
        );
      } else {
        await Process.run('cmd', ['/c', 'start', '', tempHtmlFile.path]);
      }
    } catch (e) {
      // Fallback: start with default browser
      try {
        final tempDir = Directory.systemTemp;
        final tempHtmlFile = File('${tempDir.path}\\hotel_print_${DateTime.now().millisecondsSinceEpoch}.html');
        final String fallbackHtml = htmlContent.contains('window.print()')
            ? htmlContent
            : '<!DOCTYPE html><html><head><meta charset="utf-8"><script>window.onload=function(){setTimeout(function(){window.print();},400);};</script></head><body>$htmlContent</body></html>';
        await tempHtmlFile.writeAsString(fallbackHtml, encoding: utf8);
        await Process.run('cmd', ['/c', 'start', '', tempHtmlFile.path]);
      } catch (err) {
        rethrow;
      }
    }
  }

  /// Build complete HTML for Invoice (Thermal 80mm or Standard A4)
  static String buildInvoiceHtml(Map<String, dynamic> rawInv, String mode) {
    final inv = AppState.safeMap(rawInv);
    final booking = AppState.safeMap(inv['booking']);
    final guest = AppState.safeMap(booking['guest'] ?? inv['guest']);
    final room = AppState.safeMap(inv['room'] ?? booking['room']);

    final guestName = AppState.safeString(guest['name'] ?? inv['guest_name'], 'نزيل محترم');
    final guestPhone = AppState.safeString(guest['phone'] ?? inv['guest_phone'], '—');
    final roomNumber = AppState.safeString(room['room_number'] ?? inv['room_number'] ?? booking['room_number'], '—');
    final roomType = AppState.safeString(
      room['room_type'] ?? room['type'] ?? booking['room_type'] ?? inv['room_type'],
      'غرفة فندقية'
    );
    final invoiceNum = AppState.safeString(inv['invoice_number'], '#${inv['id'] ?? '—'}');
    final amount = AppState.safeDouble(inv['amount'] ?? booking['total_price']);
    final serviceAmount = AppState.safeDouble(inv['service_amount']);
    final serviceDesc = AppState.safeString(inv['service_description'], 'خدمات إضافية');
    final tax = AppState.safeDouble(inv['tax']);
    final total = AppState.safeDouble(inv['total'], amount + serviceAmount + tax);
    final currency = AppState.safeString(inv['currency'] ?? booking['currency'], 'USD');
    final isPaid = inv['status'] == 'paid';
    final dateStr = AppState.safeString(inv['paid_at'] ?? inv['created_at'], DateTime.now().toString().substring(0, 16));
    final hotelName = AppState.safeString(AppState.currentUser?['hotel_name'], 'فندق المحبة والضيافة');
    final cashier = AppState.safeString(AppState.currentUser?['name'], 'موظف الاستقبال');

    final hotelMap = AppState.safeMap(inv['hotel']);
    final hotelSlug = AppState.safeString(hotelMap['slug'] ?? inv['hotel_slug'] ?? AppState.currentUser?['hotel_slug'], 'hotel');
    final invoiceId = AppState.safeString(inv['id']);
    final baseUrl = AppState.serverBaseUrl.isNotEmpty ? AppState.serverBaseUrl : 'https://hotel.ahmadinsights.online';
    final publicUrl = '$baseUrl/$hotelSlug/invoices/$invoiceId';
    final qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data=${Uri.encodeComponent(publicUrl)}';

    String formatNum(dynamic v) {
      final n = AppState.safeDouble(v);
      return n.toStringAsFixed(2);
    }

    final hasService = serviceAmount > 0;
    final hasTax = tax > 0;

    if (mode == 'thermal') {
      final thermalServiceRow = hasService ? '<div class="row"><span>خدمات ($serviceDesc):</span><span>${formatNum(serviceAmount)} $currency</span></div>' : '';
      final thermalTaxRow = hasTax ? '<div class="row"><span>الضريبة والرسوم:</span><span>${formatNum(tax)} $currency</span></div>' : '';
      final statusBadge = isPaid ? '✓ تم السداد بالكامل (PAID)' : '⚠️ غير مسددة (UNPAID)';

      return '''
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>فاتورة إيصال $invoiceNum</title>
<style>
@page { size: 80mm auto; margin: 3mm; }
body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 12px; margin: 0; padding: 6px; direction: rtl; color: #000; }
.text-center { text-align: center; }
.bold { font-weight: bold; }
.divider { border-top: 1px dashed #000; margin: 8px 0; }
.double-divider { border-top: 2px solid #000; margin: 8px 0; }
.row { display: flex; justify-content: space-between; margin: 4px 0; }
.total { font-size: 15px; font-weight: bold; }
.badge { border: 1px solid #000; padding: 3px 6px; border-radius: 4px; display: inline-block; font-size: 11px; font-weight: bold; }
</style>
</head>
<body>
<div class="text-center">
  <div style="font-size: 20px;">🏨</div>
  <div style="font-size: 16px; font-weight: bold;">$hotelName</div>
  <div style="font-size: 11px; color: #444;">إيصال سداد إقامة وخدمات فندقية</div>
</div>
<div class="divider"></div>
<div class="row"><span>رقم الفاتورة:</span><span class="bold">$invoiceNum</span></div>
<div class="row"><span>التاريخ والوقت:</span><span>$dateStr</span></div>
<div class="row"><span>الموظف:</span><span>$cashier</span></div>
<div class="divider"></div>
<div class="row"><span>النزيل:</span><span class="bold">$guestName</span></div>
<div class="row"><span>هاتف النزيل:</span><span>$guestPhone</span></div>
<div class="row"><span>الغرفة:</span><span>رقم $roomNumber ($roomType)</span></div>
<div class="divider"></div>
<div class="row"><span>أجرة الإقامة الأساسية:</span><span>${formatNum(amount)} $currency</span></div>
$thermalServiceRow
$thermalTaxRow
<div class="double-divider"></div>
<div class="row total"><span>الإجمالي المسدد:</span><span>${formatNum(total)} $currency</span></div>
<div class="text-center" style="margin-top: 8px;">
  <span class="badge">$statusBadge</span>
</div>
<div class="divider"></div>
<div class="text-center" style="margin: 8px 0;">
  <img src="$qrImgUrl" width="90" height="90" alt="QR Code" style="display: block; margin: 0 auto 4px auto;">
  <div style="font-size: 8.5px; color: #444; word-break: break-all;">/$hotelSlug/invoices/$invoiceId</div>
</div>
<div class="divider"></div>
<div class="text-center" style="font-size: 10px; color: #555;">
  شكراً لإقامتكم معنا | نتمنى لكم رحلة سعيدة<br>
  يرجى الاحتفاظ بهذا الإيصال للمراجعة
</div>
</body>
</html>
''';
    }

    // A4 Standard format
    final a4ServiceRow = hasService ? '''
<tr>
  <td>خدمات فندقية إضافية: $serviceDesc</td>
  <td>1</td>
  <td>${formatNum(serviceAmount)} $currency</td>
  <td><strong>${formatNum(serviceAmount)} $currency</strong></td>
</tr>
''' : '';

    final a4TaxRow = hasTax ? '''
<tr>
  <td>الضريبة والرسوم الفندقية</td>
  <td>1</td>
  <td>${formatNum(tax)} $currency</td>
  <td><strong>${formatNum(tax)} $currency</strong></td>
</tr>
''' : '';

    final a4ServiceTotal = hasService ? '<div class="totals-row"><span>الخدمات الإضافية:</span><span>${formatNum(serviceAmount)} $currency</span></div>' : '';
    final a4TaxTotal = hasTax ? '<div class="totals-row"><span>الضريبة:</span><span>${formatNum(tax)} $currency</span></div>' : '';
    final a4BadgeClass = isPaid ? 'badge-paid' : 'badge-unpaid';
    final a4BadgeText = isPaid ? 'مدفوعة بالكامل ✓' : 'غير مسددة ⚠️';

    return '''
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>فاتورة رسمية $invoiceNum</title>
<style>
@page { size: A4 portrait; margin: 12mm; }
body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 13px; margin: 0; padding: 20px; direction: rtl; color: #1e293b; background: #fff; }
.header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
.hotel-title { font-size: 22px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
.subtitle { font-size: 12px; color: #64748b; }
.badge-paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 6px 14px; border-radius: 8px; font-weight: bold; font-size: 13px; }
.badge-unpaid { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 6px 14px; border-radius: 8px; font-weight: bold; font-size: 13px; }
.info-grid { display: flex; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 20px; }
.info-col { flex: 1; }
.info-label { font-size: 11px; color: #64748b; font-weight: bold; margin-bottom: 2px; }
.info-val { font-size: 13px; font-weight: bold; color: #0f172a; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th { background: #f1f5f9; color: #0f172a; font-weight: bold; padding: 10px; border: 1px solid #cbd5e1; text-align: right; }
td { padding: 10px; border: 1px solid #cbd5e1; text-align: right; }
.totals-box { width: 280px; margin-right: auto; margin-left: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 30px; }
.totals-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 12px; }
.totals-total { font-size: 15px; font-weight: bold; color: #1d4ed8; border-top: 1px solid #cbd5e1; padding-top: 6px; margin-top: 6px; }
.signatures { display: flex; justify-content: space-around; margin-top: 50px; text-align: center; }
.sign-line { border-bottom: 1px dotted #94a3b8; width: 160px; margin: 30px auto 4px auto; }
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="hotel-title">🏨 $hotelName</div>
    <div class="subtitle">نظام الإدارة الفندقية المتكامل · قسم المحاسبة والفوترة الرسمية</div>
    <div class="subtitle">المصدر / المحاسب: $cashier · تاريخ الإصدار: $dateStr</div>
  </div>
  <div>
    <span class="$a4BadgeClass">$a4BadgeText</span>
    <div style="font-size: 11px; color: #64748b; margin-top: 6px; text-align: left;">REF: $invoiceNum</div>
  </div>
</div>

<div class="info-grid">
  <div class="info-col">
    <div class="info-label">بيانات النزيل المستفيد</div>
    <div class="info-val">$guestName</div>
    <div style="font-size: 12px; color: #475569;">الهاتف: $guestPhone</div>
  </div>
  <div class="info-col">
    <div class="info-label">بيانات الغرفة والإقامة</div>
    <div class="info-val">غرفة رقم $roomNumber</div>
    <div style="font-size: 12px; color: #475569;">تصنيف: $roomType</div>
  </div>
  <div class="info-col">
    <div class="info-label">رقم الفاتورة والمرجع</div>
    <div class="info-val">$invoiceNum</div>
    <div style="font-size: 12px; color: #475569;">العملة المعتمدة: $currency</div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th style="width: 50%;">بيان الخدمة / بند الإقامة</th>
      <th style="width: 15%;">الكمية</th>
      <th style="width: 15%;">السعر الفردي</th>
      <th style="width: 20%;">الإجمالي</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>إقامة فندقية - غرفة رقم $roomNumber ($roomType)</td>
      <td>1</td>
      <td>${formatNum(amount)} $currency</td>
      <td><strong>${formatNum(amount)} $currency</strong></td>
    </tr>
    $a4ServiceRow
    $a4TaxRow
  </tbody>
</table>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
  <!-- QR Code Section -->
  <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; text-align: center; width: 170px;">
    <img src="$qrImgUrl" width="105" height="105" alt="QR Code" style="display: block; margin: 0 auto 4px auto;">
    <div style="font-size: 10px; font-weight: bold; color: #0f172a;">رمز التحقق الرقمي من الفاتورة</div>
    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px; word-break: break-all;">/$hotelSlug/invoices/$invoiceId</div>
  </div>

  <!-- Totals Box -->
  <div class="totals-box" style="margin: 0; width: 300px;">
    <div class="totals-row"><span>المجموع الفرعي للإقامة:</span><span>${formatNum(amount)} $currency</span></div>
    $a4ServiceTotal
    $a4TaxTotal
    <div class="totals-row totals-total"><span>المبلغ الإجمالي النهائي:</span><span>${formatNum(total)} $currency</span></div>
  </div>
</div>

<div class="signatures">
  <div>
    <div style="font-weight: bold; font-size: 12px; color: #475569;">توقيع النزيل المستلم</div>
    <div class="sign-line"></div>
  </div>
  <div>
    <div style="font-weight: bold; font-size: 12px; color: #475569;">المحاسب المسؤول</div>
    <div class="sign-line"></div>
  </div>
  <div>
    <div style="font-weight: bold; font-size: 12px; color: #475569;">ختم وتوقيع إدارة الفندق</div>
    <div class="sign-line"></div>
  </div>
</div>

<div style="border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 10px; text-align: center; font-size: 11px; color: #64748b;">
  <div>$hotelName · نظام الإدارة الفندقية الموحد</div>
  <div style="color: #94a3b8; margin-top: 3px;">شكراً لاختياركم فندقنا · نتمنى لكم إقامة سعيدة ومميزة</div>
</div>
</body>
</html>
''';
  }

  /// Build complete HTML for Accounting Statement
  static String buildStatementHtml(Map<String, dynamic> report) {
    final rows = (report['statement_rows'] as List?) ?? [];
    final hotelName = report['hotel_name'] ?? 'الفندق';
    final hotelAddress = report['hotel_address'] ?? 'الجمهورية العربية السورية';
    final hotelPhone = report['hotel_phone'] ?? '—';
    final periodLabel = report['period_label'] ?? 'كشف حساب';
    final from = report['from'] ?? '';
    final to = report['to'] ?? '';
    final currency = report['currency'] ?? 'all';

    String formatNum(dynamic v) {
      if (v == null) return '0.00';
      final num n = (v is num) ? v : (double.tryParse(v.toString()) ?? 0);
      return n.toStringAsFixed(2);
    }

    final openingSyp = formatNum(report['opening_balance_syp']);
    final openingUsd = formatNum(report['opening_balance_usd']);
    final incomeSyp = formatNum(report['income_syp']);
    final incomeUsd = formatNum(report['income_usd']);
    final expenseSyp = formatNum(report['expense_syp']);
    final expenseUsd = formatNum(report['expense_usd']);
    final closingSyp = formatNum(report['closing_balance_syp']);
    final closingUsd = formatNum(report['closing_balance_usd']);

    final rowsHtml = StringBuffer();
    for (int i = 0; i < rows.length; i++) {
      final r = rows[i];
      final isIncome = r['type'] == 'income';
      final idx = i + 1;
      final rowBg = i % 2 == 0 ? '#fff' : '#f8fafc';
      final notesStr = r['notes'] != null ? ' (${r['notes']})' : '';
      final inflowStr = isIncome ? '+${formatNum(r['inflow'])}' : '—';
      final outflowStr = !isIncome ? '-${formatNum(r['outflow'])}' : '—';
      final inflowColor = isIncome ? '#059669' : '#000';
      final outflowColor = !isIncome ? '#dc2626' : '#000';

      rowsHtml.write('''
<tr style="background: $rowBg;">
  <td style="text-align: center;">$idx</td>
  <td style="text-align: center;">${r['date'] ?? ''}</td>
  <td style="font-weight: bold;">${r['ref'] ?? ''}</td>
  <td>${r['title'] ?? ''}$notesStr</td>
  <td style="text-align: center;">${r['currency'] ?? ''}</td>
  <td style="color: $inflowColor; font-weight: ${isIncome ? 'bold' : 'normal'};">$inflowStr</td>
  <td style="color: $outflowColor; font-weight: ${!isIncome ? 'bold' : 'normal'};">$outflowStr</td>
  <td style="font-weight: bold; color: #1e293b;">${formatNum(r['balance'])}</td>
</tr>
''');
    }

    if (rows.isEmpty) {
      rowsHtml.write('<tr><td colspan="8" style="text-align: center; padding: 20px; font-weight: bold; color: #64748b;">لا توجد حركات مالية مسجلة في هذه الفترة الزمنية.</td></tr>');
    }

    final currencyLabel = currency == 'all' ? 'جميع العملات (SYP + USD)' : currency;
    final nowStr = DateTime.now().toString().substring(0, 16);

    return '''
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>$periodLabel</title>
<style>
@page { size: A4 portrait; margin: 12mm; }
body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 12px; margin: 0; padding: 15px; direction: rtl; color: #0f172a; background: #fff; }
.header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
.title { font-size: 20px; font-weight: bold; }
.subtitle { font-size: 11px; color: #64748b; }
.ribbon { display: flex; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 15px; font-size: 11px; font-weight: bold; }
.kpi-row { display: flex; gap: 10px; margin-bottom: 20px; }
.kpi-card { flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; background: #f8fafc; }
.kpi-title { font-size: 10px; font-weight: bold; color: #475569; margin-bottom: 4px; }
.kpi-val { font-size: 14px; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
th { background: #f1f5f9; color: #0f172a; font-weight: bold; padding: 8px; border: 1px solid #cbd5e1; font-size: 11px; }
td { padding: 6px 8px; border: 1px solid #cbd5e1; font-size: 11px; }
.signatures { display: flex; justify-content: space-around; margin-top: 40px; text-align: center; }
.sign-line { border-bottom: 1px dotted #94a3b8; width: 150px; margin: 30px auto 4px auto; }
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="title">🏢 $hotelName</div>
    <div class="subtitle">قسم الإدارة المالية والمحاسبية · FINANCIAL DEPARTMENT</div>
    <div class="subtitle">$hotelAddress · هاتف: $hotelPhone</div>
  </div>
  <div style="text-align: left;">
    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; display: inline-block;">DOCUMENT TYPE: ACCOUNT STATEMENT</div>
    <div class="subtitle" style="margin-top: 4px;">تاريخ التوليد: $nowStr</div>
  </div>
</div>

<div class="ribbon">
  <span>نوع التقرير: $periodLabel</span>
  <span>الفترة: $from ⬅️ $to</span>
  <span>العملة: $currencyLabel</span>
  <span>العمليات: ${rows.length}</span>
</div>

<div class="kpi-row">
  <div class="kpi-card">
    <div class="kpi-title">الرصيد الافتتاحي (السابق)</div>
    <div class="kpi-val">$openingSyp <span style="font-size: 10px; color: #64748b;">ل.س</span></div>
    <div class="kpi-val">\$$openingUsd <span style="font-size: 10px; color: #64748b;">USD</span></div>
  </div>
  <div class="kpi-card" style="background: #ecfdf5; border-color: #a7f3d0;">
    <div class="kpi-title" style="color: #065f46;">إجمالي الوارد (دائن)</div>
    <div class="kpi-val" style="color: #059669;">$incomeSyp <span style="font-size: 10px;">ل.س</span></div>
    <div class="kpi-val" style="color: #059669;">\$$incomeUsd <span style="font-size: 10px;">USD</span></div>
  </div>
  <div class="kpi-card" style="background: #fef2f2; border-color: #fecaca;">
    <div class="kpi-title" style="color: #991b1b;">إجمالي الصادر (مدين)</div>
    <div class="kpi-val" style="color: #dc2626;">$expenseSyp <span style="font-size: 10px;">ل.س</span></div>
    <div class="kpi-val" style="color: #dc2626;">\$$expenseUsd <span style="font-size: 10px;">USD</span></div>
  </div>
  <div class="kpi-card" style="background: #eff6ff; border-color: #bfdbfe;">
    <div class="kpi-title" style="color: #1e40af;">الرصيد الختامي الحالي</div>
    <div class="kpi-val" style="color: #1d4ed8;">$closingSyp <span style="font-size: 10px;">ل.س</span></div>
    <div class="kpi-val" style="color: #1d4ed8;">\$$closingUsd <span style="font-size: 10px;">USD</span></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th style="width: 5%;">#</th>
      <th style="width: 12%;">التاريخ</th>
      <th style="width: 14%;">المرجع</th>
      <th style="width: 29%;">البيان والتفاصيل</th>
      <th style="width: 8%;">العملة</th>
      <th style="width: 11%;">الوارد (+)</th>
      <th style="width: 11%;">الصادر (-)</th>
      <th style="width: 10%;">الرصيد</th>
    </tr>
  </thead>
  <tbody>
    $rowsHtml
  </tbody>
</table>

<div class="signatures">
  <div>
    <div style="font-weight: bold; font-size: 11px; color: #475569;">المحاسب المعتمد</div>
    <div class="sign-line"></div>
  </div>
  <div>
    <div style="font-weight: bold; font-size: 11px; color: #475569;">الختم الرسمي للفندق</div>
    <div class="sign-line"></div>
  </div>
  <div>
    <div style="font-weight: bold; font-size: 11px; color: #475569;">مدير الفندق / الإدارة المالية</div>
    <div class="sign-line"></div>
  </div>
</div>
</body>
</html>
''';
  }
}

// ============================================================================
// PRINTER SETUP & NATIVE SELECTION MODAL
// ============================================================================
class PrinterOptionsDialog extends StatefulWidget {
  final String title;
  final String documentType; // 'invoice' or 'statement'
  final String defaultFormat; // 'thermal' or 'a4'
  final String htmlContent;
  final String defaultFileName;

  const PrinterOptionsDialog({
    super.key,
    required this.title,
    required this.documentType,
    required this.defaultFormat,
    required this.htmlContent,
    required this.defaultFileName,
  });

  @override
  State<PrinterOptionsDialog> createState() => _PrinterOptionsDialogState();
}

class _PrinterOptionsDialogState extends State<PrinterOptionsDialog> {
  List<String> _printers = [];
  String? _selectedPrinter;
  bool _isLoadingPrinters = true;
  String _format = 'thermal';
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _format = widget.defaultFormat;
    _detectPrinters();
  }

  Future<void> _detectPrinters() async {
    try {
      setState(() => _isLoadingPrinters = true);
      final list = await NativePrintAndSaveService.getConnectedPrinters();
      if (mounted) {
        setState(() {
          _printers = list;
          _selectedPrinter = list.isNotEmpty ? list.first : 'طابعة النظام الافتراضية';
          _isLoadingPrinters = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _printers = ['طابعة النظام الافتراضية (Default Printer)', 'Microsoft Print to PDF'];
          _selectedPrinter = _printers.first;
          _isLoadingPrinters = false;
        });
      }
    }
  }

  Future<void> _printNow() async {
    try {
      Navigator.pop(context);
      await NativePrintAndSaveService.printWithNativeDialog(widget.htmlContent);
    } catch (e) {
      AppState.showGlobalErrorDialog(
        title: 'تنبيه أثناء إرسال أمر الطباعة',
        message: 'حدث خطأ أثناء تشغيل نافذة الطباعة: $e',
      );
    }
  }

  Future<void> _savePdf() async {
    try {
      setState(() => _isSaving = true);
      final chosenPath = await NativePrintAndSaveService.showSaveFileDialog(
        defaultFileName: widget.defaultFileName,
        filter: 'ملف PDF (*.pdf)|*.pdf|ملف HTML (*.html)|*.html',
      );

      if (chosenPath != null && chosenPath.isNotEmpty) {
        final success = await NativePrintAndSaveService.saveDocument(widget.htmlContent, chosenPath);
        if (mounted) {
          if (success) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('✓ تم حفظ المستند بنجاح في:\n$chosenPath'),
                backgroundColor: const Color(0xFF059669),
                duration: const Duration(seconds: 6),
                action: SnackBarAction(
                  label: '📂 فتح الملف',
                  textColor: Colors.white,
                  onPressed: () => NativePrintAndSaveService.openFile(chosenPath),
                ),
              ),
            );
            Navigator.pop(context);
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('تعذر حفظ الملف، يرجى التحقق من أذونات المجلد.'), backgroundColor: Color(0xFFDC2626)),
            );
          }
        }
      }
    } catch (e) {
      AppState.showGlobalErrorDialog(
        title: 'تنبيه أثناء حفظ الملف',
        message: 'حدث خطأ أثناء حفظ الملف: $e',
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                const Icon(Icons.print, color: Color(0xFF1D4ED8)),
                const SizedBox(width: 8),
                Text(widget.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              ],
            ),
            IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
          ],
        ),
        content: SizedBox(
          width: 500,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFCBD5E1)),
                ),
                child: Row(
                  children: const [
                    Icon(Icons.info_outline, size: 18, color: Color(0xFF1D4ED8)),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'عند الضغط على "طباعة الآن" ستظهر نافذة خيارات وإعدادات الطابعات المتصلة بالجهاز كما في الموقع تماماً.',
                        style: TextStyle(fontSize: 11, color: Color(0xFF334155), height: 1.4),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('الطابعات المتصلة بالجهاز حالياً:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                  TextButton.icon(
                    onPressed: _detectPrinters,
                    icon: const Icon(Icons.refresh, size: 14),
                    label: const Text('تحديث القائمة', style: TextStyle(fontSize: 11)),
                  ),
                ],
              ),
              const SizedBox(height: 4),

              if (_isLoadingPrinters)
                const Center(child: Padding(padding: EdgeInsets.all(8), child: CircularProgressIndicator()))
              else
                DropdownButtonFormField<String>(
                  value: _printers.contains(_selectedPrinter) ? _selectedPrinter : (_printers.isNotEmpty ? _printers.first : null),
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.print_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                  items: _printers.map((p) => DropdownMenuItem(value: p, child: Text(p, style: const TextStyle(fontSize: 12)))).toList(),
                  onChanged: (v) => setState(() => _selectedPrinter = v),
                ),
              const SizedBox(height: 16),

              if (widget.documentType == 'invoice') ...[
                const Text('نمط وتنسيق الورقة:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: () => setState(() => _format = 'thermal'),
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _format == 'thermal' ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                            border: Border.all(color: _format == 'thermal' ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1), width: _format == 'thermal' ? 2 : 1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              Icon(_format == 'thermal' ? Icons.radio_button_checked : Icons.radio_button_unchecked, color: const Color(0xFF1D4ED8), size: 16),
                              const SizedBox(width: 6),
                              const Text('🧾 حراري 80mm', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: InkWell(
                        onTap: () => setState(() => _format = 'a4'),
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _format == 'a4' ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                            border: Border.all(color: _format == 'a4' ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1), width: _format == 'a4' ? 2 : 1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              Icon(_format == 'a4' ? Icons.radio_button_checked : Icons.radio_button_unchecked, color: const Color(0xFF1D4ED8), size: 16),
                              const SizedBox(width: 6),
                              const Text('📄 ورقة A4 قياسية', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFF0F172A),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: _isSaving ? null : _savePdf,
            icon: _isSaving ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.save_alt, size: 16),
            label: const Text('💾 اختيار مكان الحفظ وحفظ PDF', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF1D4ED8),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: _printNow,
            icon: const Icon(Icons.print, size: 16),
            label: const Text('🖨️ طباعة الآن (نافذة الطابعات)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// TOP SETTINGS & LANGUAGE MODAL
// ============================================================================
class SettingsAndLanguageDialog extends StatefulWidget {
  final VoidCallback onLogout;
  const SettingsAndLanguageDialog({super.key, required this.onLogout});

  @override
  State<SettingsAndLanguageDialog> createState() => _SettingsAndLanguageDialogState();
}

class _SettingsAndLanguageDialogState extends State<SettingsAndLanguageDialog> {
  late String _selectedLocale;

  @override
  void initState() {
    super.initState();
    _selectedLocale = AppLocale.current.value;
  }

  void _changeLanguage(String lang) {
    setState(() {
      _selectedLocale = lang;
    });
    AppLocale.setLocale(lang);
  }

  @override
  Widget build(BuildContext context) {
    final user = AppState.currentUser;

    return Directionality(
      textDirection: AppLocale.direction,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: const EdgeInsets.all(24),
        titlePadding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.settings, color: Color(0xFF1D4ED8), size: 24),
                ),
                const SizedBox(width: 12),
                Text(
                  AppLocale.tr('إعدادات النظام واللغة', 'System Settings & Language'),
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            IconButton(
              icon: const Icon(Icons.close, size: 20, color: Color(0xFF64748B)),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        content: SizedBox(
          width: 440,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // User info header
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: const Color(0xFF1D4ED8),
                      radius: 20,
                      child: Text(
                        (user?['name'] ?? 'U').substring(0, 1),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            user?['name'] ?? '',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            '${AppLocale.tr("الدور: ", "Role: ")}${user?['role'] ?? ''} · ${user?['hotel_name'] ?? ''}',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Language selector section
              Text(
                AppLocale.tr('اختيار لغة النظام (Language)', 'System Language'),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF334155)),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: () => _changeLanguage('ar'),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
                        decoration: BoxDecoration(
                          color: _selectedLocale == 'ar' ? const Color(0xFFEFF6FF) : Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _selectedLocale == 'ar' ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1),
                            width: _selectedLocale == 'ar' ? 2 : 1,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('🇸🇾', style: TextStyle(fontSize: 18)),
                            const SizedBox(width: 8),
                            Text(
                              'العربية (RTL)',
                              style: TextStyle(
                                fontWeight: _selectedLocale == 'ar' ? FontWeight.bold : FontWeight.w500,
                                color: _selectedLocale == 'ar' ? const Color(0xFF1D4ED8) : const Color(0xFF334155),
                                fontSize: 13,
                              ),
                            ),
                            if (_selectedLocale == 'ar') ...[
                              const SizedBox(width: 6),
                              const Icon(Icons.check_circle, color: Color(0xFF1D4ED8), size: 16),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: () => _changeLanguage('en'),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
                        decoration: BoxDecoration(
                          color: _selectedLocale == 'en' ? const Color(0xFFEFF6FF) : Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _selectedLocale == 'en' ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1),
                            width: _selectedLocale == 'en' ? 2 : 1,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('🇬🇧', style: TextStyle(fontSize: 18)),
                            const SizedBox(width: 8),
                            Text(
                              'English (LTR)',
                              style: TextStyle(
                                fontWeight: _selectedLocale == 'en' ? FontWeight.bold : FontWeight.w500,
                                color: _selectedLocale == 'en' ? const Color(0xFF1D4ED8) : const Color(0xFF334155),
                                fontSize: 13,
                              ),
                            ),
                            if (_selectedLocale == 'en') ...[
                              const SizedBox(width: 6),
                              const Icon(Icons.check_circle, color: Color(0xFF1D4ED8), size: 16),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 24),
              const Divider(color: Color(0xFFE2E8F0)),
              const SizedBox(height: 16),

              // Prominent Logout button directly below language selection
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFDC2626),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 1,
                ),
                icon: const Icon(Icons.logout, size: 18),
                label: Text(
                  AppLocale.tr('تسجيل الخروج من النظام', 'Logout from System'),
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                ),
                onPressed: () {
                  widget.onLogout();
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// NOTIFICATIONS CENTER & BROADCAST SYSTEM
// ============================================================================
class NotificationsCenterDialog extends StatefulWidget {
  final Function(String categoryId, [String? subTabId]) onNavigate;
  const NotificationsCenterDialog({super.key, required this.onNavigate});

  @override
  State<NotificationsCenterDialog> createState() => _NotificationsCenterDialogState();
}

class _NotificationsCenterDialogState extends State<NotificationsCenterDialog> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  int _unreadCount = 0;
  List<dynamic> _notifications = [];
  String _activeFilter = 'all'; // all, unread, booking, operations, finance

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/notifications'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _unreadCount = (body['unread_count'] is num) ? (body['unread_count'] as num).toInt() : 0;
          _notifications = (body['data'] as List?) ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _markAllRead() async {
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/notifications/read-all'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        setState(() {
          _unreadCount = 0;
          for (var n in _notifications) {
            n['is_read'] = true;
          }
        });
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم تحديد كافة الإشعارات كمقروءة.')));
      }
    } catch (_) {}
  }

  Future<void> _markSingleRead(int id, Map<String, dynamic> notif) async {
    try {
      await http.post(
        Uri.parse('${AppState.apiUrl}/staff/notifications/$id/read'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      setState(() {
        notif['is_read'] = true;
        if (_unreadCount > 0) _unreadCount--;
      });
    } catch (_) {}
  }

  void _handleNotificationClick(Map<String, dynamic> notif) async {
    final id = notif['id'];
    if (id != null && notif['is_read'] != true) {
      await _markSingleRead(id, notif);
    }

    Navigator.pop(context);

    final type = (notif['type'] ?? '').toString();
    final link = (notif['link'] ?? '').toString();

    if (type.contains('booking') || link == '/bookings' || type == 'check_in' || type == 'check_out') {
      widget.onNavigate('reception', 'bookings');
    } else if (type.contains('clean') || link == '/cleaning') {
      widget.onNavigate('operations', 'cleaning');
    } else if (type.contains('maint') || link == '/maintenance') {
      widget.onNavigate('operations', 'maintenance');
    } else if (type.contains('invoice') || link == '/invoices') {
      widget.onNavigate('finance', 'invoices');
    } else if (type.contains('complaint') || link == '/complaints') {
      widget.onNavigate('operations', 'complaints');
    }
  }

  void _openBroadcastDialog() {
    showDialog(
      context: context,
      builder: (ctx) => BroadcastNotificationDialog(onSent: _fetch),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _notifications.where((n) {
      if (_activeFilter == 'unread') return n['is_read'] != true;
      final type = (n['type'] ?? '').toString();
      if (_activeFilter == 'booking') return type.contains('booking') || type == 'check_in' || type == 'check_out';
      if (_activeFilter == 'operations') return type.contains('clean') || type.contains('maint') || type.contains('complaint');
      if (_activeFilter == 'finance') return type.contains('invoice') || type.contains('account');
      return true;
    }).toList();

    final isOwner = AppState.isReadOnlyOwner();

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
        child: Container(
          width: 780,
          constraints: const BoxConstraints(maxHeight: 740),
          color: Colors.white,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 18),
                decoration: const BoxDecoration(
                  color: Color(0xFF0F172A),
                  borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(color: const Color(0xFF1D4ED8), borderRadius: BorderRadius.circular(10)),
                          child: const Icon(Icons.notifications_active, color: Colors.white, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                const Text('مركز الإشعارات والتنبيهات الفندقية', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                                const SizedBox(width: 8),
                                if (_unreadCount > 0)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(color: const Color(0xFFDC2626), borderRadius: BorderRadius.circular(12)),
                                    child: Text('$_unreadCount ${AppLocale.tr("جديد", "new")}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                                  ),
                              ],
                            ),
                            Text(AppLocale.tr('متابعة تنبيهات الحجوزات، التنظيف، الصيانة، الفواتير، والتعاميم الإدارية', 'Follow up on bookings, cleaning, maintenance, invoices, and notices'), style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                          ],
                        ),
                      ],
                    ),
                    Row(
                      children: [
                        if (!isOwner)
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF1D4ED8),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            icon: const Icon(Icons.campaign, size: 16),
                            label: Text(AppLocale.tr('📢 إرسال إشعار للجميع', '📢 Broadcast to All'), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            onPressed: _openBroadcastDialog,
                          ),
                        const SizedBox(width: 8),
                        IconButton(
                          onPressed: () => Navigator.pop(context),
                          icon: const Icon(Icons.close, color: Colors.white),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Sub-toolbar with filter tabs & mark all read
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                decoration: const BoxDecoration(
                  color: Color(0xFFF8FAFC),
                  border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _filterChip('all', '${AppLocale.tr("الكل", "All")} (${_notifications.length})'),
                          const SizedBox(width: 6),
                          _filterChip('unread', '${AppLocale.tr("غير مقروءة", "Unread")} ($_unreadCount)'),
                          const SizedBox(width: 6),
                          _filterChip('booking', AppLocale.tr('🏨 الحجوزات', '🏨 Bookings')),
                          const SizedBox(width: 6),
                          _filterChip('operations', AppLocale.tr('🛠️ النظافة والصيانة', '🛠️ Operations')),
                          const SizedBox(width: 6),
                          _filterChip('finance', AppLocale.tr('💵 المالية', '💵 Finance')),
                        ],
                      ),
                    ),
                    if (_unreadCount > 0)
                      TextButton.icon(
                        onPressed: _markAllRead,
                        icon: const Icon(Icons.done_all, size: 16, color: Color(0xFF1D4ED8)),
                        label: const Text('تحديد الكل كمقروء', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8))),
                      ),
                  ],
                ),
              ),

              // Notifications List
              Expanded(
                child: _isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : filtered.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: const [
                                Icon(Icons.notifications_none, size: 48, color: Color(0xFFCBD5E1)),
                                SizedBox(height: 12),
                                Text('لا توجد إشعارات مسجلة ضمن هذا التصنيف', style: TextStyle(color: Color(0xFF64748B), fontSize: 14)),
                              ],
                            ),
                          )
                        : Scrollbar(
                            controller: _scrollController,
                            thumbVisibility: true,
                            trackVisibility: true,
                            interactive: true,
                            child: ListView.separated(
                              controller: _scrollController,
                              padding: const EdgeInsets.all(16),
                              itemCount: filtered.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (ctx, idx) {
                                final notif = filtered[idx];
                                final isRead = notif['is_read'] == true;
                                final iconStr = (notif['icon'] ?? '🔔').toString();
                                return InkWell(
                                  onTap: () => _handleNotificationClick(notif),
                                  borderRadius: BorderRadius.circular(12),
                                  child: Container(
                                    padding: const EdgeInsets.all(14),
                                    decoration: BoxDecoration(
                                      color: isRead ? Colors.white : const Color(0xFFF0FDF4),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(
                                        color: isRead ? const Color(0xFFE2E8F0) : const Color(0xFF86EFAC),
                                        width: isRead ? 1 : 1.5,
                                      ),
                                      boxShadow: [
                                        BoxShadow(
                                          color: Colors.black.withValues(alpha: 0.02),
                                          blurRadius: 4,
                                          offset: const Offset(0, 2),
                                        ),
                                      ],
                                    ),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          width: 40,
                                          height: 40,
                                          decoration: BoxDecoration(
                                            color: isRead ? const Color(0xFFF1F5F9) : const Color(0xFFDCFCE7),
                                            borderRadius: BorderRadius.circular(10),
                                          ),
                                          child: Center(
                                            child: Text(iconStr, style: const TextStyle(fontSize: 18)),
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Text(
                                                    notif['title'] ?? 'إشعار فندقي',
                                                    style: TextStyle(
                                                      fontWeight: isRead ? FontWeight.bold : FontWeight.w900,
                                                      fontSize: 13,
                                                      color: const Color(0xFF0F172A),
                                                    ),
                                                  ),
                                                  Row(
                                                    children: [
                                                      Text(
                                                        notif['time_ago'] ?? notif['created_at'] ?? '',
                                                        style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                                                      ),
                                                      if (!isRead) ...[
                                                        const SizedBox(width: 6),
                                                        Container(
                                                          width: 8,
                                                          height: 8,
                                                          decoration: const BoxDecoration(
                                                            color: Color(0xFF10B981),
                                                            shape: BoxShape.circle,
                                                          ),
                                                        ),
                                                      ],
                                                    ],
                                                  ),
                                                ],
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                notif['message'] ?? '',
                                                style: const TextStyle(fontSize: 12, color: Color(0xFF475569), height: 1.4),
                                              ),
                                              if (notif['link'] != null) ...[
                                                const SizedBox(height: 6),
                                                Row(
                                                  children: const [
                                                    Text('انقر هنا للمعاينة والانتقال للقسم مباشرة', style: TextStyle(fontSize: 11, color: Color(0xFF1D4ED8), fontWeight: FontWeight.bold)),
                                                    SizedBox(width: 4),
                                                    Icon(Icons.arrow_back, size: 12, color: Color(0xFF1D4ED8)),
                                                  ],
                                                ),
                                              ],
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _filterChip(String id, String label) {
    final selected = _activeFilter == id;
    return ChoiceChip(
      label: Text(label, style: TextStyle(fontSize: 11, fontWeight: selected ? FontWeight.bold : FontWeight.normal)),
      selected: selected,
      onSelected: (_) => setState(() => _activeFilter = id),
      selectedColor: const Color(0xFF1D4ED8),
      labelStyle: TextStyle(color: selected ? Colors.white : const Color(0xFF475569)),
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    );
  }
}

// ============================================================================
// BROADCAST NOTIFICATION DIALOG
// ============================================================================
class BroadcastNotificationDialog extends StatefulWidget {
  final VoidCallback onSent;
  const BroadcastNotificationDialog({super.key, required this.onSent});

  @override
  State<BroadcastNotificationDialog> createState() => _BroadcastNotificationDialogState();
}

class _BroadcastNotificationDialogState extends State<BroadcastNotificationDialog> {
  final _titleCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  String _target = 'everyone';
  String _icon = '📢';
  bool _isSending = false;

  final List<Map<String, String>> _targets = [
    {'id': 'everyone', 'label': '👥 الجميع (كافة الموظفين، المدراء، والنزلاء)'},
    {'id': 'staff', 'label': '👔 جميع موظفي الفندق والإدارة'},
    {'id': 'reception', 'label': '🛎️ قسم الاستقبال والتسكين فقط'},
    {'id': 'cleaner', 'label': '🧹 قسم النظافة والتدبير الفندقي فقط'},
    {'id': 'maintenance', 'label': '🔧 قسم الصيانة والدعم الفني فقط'},
    {'id': 'guest', 'label': '🧳 النزلاء المقيمون فقط'},
  ];

  final List<String> _icons = ['📢', '⚠️', '🏨', '🛎️', '🧹', '🔧', '💵', '🎉', 'ℹ️'];

  @override
  void dispose() {
    _titleCtrl.dispose();
    _messageCtrl.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final title = _titleCtrl.text.trim();
    final message = _messageCtrl.text.trim();
    if (title.isEmpty || message.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('يرجى ملء عنوان ونص الإشعار.')));
      return;
    }

    setState(() => _isSending = true);
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/notifications/broadcast'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'title': title,
          'message': message,
          'target': _target,
          'icon': _icon,
        }),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(body['message'] ?? 'تم إرسال الإشعار بنجاح.'), backgroundColor: const Color(0xFF059669)),
        );
        widget.onSent();
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(body['message'] ?? 'تعذر إرسال الإشعار.'), backgroundColor: const Color(0xFFDC2626)),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('خطأ بالاتصال: $e')));
    }
    if (mounted) setState(() => _isSending = false);
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: const [
            Icon(Icons.campaign, color: Color(0xFF1D4ED8)),
            SizedBox(width: 8),
            Text('📢 إرسال إشعار / تعميم للجميع', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: SizedBox(
          width: 520,
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('الفئة المستهدفة بالإشعار:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  initialValue: _target,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                  items: _targets.map((t) => DropdownMenuItem(value: t['id'], child: Text(t['label']!, style: const TextStyle(fontSize: 12)))).toList(),
                  onChanged: (v) => setState(() => _target = v!),
                ),
                const SizedBox(height: 14),

                const Text('أيقونة الإشعار:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 8,
                  children: _icons.map((ic) {
                    final sel = _icon == ic;
                    return InkWell(
                      onTap: () => setState(() => _icon = ic),
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: sel ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                          border: Border.all(color: sel ? const Color(0xFF1D4ED8) : const Color(0xFFCBD5E1), width: sel ? 2 : 1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(ic, style: const TextStyle(fontSize: 18)),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 14),

                const Text('عنوان الإشعار:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 6),
                TextField(
                  controller: _titleCtrl,
                  decoration: InputDecoration(
                    hintText: 'مثال: تنبيه هام لجميع موظفي الاستقبال / موعد الصيانة...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                ),
                const SizedBox(height: 14),

                const Text('نص الإشعار والتفاصيل:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 6),
                TextField(
                  controller: _messageCtrl,
                  maxLines: 4,
                  decoration: InputDecoration(
                    hintText: 'اكتب تفاصيل التنبيه أو التعليمات هنا...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF1D4ED8),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: _isSending ? null : _send,
            icon: _isSending ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send, size: 16),
            label: const Text('إرسال وتعميم الإشعار', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// SUBSCRIPTION & LICENSING DETAILS DIALOG
// ============================================================================
class SubscriptionDetailsDialog extends StatefulWidget {
  final Map<String, dynamic>? subscriptionData;
  final Future<void> Function() onRefresh;

  const SubscriptionDetailsDialog({
    super.key,
    required this.subscriptionData,
    required this.onRefresh,
  });

  @override
  State<SubscriptionDetailsDialog> createState() => _SubscriptionDetailsDialogState();
}

class _SubscriptionDetailsDialogState extends State<SubscriptionDetailsDialog> {
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    final sub = widget.subscriptionData?['subscription'] ?? {};
    final hotel = widget.subscriptionData?['hotel'] ?? {};
    final plans = (widget.subscriptionData?['plans'] as List?) ?? [];

    final isExpired = sub['is_expired'] == true;
    final isExpiringSoon = sub['is_expiring_soon'] == true;
    final remainingDays = (sub['remaining_days'] is num) ? (sub['remaining_days'] as num).toInt() : 0;
    final planName = sub['plan_name'] ?? 'الباقة القياسية';
    final statusLabel = sub['status_label'] ?? (isExpired ? 'منتهي' : 'نشط');

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
        child: Container(
          width: 820,
          constraints: const BoxConstraints(maxHeight: 700),
          padding: const EdgeInsets.all(28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFBFDBFE)),
                        ),
                        child: const Icon(Icons.workspace_premium_rounded, color: Color(0xFF1D4ED8), size: 28),
                      ),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'ترخيص واشتراك فندقك في النظام',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            'الفندق: ${hotel['name'] ?? ''} • رقم المنشأة: #${hotel['id'] ?? ''}',
                            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ],
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Color(0xFF64748B)),
                  ),
                ],
              ),
              const Divider(height: 28),

              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Status Banner
                      if (isExpired)
                        Container(
                          padding: const EdgeInsets.all(14),
                          margin: const EdgeInsets.only(bottom: 16),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEE2E2),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFFCA5A5)),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 24),
                              SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  'تحذير: انتهت صلاحية اشتراك فندقكم! يرجى تجديد الاشتراك فوراً أو التواصل مع الدعم الفني لاستمرار مزامنة البيانات.',
                                  style: TextStyle(color: Color(0xFF991B1B), fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                              ),
                            ],
                          ),
                        )
                      else if (isExpiringSoon)
                        Container(
                          padding: const EdgeInsets.all(14),
                          margin: const EdgeInsets.only(bottom: 16),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF3C7),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFFCD34D)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.warning_amber_rounded, color: Color(0xFFD97706), size: 24),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  'تنبيه: اشتراككم قارب على الانتهاء! متبقي $remainingDays يوماً فقط. يرجى تجديد الاشتراك لضمان عدم انقطاع الخدمة.',
                                  style: const TextStyle(color: Color(0xFF92400E), fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                              ),
                            ],
                          ),
                        ),

                      // Current Subscription Card
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.15),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    const Text('👑', style: TextStyle(fontSize: 24)),
                                    const SizedBox(width: 10),
                                    Text(
                                      planName,
                                      style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                                    ),
                                  ],
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: isExpired
                                        ? const Color(0xFFDC2626)
                                        : (isExpiringSoon ? const Color(0xFFD97706) : const Color(0xFF16A34A)),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    statusLabel,
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 18),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _statItem('الأيام المتبقية', isExpired ? '0 يوم' : '$remainingDays يوم', Icons.calendar_today_rounded),
                                _statItem('تاريخ البداية', sub['start_date'] ?? 'غير محدد', Icons.play_circle_outline),
                                _statItem('تاريخ الانتهاء', sub['end_date'] ?? 'غير محدد', Icons.event_busy_rounded),
                                _statItem('سعة الغرف', sub['max_rooms'] != null && sub['max_rooms'] > 0 ? '${sub['max_rooms']} غرفة' : 'غير محدود', Icons.hotel_rounded),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Available Plans for Upgrades
                      const Text(
                        'الباقات والخطط المتاحة في النظام:',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 12,
                        runSpacing: 12,
                        children: plans.map((p) {
                          final isCurrent = p['name'] == planName;
                          return Container(
                            width: 240,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: isCurrent ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: isCurrent ? const Color(0xFF1D4ED8) : const Color(0xFFE2E8F0),
                                width: isCurrent ? 2 : 1,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      p['name'] ?? '',
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14,
                                        color: isCurrent ? const Color(0xFF1D4ED8) : const Color(0xFF1E293B),
                                      ),
                                    ),
                                    if (isCurrent)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(color: const Color(0xFF1D4ED8), borderRadius: BorderRadius.circular(6)),
                                        child: const Text('باقتك الحالية', style: TextStyle(color: Colors.white, fontSize: 10)),
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  '${p['price']} ${p['currency'] ?? "USD"} / ${p['billing_interval'] == "yearly" ? "سنوياً" : "شهرياً"}',
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  '🚪 السعة: ${p['max_rooms'] != null && p['max_rooms'] > 0 ? "${p['max_rooms']} غرفة" : "غرف غير محدودة"}',
                                  style: const TextStyle(fontSize: 12, color: Color(0xFF475569)),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  p['description'] ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                          );
                        }).toList(),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  OutlinedButton.icon(
                    onPressed: () async {
                      setState(() => _isLoading = true);
                      await widget.onRefresh();
                      if (mounted) setState(() => _isLoading = false);
                    },
                    icon: _isLoading ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.refresh, size: 16),
                    label: const Text('تحديث بيانات الترخيص'),
                  ),
                  Row(
                    children: [
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1D4ED8),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () {
                          Navigator.pop(context);
                          showDialog(
                            context: context,
                            builder: (ctx) => const SupportCenterDialog(initialDepartment: 'billing'),
                          );
                        },
                        icon: const Icon(Icons.support_agent_rounded, size: 18),
                        label: const Text('طلب تجديد أو ترقية الاشتراك (تذكرة دعم)'),
                      ),
                      const SizedBox(width: 8),
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('إغلاق'),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statItem(String label, String value, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: const Color(0xFF93C5FD), size: 20),
        const SizedBox(height: 6),
        Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
      ],
    );
  }
}

// ============================================================================
// TECHNICAL SUPPORT TICKETS SYSTEM DIALOG
// ============================================================================
class SupportCenterDialog extends StatefulWidget {
  final String? initialDepartment;
  const SupportCenterDialog({super.key, this.initialDepartment});

  @override
  State<SupportCenterDialog> createState() => _SupportCenterDialogState();
}

class _SupportCenterDialogState extends State<SupportCenterDialog> {
  bool _isLoading = true;
  List<dynamic> _tickets = [];
  bool _isCreating = false;

  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  String _department = 'technical';
  String _priority = 'medium';
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialDepartment != null) {
      _department = widget.initialDepartment!;
      _isCreating = true;
    }
    _fetchTickets();
  }

  Future<void> _fetchTickets() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/support/tickets'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _tickets = (body['data'] as List?) ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _submitNewTicket() async {
    final sub = _subjectCtrl.text.trim();
    final msg = _messageCtrl.text.trim();
    if (sub.isEmpty || msg.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('يرجى كتابة عنوان التذكرة وتفاصيل الرسالة.')));
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/support/tickets'),
        headers: {
          'Authorization': 'Bearer ${AppState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'subject': sub,
          'department': _department,
          'priority': _priority,
          'message': msg,
        }),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        _subjectCtrl.clear();
        _messageCtrl.clear();
        setState(() => _isCreating = false);
        _fetchTickets();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(body['message'] ?? 'تم إرسال التذكرة بنجاح!'),
            backgroundColor: Colors.green,
          ));
        }
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(body['message'] ?? 'فشل إرسال التذكرة.'),
          backgroundColor: Colors.red,
        ));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('خطأ في الاتصال بالخادم.')));
      }
    }
    if (mounted) setState(() => _isSubmitting = false);
  }

  void _openThread(int ticketId) {
    showDialog(
      context: context,
      builder: (ctx) => SupportTicketThreadDialog(ticketId: ticketId),
    ).then((_) => _fetchTickets());
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
        child: Container(
          width: 860,
          constraints: const BoxConstraints(maxHeight: 720),
          padding: const EdgeInsets.all(26),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFBFDBFE)),
                        ),
                        child: const Icon(Icons.headset_mic_rounded, color: Color(0xFF1D4ED8), size: 26),
                      ),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'مركز الدعم الفني وتذاكر المساعدة 🎧',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            'تواصل مباشر مع فريق إدارة النظام، الفوترة، والتطوير البرمجي',
                            style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ],
                  ),
                  Row(
                    children: [
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _isCreating ? const Color(0xFF64748B) : const Color(0xFF1D4ED8),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () => setState(() => _isCreating = !_isCreating),
                        icon: Icon(_isCreating ? Icons.list_rounded : Icons.add_rounded, size: 18),
                        label: Text(_isCreating ? 'عرض التذاكر السابقة' : 'فتح تذكرة جديدة'),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ],
              ),
              const Divider(height: 24),

              Expanded(
                child: _isCreating ? _buildCreateForm() : _buildTicketsList(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCreateForm() {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            margin: const EdgeInsets.only(bottom: 16),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFBFDBFE)),
            ),
            child: const Row(
              children: [
                Icon(Icons.info_outline, color: Color(0xFF1D4ED8), size: 20),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'قم بتعبئة النموذج وسيقوم فريق الدعم الفني بالرد المباشر عليك عبر النظام.',
                    style: TextStyle(color: Color(0xFF1E40AF), fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _department,
                  decoration: InputDecoration(
                    labelText: 'القسم المعني',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'technical', child: Text('🛠️ الدعم الفني والتقني')),
                    DropdownMenuItem(value: 'billing', child: Text('💳 الاشتراكات والفوترة')),
                    DropdownMenuItem(value: 'sales', child: Text('💼 المبيعات والتراخيص')),
                    DropdownMenuItem(value: 'feature_request', child: Text('💡 اقتراحات وميزات مطلوبة')),
                    DropdownMenuItem(value: 'general', child: Text('❓ استفسار عام')),
                  ],
                  onChanged: (val) => setState(() => _department = val ?? 'technical'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _priority,
                  decoration: InputDecoration(
                    labelText: 'الأولوية والأهمية',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'low', child: Text('🟢 عادي')),
                    DropdownMenuItem(value: 'medium', child: Text('🟡 متوسط')),
                    DropdownMenuItem(value: 'high', child: Text('🟠 عاجل ومهم')),
                    DropdownMenuItem(value: 'urgent', child: Text('🔴 حرج جداً (توقف عمل)')),
                  ],
                  onChanged: (val) => setState(() => _priority = val ?? 'medium'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _subjectCtrl,
            decoration: InputDecoration(
              labelText: 'عنوان التذكرة أو المشكلة',
              hintText: 'مثال: مشكلة في طباعة الفواتير الحرارية / استفسار عن تجديد الباقة',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _messageCtrl,
            maxLines: 5,
            decoration: InputDecoration(
              labelText: 'تفاصيل المشكلة أو الطلب',
              hintText: 'اشرح المشكلة أو الاستفسار بالتفصيل هنا...',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            ),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF1D4ED8),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: _isSubmitting ? null : _submitNewTicket,
            icon: _isSubmitting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Icon(Icons.send_rounded, size: 18),
            label: const Text('إرسال تذكرة الدعم الفني', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          ),
        ],
      ),
    );
  }

  Widget _buildTicketsList() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_tickets.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.mark_email_read_outlined, size: 54, color: Color(0xFF94A3B8)),
            const SizedBox(height: 12),
            const Text('لا توجد تذاكر دعم فني مسجلة حتى الآن.', style: TextStyle(color: Color(0xFF64748B), fontSize: 14, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            ElevatedButton(
              onPressed: () => setState(() => _isCreating = true),
              child: const Text('فتح تذكرة الآن'),
            ),
          ],
        ),
      );
    }

    return ListView.separated(
      itemCount: _tickets.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (ctx, idx) {
        final t = _tickets[idx];
        final status = t['status'] ?? 'open';
        final priority = t['priority'] ?? 'medium';

        Color statusColor;
        String statusText;
        if (status == 'closed') {
          statusColor = const Color(0xFF64748B);
          statusText = 'مغلقة';
        } else if (status == 'answered') {
          statusColor = const Color(0xFF16A34A);
          statusText = 'تم الرد من الدعم';
        } else if (status == 'in_progress') {
          statusColor = const Color(0xFFD97706);
          statusText = 'جاري المعالجة';
        } else {
          statusColor = const Color(0xFF1D4ED8);
          statusText = 'مفتوحة';
        }

        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFE2E8F0)),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 4, offset: const Offset(0, 2)),
            ],
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                ),
                child: Text(
                  statusText,
                  style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 11),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          t['ticket_number'] ?? '#TICKET',
                          style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8), fontSize: 12),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '• الأولوية: ${_getPriorityLabel(priority)}',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '• القسم: ${_getDepartmentLabel(t['department'])}',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      t['subject'] ?? '',
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'آخر تحديث: ${t['updated_at'] ?? ""}',
                      style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFF1F5F9),
                  foregroundColor: const Color(0xFF1E293B),
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                onPressed: () => _openThread(t['id']),
                icon: const Icon(Icons.chat_bubble_outline_rounded, size: 16),
                label: const Text('المحادثة والرد'),
              ),
            ],
          ),
        );
      },
    );
  }

  String _getPriorityLabel(String? p) {
    switch (p) {
      case 'urgent': return '🔴 حرج';
      case 'high': return '🟠 عاجل';
      case 'medium': return '🟡 متوسط';
      default: return '🟢 عادي';
    }
  }

  String _getDepartmentLabel(String? d) {
    switch (d) {
      case 'billing': return 'الاشتراكات والفوترة';
      case 'sales': return 'المبيعات';
      case 'feature_request': return 'اقتراحات ومميزات';
      case 'general': return 'عام';
      default: return 'الدعم الفني والتقني';
    }
  }
}

// ============================================================================
// TICKET CONVERSATION THREAD DIALOG
// ============================================================================
class SupportTicketThreadDialog extends StatefulWidget {
  final int ticketId;
  const SupportTicketThreadDialog({super.key, required this.ticketId});

  @override
  State<SupportTicketThreadDialog> createState() => _SupportTicketThreadDialogState();
}

class _SupportTicketThreadDialogState extends State<SupportTicketThreadDialog> {
  bool _isLoading = true;
  Map<String, dynamic>? _ticket;
  final _replyCtrl = TextEditingController();
  bool _isSending = false;
  final ScrollController _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/support/tickets/${widget.ticketId}'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _ticket = body['ticket'];
        });
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_scrollCtrl.hasClients) {
            _scrollCtrl.jumpTo(_scrollCtrl.position.maxScrollExtent);
          }
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _sendReply() async {
    final text = _replyCtrl.text.trim();
    if (text.isEmpty) return;

    setState(() => _isSending = true);
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/support/tickets/${widget.ticketId}/reply'),
        headers: {
          'Authorization': 'Bearer ${AppState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'message': text}),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        _replyCtrl.clear();
        await _fetch();
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'فشل إرسال الرد.')));
      }
    } catch (_) {}
    if (mounted) setState(() => _isSending = false);
  }

  @override
  Widget build(BuildContext context) {
    final messages = (_ticket?['messages'] as List?) ?? [];

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
        child: Container(
          width: 800,
          constraints: const BoxConstraints(maxHeight: 700),
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Top Bar
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                        child: const Icon(Icons.forum_rounded, color: Color(0xFF1D4ED8), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _ticket?['ticket_number'] ?? '#TICKET',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            _ticket?['subject'] ?? '',
                            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ],
                  ),
                  IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
                ],
              ),
              const Divider(height: 20),

              // Messages
              Expanded(
                child: _isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : ListView.builder(
                        controller: _scrollCtrl,
                        itemCount: messages.length,
                        itemBuilder: (ctx, idx) {
                          final m = messages[idx];
                          final isSupportReply = m['is_staff_reply'] == true;
                          final senderName = m['sender']?['name'] ?? (isSupportReply ? 'فريق الدعم الفني' : 'موظف الفندق');

                          return Align(
                            alignment: isSupportReply ? Alignment.centerRight : Alignment.centerLeft,
                            child: Container(
                              margin: const EdgeInsets.symmetric(vertical: 6),
                              padding: const EdgeInsets.all(14),
                              constraints: const BoxConstraints(maxWidth: 550),
                              decoration: BoxDecoration(
                                color: isSupportReply ? const Color(0xFFEFF6FF) : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(
                                  color: isSupportReply ? const Color(0xFFBFDBFE) : const Color(0xFFCBD5E1),
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(
                                        isSupportReply ? Icons.verified_user_rounded : Icons.person_rounded,
                                        size: 15,
                                        color: isSupportReply ? const Color(0xFF1D4ED8) : const Color(0xFF475569),
                                      ),
                                      const SizedBox(width: 6),
                                      Text(
                                        senderName,
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 12,
                                          color: isSupportReply ? const Color(0xFF1D4ED8) : const Color(0xFF334155),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        m['created_at'] != null ? m['created_at'].toString().split('T').first : '',
                                        style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    m['message'] ?? '',
                                    style: const TextStyle(fontSize: 13, height: 1.4, color: Color(0xFF1E293B)),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),

              const SizedBox(height: 12),
              // Reply Box
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _replyCtrl,
                      decoration: InputDecoration(
                        hintText: 'اكتب ردك على التذكرة هنا...',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1D4ED8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _isSending ? null : _sendReply,
                    icon: _isSending ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Icon(Icons.send_rounded, size: 16),
                    label: const Text('إرسال الرد'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 2.1 INVOICE PRINT & THERMAL RECEIPT DIALOG (A4 & Thermal 80mm Preview)
// ============================================================================
class InvoicePrintReceiptDialog extends StatefulWidget {
  final Map<String, dynamic> invoice;
  const InvoicePrintReceiptDialog({super.key, required this.invoice});

  @override
  State<InvoicePrintReceiptDialog> createState() => _InvoicePrintReceiptDialogState();
}

class _InvoicePrintReceiptDialogState extends State<InvoicePrintReceiptDialog> {
  String _viewMode = 'thermal'; // 'thermal' or 'a4'
  bool _isSaving = false;

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString().replaceAll(',', '').trim()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  Future<void> _handlePrint(String mode) async {
    try {
      final html = NativePrintAndSaveService.buildInvoiceHtml(widget.invoice, mode);
      await NativePrintAndSaveService.printWithNativeDialog(html);
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء طباعة الفاتورة',
          message: 'تعذر تشغيل نافذة الطباعة: $e\nيمكنك حفظ الفاتورة كملف PDF كخيار بديل ومضمون.',
        );
      }
    }
  }

  Future<void> _handleSavePdf(String mode) async {
    try {
      setState(() => _isSaving = true);
      final inv = AppState.safeMap(widget.invoice);
      final invoiceNum = AppState.safeString(inv['invoice_number'], '#${inv['id'] ?? ''}').replaceAll('#', '');
      final defaultFileName = 'فاتورة_${invoiceNum}_${mode == 'thermal' ? 'حراري' : 'A4'}.pdf';

      final chosenPath = await NativePrintAndSaveService.showSaveFileDialog(
        defaultFileName: defaultFileName,
        filter: 'ملفات PDF (*.pdf)|*.pdf|ملفات HTML (*.html)|*.html|جميع الملفات (*.*)|*.*',
      );

      if (chosenPath != null && chosenPath.isNotEmpty) {
        final html = NativePrintAndSaveService.buildInvoiceHtml(inv, mode);
        final success = await NativePrintAndSaveService.saveDocument(html, chosenPath);
        if (mounted) {
          if (success) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('✓ تم حفظ الفاتورة بنجاح في:\n$chosenPath'),
                backgroundColor: const Color(0xFF059669),
                duration: const Duration(seconds: 6),
                action: SnackBarAction(
                  label: '📂 فتح الملف',
                  textColor: Colors.white,
                  onPressed: () => NativePrintAndSaveService.openFile(chosenPath),
                ),
              ),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('تعذر حفظ الفاتورة، يرجى التحقق من أذونات المجلد.'), backgroundColor: Color(0xFFDC2626)),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء حفظ الفاتورة',
          message: 'حدث خطأ أثناء حفظ الفاتورة: $e',
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  void _openPrinterOptions() {
    try {
      final inv = AppState.safeMap(widget.invoice);
      final invoiceNum = AppState.safeString(inv['invoice_number'], '#${inv['id'] ?? ''}');
      final html = NativePrintAndSaveService.buildInvoiceHtml(inv, _viewMode);
      showDialog(
        context: context,
        builder: (ctx) => PrinterOptionsDialog(
          title: 'إعدادات وخيارات الطباعة للفاتورة $invoiceNum',
          documentType: 'invoice',
          defaultFormat: _viewMode,
          htmlContent: html,
          defaultFileName: 'فاتورة_${invoiceNum.replaceAll('#', '')}.pdf',
        ),
      );
    } catch (e) {
      AppState.showGlobalErrorDialog(
        title: 'تنبيه في خيارات الطباعة',
        message: 'تعذر فتح نافذة خيارات الطباعة: $e',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    try {
      final inv = AppState.safeMap(widget.invoice);
      final booking = AppState.safeMap(inv['booking']);
      final guest = AppState.safeMap(booking['guest'] ?? inv['guest']);
      final room = AppState.safeMap(inv['room'] ?? booking['room']);

      final guestName = AppState.safeString(guest['name'] ?? inv['guest_name'], 'نزيل محترم');
      final guestPhone = AppState.safeString(guest['phone'] ?? inv['guest_phone'], '—');
      final roomNumber = AppState.safeString(room['room_number'] ?? inv['room_number'] ?? booking['room_number'], '—');
      final roomType = AppState.safeString(
        room['room_type'] ?? room['type'] ?? booking['room_type'] ?? inv['room_type'],
        'غرفة فندقية'
      );
      final invoiceNum = AppState.safeString(inv['invoice_number'], '#${inv['id'] ?? '—'}');
      final amount = AppState.safeDouble(inv['amount'] ?? booking['total_price']);
      final serviceAmount = AppState.safeDouble(inv['service_amount']);
      final serviceDesc = AppState.safeString(inv['service_description'], 'خدمات إضافية');
      final tax = AppState.safeDouble(inv['tax']);
      final total = AppState.safeDouble(inv['total'], amount + serviceAmount + tax);
      final currency = AppState.safeString(inv['currency'] ?? booking['currency'], 'USD');
      final isPaid = inv['status'] == 'paid';
      final dateStr = AppState.safeString(inv['paid_at'] ?? inv['created_at'], DateTime.now().toString().substring(0, 16));
      final hotelName = AppState.safeString(AppState.currentUser?['hotel_name'], 'فندق المحبة والضيافة');
      final cashier = AppState.safeString(AppState.currentUser?['name'], 'موظف الاستقبال');

      return Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(color: const Color(0xFF1D4ED8).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
                    child: const Icon(Icons.print_outlined, color: Color(0xFF1D4ED8), size: 22),
                  ),
                  const SizedBox(width: 10),
                  Text('فاتورة وإيصال سداد رقم $invoiceNum', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ],
              ),
              Row(
                children: [
                  ChoiceChip(
                    label: const Text('🧾 إيصال حراري 80mm'),
                    selected: _viewMode == 'thermal',
                    onSelected: (val) {
                      if (val) setState(() => _viewMode = 'thermal');
                    },
                  ),
                  const SizedBox(width: 8),
                  ChoiceChip(
                    label: const Text('📄 فاتورة رسمية A4'),
                    selected: _viewMode == 'a4',
                    onSelected: (val) {
                      if (val) setState(() => _viewMode = 'a4');
                    },
                  ),
                ],
              ),
            ],
          ),
          content: SizedBox(
            width: _viewMode == 'thermal' ? 400 : 620,
            child: SingleChildScrollView(
              child: _viewMode == 'thermal'
                  ? _buildThermalView(
                      hotelName: hotelName,
                      invoiceNum: invoiceNum,
                      dateStr: dateStr,
                      cashier: cashier,
                      guestName: guestName,
                      guestPhone: guestPhone,
                      roomNumber: roomNumber,
                      roomType: roomType,
                      amount: amount,
                      serviceAmount: serviceAmount,
                      serviceDesc: serviceDesc,
                      tax: tax,
                      total: total,
                      currency: currency,
                      isPaid: isPaid,
                    )
                  : _buildA4View(
                      hotelName: hotelName,
                      invoiceNum: invoiceNum,
                      dateStr: dateStr,
                      cashier: cashier,
                      guestName: guestName,
                      guestPhone: guestPhone,
                      roomNumber: roomNumber,
                      roomType: roomType,
                      amount: amount,
                      serviceAmount: serviceAmount,
                      serviceDesc: serviceDesc,
                      tax: tax,
                      total: total,
                      currency: currency,
                      isPaid: isPaid,
                    ),
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('إغلاق')),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF475569),
                side: const BorderSide(color: Color(0xFFCBD5E1)),
              ),
              onPressed: _openPrinterOptions,
              icon: const Icon(Icons.settings, size: 16),
              label: const Text('⚙️ خيارات الطابعة'),
            ),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF1D4ED8),
                side: const BorderSide(color: Color(0xFF1D4ED8)),
              ),
              onPressed: _isSaving ? null : () => _handleSavePdf(_viewMode),
              icon: _isSaving
                  ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.save_alt, size: 16),
              label: const Text('💾 حفظ PDF (اختيار المكان)'),
            ),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF059669),
                foregroundColor: Colors.white,
              ),
              onPressed: () => _handlePrint(_viewMode),
              icon: const Icon(Icons.print, size: 16),
              label: Text(_viewMode == 'thermal' ? '🖨️ طباعة حرارية (نافذة الطابعات)' : '🖨️ طباعة A4 (نافذة الطابعات)'),
            ),
          ],
        ),
      );
    } catch (e, stack) {
      debugPrint('Error building InvoicePrintReceiptDialog: $e\n$stack');
      return Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: const [
              Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 28),
              SizedBox(width: 8),
              Text('تنبيه في تجهيز الفاتورة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('تم اعتراض خطأ أثناء تجهيز بيانات الفاتورة لمنع ظهور شاشة بيضاء:'),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(8)),
                child: SelectableText(e.toString(), style: const TextStyle(fontSize: 11, fontFamily: 'monospace', color: Colors.red)),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => exit(0),
              child: const Text('إغلاق البرنامج', style: TextStyle(color: Colors.red)),
            ),
            OutlinedButton(
              onPressed: () => setState(() {}),
              child: const Text('انتظار / إعادة المحاولة'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('موافق (إغلاق الفاتورة)'),
            ),
          ],
        ),
      );
    }
  }

  Widget _buildThermalView({
    required dynamic hotelName,
    required dynamic invoiceNum,
    required dynamic dateStr,
    required dynamic cashier,
    required dynamic guestName,
    required dynamic guestPhone,
    required dynamic roomNumber,
    required dynamic roomType,
    required dynamic amount,
    required dynamic serviceAmount,
    required dynamic serviceDesc,
    required dynamic tax,
    required dynamic total,
    required dynamic currency,
    required dynamic isPaid,
  }) {
    final strHotelName = AppState.safeString(hotelName);
    final strInvoiceNum = AppState.safeString(invoiceNum);
    final strDate = AppState.safeString(dateStr);
    final strCashier = AppState.safeString(cashier);
    final strGuestName = AppState.safeString(guestName);
    final strGuestPhone = AppState.safeString(guestPhone);
    final strRoomNum = AppState.safeString(roomNumber);
    final strRoomType = AppState.safeString(roomType);
    final strCurrency = AppState.safeString(currency, 'USD');
    final strServiceDesc = AppState.safeString(serviceDesc, 'خدمات');
    final numService = AppState.safeDouble(serviceAmount);
    final numTax = AppState.safeDouble(tax);
    final boolPaid = isPaid == true;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB).withValues(alpha: 0.4),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFCBD5E1), width: 1.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Column(
              children: [
                const Text('🏨', style: TextStyle(fontSize: 28)),
                const SizedBox(height: 4),
                Text(strHotelName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A))),
                const Text('إيصال سداد إقامة وخدمات فندقية', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                const Text('------------------------------------------------', style: TextStyle(color: Color(0xFF94A3B8))),
              ],
            ),
          ),
          const SizedBox(height: 6),
          _thermalRow('رقم الفاتورة:', strInvoiceNum, isBold: true),
          _thermalRow('التاريخ والوقت:', strDate),
          _thermalRow('الكاشير / الموظف:', strCashier),
          const Text('------------------------------------------------', style: TextStyle(color: Color(0xFF94A3B8))),
          _thermalRow('النزيل:', strGuestName, isBold: true),
          _thermalRow('هاتف النزيل:', strGuestPhone),
          _thermalRow('الغرفة:', 'غرفة $strRoomNum ($strRoomType)'),
          const Text('------------------------------------------------', style: TextStyle(color: Color(0xFF94A3B8))),
          const Text('تفاصيل الحساب والخدمات:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
          const SizedBox(height: 6),
          _thermalRow('أجرة الإقامة الأساسية:', '${_formatNumber(amount)} $strCurrency'),
          if (numService > 0)
            _thermalRow('خدمات إضافية ($strServiceDesc):', '${_formatNumber(numService)} $strCurrency'),
          if (numTax > 0)
            _thermalRow('الضريبة والرسوم:', '${_formatNumber(numTax)} $strCurrency'),
          const Text('================================================', style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold)),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('الإجمالي النهائي المسدد:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              Text('${_formatNumber(total)} $strCurrency', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF059669))),
            ],
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: boolPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Center(
              child: Text(
                boolPaid ? '✓ تم السداد بالكامل (Paid in Full)' : '⚠️ غير مسددة (Unpaid)',
                style: TextStyle(
                  color: boolPaid ? const Color(0xFF166534) : const Color(0xFFDC2626),
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          // Barcode simulation lines
          Center(
            child: Column(
              children: [
                Container(
                  height: 36,
                  width: 220,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: Colors.black26),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: List.generate(
                      24,
                      (idx) => Container(
                        width: idx % 3 == 0 ? 3 : (idx % 2 == 0 ? 1 : 2),
                        height: 28,
                        color: Colors.black,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Text(strInvoiceNum, style: const TextStyle(fontSize: 10, fontFamily: 'monospace', letterSpacing: 2)),
              ],
            ),
          ),
          const SizedBox(height: 10),
          const Center(
            child: Text(
              'شكراً لإقامتكم معنا في الفندق\nنتمنى لكم رحلة موفقة وعودة ميمونة! 🌹',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _thermalRow(String title, dynamic value, {bool isBold = false}) {
    final strVal = AppState.safeString(value);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(title, style: TextStyle(fontSize: 12, color: const Color(0xFF475569), fontWeight: isBold ? FontWeight.bold : FontWeight.normal)),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              strVal,
              textAlign: TextAlign.left,
              style: TextStyle(fontSize: 12, color: const Color(0xFF0F172A), fontWeight: isBold ? FontWeight.bold : FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildA4View({
    required dynamic hotelName,
    required dynamic invoiceNum,
    required dynamic dateStr,
    required dynamic cashier,
    required dynamic guestName,
    required dynamic guestPhone,
    required dynamic roomNumber,
    required dynamic roomType,
    required dynamic amount,
    required dynamic serviceAmount,
    required dynamic serviceDesc,
    required dynamic tax,
    required dynamic total,
    required dynamic currency,
    required dynamic isPaid,
  }) {
    final strHotelName = AppState.safeString(hotelName);
    final strInvoiceNum = AppState.safeString(invoiceNum);
    final strDate = AppState.safeString(dateStr);
    final strCashier = AppState.safeString(cashier);
    final strGuestName = AppState.safeString(guestName);
    final strGuestPhone = AppState.safeString(guestPhone);
    final strRoomNum = AppState.safeString(roomNumber);
    final strRoomType = AppState.safeString(roomType);
    final strCurrency = AppState.safeString(currency, 'USD');
    final strServiceDesc = AppState.safeString(serviceDesc, 'خدمات فندقية');
    final numService = AppState.safeDouble(serviceAmount);
    final numTax = AppState.safeDouble(tax);
    final boolPaid = isPaid == true;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFCBD5E1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(strHotelName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8))),
                  const Text('نظام الإدارة الفندقية المتكامل - الفاتورة الضريبية', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                  Text('المصدر / المحاسب: $strCashier', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: boolPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: boolPaid ? const Color(0xFF86EFAC) : const Color(0xFFFCA5A5)),
                ),
                child: Text(
                  boolPaid ? 'مدفوعة بالكامل ✓' : 'غير مسددة',
                  style: TextStyle(color: boolPaid ? const Color(0xFF166534) : const Color(0xFFDC2626), fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ),
            ],
          ),
          const Divider(height: 28),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('بيانات النزيل المستفيد:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                    const SizedBox(height: 4),
                    Text('الاسم: $strGuestName', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    Text('الهاتف: $strGuestPhone', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('بيانات الفاتورة والحجز:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                    const SizedBox(height: 4),
                    Text('رقم الفاتورة: $strInvoiceNum', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    Text('الغرفة: غرفة $strRoomNum ($strRoomType)', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                    Text('تاريخ الإصدار: $strDate', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Table(
            border: TableBorder.all(color: const Color(0xFFE2E8F0)),
            columnWidths: const {
              0: FlexColumnWidth(4),
              1: FlexColumnWidth(1),
              2: FlexColumnWidth(2),
              3: FlexColumnWidth(2),
            },
            children: [
              TableRow(
                decoration: const BoxDecoration(color: Color(0xFFF1F5F9)),
                children: const [
                  Padding(padding: EdgeInsets.all(8), child: Text('بيان الخدمة / الإقامة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                  Padding(padding: EdgeInsets.all(8), child: Text('الكمية', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                  Padding(padding: EdgeInsets.all(8), child: Text('السعر الفردي', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                  Padding(padding: EdgeInsets.all(8), child: Text('الإجمالي', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                ],
              ),
              TableRow(
                children: [
                  Padding(padding: const EdgeInsets.all(8), child: Text('إقامة فندقية - غرفة $strRoomNum ($strRoomType)', style: const TextStyle(fontSize: 12))),
                  const Padding(padding: EdgeInsets.all(8), child: Text('1', style: TextStyle(fontSize: 12))),
                  Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(amount)} $strCurrency', style: const TextStyle(fontSize: 12))),
                  Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(amount)} $strCurrency', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold))),
                ],
              ),
              if (numService > 0)
                TableRow(
                  children: [
                    Padding(padding: const EdgeInsets.all(8), child: Text('خدمات فندقية إضافية: $strServiceDesc', style: const TextStyle(fontSize: 12))),
                    const Padding(padding: EdgeInsets.all(8), child: Text('1', style: TextStyle(fontSize: 12))),
                    Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(numService)} $strCurrency', style: const TextStyle(fontSize: 12))),
                    Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(numService)} $strCurrency', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold))),
                  ],
                ),
              if (numTax > 0)
                TableRow(
                  children: [
                    const Padding(padding: EdgeInsets.all(8), child: Text('الضريبة والرسوم الفندقية', style: TextStyle(fontSize: 12))),
                    const Padding(padding: EdgeInsets.all(8), child: Text('1', style: TextStyle(fontSize: 12))),
                    Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(numTax)} $strCurrency', style: const TextStyle(fontSize: 12))),
                    Padding(padding: const EdgeInsets.all(8), child: Text('${_formatNumber(numTax)} $strCurrency', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold))),
                  ],
                ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Container(
                width: 260,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10), border: Border.all(color: const Color(0xFFE2E8F0))),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('المجموع الفرعي:', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                        Text('${_formatNumber(amount)} $strCurrency', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('الخدمات والرسوم:', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                        Text('${_formatNumber(numService + numTax)} $strCurrency', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const Divider(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('المبلغ الإجمالي:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                        Text('${_formatNumber(total)} $strCurrency', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1D4ED8))),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Text('توقيع النزيل المستلم:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                  SizedBox(height: 24),
                  Text('...........................................', style: TextStyle(color: Color(0xFFCBD5E1))),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: const [
                  Text('ختم وتوقيع إدارة الفندق:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                  SizedBox(height: 24),
                  Text('...........................................', style: TextStyle(color: Color(0xFFCBD5E1))),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 2.2 CHECKOUT & INSTANT SETTLEMENT DIALOG (سداد الفاتورة وتسجيل المغادرة)
// ============================================================================
class CheckoutBookingDialog extends StatefulWidget {
  final Map<String, dynamic> booking;
  final Map<String, dynamic>? room;
  final VoidCallback onComplete;

  const CheckoutBookingDialog({
    super.key,
    required this.booking,
    this.room,
    required this.onComplete,
  });

  @override
  State<CheckoutBookingDialog> createState() => _CheckoutBookingDialogState();
}

class _CheckoutBookingDialogState extends State<CheckoutBookingDialog> {
  final _serviceAmountCtrl = TextEditingController(text: '0');
  final _serviceDescCtrl = TextEditingController();
  String _paymentMethod = 'cash';
  bool _isSubmitting = false;

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString().replaceAll(',', '').trim()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  @override
  Widget build(BuildContext context) {
    final b = AppState.safeMap(widget.booking);
    final r = AppState.safeMap(widget.room ?? b['room']);
    final guest = AppState.safeMap(b['guest']);
    final guestName = AppState.safeString(b['guest_name'] ?? guest['name'], 'نزيل');
    final roomNumber = AppState.safeString(r['room_number'] ?? b['room_number'], '—');
    final roomType = AppState.safeString(
      r['room_type'] ?? r['type'] ?? b['room_type'],
      'غرفة فندقية'
    );
    final basePrice = AppState.safeDouble(b['total_price']);
    final currency = AppState.safeString(b['currency'], 'USD');
    final serviceAmount = double.tryParse(_serviceAmountCtrl.text.replaceAll(',', '').trim()) ?? 0;
    final grandTotal = basePrice + serviceAmount;

    final existingInvoice = b['invoice'] is Map ? AppState.safeMap(b['invoice']) : null;
    final isAlreadyPaid = existingInvoice != null && existingInvoice['status'] == 'paid';

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: const Color(0xFFDC2626).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
              child: const Icon(Icons.logout, color: Color(0xFFDC2626), size: 22),
            ),
            const SizedBox(width: 10),
            const Text('تسجيل مغادرة النزيل وسداد الفاتورة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
          ],
        ),
        content: SizedBox(
          width: 520,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Info Summary Box
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('👤 النزيل: $guestName', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          Text('🚪 غرفة $roomNumber ($roomType)', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1D4ED8))),
                        ],
                      ),
                      const Divider(height: 18),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('فترة الإقامة: ${b['check_in']} ⬅️ ${b['check_out']}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                          Text('أجرة الإقامة: ${_formatNumber(basePrice)} $currency', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                if (isAlreadyPaid)
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFDCFCE7),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF86EFAC)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle, color: Color(0xFF16A34A), size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'الفاتورة مسددة مسبقاً بقيمة ${_formatNumber(existingInvoice['total'])} $currency. سيتم تسجيل المغادرة، إرسال الغرفة للتنظيف، وطباعة الإيصال.',
                            style: const TextStyle(color: Color(0xFF166534), fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                  )
                else
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFFCD34D)),
                    ),
                    child: Row(
                      children: const [
                        Icon(Icons.info_outline, color: Color(0xFFD97706), size: 20),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'سيتم إصدار وسداد الفاتورة تلقائياً، قيد الإيراد في دفتر اليومية، وتحويل الغرفة لقسم التنظيف.',
                            style: TextStyle(color: Color(0xFF92400E), fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                  ),

                const Text('الخدمات الإضافية أثناء الإقامة (ميني بار، غسيل، مطعم...):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextField(
                        controller: _serviceAmountCtrl,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          labelText: 'مبلغ الخدمات الإضافية ($currency)',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onChanged: (_) => setState(() {}),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 3,
                      child: TextField(
                        controller: _serviceDescCtrl,
                        decoration: InputDecoration(
                          labelText: 'وصف الخدمات المضافة',
                          hintText: 'مثال: ميني بار ومغسلة',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                const Text('طريقة تحصيل وسداد الفاتورة:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF334155))),
                const SizedBox(height: 8),
                Row(
                  children: [
                    ChoiceChip(
                      label: const Text('💵 نقداً (كاش)'),
                      selected: _paymentMethod == 'cash',
                      onSelected: (val) => setState(() => _paymentMethod = 'cash'),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('💳 بطاقة بنكية (POS)'),
                      selected: _paymentMethod == 'card',
                      onSelected: (val) => setState(() => _paymentMethod = 'card'),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('🏦 تحويل بنكي'),
                      selected: _paymentMethod == 'bank',
                      onSelected: (val) => setState(() => _paymentMethod = 'bank'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                // Total Calculation Box
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1D4ED8).withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFF1D4ED8).withValues(alpha: 0.2)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('المبلغ الإجمالي النهائي للسداد:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B))),
                      Text(
                        '${_formatNumber(grandTotal)} $currency',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF1D4ED8)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF16A34A),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: _isSubmitting
                ? null
                : () async {
                    setState(() => _isSubmitting = true);
                    try {
                      final servAmt = double.tryParse(_serviceAmountCtrl.text.replaceAll(',', '').trim()) ?? 0;
                      final servDesc = _serviceDescCtrl.text.trim();

                      final res = await http.post(
                        Uri.parse('${AppState.apiUrl}/staff/bookings/${b['id']}/check-out'),
                        headers: {
                          'Authorization': 'Bearer ${AppState.token}',
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                        },
                        body: jsonEncode({
                          'service_amount': servAmt,
                          'service_description': servDesc,
                          'payment_method': _paymentMethod,
                        }),
                      ).timeout(const Duration(seconds: 20));

                      final body = jsonDecode(res.body);
                      if (res.statusCode == 200 && mounted) {
                        Navigator.pop(context, body);
                        widget.onComplete();
                      } else if (mounted) {
                        final msg = (body is Map && body['message'] != null) ? body['message'].toString() : 'فشل تسجيل المغادرة.';
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(msg), backgroundColor: Colors.red),
                        );
                      }
                    } catch (e) {
                      if (mounted) {
                        AppState.showGlobalErrorDialog(
                          title: 'تنبيه أثناء تسجيل المغادرة',
                          message: 'حدث خطأ أثناء إتمام عملية تسجيل المغادرة والسداد: $e',
                        );
                      }
                    } finally {
                      if (mounted) setState(() => _isSubmitting = false);
                    }
                  },
            icon: _isSubmitting ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Icon(Icons.check, size: 18),
            label: const Text('تأكيد تسجيل المغادرة وسداد الفاتورة', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 2.3 CREATE & WALK-IN BOOKING DIALOG (تسكين وحجز فوري)
// ============================================================================
class CreateBookingDialog extends StatefulWidget {
  final int? preselectedRoomId;
  final String? preselectedGuestName;
  final String? preselectedGuestPhone;
  final VoidCallback onBookingCreated;

  const CreateBookingDialog({
    super.key,
    this.preselectedRoomId,
    this.preselectedGuestName,
    this.preselectedGuestPhone,
    required this.onBookingCreated,
  });

  @override
  State<CreateBookingDialog> createState() => _CreateBookingDialogState();
}

class _CreateBookingDialogState extends State<CreateBookingDialog> {
  final _guestNameCtrl = TextEditingController();
  final _guestFatherNameCtrl = TextEditingController();
  final _guestPhoneCtrl = TextEditingController();
  final _guestNationalIdCtrl = TextEditingController();
  final _guestNationalityCtrl = TextEditingController(text: 'سوري');
  int? _selectedRoomId;
  List<dynamic> _availableRooms = [];
  bool _isLoadingRooms = true;
  DateTime _checkInDate = DateTime.now();
  DateTime _checkOutDate = DateTime.now().add(const Duration(days: 1));
  String _currency = 'USD';
  bool _immediateCheckIn = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _guestNameCtrl.text = widget.preselectedGuestName ?? '';
    _guestPhoneCtrl.text = widget.preselectedGuestPhone ?? '';
    _fetchRooms();
  }

  @override
  void dispose() {
    _guestNameCtrl.dispose();
    _guestFatherNameCtrl.dispose();
    _guestPhoneCtrl.dispose();
    _guestNationalIdCtrl.dispose();
    _guestNationalityCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchRooms() async {
    setState(() => _isLoadingRooms = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/rooms'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final all = (body['data'] as List?) ?? [];
        setState(() {
          _availableRooms = all.where((r) => r['status'] == 'available' || r['id'] == widget.preselectedRoomId).toList();
          if (widget.preselectedRoomId != null) {
            _selectedRoomId = widget.preselectedRoomId;
          } else if (_availableRooms.isNotEmpty) {
            _selectedRoomId = _availableRooms.first['id'];
          }
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoadingRooms = false);
  }

  @override
  Widget build(BuildContext context) {
    final selectedRoom = _availableRooms.firstWhere((r) => r['id'] == _selectedRoomId, orElse: () => null);
    final nights = _checkOutDate.difference(_checkInDate).inDays > 0 ? _checkOutDate.difference(_checkInDate).inDays : 1;
    final roomPrice = selectedRoom != null ? (_currency == 'USD' ? (selectedRoom['price_usd'] ?? 0) : (selectedRoom['price_syp'] ?? 0)) : 0;
    final totalPrice = roomPrice * nights;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.person_add_alt_1, color: Color(0xFF1D4ED8)),
            SizedBox(width: 8),
            Text('إنشاء حجز فندقي وتسجيل النزيل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
          ],
        ),
        content: SizedBox(
          width: 560,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('بيانات النزيل (تُحفظ تلقائياً في سجل النزلاء):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B))),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      flex: 3,
                      child: TextField(
                        controller: _guestNameCtrl,
                        decoration: InputDecoration(
                          labelText: 'اسم النزيل الكامل *',
                          prefixIcon: const Icon(Icons.badge_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: TextField(
                        controller: _guestFatherNameCtrl,
                        decoration: InputDecoration(
                          labelText: 'اسم الأب',
                          prefixIcon: const Icon(Icons.family_restroom_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _guestPhoneCtrl,
                        decoration: InputDecoration(
                          labelText: 'الهاتف / واتساب *',
                          prefixIcon: const Icon(Icons.phone_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _guestNationalityCtrl,
                        decoration: InputDecoration(
                          labelText: 'الجنسية',
                          prefixIcon: const Icon(Icons.flag_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _guestNationalIdCtrl,
                  decoration: InputDecoration(
                    labelText: 'الرقم الوطني / رقم جواز السفر',
                    prefixIcon: const Icon(Icons.credit_card_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 16),
                const Text('اختيار الغرفة الفندقية:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B))),
                const SizedBox(height: 8),
                if (_isLoadingRooms)
                  const Center(child: Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator()))
                else if (_availableRooms.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                    child: const Text('⚠️ لا توجد غرف متاحة حالياً. يرجى مراجعة إدارة الغرف أو تفريغ غرفة.', style: TextStyle(color: Colors.red, fontSize: 12)),
                  )
                else
                  DropdownButtonFormField<int>(
                    initialValue: _selectedRoomId,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.meeting_room_outlined),
                    ),
                    items: _availableRooms.map<DropdownMenuItem<int>>((r) {
                      return DropdownMenuItem<int>(
                        value: r['id'],
                        child: Text('غرفة ${r['room_number']} (${r['room_type']}) - \$${r['price_usd']} / ${r['price_syp']} ل.س'),
                      );
                    }).toList(),
                    onChanged: (val) {
                      setState(() => _selectedRoomId = val);
                    },
                  ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('تاريخ الوصول:'),
                          const SizedBox(height: 4),
                          OutlinedButton.icon(
                            onPressed: () async {
                              final d = await showDatePicker(
                                context: context,
                                initialDate: _checkInDate,
                                firstDate: DateTime.now().subtract(const Duration(days: 1)),
                                lastDate: DateTime.now().add(const Duration(days: 365)),
                              );
                              if (d != null) {
                                setState(() {
                                  _checkInDate = d;
                                  if (_checkOutDate.isBefore(_checkInDate)) {
                                    _checkOutDate = _checkInDate.add(const Duration(days: 1));
                                  }
                                });
                              }
                            },
                            icon: const Icon(Icons.calendar_today, size: 16),
                            label: Text('${_checkInDate.year}-${_checkInDate.month.toString().padLeft(2, '0')}-${_checkInDate.day.toString().padLeft(2, '0')}'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('تاريخ المغادرة:'),
                          const SizedBox(height: 4),
                          OutlinedButton.icon(
                            onPressed: () async {
                              final d = await showDatePicker(
                                context: context,
                                initialDate: _checkOutDate,
                                firstDate: _checkInDate.add(const Duration(days: 1)),
                                lastDate: DateTime.now().add(const Duration(days: 365)),
                              );
                              if (d != null) {
                                setState(() => _checkOutDate = d);
                              }
                            },
                            icon: const Icon(Icons.event, size: 16),
                            label: Text('${_checkOutDate.year}-${_checkOutDate.month.toString().padLeft(2, '0')}-${_checkOutDate.day.toString().padLeft(2, '0')}'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    const Text('عملة الحساب والفاتورة: ', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('دولار أمريكي (USD)'),
                      selected: _currency == 'USD',
                      onSelected: (val) => setState(() => _currency = 'USD'),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('ليرة سورية (SYP)'),
                      selected: _currency == 'SYP',
                      onSelected: (val) => setState(() => _currency = 'SYP'),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                // Radio/Switch for Stage 1: Reserved (Before arrival) vs Stage 2: Check-in (Arrived)
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: _immediateCheckIn ? const Color(0xFFFEF2F2) : const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: _immediateCheckIn ? const Color(0xFFFCA5A5) : const Color(0xFFBFDBFE)),
                  ),
                  child: Column(
                    children: [
                      RadioListTile<bool>(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('تسجيل دخول وتسكين فوري (النزيل متواجد الآن في الاستقبال)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        subtitle: const Text('تصبح حالة الغرفة: 🔴 مسكونة فوراً', style: TextStyle(fontSize: 11, color: Color(0xFFDC2626))),
                        value: true,
                        groupValue: _immediateCheckIn,
                        activeColor: const Color(0xFFDC2626),
                        onChanged: (val) => setState(() => _immediateCheckIn = val ?? true),
                      ),
                      RadioListTile<bool>(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('حجز مسبق قبل الوصول (في انتظار قدوم النزيل)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        subtitle: const Text('تصبح حالة الغرفة: 🔵 محجوزة، وتتحول لمسكونة عند وصوله وتسجيل الدخول', style: TextStyle(fontSize: 11, color: Color(0xFF2563EB))),
                        value: false,
                        groupValue: _immediateCheckIn,
                        activeColor: const Color(0xFF2563EB),
                        onChanged: (val) => setState(() => _immediateCheckIn = val ?? false),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(12)),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('عدد الليالي: $nights ليلة', style: const TextStyle(fontWeight: FontWeight.bold)),
                      Text('الإجمالي: $totalPrice $_currency', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1D4ED8))),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: _immediateCheckIn ? const Color(0xFFDC2626) : const Color(0xFF1D4ED8),
              foregroundColor: Colors.white,
            ),
            onPressed: (_selectedRoomId == null || _guestNameCtrl.text.trim().isEmpty || _isSubmitting)
                ? null
                : () async {
                    setState(() => _isSubmitting = true);
                    try {
                      final inStr = '${_checkInDate.year}-${_checkInDate.month.toString().padLeft(2, '0')}-${_checkInDate.day.toString().padLeft(2, '0')}';
                      final outStr = '${_checkOutDate.year}-${_checkOutDate.month.toString().padLeft(2, '0')}-${_checkOutDate.day.toString().padLeft(2, '0')}';

                      final res = await http.post(
                        Uri.parse('${AppState.apiUrl}/staff/bookings'),
                        headers: {
                          'Authorization': 'Bearer ${AppState.token}',
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                        },
                        body: jsonEncode({
                          'room_id': _selectedRoomId,
                          'guest_name': _guestNameCtrl.text.trim(),
                          'guest_father_name': _guestFatherNameCtrl.text.trim(),
                          'guest_phone': _guestPhoneCtrl.text.trim(),
                          'guest_nationality': _guestNationalityCtrl.text.trim(),
                          'guest_national_id': _guestNationalIdCtrl.text.trim(),
                          'check_in': inStr,
                          'check_out': outStr,
                          'currency': _currency,
                          'status': _immediateCheckIn ? 'checked_in' : 'confirmed',
                          'total_price': totalPrice,
                        }),
                      );
                      final body = jsonDecode(res.body);
                      if (res.statusCode == 200 && mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم الحجز بنجاح.'), backgroundColor: Colors.green));
                        widget.onBookingCreated();
                      } else if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'فشل إنشاء الحجز.'), backgroundColor: Colors.red));
                      }
                    } catch (_) {}
                    if (mounted) setState(() => _isSubmitting = false);
                  },
            child: _isSubmitting
                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : Text(_immediateCheckIn ? 'تأكيد وتسكين فوري 🔴' : 'تأكيد الحجز المسبق 🔵'),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 2.4 ROOM RACK TAB (لوحة الغرف وحالتها اللحظية التفاعلية)
// ============================================================================
class RoomRackTab extends StatefulWidget {
  final VoidCallback? onOpenBooking;
  final VoidCallback? onOpenRooms;
  const RoomRackTab({super.key, this.onOpenBooking, this.onOpenRooms});

  @override
  State<RoomRackTab> createState() => _RoomRackTabState();
}

class _RoomRackTabState extends State<RoomRackTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _rooms = [];
  String _statusFilter = 'all';
  final _searchCtrl = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _fetchRooms();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchRooms() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/rooms'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _rooms = body['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _checkInBooking(int bookingId) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/bookings/$bookingId/check-in'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(body['message'] ?? 'تم تسجيل دخول وتسكين النزيل بنجاح.'),
          backgroundColor: Colors.green,
        ));
        _fetchRooms();
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(body['message'] ?? 'فشل تسجيل الدخول.'),
          backgroundColor: Colors.red,
        ));
      }
    } catch (_) {}
  }

  Future<void> _completeCleaning(int roomId) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/cleaning/$roomId/complete'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم إتمام التنظيف وتجهيز الغرفة.'), backgroundColor: Colors.green));
        _fetchRooms();
      }
    } catch (_) {}
  }

  Future<void> _endMaintenance(int roomId) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.put(
        Uri.parse('${AppState.apiUrl}/staff/rooms/$roomId'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'status': 'available', 'needs_cleaning': false}),
      );
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم إنهاء الصيانة وإعادة الغرفة لحالة المتاحة.'), backgroundColor: Colors.green));
        _fetchRooms();
      }
    } catch (_) {}
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  @override
  Widget build(BuildContext context) {
    final totalCount = _rooms.length;
    final availableCount = _rooms.where((r) => r['status'] == 'available').length;
    final bookedCount = _rooms.where((r) => r['status'] == 'booked').length;
    final occupiedCount = _rooms.where((r) => r['status'] == 'occupied').length;
    final cleaningCount = _rooms.where((r) => r['status'] == 'cleaning' || r['needs_cleaning'] == true).length;
    final maintenanceCount = _rooms.where((r) => r['status'] == 'maintenance').length;

    final filtered = _rooms.where((r) {
      final status = r['status'] ?? '';
      final needsCleaning = r['needs_cleaning'] == true;

      if (_statusFilter == 'available' && status != 'available') return false;
      if (_statusFilter == 'booked' && status != 'booked') return false;
      if (_statusFilter == 'occupied' && status != 'occupied') return false;
      if (_statusFilter == 'cleaning' && status != 'cleaning' && !needsCleaning) return false;
      if (_statusFilter == 'maintenance' && status != 'maintenance') return false;

      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        final rNum = r['room_number']?.toString().toLowerCase() ?? '';
        final gName = r['active_booking']?['guest_name']?.toString().toLowerCase() ?? '';
        if (!rNum.contains(q) && !gName.contains(q)) return false;
      }
      return true;
    }).toList();

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header Row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Text('🗂️ لوحة الغرف وحالتها اللحظية (Room Rack)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    SizedBox(height: 4),
                    Text('مراقبة حالة كل غرفة: متوفرة، محجوزة مسبقاً (في انتظار الوصول)، مسكونة، تنظيف، صيانة', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  ],
                ),
                Row(
                  children: [
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('حجز جديد / تسكين (+)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      onPressed: () {
                        showDialog(
                          context: context,
                          builder: (ctx) => CreateBookingDialog(onBookingCreated: _fetchRooms),
                        );
                      },
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      onPressed: _fetchRooms,
                      icon: const Icon(Icons.refresh, color: Color(0xFF64748B)),
                      tooltip: 'تحديث حالة الغرف',
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Statistics Counters Bar (6 KPIs responsive)
            LayoutBuilder(
              builder: (ctx, constraints) {
                return Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _buildRackKpiItem('إجمالي الغرف', '$totalCount', Icons.domain, const Color(0xFF64748B), const Color(0xFFF1F5F9)),
                    _buildRackKpiItem('متوفرة (أخضر)', '$availableCount', Icons.check_circle_outline, const Color(0xFF059669), const Color(0xFFDCFCE7)),
                    _buildRackKpiItem('محجوزة (أزرق)', '$bookedCount', Icons.event_available_outlined, const Color(0xFF2563EB), const Color(0xFFDBEAFE)),
                    _buildRackKpiItem('مسكونة (أحمر)', '$occupiedCount', Icons.person_pin, const Color(0xFFDC2626), const Color(0xFFFEE2E2)),
                    _buildRackKpiItem('تنظيف (أصفر)', '$cleaningCount', Icons.cleaning_services_outlined, const Color(0xFFD97706), const Color(0xFFFEF3C7)),
                    _buildRackKpiItem('صيانة (برتقالي)', '$maintenanceCount', Icons.build_outlined, const Color(0xFFEA580C), const Color(0xFFFFEDD5)),
                  ],
                );
              },
            ),
            const SizedBox(height: 20),

            // Filter Chips & Search Bar
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      children: [
                        ChoiceChip(
                          label: Text('الكل ($totalCount)'),
                          selected: _statusFilter == 'all',
                          onSelected: (val) => setState(() => _statusFilter = 'all'),
                        ),
                        ChoiceChip(
                          label: Text('🟢 متوفرة ($availableCount)'),
                          selected: _statusFilter == 'available',
                          onSelected: (val) => setState(() => _statusFilter = 'available'),
                        ),
                        ChoiceChip(
                          label: Text('🔵 محجوزة ($bookedCount)'),
                          selected: _statusFilter == 'booked',
                          onSelected: (val) => setState(() => _statusFilter = 'booked'),
                        ),
                        ChoiceChip(
                          label: Text('🔴 مسكونة ($occupiedCount)'),
                          selected: _statusFilter == 'occupied',
                          onSelected: (val) => setState(() => _statusFilter = 'occupied'),
                        ),
                        ChoiceChip(
                          label: Text('🟡 تنظيف ($cleaningCount)'),
                          selected: _statusFilter == 'cleaning',
                          onSelected: (val) => setState(() => _statusFilter = 'cleaning'),
                        ),
                        ChoiceChip(
                          label: Text('🟠 صيانة ($maintenanceCount)'),
                          selected: _statusFilter == 'maintenance',
                          onSelected: (val) => setState(() => _statusFilter = 'maintenance'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  SizedBox(
                    width: 260,
                    height: 40,
                    child: TextField(
                      controller: _searchCtrl,
                      decoration: InputDecoration(
                        hintText: 'بحث برقم الغرفة أو اسم النزيل...',
                        prefixIcon: const Icon(Icons.search, size: 18),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onChanged: (val) => setState(() => _searchQuery = val.trim()),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Rooms Grid
            if (_isLoading)
              const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator()))
            else if (filtered.isEmpty)
              Container(
                padding: const EdgeInsets.all(40),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                child: const Center(
                  child: Text('لا توجد غرف مطابقة لمعايير الفلترة المحددة.', style: TextStyle(color: Color(0xFF64748B), fontSize: 13)),
                ),
              )
            else
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 350,
                  mainAxisExtent: 320,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                ),
                itemCount: filtered.length,
                itemBuilder: (ctx, i) {
                  final room = filtered[i];
                  return _buildRoomCard(room);
                },
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildRackKpiItem(String title, String val, IconData icon, Color color, Color bg) {
    return Container(
      constraints: const BoxConstraints(minWidth: 140),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
              Text(val, style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: color)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildRoomCard(Map<String, dynamic> room) {
    final status = room['status'] ?? 'available';
    final isBooked = status == 'booked';
    final isOccupied = status == 'occupied';
    final isCleaning = status == 'cleaning' || room['needs_cleaning'] == true;
    final isMaintenance = status == 'maintenance';
    final isAvailable = !isBooked && !isOccupied && !isCleaning && !isMaintenance;

    Color borderColor;
    Color bgColor;
    Color badgeColor;
    String badgeText;

    if (isAvailable) {
      borderColor = const Color(0xFF10B981);
      bgColor = const Color(0xFFF0FDF4);
      badgeColor = const Color(0xFF059669);
      badgeText = '🟢 متوفرة جاهزة';
    } else if (isBooked) {
      borderColor = const Color(0xFF2563EB);
      bgColor = const Color(0xFFEFF6FF);
      badgeColor = const Color(0xFF1D4ED8);
      badgeText = '🔵 محجوزة (في انتظار الوصول)';
    } else if (isOccupied) {
      borderColor = const Color(0xFFDC2626);
      bgColor = const Color(0xFFFEF2F2);
      badgeColor = const Color(0xFFB91C1C);
      badgeText = '🔴 مسكونة حالياً';
    } else if (isCleaning) {
      borderColor = const Color(0xFFF59E0B);
      bgColor = const Color(0xFFFFFBEB);
      badgeColor = const Color(0xFFD97706);
      badgeText = '🟡 قيد التنظيف والتعقيم';
    } else {
      borderColor = const Color(0xFFEA580C);
      bgColor = const Color(0xFFFFF7ED);
      badgeColor = const Color(0xFFC2410C);
      badgeText = '🟠 قيد الصيانة';
    }

    final b = room['active_booking'];
    final inv = b?['invoice'];
    final isInvPaid = inv != null && inv['status'] == 'paid';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor, width: 2),
        boxShadow: [
          BoxShadow(color: borderColor.withValues(alpha: 0.08), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Card Top Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: const BorderRadius.only(topLeft: Radius.circular(14), topRight: Radius.circular(14)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Text('🚪', style: TextStyle(fontSize: 16)),
                    const SizedBox(width: 6),
                    Text(
                      'غرفة ${room['room_number']}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: borderColor.withValues(alpha: 0.5)),
                  ),
                  child: Text(badgeText, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: badgeColor)),
                ),
              ],
            ),
          ),

          // Card Body
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(6)),
                        child: Text(room['room_type'] ?? 'قياسي', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF475569))),
                      ),
                      Text(
                        '\$${_formatNumber(room['price_usd'])} / ${_formatNumber(room['price_syp'])} ل.س',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F766E)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  if (isBooked && b != null) ...[
                    Row(
                      children: [
                        const Icon(Icons.person, size: 16, color: Color(0xFF2563EB)),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            b['guest_name'] ?? 'نزيل حاجز',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text('📞 ${b['guest_phone'] ?? '—'}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                    const SizedBox(height: 4),
                    Text('🗓️ الوصول: ${b['check_in']} ⬅️ المغادرة: ${b['check_out']}', style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFBFDBFE)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.access_time_rounded, size: 14, color: Color(0xFF2563EB)),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              'حجز مسبق مؤكد (${_formatNumber(b['total_price'])} ${b['currency']})',
                              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8)),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ] else if (isOccupied && b != null) ...[
                    Row(
                      children: [
                        const Icon(Icons.person, size: 16, color: Color(0xFFDC2626)),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            b['guest_name'] ?? 'نزيل مقيم',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text('📞 ${b['guest_phone'] ?? '—'}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                    const SizedBox(height: 4),
                    Text('🗓️ الدخول: ${b['check_in']} ⬅️ المغادرة: ${b['check_out']}', style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                    const SizedBox(height: 8),
                    // Invoice pill
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: isInvPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: isInvPaid ? const Color(0xFF86EFAC) : const Color(0xFFFCA5A5)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(isInvPaid ? Icons.check_circle : Icons.warning_amber, size: 14, color: isInvPaid ? const Color(0xFF16A34A) : const Color(0xFFDC2626)),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              isInvPaid
                                  ? 'الفاتورة: مسددة (${_formatNumber(inv['total'])} ${inv['currency']})'
                                  : 'الفاتورة: غير مسددة (${_formatNumber(b['total_price'])} ${b['currency']})',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                color: isInvPaid ? const Color(0xFF166534) : const Color(0xFFDC2626),
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ] else if (isAvailable) ...[
                    const Spacer(),
                    Center(
                      child: Column(
                        children: const [
                          Icon(Icons.hotel_outlined, size: 32, color: Color(0xFF10B981)),
                          SizedBox(height: 6),
                          Text('الغرفة شاغرة ونظيفة، جاهزة لاستقبال وتسجيل دخول النزيل فوراً.', textAlign: TextAlign.center, style: TextStyle(fontSize: 11, color: Color(0xFF059669))),
                        ],
                      ),
                    ),
                    const Spacer(),
                  ] else if (isCleaning) ...[
                    const Spacer(),
                    Center(
                      child: Column(
                        children: const [
                          Icon(Icons.cleaning_services_outlined, size: 32, color: Color(0xFFD97706)),
                          SizedBox(height: 6),
                          Text('⚠️ الغرفة غير جاهزة: قيد أعمال التنظيف، التعقيم، وتبديل الشراشف.', textAlign: TextAlign.center, style: TextStyle(fontSize: 11, color: Color(0xFF92400E))),
                        ],
                      ),
                    ),
                    const Spacer(),
                  ] else ...[
                    const Spacer(),
                    Center(
                      child: Column(
                        children: [
                          const Icon(Icons.build_circle_outlined, size: 32, color: Color(0xFFEA580C)),
                          const SizedBox(height: 6),
                          Text(
                            '🔧 صيانة: ${room['maintenance_notes'] ?? 'يوجد بلاغ صيانة مفتوح'}',
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 11, color: Color(0xFF9A3412)),
                          ),
                        ],
                      ),
                    ),
                    const Spacer(),
                  ],
                ],
              ),
            ),
          ),

          // Card Bottom Actions
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: const BoxDecoration(
              color: Color(0xFFF8FAFC),
              border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
              borderRadius: BorderRadius.only(bottomLeft: Radius.circular(14), bottomRight: Radius.circular(14)),
            ),
            child: isBooked && b != null
                ? ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () => _checkInBooking(b['id']),
                    icon: const Icon(Icons.login_rounded, size: 16),
                    label: const Text('تسجيل دخول وتسكين النزيل الآن ✓', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                  )
                : isOccupied && b != null
                    ? Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFFDC2626),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () async {
                                final res = await showDialog<Map<String, dynamic>?>(
                                  context: context,
                                  builder: (ctx) => CheckoutBookingDialog(
                                    booking: b,
                                    room: room,
                                    onComplete: _fetchRooms,
                                  ),
                                );
                                if (res != null && mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(res['message'] ?? 'تم تسجيل المغادرة، سداد الفاتورة، وترحيل الإيراد بنجاح.'),
                                      backgroundColor: Colors.green,
                                    ),
                                  );
                                  if (res['invoice'] != null) {
                                    await Future.delayed(const Duration(milliseconds: 150));
                                    if (mounted) {
                                      final invoiceWithDetails = {
                                        ...AppState.safeMap(res['invoice']),
                                        if (res['invoice']['booking'] == null) 'booking': b,
                                        if (res['invoice']['room'] == null) 'room': room,
                                      };
                                      showDialog(
                                        context: context,
                                        builder: (ctx) => InvoicePrintReceiptDialog(invoice: invoiceWithDetails),
                                      );
                                    }
                                  }
                                }
                              },
                              icon: const Icon(Icons.logout, size: 14),
                              label: const Text('تسجيل مغادرة وسداد', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            ),
                          ),
                          const SizedBox(width: 6),
                          IconButton(
                            style: IconButton.styleFrom(
                              backgroundColor: Colors.white,
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                            onPressed: () {
                              final invoiceData = inv != null ? {
                                ...AppState.safeMap(inv),
                                'booking': b,
                                'room': room,
                              } : {
                                'invoice_number': 'INV-${b['id']}',
                                'amount': b['total_price'],
                                'service_amount': 0,
                                'total': b['total_price'],
                                'currency': b['currency'],
                                'status': 'unpaid',
                                'booking': b,
                                'room': room,
                              };
                              showDialog(
                                context: context,
                                builder: (ctx) => InvoicePrintReceiptDialog(invoice: invoiceData),
                              );
                            },
                            icon: const Icon(Icons.print_outlined, size: 16, color: Color(0xFF1D4ED8)),
                            tooltip: 'معاينة وطباعة الفاتورة',
                          ),
                        ],
                      )
                    : isAvailable
                        ? ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF059669),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                            onPressed: () {
                              showDialog(
                                context: context,
                                builder: (ctx) => CreateBookingDialog(
                                  preselectedRoomId: room['id'],
                                  onBookingCreated: _fetchRooms,
                                ),
                              );
                            },
                            icon: const Icon(Icons.person_add_alt_1, size: 16),
                            label: const Text('حجز وتسجيل دخول (+)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          )
                        : isCleaning
                            ? ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFD97706),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 8),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                                onPressed: () => _completeCleaning(room['id']),
                                icon: const Icon(Icons.check, size: 16),
                                label: const Text('إتمام التنظيف وجعلها متاحة ✓', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              )
                            : ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFEA580C),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 8),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                                onPressed: () => _endMaintenance(room['id']),
                                icon: const Icon(Icons.build, size: 16),
                                label: const Text('إنهاء الصيانة وجعلها متاحة', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 2.5 GUESTS DIRECTORY TAB (دليل وسجل النزلاء)
// ============================================================================
class GuestsTab extends StatefulWidget {
  final Function(String name, String phone)? onBookForGuest;
  const GuestsTab({super.key, this.onBookForGuest});

  @override
  State<GuestsTab> createState() => _GuestsTabState();
}

class _GuestsTabState extends State<GuestsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _guests = [];
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchGuests();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchGuests([String? query]) async {
    setState(() => _isLoading = true);
    try {
      final q = query ?? _searchCtrl.text.trim();
      final uri = q.isEmpty
          ? Uri.parse('${AppState.apiUrl}/staff/guests')
          : Uri.parse('${AppState.apiUrl}/staff/guests?q=${Uri.encodeComponent(q)}');
      final res = await http.get(uri, headers: {
        'Authorization': 'Bearer ${AppState.token}',
        'Accept': 'application/json',
      });
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final raw = body['data'];
        setState(() {
          _guests = (raw is Map ? raw['data'] : raw) ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0';
    double d = double.tryParse(val.toString()) ?? 0;
    if (d == d.roundToDouble()) {
      return d.toInt().toString().replaceAllMapped(
            RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
            (m) => '${m[1]},',
          );
    }
    return d.toStringAsFixed(2);
  }

  Future<void> _openGuestStaysDialog(Map<String, dynamic> guest) async {
    final guestId = guest['id'];
    final guestName = guest['name'] ?? 'النزيل';

    showDialog(
      context: context,
      builder: (ctx) {
        return FutureBuilder<http.Response>(
          future: http.get(
            Uri.parse('${AppState.apiUrl}/staff/guests/$guestId/stays'),
            headers: {
              'Authorization': 'Bearer ${AppState.token}',
              'Accept': 'application/json',
            },
          ),
          builder: (context, snapshot) {
            Widget body;
            if (snapshot.connectionState == ConnectionState.waiting) {
              body = const Center(
                child: Padding(
                  padding: EdgeInsets.all(40),
                  child: CircularProgressIndicator(),
                ),
              );
            } else if (snapshot.hasError || !snapshot.hasData || snapshot.data!.statusCode != 200) {
              body = const Center(
                child: Padding(
                  padding: EdgeInsets.all(30),
                  child: Text('تعذر تحميل سجل الإقامات، يرجى المحاولة لاحقاً.', style: TextStyle(color: Colors.red)),
                ),
              );
            } else {
              final json = jsonDecode(snapshot.data!.body);
              final List stays = json['stays'] ?? [];

              if (stays.isEmpty) {
                body = const Center(
                  child: Padding(
                    padding: EdgeInsets.all(30),
                    child: Text('لا توجد إقامات مسجلة لهذا النزيل حتى الآن.', style: TextStyle(color: Color(0xFF64748B))),
                  ),
                );
              } else {
                body = ListView.separated(
                  shrinkWrap: true,
                  itemCount: stays.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (_, idx) {
                    final s = stays[idx];
                    final isUsd = s['currency'] == 'USD';
                    final rate = isUsd ? '\$${_formatNumber(s['nightly_rate'])} USD' : '${_formatNumber(s['nightly_rate'])} ل.س';
                    final total = isUsd ? '\$${_formatNumber(s['invoice_total'])} USD' : '${_formatNumber(s['invoice_total'])} ل.س';
                    final paid = isUsd ? '\$${_formatNumber(s['actual_paid'])} USD' : '${_formatNumber(s['actual_paid'])} ل.س';
                    final isPaid = s['is_paid'] == true;

                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFEFF6FF),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: const Color(0xFFBFDBFE)),
                                ),
                                child: Text('غرفة ${s['room_number']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1D4ED8))),
                              ),
                              const SizedBox(width: 8),
                              Text('${s['room_type']}', style: const TextStyle(fontSize: 12, color: Color(0xFF475569))),
                              const Spacer(),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: isPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: isPaid ? const Color(0xFF86EFAC) : const Color(0xFFFCA5A5)),
                                ),
                                child: Text(
                                  isPaid ? 'مسدد بالكامل ✔' : 'غير مسدد',
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: isPaid ? const Color(0xFF166534) : const Color(0xFF991B1B)),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(Icons.calendar_today, size: 13, color: Color(0xFF64748B)),
                              const SizedBox(width: 4),
                              Text('${s['check_in']} ⬅️ ${s['check_out']} (${s['nights']} ليالٍ)', style: const TextStyle(fontSize: 12, color: Color(0xFF334155))),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    const Text('سعر الليلة الفعلي: ', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                                    Text(rate, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: isUsd ? const Color(0xFF047857) : const Color(0xFF1D4ED8))),
                                  ],
                                ),
                                Row(
                                  children: [
                                    const Text('المسدد فعلاً: ', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                                    Text(paid, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                                    Text(' / $total', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                );
              }
            }

            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Row(
                children: [
                  const Icon(Icons.hotel_class, color: Color(0xFF1D4ED8)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'سجل إقامات النزيل: $guestName',
                      style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              content: SizedBox(
                width: 600,
                child: body,
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('إغلاق'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _openAddGuestDialog() {
    if (AppState.isReadOnlyOwner()) return;

    final nameCtrl = TextEditingController();
    final fatherNameCtrl = TextEditingController();
    final nationalityCtrl = TextEditingController(text: 'سوري');
    String idType = 'national_id';
    final idNumberCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final birthDateCtrl = TextEditingController();
    final addressCtrl = TextEditingController();
    final notesCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.person_add_rounded, color: Color(0xFF1D4ED8)),
                SizedBox(width: 8),
                Text('تسجيل نزيل جديد في الدليل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 520,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: nameCtrl,
                            decoration: InputDecoration(
                              labelText: 'اسم النزيل الكامل *',
                              prefixIcon: const Icon(Icons.badge_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: fatherNameCtrl,
                            decoration: InputDecoration(
                              labelText: 'اسم الأب',
                              prefixIcon: const Icon(Icons.family_restroom_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: phoneCtrl,
                            decoration: InputDecoration(
                              labelText: 'رقم الهاتف / واتساب *',
                              prefixIcon: const Icon(Icons.phone_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: nationalityCtrl,
                            decoration: InputDecoration(
                              labelText: 'الجنسية',
                              prefixIcon: const Icon(Icons.flag_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: DropdownButtonFormField<String>(
                            value: idType,
                            decoration: InputDecoration(
                              labelText: 'نوع الوثيقة',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'national_id', child: Text('بطاقة شخصية / هوية')),
                              DropdownMenuItem(value: 'passport', child: Text('جواز سفر')),
                              DropdownMenuItem(value: 'residence', child: Text('إقامة')),
                              DropdownMenuItem(value: 'driver_license', child: Text('شهادة قيادة')),
                            ],
                            onChanged: (val) {
                              if (val != null) setDlgState(() => idType = val);
                            },
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: idNumberCtrl,
                            decoration: InputDecoration(
                              labelText: 'رقم الوثيقة / الرقم الوطني',
                              prefixIcon: const Icon(Icons.credit_card_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: emailCtrl,
                            decoration: InputDecoration(
                              labelText: 'البريد الإلكتروني',
                              prefixIcon: const Icon(Icons.email_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: birthDateCtrl,
                            decoration: InputDecoration(
                              labelText: 'تاريخ الميلاد (YYYY-MM-DD)',
                              prefixIcon: const Icon(Icons.cake_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: addressCtrl,
                      decoration: InputDecoration(
                        labelText: 'العنوان ومحل الإقامة',
                        prefixIcon: const Icon(Icons.home_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: notesCtrl,
                      maxLines: 2,
                      decoration: InputDecoration(
                        labelText: 'ملاحظات إضافية حول النزيل',
                        prefixIcon: const Icon(Icons.notes_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  if (nameCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  try {
                    final res = await http.post(
                      Uri.parse('${AppState.apiUrl}/staff/guests'),
                      headers: {
                        'Authorization': 'Bearer ${AppState.token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                      },
                      body: jsonEncode({
                        'name': nameCtrl.text.trim(),
                        'father_name': fatherNameCtrl.text.trim(),
                        'phone': phoneCtrl.text.trim(),
                        'nationality': nationalityCtrl.text.trim(),
                        'id_type': idType,
                        'id_number': idNumberCtrl.text.trim(),
                        'national_id': idNumberCtrl.text.trim(),
                        'email': emailCtrl.text.trim(),
                        'birth_date': birthDateCtrl.text.trim().isNotEmpty ? birthDateCtrl.text.trim() : null,
                        'address': addressCtrl.text.trim(),
                        'notes': notesCtrl.text.trim(),
                      }),
                    );
                    final body = jsonDecode(res.body);
                    if (res.statusCode == 200 && mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم إضافة النزيل بنجاح.'), backgroundColor: Colors.green));
                      _fetchGuests();
                    }
                  } catch (_) {}
                },
                child: const Text('حفظ وتسجيل النزيل'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _openEditGuestDialog(Map<String, dynamic> guest) {
    if (AppState.isReadOnlyOwner()) return;

    final nameCtrl = TextEditingController(text: guest['name'] ?? '');
    final fatherNameCtrl = TextEditingController(text: guest['father_name'] ?? '');
    final nationalityCtrl = TextEditingController(text: guest['nationality'] ?? 'سوري');
    String idType = guest['id_type'] ?? 'national_id';
    final idNumberCtrl = TextEditingController(text: guest['id_number'] ?? guest['national_id'] ?? '');
    final phoneCtrl = TextEditingController(text: guest['phone'] ?? '');
    final emailCtrl = TextEditingController(text: guest['email'] ?? '');
    final birthDateCtrl = TextEditingController(text: guest['birth_date'] ?? '');
    final addressCtrl = TextEditingController(text: guest['address'] ?? '');
    final notesCtrl = TextEditingController(text: guest['notes'] ?? '');

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Row(
              children: [
                const Icon(Icons.edit_note_rounded, color: Color(0xFF1D4ED8)),
                const SizedBox(width: 8),
                Text('تعديل بيانات النزيل: ${guest['name']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              ],
            ),
            content: SizedBox(
              width: 520,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: nameCtrl,
                            decoration: InputDecoration(
                              labelText: 'اسم النزيل الكامل *',
                              prefixIcon: const Icon(Icons.badge_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: fatherNameCtrl,
                            decoration: InputDecoration(
                              labelText: 'اسم الأب',
                              prefixIcon: const Icon(Icons.family_restroom_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: phoneCtrl,
                            decoration: InputDecoration(
                              labelText: 'رقم الهاتف / واتساب *',
                              prefixIcon: const Icon(Icons.phone_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: nationalityCtrl,
                            decoration: InputDecoration(
                              labelText: 'الجنسية',
                              prefixIcon: const Icon(Icons.flag_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: DropdownButtonFormField<String>(
                            value: idType,
                            decoration: InputDecoration(
                              labelText: 'نوع الوثيقة',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'national_id', child: Text('بطاقة شخصية / هوية')),
                              DropdownMenuItem(value: 'passport', child: Text('جواز سفر')),
                              DropdownMenuItem(value: 'residence', child: Text('إقامة')),
                              DropdownMenuItem(value: 'driver_license', child: Text('شهادة قيادة')),
                            ],
                            onChanged: (val) {
                              if (val != null) setDlgState(() => idType = val);
                            },
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: idNumberCtrl,
                            decoration: InputDecoration(
                              labelText: 'رقم الوثيقة / الرقم الوطني',
                              prefixIcon: const Icon(Icons.credit_card_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: emailCtrl,
                            decoration: InputDecoration(
                              labelText: 'البريد الإلكتروني',
                              prefixIcon: const Icon(Icons.email_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextField(
                            controller: birthDateCtrl,
                            decoration: InputDecoration(
                              labelText: 'تاريخ الميلاد (YYYY-MM-DD)',
                              prefixIcon: const Icon(Icons.cake_outlined),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: addressCtrl,
                      decoration: InputDecoration(
                        labelText: 'العنوان ومحل الإقامة',
                        prefixIcon: const Icon(Icons.home_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: notesCtrl,
                      maxLines: 2,
                      decoration: InputDecoration(
                        labelText: 'ملاحظات إضافية حول النزيل',
                        prefixIcon: const Icon(Icons.notes_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  if (nameCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  try {
                    final res = await http.put(
                      Uri.parse('${AppState.apiUrl}/staff/guests/${guest['id']}'),
                      headers: {
                        'Authorization': 'Bearer ${AppState.token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                      },
                      body: jsonEncode({
                        'name': nameCtrl.text.trim(),
                        'father_name': fatherNameCtrl.text.trim(),
                        'phone': phoneCtrl.text.trim(),
                        'nationality': nationalityCtrl.text.trim(),
                        'id_type': idType,
                        'id_number': idNumberCtrl.text.trim(),
                        'national_id': idNumberCtrl.text.trim(),
                        'email': emailCtrl.text.trim(),
                        'birth_date': birthDateCtrl.text.trim().isNotEmpty ? birthDateCtrl.text.trim() : null,
                        'address': addressCtrl.text.trim(),
                        'notes': notesCtrl.text.trim(),
                      }),
                    );
                    final body = jsonDecode(res.body);
                    if (res.statusCode == 200 && mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم تحديث بيانات النزيل.'), backgroundColor: Colors.green));
                      _fetchGuests();
                    } else if (mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'فشل التحديث.'), backgroundColor: Colors.red));
                    }
                  } catch (_) {}
                },
                child: const Text('تحديث البيانات'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _deleteGuest(Map<String, dynamic> guest) {
    if (AppState.isReadOnlyOwner()) return;

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('تأكيد حذف النزيل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          content: Text('هل أنت متأكد من حذف بيانات النزيل "${guest['name']}" من السجل؟\nلا يمكن التراجع عن هذه الخطوة.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626), foregroundColor: Colors.white),
              onPressed: () async {
                Navigator.pop(ctx);
                try {
                  final res = await http.delete(
                    Uri.parse('${AppState.apiUrl}/staff/guests/${guest['id']}'),
                    headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
                  );
                  final body = jsonDecode(res.body);
                  if (res.statusCode == 200 && mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم حذف النزيل.'), backgroundColor: Colors.green));
                    _fetchGuests();
                  } else if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تعذر الحذف.'), backgroundColor: Colors.red));
                  }
                } catch (_) {}
              },
              child: const Text('نعم، تأكيد الحذف'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('👤 دليل وسجل النزلاء (Guests Directory)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const SizedBox(height: 4),
                    Text('إجمالي النزلاء المسجلين: ${_guests.length} نزيل · بطاقات بيانات كاملة، إمكانية التعديل والحجز السريع', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  ],
                ),
                Row(
                  children: [
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('تسجيل نزيل جديد (+)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      onPressed: _openAddGuestDialog,
                    ),
                    const SizedBox(width: 8),
                    IconButton(onPressed: () => _fetchGuests(), icon: const Icon(Icons.refresh, color: Color(0xFF64748B))),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Search Box
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _searchCtrl,
                      decoration: InputDecoration(
                        hintText: 'البحث باسم النزيل، اسم الأب، رقم الهاتف، أو رقم الهوية / الجواز...',
                        prefixIcon: const Icon(Icons.search, size: 20),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                      onSubmitted: (val) => _fetchGuests(val),
                    ),
                  ),
                  const SizedBox(width: 10),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                    onPressed: () => _fetchGuests(),
                    child: const Text('بحث'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            if (_isLoading)
              const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator()))
            else if (_guests.isEmpty)
              Container(
                padding: const EdgeInsets.all(40),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                child: const Center(
                  child: Column(
                    children: [
                      Icon(Icons.people_outline, size: 48, color: Color(0xFF94A3B8)),
                      SizedBox(height: 12),
                      Text('لا توجد بيانات نزلاء مسجلة تطابق البحث.', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                      SizedBox(height: 4),
                      Text('يمكنك إضافة نزيل جديد عبر الزر أعلاه أو يتم إدراجه تلقائياً عند إنشاء أي حجز.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                    ],
                  ),
                ),
              )
            else
              // Cards Grid View matching website design
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 380,
                  mainAxisExtent: 260,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                ),
                itemCount: _guests.length,
                itemBuilder: (ctx, i) {
                  final g = _guests[i];
                  return _buildGuestCard(g);
                },
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildGuestCard(Map<String, dynamic> g) {
    final name = g['name'] ?? 'نزيل';
    final fatherName = g['father_name'];
    final nationality = g['nationality'] ?? 'سوري';
    final phone = g['phone'] ?? '';
    final email = g['email'] ?? '';
    final idNumber = g['id_number'] ?? g['national_id'] ?? '';
    final idType = g['id_type'] ?? 'national_id';
    final staysCount = g['bookings_count'] ?? 0;

    String idTypeLabel = 'هوية وطنية';
    if (idType == 'passport') idTypeLabel = 'جواز سفر';
    if (idType == 'residence') idTypeLabel = 'إقامة';
    if (idType == 'driver_license') idTypeLabel = 'رخصة قيادة';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 3)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Card Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: const BoxDecoration(
              color: Color(0xFFF8FAFC),
              borderRadius: BorderRadius.only(topLeft: Radius.circular(15), topRight: Radius.circular(15)),
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 16,
                  backgroundColor: const Color(0xFF1D4ED8).withValues(alpha: 0.12),
                  child: Text(
                    name.isNotEmpty ? name.substring(0, 1) : 'U',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8), fontSize: 13),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (fatherName != null && fatherName.toString().isNotEmpty)
                        Text('الأب: $fatherName', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: const Color(0xFFCBD5E1)),
                  ),
                  child: Text(nationality, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                ),
              ],
            ),
          ),

          // Card Body
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ID row
                  Row(
                    children: [
                      const Icon(Icons.credit_card, size: 14, color: Color(0xFF64748B)),
                      const SizedBox(width: 6),
                      Text('$idTypeLabel: ', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                      Expanded(
                        child: Text(
                          idNumber.isNotEmpty ? idNumber : '—',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Phone row
                  Row(
                    children: [
                      const Icon(Icons.phone, size: 14, color: Color(0xFF059669)),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          phone.isNotEmpty ? phone : '—',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                          textDirection: TextDirection.ltr,
                          textAlign: TextAlign.right,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Email or Address
                  if (email.isNotEmpty)
                    Row(
                      children: [
                        const Icon(Icons.email_outlined, size: 14, color: Color(0xFF64748B)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            email,
                            style: const TextStyle(fontSize: 11, color: Color(0xFF475569)),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  const Spacer(),

                  // Stays count pill (clickable to view history)
                  InkWell(
                    onTap: () => _openGuestStaysDialog(g),
                    borderRadius: BorderRadius.circular(6),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFFDCFCE7),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFF86EFAC)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.hotel, size: 12, color: Color(0xFF166534)),
                          const SizedBox(width: 4),
                          Text('$staysCount إقامة فندقية', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF166534))),
                          const SizedBox(width: 2),
                          const Icon(Icons.arrow_drop_down, size: 14, color: Color(0xFF166534)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Card Footer Actions
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: const BoxDecoration(
              color: Color(0xFFF8FAFC),
              borderRadius: BorderRadius.only(bottomLeft: Radius.circular(15), bottomRight: Radius.circular(15)),
              border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF1D4ED8),
                      side: const BorderSide(color: Color(0xFF93C5FD)),
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () => _openEditGuestDialog(g),
                    icon: const Icon(Icons.edit, size: 13),
                    label: const Text('تعديل', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1D4ED8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () {
                      showDialog(
                        context: context,
                        builder: (ctx) => CreateBookingDialog(
                          preselectedGuestName: g['name'],
                          preselectedGuestPhone: g['phone'],
                          onBookingCreated: () => _fetchGuests(),
                        ),
                      );
                    },
                    icon: const Icon(Icons.add, size: 13),
                    label: const Text('حجز جديد', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 6),
                IconButton(
                  constraints: const BoxConstraints(),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFFFECACA)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    padding: const EdgeInsets.all(6),
                  ),
                  onPressed: () => _deleteGuest(g),
                  icon: const Icon(Icons.delete_outline, size: 15, color: Color(0xFFDC2626)),
                  tooltip: 'حذف النزيل',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 3. BOOKINGS MANAGEMENT TAB (Front Desk: Full Operations, Check-in / Check-out)
// ============================================================================
class BookingsTab extends StatefulWidget {
  const BookingsTab({super.key});

  @override
  State<BookingsTab> createState() => _BookingsTabState();
}

class _BookingsTabState extends State<BookingsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _bookings = [];
  String _statusFilter = 'all';

  @override
  void initState() {
    super.initState();
    _fetchBookings();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchBookings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/bookings?status=$_statusFilter'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _bookings = body['data']['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _performCheckIn(int id) async {
    if (AppState.isReadOnlyOwner()) {
      _showOwnerWarning();
      return;
    }
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/bookings/$id/check-in'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم تسجيل الدخول بنجاح.')));
        _fetchBookings();
      }
    } catch (_) {}
  }


  Future<void> _performCancel(int id) async {
    if (AppState.isReadOnlyOwner()) {
      _showOwnerWarning();
      return;
    }
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/bookings/$id/cancel'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم إلغاء الحجز بنجاح.')));
        _fetchBookings();
      }
    } catch (_) {}
  }

  void _showOwnerWarning() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('عذراً: حساب مالك الفندق مخصص للاطلاع والمعاينة فقط دون صلاحية الكتابة والتعديل.')),
    );
  }

  void _openCreateBookingDialog() {
    if (AppState.isReadOnlyOwner()) {
      _showOwnerWarning();
      return;
    }
    showDialog(
      context: context,
      builder: (ctx) => CreateBookingDialog(
        onBookingCreated: () => _fetchBookings(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1D4ED8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _openCreateBookingDialog,
                    icon: const Icon(Icons.add, size: 20),
                    label: const Text('حجز جديد لزبون (Walk-in)', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 16),
                  DropdownButton<String>(
                    value: _statusFilter,
                    underline: const SizedBox(),
                    items: const [
                      DropdownMenuItem(value: 'all', child: Text('جميع الحالات')),
                      DropdownMenuItem(value: 'confirmed', child: Text('المؤكدة')),
                      DropdownMenuItem(value: 'checked_in', child: Text('المسكنة حالياً (Checked-in)')),
                      DropdownMenuItem(value: 'checked_out', child: Text('المغادرة')),
                      DropdownMenuItem(value: 'cancelled', child: Text('الملغاة')),
                    ],
                    onChanged: (val) {
                      if (val != null) {
                        setState(() => _statusFilter = val);
                        _fetchBookings();
                      }
                    },
                  ),
                ],
              ),
              IconButton(onPressed: _fetchBookings, icon: const Icon(Icons.refresh), tooltip: 'تحديث الحجوزات'),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('رقم الحجز')),
                  DataColumn(label: Text('اسم النزيل')),
                  DataColumn(label: Text('رقم الهاتف')),
                  DataColumn(label: Text('الغرفة والنوع')),
                  DataColumn(label: Text('تاريخ الوصول')),
                  DataColumn(label: Text('تاريخ المغادرة')),
                  DataColumn(label: Text('المبلغ الإجمالي')),
                  DataColumn(label: Text('الحالة')),
                  DataColumn(label: Text('إجراءات الاستقبال')),
                ],
                rows: _bookings.map((b) {
                  final status = b['status'] ?? '';
                  Color statusColor = Colors.grey;
                  String statusLabel = status;
                  if (status == 'confirmed') {
                    statusColor = Colors.blue;
                    statusLabel = 'حجز مؤكد';
                  } else if (status == 'checked_in') {
                    statusColor = Colors.green;
                    statusLabel = 'مقيم حالياً';
                  } else if (status == 'checked_out') {
                    statusColor = Colors.teal;
                    statusLabel = 'غادر الفندق';
                  } else if (status == 'cancelled') {
                    statusColor = Colors.red;
                    statusLabel = 'ملغى';
                  }

                  return DataRow(cells: [
                    DataCell(Text('#${b['id']}', style: const TextStyle(fontWeight: FontWeight.bold))),
                    DataCell(Text(b['guest']?['name'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold))),
                    DataCell(Text(b['guest']?['phone'] ?? '—')),
                    DataCell(Text('غرفة ${b['room']?['room_number'] ?? '—'} (${b['room']?['room_type']?['name'] ?? ''})')),
                    DataCell(Text(b['check_in'] ?? '')),
                    DataCell(Text(b['check_out'] ?? '')),
                    DataCell(Text('${b['total_price']} ${b['currency']}', style: const TextStyle(fontWeight: FontWeight.bold))),
                    DataCell(Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(8)),
                      child: Text(statusLabel, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 11)),
                    )),
                    DataCell(
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (status == 'confirmed')
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF1D4ED8),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              ),
                              onPressed: () => _performCheckIn(b['id']),
                              icon: const Icon(Icons.login, size: 14),
                              label: const Text('تسجيل دخول (Check-in)', style: TextStyle(fontSize: 11)),
                            ),
                          if (status == 'checked_in') ...[
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF16A34A),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () async {
                                final res = await showDialog<Map<String, dynamic>?>(
                                  context: context,
                                  builder: (ctx) => CheckoutBookingDialog(
                                    booking: b,
                                    room: b['room'],
                                    onComplete: () => _fetchBookings(),
                                  ),
                                );
                                if (res != null && mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(res['message'] ?? 'تم تسجيل المغادرة، سداد الفاتورة، وترحيل الإيراد بنجاح.'),
                                      backgroundColor: Colors.green,
                                    ),
                                  );
                                  if (res['invoice'] != null) {
                                    await Future.delayed(const Duration(milliseconds: 150));
                                    if (mounted) {
                                      final invoiceWithDetails = {
                                        ...AppState.safeMap(res['invoice']),
                                        if (res['invoice']['booking'] == null) 'booking': b,
                                        if (res['invoice']['room'] == null) 'room': b['room'],
                                      };
                                      showDialog(
                                        context: context,
                                        builder: (ctx) => InvoicePrintReceiptDialog(invoice: invoiceWithDetails),
                                      );
                                    }
                                  }
                                }
                              },
                              icon: const Icon(Icons.logout, size: 14),
                              label: const Text('تسجيل مغادرة وسداد (Check-out)', style: TextStyle(fontSize: 11)),
                            ),
                          ],
                          if (b['invoices'] != null && (b['invoices'] as List).isNotEmpty) ...[
                            const SizedBox(width: 6),
                            IconButton(
                              icon: const Icon(Icons.receipt_long, color: Color(0xFF1D4ED8), size: 18),
                              tooltip: 'معاينة وطباعة الفاتورة',
                              onPressed: () {
                                final inv = (b['invoices'] as List).first;
                                showDialog(
                                  context: context,
                                  builder: (ctx) => InvoicePrintReceiptDialog(invoice: {
                                    ...AppState.safeMap(inv),
                                    'booking': b,
                                    'room': b['room'],
                                  }),
                                );
                              },
                            ),
                          ],
                          if (status != 'cancelled' && status != 'checked_out') ...[
                            const SizedBox(width: 6),
                            IconButton(
                              onPressed: () => _performCancel(b['id']),
                              icon: const Icon(Icons.cancel_outlined, color: Colors.red, size: 18),
                              tooltip: 'إلغاء الحجز',
                            ),
                          ],
                        ],
                      ),
                    ),
                  ]);
                }).toList(),
              ),
            ),
          ),
        ],
      ),
    ),
    );
  }
}

// ============================================================================
// 4. ROOMS MANAGEMENT TAB (Full Create, Edit, Status Changes)
// ============================================================================
class RoomsTab extends StatefulWidget {
  const RoomsTab({super.key});

  @override
  State<RoomsTab> createState() => _RoomsTabState();
}

class _RoomsTabState extends State<RoomsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _rooms = [];
  List<dynamic> _roomTypes = [];

  @override
  void initState() {
    super.initState();
    _fetchRooms();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchRooms() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/rooms'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _rooms = body['data'] ?? [];
          _roomTypes = body['room_types'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _showRoomDialog([Map<String, dynamic>? room]) {
    if (AppState.isReadOnlyOwner()) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('وضع مالك الفندق مخصص للاطلاع فقط.')),
      );
      return;
    }

    final isEdit = room != null;
    final numCtrl = TextEditingController(text: isEdit ? room['room_number'] : '');
    final sypCtrl = TextEditingController(text: isEdit ? '${room['price_syp']}' : '500000');
    final usdCtrl = TextEditingController(text: isEdit ? '${room['price_usd']}' : '50');
    int? typeId = isEdit ? room['room_type_id'] : (_roomTypes.isNotEmpty ? _roomTypes.first['id'] : null);
    String status = isEdit ? room['status'] : 'available';

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Row(
              children: [
                Icon(isEdit ? Icons.edit : Icons.add_home_work, color: const Color(0xFF1D4ED8)),
                const SizedBox(width: 8),
                Text(isEdit ? 'تعديل بيانات الغرفة' : 'إنشاء غرفة فندقية جديدة', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 440,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: numCtrl,
                      decoration: InputDecoration(
                        labelText: 'رقم الغرفة (مثل 101, 204B) *',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    if (_roomTypes.isNotEmpty)
                      DropdownButtonFormField<int>(
                        initialValue: typeId,
                        decoration: InputDecoration(
                          labelText: 'نوع الغرفة',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        items: _roomTypes.map<DropdownMenuItem<int>>((t) {
                          return DropdownMenuItem<int>(
                            value: t['id'],
                            child: Text(t['name'] ?? ''),
                          );
                        }).toList(),
                        onChanged: (val) => setDState(() => typeId = val),
                      ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: usdCtrl,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              labelText: 'السعر بالدولار (\$)',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextField(
                            controller: sypCtrl,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              labelText: 'السعر بالليرة (ل.س)',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: status,
                      decoration: InputDecoration(
                        labelText: 'حالة الغرفة التشغيلية',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'available', child: Text('🟢 متاح للاستقبال')),
                        DropdownMenuItem(value: 'occupied', child: Text('🔴 مشغولة بنزيل')),
                        DropdownMenuItem(value: 'cleaning', child: Text('🟠 تحت التنظيف')),
                        DropdownMenuItem(value: 'maintenance', child: Text('🟣 تحت الصيانة')),
                      ],
                      onChanged: (val) {
                        if (val != null) setDState(() => status = val);
                      },
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  if (numCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  try {
                    final payload = {
                      'room_number': numCtrl.text.trim(),
                      'room_type_id': typeId,
                      'price_usd': double.tryParse(usdCtrl.text) ?? 0,
                      'price_syp': double.tryParse(sypCtrl.text) ?? 0,
                      'status': status,
                    };

                    final url = isEdit ? '${AppState.apiUrl}/staff/rooms/${room['id']}' : '${AppState.apiUrl}/staff/rooms';

                    final res = isEdit
                        ? await http.put(
                            Uri.parse(url),
                            headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                            body: jsonEncode(payload),
                          )
                        : await http.post(
                            Uri.parse(url),
                            headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                            body: jsonEncode(payload),
                          );

                    final body = jsonDecode(res.body);
                    if (res.statusCode == 200) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم حفظ الغرفة بنجاح.'), backgroundColor: Colors.green));
                      _fetchRooms();
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'فشل حفظ الغرفة.'), backgroundColor: Colors.red));
                    }
                  } catch (_) {}
                },
                child: Text(isEdit ? 'حفظ التعديلات' : 'إضافة الغرفة'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _deleteRoom(int id) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.delete(
        Uri.parse('${AppState.apiUrl}/staff/rooms/$id'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم حذف الغرفة بنجاح.')));
        _fetchRooms();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تعذر الحذف.')));
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => _showRoomDialog(),
                  icon: const Icon(Icons.add, size: 20),
                  label: const Text('إنشاء غرفة جديدة', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
                IconButton(onPressed: _fetchRooms, icon: const Icon(Icons.refresh), tooltip: 'تحديث الغرف'),
              ],
            ),
            const SizedBox(height: 20),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                maxCrossAxisExtent: 320,
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                childAspectRatio: 1.45,
              ),
            itemCount: _rooms.length,
            itemBuilder: (ctx, i) {
              final r = _rooms[i];
              Color statusColor = Colors.green;
              String statusText = 'متاحة';
              if (r['status'] == 'occupied') {
                statusColor = Colors.red;
                statusText = 'مشغولة';
              } else if (r['status'] == 'cleaning' || r['needs_cleaning'] == true) {
                statusColor = Colors.orange;
                statusText = 'تحتاج تنظيف';
              } else if (r['status'] == 'maintenance') {
                statusColor = Colors.purple;
                statusText = 'صيانة';
              }

              return Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: statusColor.withValues(alpha: 0.4), width: 1.5),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 8, offset: const Offset(0, 3)),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('غرفة ${r['room_number']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(6)),
                          child: Text(statusText, style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                    Text(r['room_type'] ?? 'غرفة فندقية', style: const TextStyle(color: Color(0xFF64748B), fontSize: 12)),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('\$${r['price_usd']}', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8), fontSize: 14)),
                        Text('${r['price_syp']} ل.س', style: const TextStyle(fontSize: 12, color: Color(0xFF059669), fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const Divider(height: 1),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton.icon(
                          onPressed: () => _showRoomDialog(r),
                          icon: const Icon(Icons.edit, size: 14),
                          label: const Text('تعديل', style: TextStyle(fontSize: 11)),
                        ),
                        if (r['status'] != 'occupied')
                          IconButton(
                            onPressed: () => _deleteRoom(r['id']),
                            icon: const Icon(Icons.delete_outline, size: 16, color: Colors.red),
                            tooltip: 'حذف الغرفة',
                          ),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    ),
    );
  }
}

// ============================================================================
// 5. HOUSEKEEPING & CLEANING TAB
// ============================================================================
class CleaningTab extends StatefulWidget {
  const CleaningTab({super.key});

  @override
  State<CleaningTab> createState() => _CleaningTabState();
}

class _CleaningTabState extends State<CleaningTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _cleaningRooms = [];

  @override
  void initState() {
    super.initState();
    _fetchCleaning();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchCleaning() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/cleaning'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _cleaningRooms = body['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _completeCleaning(int roomId) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/cleaning/$roomId/complete'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم تأكيد التنظيف بنجاح.'), backgroundColor: Colors.green));
        _fetchCleaning();
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('طابور التدبير المنزلي وتنظيف وتعقيم الغرف', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  SizedBox(height: 4),
                  Text('الغرف التي تحتاج إلى تنظيف بعد مغادرة النزيل أو بطلب خاص من موظف الاستقبال', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
              IconButton(onPressed: _fetchCleaning, icon: const Icon(Icons.refresh)),
            ],
          ),
          const SizedBox(height: 20),
          if (_cleaningRooms.isEmpty)
            Expanded(
              child: Center(
                child: Container(
                  padding: const EdgeInsets.all(32),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
                  child: const Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.check_circle_outline, color: Colors.green, size: 54),
                      SizedBox(height: 12),
                      Text('جميع الغرف نظيفة ومعقمة وجاهزة لاستقبال النزلاء!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                ),
              ),
            )
          else
            Expanded(
              child: Scrollbar(
                controller: _scrollController,
                thumbVisibility: true,
                trackVisibility: true,
                interactive: true,
                child: ListView.builder(
                  controller: _scrollController,
                  itemCount: _cleaningRooms.length,
                itemBuilder: (ctx, i) {
                  final r = _cleaningRooms[i];
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.orange.withValues(alpha: 0.4)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: const Color(0xFFFFF7ED), borderRadius: BorderRadius.circular(12)),
                              child: const Icon(Icons.cleaning_services, color: Colors.orange, size: 28),
                            ),
                            const SizedBox(width: 16),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('غرفة رقم ${r['room_number']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                Text('نوع الغرفة: ${r['room_type']?['name'] ?? 'قياسي'} - الحالة: تحتاج تنظيف وتجهيز', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                              ],
                            ),
                          ],
                        ),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF16A34A),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () => _completeCleaning(r['id']),
                          icon: const Icon(Icons.done_all, size: 18),
                          label: const Text('تأكيد إتمام التنظيف والتعقيم'),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// 6. MAINTENANCE TAB
// ============================================================================
class MaintenanceTab extends StatefulWidget {
  const MaintenanceTab({super.key});

  @override
  State<MaintenanceTab> createState() => _MaintenanceTabState();
}

class _MaintenanceTabState extends State<MaintenanceTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _tickets = [];

  @override
  void initState() {
    super.initState();
    _fetchMaintenance();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchMaintenance() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/maintenance'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _tickets = body['data']['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _openCreateTicketDialog() async {
    if (AppState.isReadOnlyOwner()) return;

    List<dynamic> rooms = [];
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/rooms'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        rooms = body['data'] ?? [];
      }
    } catch (_) {}

    if (!mounted || rooms.isEmpty) return;

    int selectedRoomId = rooms.first['id'];
    String issueType = 'تكييف وتبريد';
    String priority = 'normal';
    final notesCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.build_circle, color: Color(0xFF1D4ED8)),
                SizedBox(width: 8),
                Text('تسجيل بلاغ صيانة لغرفة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 440,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DropdownButtonFormField<int>(
                      initialValue: selectedRoomId,
                      decoration: InputDecoration(labelText: 'الغرفة', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      items: rooms.map<DropdownMenuItem<int>>((r) => DropdownMenuItem(value: r['id'], child: Text('غرفة ${r['room_number']}'))).toList(),
                      onChanged: (val) {
                        if (val != null) setDState(() => selectedRoomId = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: issueType,
                      decoration: InputDecoration(labelText: 'نوع العطل', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      items: const [
                        DropdownMenuItem(value: 'تكييف وتبريد', child: Text('تكييف وتبريد')),
                        DropdownMenuItem(value: 'كهرباء وإنارة', child: Text('كهرباء وإنارة')),
                        DropdownMenuItem(value: 'سباكة ومياه', child: Text('سباكة ومياه')),
                        DropdownMenuItem(value: 'أثاث وأبواب', child: Text('أثاث وأبواب')),
                        DropdownMenuItem(value: 'أجهزة وشاشات', child: Text('أجهزة وشاشات')),
                      ],
                      onChanged: (val) {
                        if (val != null) setDState(() => issueType = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: priority,
                      decoration: InputDecoration(labelText: 'الأولوية', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      items: const [
                        DropdownMenuItem(value: 'low', child: Text('منخفضة')),
                        DropdownMenuItem(value: 'normal', child: Text('عادية')),
                        DropdownMenuItem(value: 'urgent', child: Text('🔴 عاجلة')),
                      ],
                      onChanged: (val) {
                        if (val != null) setDState(() => priority = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: notesCtrl,
                      maxLines: 3,
                      decoration: InputDecoration(
                        labelText: 'تفاصيل العطل وملاحظات الفني',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  Navigator.pop(ctx);
                  try {
                    await http.post(
                      Uri.parse('${AppState.apiUrl}/staff/maintenance'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({
                        'room_id': selectedRoomId,
                        'issue_type': issueType,
                        'priority': priority,
                        'notes': notesCtrl.text.trim(),
                      }),
                    );
                    _fetchMaintenance();
                  } catch (_) {}
                },
                child: const Text('تسجيل البلاغ'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _updateTicketStatus(int id, String status) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      await http.put(
        Uri.parse('${AppState.apiUrl}/staff/maintenance/$id/status'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'status': status}),
      );
      _fetchMaintenance();
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _openCreateTicketDialog,
                  icon: const Icon(Icons.add, size: 20),
                  label: const Text('تسجيل بلاغ صيانة جديد', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
                IconButton(onPressed: _fetchMaintenance, icon: const Icon(Icons.refresh)),
              ],
            ),
            const SizedBox(height: 20),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: DataTable(
                  columns: const [
                DataColumn(label: Text('رقم البلاغ')),
                DataColumn(label: Text('الغرفة')),
                DataColumn(label: Text('نوع العطل')),
                DataColumn(label: Text('الأولوية')),
                DataColumn(label: Text('الحالة')),
                DataColumn(label: Text('ملاحظات')),
                DataColumn(label: Text('الإجراء')),
              ],
              rows: _tickets.map((t) {
                final status = t['status'] ?? '';
                final isDone = status == 'completed';

                return DataRow(cells: [
                  DataCell(Text('#${t['id']}')),
                  DataCell(Text('غرفة ${t['room']?['room_number'] ?? '—'}', style: const TextStyle(fontWeight: FontWeight.bold))),
                  DataCell(Text(t['issue_type'] ?? '')),
                  DataCell(Text(t['priority'] == 'urgent' ? '🔴 عاجل' : (t['priority'] == 'normal' ? 'عادي' : 'منخفض'))),
                  DataCell(Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isDone ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(isDone ? 'تم الإصلاح ✓' : 'قيد المتابعة', style: TextStyle(color: isDone ? Colors.green : Colors.orange, fontWeight: FontWeight.bold, fontSize: 11)),
                  )),
                  DataCell(Text(t['notes'] ?? '—')),
                  DataCell(
                    isDone
                      ? const Text('مكتمل', style: TextStyle(color: Colors.green))
                      : ElevatedButton(
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF16A34A), foregroundColor: Colors.white),
                          onPressed: () => _updateTicketStatus(t['id'], 'completed'),
                          child: const Text('تم الإصلاح واستعادة الغرفة', style: TextStyle(fontSize: 11)),
                        ),
                  ),
                ]);
              }).toList(),
              ),
            ),
          ),
        ],
      ),
    ),
    );
  }
}

// ============================================================================
// 7. INVOICES & BILLING TAB
// ============================================================================
class InvoicesTab extends StatefulWidget {
  const InvoicesTab({super.key});

  @override
  State<InvoicesTab> createState() => _InvoicesTabState();
}

class _InvoicesTabState extends State<InvoicesTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _invoices = [];
  String _statusFilter = 'all';
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchInvoices();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchInvoices() async {
    setState(() => _isLoading = true);
    try {
      final q = Uri.encodeComponent(_searchCtrl.text.trim());
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/invoices?status=$_statusFilter&q=$q'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _invoices = body['data']['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  Future<void> _handlePrintInvoice(Map<String, dynamic> inv) async {
    try {
      final html = NativePrintAndSaveService.buildInvoiceHtml(inv, 'a4');
      await NativePrintAndSaveService.printWithNativeDialog(html);
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء طباعة الفاتورة',
          message: 'تعذر تشغيل أمر الطباعة: $e',
        );
      }
    }
  }

  Future<void> _handleDownloadInvoice(Map<String, dynamic> inv) async {
    try {
      final invMap = AppState.safeMap(inv);
      final invNumber = AppState.safeString(invMap['invoice_number'], '${invMap['id']}').replaceAll('#', '');
      final defaultName = 'فاتورة_$invNumber.pdf';
      final chosenPath = await NativePrintAndSaveService.showSaveFileDialog(
        defaultFileName: defaultName,
        filter: 'ملفات PDF (*.pdf)|*.pdf|ملفات HTML (*.html)|*.html|جميع الملفات (*.*)|*.*',
      );
      if (chosenPath != null && chosenPath.isNotEmpty) {
        final html = NativePrintAndSaveService.buildInvoiceHtml(invMap, 'a4');
        final success = await NativePrintAndSaveService.saveDocument(html, chosenPath);
        if (success && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: const Color(0xFF059669),
              duration: const Duration(seconds: 5),
              content: Text(
                AppLocale.tr(
                  '✓ تم حفظ وتنزيل الفاتورة بنجاح في:\n$chosenPath',
                  '✓ Invoice downloaded and saved successfully at:\n$chosenPath',
                ),
              ),
              action: SnackBarAction(
                label: AppLocale.tr('📂 فتح الملف', '📂 Open File'),
                textColor: Colors.white,
                onPressed: () => NativePrintAndSaveService.openFile(chosenPath),
              ),
            ),
          );
        } else if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: const Color(0xFFDC2626),
              content: Text(
                AppLocale.tr('تعذر حفظ الفاتورة، يرجى المحاولة مجدداً.', 'Failed to save invoice, please try again.'),
              ),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء تنزيل الفاتورة',
          message: 'تعذر تنزيل وحفظ الفاتورة: $e',
        );
      }
    }
  }

  void _openCreateInvoiceDialog() async {
    if (AppState.isReadOnlyOwner()) return;

    List<dynamic> rooms = [];
    try {
      final rRes = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/rooms'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (rRes.statusCode == 200) {
        final rBody = jsonDecode(rRes.body);
        rooms = rBody['data'] ?? [];
      }
    } catch (_) {}

    if (!mounted) return;

    final amountCtrl = TextEditingController(text: '100');
    final serviceCtrl = TextEditingController(text: '0');
    final serviceDescCtrl = TextEditingController();
    String currency = 'USD';
    String status = 'paid';
    int? selectedRoomId;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.post_add, color: Color(0xFF1D4ED8)),
                SizedBox(width: 8),
                Text('إصدار فاتورة إقامة أو خدمات جديدة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 480,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('الغرفة المرتبطة (اختياري):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<int?>(
                      initialValue: selectedRoomId,
                      decoration: InputDecoration(
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        prefixIcon: const Icon(Icons.meeting_room_outlined),
                      ),
                      hint: const Text('بدون تحديد غرفة (خدمات عامة / Walk-in)'),
                      items: [
                        const DropdownMenuItem<int?>(value: null, child: Text('بدون تحديد غرفة (خدمات عامة)')),
                        ...rooms.map((r) => DropdownMenuItem<int?>(
                              value: r['id'],
                              child: Text('غرفة ${r['room_number']} (${r['room_type']})'),
                            )),
                      ],
                      onChanged: (val) => setDState(() => selectedRoomId = val),
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: amountCtrl,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'مبلغ الإقامة الأساسي *',
                        prefixIcon: const Icon(Icons.attach_money),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        const Text('العملة المنفصلة: ', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('دولار أمريكي (USD)'),
                          selected: currency == 'USD',
                          onSelected: (val) => setDState(() => currency = 'USD'),
                        ),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('ليرة سورية (SYP)'),
                          selected: currency == 'SYP',
                          onSelected: (val) => setDState(() => currency = 'SYP'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: serviceCtrl,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'مبلغ الخدمات الإضافية (ميني بار، مغسلة، مطعم...)',
                        prefixIcon: const Icon(Icons.room_service_outlined),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: serviceDescCtrl,
                      decoration: InputDecoration(
                        labelText: 'تفاصيل الخدمات المضافة',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        const Text('حالة السداد الفوري: ', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('مسددة بالكامل ✓'),
                          selected: status == 'paid',
                          selectedColor: const Color(0xFFDCFCE7),
                          onSelected: (val) => setDState(() => status = 'paid'),
                        ),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('معلقة وغير مسددة ⏳'),
                          selected: status == 'unpaid',
                          selectedColor: const Color(0xFFFEE2E2),
                          onSelected: (val) => setDState(() => status = 'unpaid'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  final base = double.tryParse(amountCtrl.text) ?? 0;
                  final serv = double.tryParse(serviceCtrl.text) ?? 0;
                  if (base <= 0) return;
                  Navigator.pop(ctx);
                  try {
                    final res = await http.post(
                      Uri.parse('${AppState.apiUrl}/staff/invoices'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({
                        'room_id': selectedRoomId,
                        'amount': base,
                        'service_amount': serv,
                        'service_description': serviceDescCtrl.text.trim(),
                        'currency': currency,
                        'status': status,
                      }),
                    );
                    final data = jsonDecode(res.body);
                    if (res.statusCode == 200) {
                      _fetchInvoices();
                      if (data['invoice'] != null) {
                        await Future.delayed(const Duration(milliseconds: 150));
                        if (mounted) {
                          showDialog(
                            context: context,
                            builder: (c) => InvoicePrintReceiptDialog(invoice: data['invoice']),
                          );
                        }
                      }
                    }
                  } catch (e) {
                    if (mounted) {
                      AppState.showGlobalErrorDialog(
                        title: 'تنبيه أثناء إصدار الفاتورة',
                        message: 'حدث خطأ أثناء إصدار الفاتورة: $e',
                      );
                    }
                  }
                },
                child: const Text('إصدار وحفظ الفاتورة'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _payInvoice(int id) async {
    if (AppState.isReadOnlyOwner()) return;
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/invoices/$id/pay'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        _fetchInvoices();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم سداد الفاتورة وإيداع القيد المحاسبي بنجاح.'), backgroundColor: Colors.green));
        }
        if (body['invoice'] != null) {
          await Future.delayed(const Duration(milliseconds: 150));
          if (mounted) {
            showDialog(
              context: context,
              builder: (ctx) => InvoicePrintReceiptDialog(invoice: body['invoice']),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء سداد الفاتورة',
          message: 'حدث خطأ أثناء سداد الفاتورة: $e',
        );
      }
    }
  }

  void _confirmDeleteInvoice(int id, String invNumber) {
    if (AppState.isReadOnlyOwner()) return;

    final reasonCtrl = TextEditingController(text: 'إلغاء الفاتورة بالاتفاق مع الإدارة');

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: Row(
            children: [
              const Icon(Icons.delete_forever, color: Color(0xFFDC2626)),
              const SizedBox(width: 8),
              Text('حذف الفاتورة رقم $invNumber'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'تنبيه: سيتم نقل هذه الفاتورة إلى سجل المحذوفات والرقابة الإدارية مع حفظ نسخة Snapshot كاملة منها ولن تحذف من السجلات الرقابية نهائياً.',
                style: TextStyle(color: Color(0xFFB91C1C), fontSize: 12),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: reasonCtrl,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'سبب الحذف الموثق (إلزامي للرقابة) *',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626), foregroundColor: Colors.white),
              onPressed: () async {
                final reason = reasonCtrl.text.trim();
                if (reason.isEmpty) return;
                Navigator.pop(ctx);
                setState(() => _isLoading = true);
                try {
                  final res = await http.delete(
                    Uri.parse('${AppState.apiUrl}/staff/invoices/$id'),
                    headers: {
                      'Authorization': 'Bearer ${AppState.token}',
                      'Content-Type': 'application/json',
                      'Accept': 'application/json',
                    },
                    body: jsonEncode({'reason': reason}),
                  );
                  final data = jsonDecode(res.body);
                  if (res.statusCode == 200) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                      content: Text(data['message'] ?? 'تم حذف الفاتورة وتوثيق العملية في سجل المحذوفات.'),
                      backgroundColor: Colors.green,
                    ));
                    _fetchInvoices();
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                      content: Text(data['message'] ?? 'تعذر حذف الفاتورة.'),
                      backgroundColor: Colors.red,
                    ));
                  }
                } catch (_) {}
                if (mounted) setState(() => _isLoading = false);
              },
              child: const Text('تأكيد الحذف والتوثيق'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final paidCount = _invoices.where((i) => i['status'] == 'paid').length;
    final unpaidCount = _invoices.where((i) => i['status'] != 'paid').length;
    double paidUsd = 0;
    double paidSyp = 0;
    for (var i in _invoices) {
      if (i['status'] == 'paid') {
        final tot = (i['total'] is num) ? (i['total'] as num).toDouble() : (double.tryParse(i['total'].toString()) ?? 0);
        if (i['currency'] == 'USD') {
          paidUsd += tot;
        } else {
          paidSyp += tot;
        }
      }
    }

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // KPI Summary Cards
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                        child: const Icon(Icons.receipt, color: Color(0xFF1D4ED8), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('إجمالي الفواتير', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('${_invoices.length} فاتورة', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(10)),
                        child: const Icon(Icons.check_circle_outline, color: Color(0xFF059669), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('مسددة (USD / SYP)', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('\$${_formatNumber(paidUsd)} / ${_formatNumber(paidSyp)} ل.س', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                        child: const Icon(Icons.pending_actions, color: Color(0xFFDC2626), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('فواتير غير مسددة', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('$unpaidCount معلقة', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFDC2626))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Action Toolbar & Search Bar
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1D4ED8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _openCreateInvoiceDialog,
                    icon: const Icon(Icons.add, size: 20),
                    label: Text(AppLocale.tr('إصدار فاتورة جديدة', 'New Invoice'), style: const TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 14),
                  Container(
                    width: 280,
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFCBD5E1)),
                    ),
                    child: TextField(
                      controller: _searchCtrl,
                      decoration: InputDecoration(
                        hintText: AppLocale.tr('بحث برقم الفاتورة أو النزيل...', 'Search by invoice # or guest...'),
                        hintStyle: const TextStyle(fontSize: 12),
                        prefixIcon: const Icon(Icons.search, size: 18),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                      onSubmitted: (_) => _fetchInvoices(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFF1F5F9),
                      foregroundColor: const Color(0xFF1E293B),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onPressed: _fetchInvoices,
                    child: Text(AppLocale.tr('بحث', 'Search')),
                  ),
                ],
              ),
              Row(
                children: [
                  ChoiceChip(
                    label: Text(AppLocale.tr('الكل', 'All')),
                    selected: _statusFilter == 'all',
                    onSelected: (val) {
                      setState(() => _statusFilter = 'all');
                      _fetchInvoices();
                    },
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: Text('${AppLocale.tr("المسددة", "Paid")} ($paidCount)'),
                    selected: _statusFilter == 'paid',
                    onSelected: (val) {
                      setState(() => _statusFilter = 'paid');
                      _fetchInvoices();
                    },
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: Text('${AppLocale.tr("غير مسددة", "Unpaid")} ($unpaidCount)'),
                    selected: _statusFilter == 'unpaid',
                    onSelected: (val) {
                      setState(() => _statusFilter = 'unpaid');
                      _fetchInvoices();
                    },
                  ),
                  const SizedBox(width: 8),
                  IconButton(onPressed: _fetchInvoices, icon: const Icon(Icons.refresh), tooltip: AppLocale.tr('تحديث', 'Refresh')),
                ],
              ),
            ],
          ),
          const SizedBox(height: 20),

          if (_isLoading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator()))
          else if (_invoices.isEmpty)
            Center(
              child: Container(
                padding: const EdgeInsets.all(32),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                child: Text(AppLocale.tr('لا توجد فواتير تطابق شروط البحث.', 'No invoices match search criteria.')),
              ),
            )
          else
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: DataTable(
                  columns: [
                    DataColumn(label: Text(AppLocale.tr('رقم الفاتورة', 'Invoice #'))),
                    DataColumn(label: Text(AppLocale.tr('النزيل والغرفة', 'Guest & Room'))),
                    DataColumn(label: Text(AppLocale.tr('المبلغ الأساسي', 'Base Amount'))),
                    DataColumn(label: Text(AppLocale.tr('الخدمات', 'Services'))),
                    DataColumn(label: Text(AppLocale.tr('الإجمالي', 'Total'))),
                    DataColumn(label: Text(AppLocale.tr('العملة', 'Currency'))),
                    DataColumn(label: Text(AppLocale.tr('الحالة', 'Status'))),
                    DataColumn(label: Text(AppLocale.tr('الطباعة والتنزيل', 'Print & Download'))),
                    DataColumn(label: Text(AppLocale.tr('الإجراءات', 'Actions'))),
                  ],
                  rows: _invoices.map((inv) {
                    final isPaid = inv['status'] == 'paid';
                    final invNumber = inv['invoice_number'] ?? '${inv['id']}';
                    final guestName = inv['booking']?['guest']?['name'] ?? 'نزيل';
                    final roomNum = inv['room']?['room_number'] ?? (inv['booking']?['room']?['room_number'] ?? '');

                    return DataRow(cells: [
                      DataCell(Text('#$invNumber', style: const TextStyle(fontWeight: FontWeight.bold))),
                      DataCell(Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(guestName, style: const TextStyle(fontWeight: FontWeight.bold)),
                          if (roomNum.toString().isNotEmpty)
                            Text('${AppLocale.tr("غرفة", "Room")} $roomNum', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                        ],
                      )),
                      DataCell(Text('${inv['amount']}')),
                      DataCell(Text('${inv['service_amount'] ?? 0}')),
                      DataCell(Text('${inv['total']}', style: const TextStyle(fontWeight: FontWeight.bold))),
                      DataCell(Text(inv['currency'] ?? 'USD', style: TextStyle(color: inv['currency'] == 'USD' ? Colors.blue : const Color(0xFF059669), fontWeight: FontWeight.bold))),
                      DataCell(Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: isPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          isPaid ? AppLocale.tr('مسددة', 'Paid') : AppLocale.tr('غير مسددة', 'Unpaid'),
                          style: TextStyle(color: isPaid ? Colors.green : Colors.red, fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                      )),
                      DataCell(
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF1D4ED8),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () => _handlePrintInvoice(inv),
                              icon: const Icon(Icons.print, size: 14),
                              label: Text(AppLocale.tr('طباعة', 'Print'), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            ),
                            const SizedBox(width: 6),
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF0F766E),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () => _handleDownloadInvoice(inv),
                              icon: const Icon(Icons.download, size: 14),
                              label: Text(AppLocale.tr('تنزيل الفاتورة', 'Download'), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                      ),
                      DataCell(
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (!isPaid)
                              ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF16A34A),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                                onPressed: () => _payInvoice(inv['id']),
                                child: Text(AppLocale.tr('سداد', 'Pay'), style: const TextStyle(fontSize: 11)),
                              ),
                            IconButton(
                              onPressed: () => _confirmDeleteInvoice(inv['id'], invNumber),
                              icon: const Icon(Icons.delete_outline, color: Color(0xFFDC2626), size: 18),
                              tooltip: AppLocale.tr('حذف وتوثيق في سجل المحذوفات', 'Delete & Record in Audit Log'),
                            ),
                          ],
                        ),
                      ),
                    ]);
                  }).toList(),
                ),
              ),
            ),
        ],
      ),
    ),
    );
  }
}

// ============================================================================
// 8. ACCOUNTS & CASH BOOK TAB (Strict Currency Separation)
// ============================================================================
class AccountsTab extends StatefulWidget {
  const AccountsTab({super.key});

  @override
  State<AccountsTab> createState() => _AccountsTabState();
}

class _AccountsTabState extends State<AccountsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _accounts = [];
  String _typeFilter = 'all';
  String _currencyFilter = 'all';
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchAccounts();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchAccounts() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/accounts'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _accounts = body['data']['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  void _openCreateVoucherDialog() {
    if (AppState.isReadOnlyOwner()) return;

    final amountCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    String type = 'income';
    String category = 'إيراد إقامة فندقية';
    String currency = 'USD';

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.account_balance, color: Color(0xFF1D4ED8)),
                SizedBox(width: 8),
                Text('تسجيل قيد مالي (سند قبض / سند صرف)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 480,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: ChoiceChip(
                            label: const Text('🟢 سند قبض (إيراد وارد)'),
                            selected: type == 'income',
                            selectedColor: const Color(0xFFDCFCE7),
                            onSelected: (val) {
                              setDState(() {
                                type = 'income';
                                category = 'إيراد إقامة فندقية';
                              });
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: ChoiceChip(
                            label: const Text('🔴 سند صرف (مصروف صادر)'),
                            selected: type == 'expense',
                            selectedColor: const Color(0xFFFEE2E2),
                            onSelected: (val) {
                              setDState(() {
                                type = 'expense';
                                category = 'مصاريف تشغيل وصيانة';
                              });
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: amountCtrl,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'المبلغ المالي *',
                        prefixIcon: const Icon(Icons.attach_money),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        const Text('العملة المنفصلة: ', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('USD (\$)'),
                          selected: currency == 'USD',
                          onSelected: (val) => setDState(() => currency = 'USD'),
                        ),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('SYP (ل.س)'),
                          selected: currency == 'SYP',
                          onSelected: (val) => setDState(() => currency = 'SYP'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: category,
                      decoration: InputDecoration(
                        labelText: 'التصنيف المحاسبي',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      items: (type == 'income'
                              ? [
                                  'إيراد إقامة فندقية',
                                  'إيراد خدمات إضافية وميني بار',
                                  'إيراد غسيل ومطعم',
                                  'إيرادات أخرى متنوعة'
                                ]
                              : [
                                  'مصاريف تشغيل وصيانة',
                                  'مشتريات مواد تنظيف وضيافة',
                                  'فواتير كهرباء وماء وإنترنت',
                                  'رواتب ومكافآت موظفين',
                                  'مصروفات نثرية'
                                ])
                          .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                          .toList(),
                      onChanged: (val) {
                        if (val != null) setDState(() => category = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: descCtrl,
                      decoration: InputDecoration(
                        labelText: 'البيان / الوصف التفصيلي للقيد *',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  final amount = double.tryParse(amountCtrl.text) ?? 0;
                  if (amount <= 0 || descCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  try {
                    await http.post(
                      Uri.parse('${AppState.apiUrl}/staff/accounts'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({
                        'amount': amount,
                        'type': type,
                        'category': category,
                        'currency': currency,
                        'description': descCtrl.text.trim(),
                        'title': descCtrl.text.trim(),
                        'date': DateTime.now().toIso8601String().substring(0, 10),
                      }),
                    );
                    _fetchAccounts();
                  } catch (_) {}
                },
                child: const Text('ترحيل القيد المالي للصندوق'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showVoucherDetails(Map<String, dynamic> a) {
    final isIncome = a['type'] == 'income';
    final cur = a['currency'] ?? 'USD';
    final amount = _formatNumber(a['amount']);

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Icon(isIncome ? Icons.arrow_downward : Icons.arrow_upward, color: isIncome ? Colors.green : Colors.red),
              const SizedBox(width: 8),
              Text(isIncome ? 'سند قبض مالي #${a['id']}' : 'سند صرف مالي #${a['id']}'),
            ],
          ),
          content: SizedBox(
            width: 420,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isIncome ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isIncome ? const Color(0xFFBBF7D0) : const Color(0xFFFECACA)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(isIncome ? 'المبلغ المقبوض:' : 'المبلغ المصروف:', style: const TextStyle(fontWeight: FontWeight.bold)),
                      Text('$amount $cur', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: isIncome ? const Color(0xFF166534) : const Color(0xFF991B1B))),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _buildInfoLine('التاريخ:', a['transaction_date'] ?? a['date'] ?? '—'),
                _buildInfoLine('التصنيف:', a['category'] ?? a['title'] ?? '—'),
                _buildInfoLine('البيان / الشرح:', a['description'] ?? a['notes'] ?? '—'),
                if (a['invoice_id'] != null) _buildInfoLine('مرتبط بفاتورة رقم:', '#${a['invoice_id']}'),
              ],
            ),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1E293B), foregroundColor: Colors.white),
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إغلاق'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoLine(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 120, child: Text(label, style: const TextStyle(color: Color(0xFF64748B), fontSize: 12))),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    // Calculate balances
    double sypIncome = 0;
    double sypExpense = 0;
    double usdIncome = 0;
    double usdExpense = 0;

    for (var a in _accounts) {
      final amt = (a['amount'] is num) ? (a['amount'] as num).toDouble() : (double.tryParse(a['amount'].toString()) ?? 0);
      final isInc = a['type'] == 'income';
      final cur = a['currency'] ?? 'USD';

      if (cur == 'USD') {
        if (isInc) {
          usdIncome += amt;
        } else {
          usdExpense += amt;
        }
      } else {
        if (isInc) {
          sypIncome += amt;
        } else {
          sypExpense += amt;
        }
      }
    }

    // Filter accounts
    final filtered = _accounts.where((a) {
      if (_typeFilter != 'all' && a['type'] != _typeFilter) return false;
      if (_currencyFilter != 'all' && a['currency'] != _currencyFilter) return false;
      final q = _searchCtrl.text.trim().toLowerCase();
      if (q.isNotEmpty) {
        final title = (a['title'] ?? '').toString().toLowerCase();
        final desc = (a['description'] ?? '').toString().toLowerCase();
        final cat = (a['category'] ?? '').toString().toLowerCase();
        if (!title.contains(q) && !desc.contains(q) && !cat.contains(q)) return false;
      }
      return true;
    }).toList();

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Wallet Cards
          Row(
            children: [
              // SYP Wallet
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Row(
                            children: [
                              Text('🇸🇾', style: TextStyle(fontSize: 20)),
                              SizedBox(width: 8),
                              Text('صندوق الليرة السورية (SYP)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(6)),
                            child: Text('صافي: ${_formatNumber(sypIncome - sypExpense)} ل.س', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF059669))),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: Text('الوارد: +${_formatNumber(sypIncome)} ل.س', style: const TextStyle(fontSize: 12, color: Color(0xFF059669), fontWeight: FontWeight.bold)),
                          ),
                          Expanded(
                            child: Text('المنصرف: -${_formatNumber(sypExpense)} ل.س', style: const TextStyle(fontSize: 12, color: Color(0xFFDC2626), fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),
              // USD Wallet
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Row(
                            children: [
                              Text('🇺🇸', style: TextStyle(fontSize: 20)),
                              SizedBox(width: 8),
                              Text('صندوق الدولار الأمريكي (USD)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(6)),
                            child: Text('صافي: \$${_formatNumber(usdIncome - usdExpense)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1D4ED8))),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: Text('الوارد: +\$${_formatNumber(usdIncome)}', style: const TextStyle(fontSize: 12, color: Color(0xFF059669), fontWeight: FontWeight.bold)),
                          ),
                          Expanded(
                            child: Text('المنصرف: -\$${_formatNumber(usdExpense)}', style: const TextStyle(fontSize: 12, color: Color(0xFFDC2626), fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Action Toolbar & Filter Chips
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1D4ED8),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _openCreateVoucherDialog,
                    icon: const Icon(Icons.add, size: 20),
                    label: const Text('تسجيل قيد مالي (سند قبض / صرف)', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 14),
                  Container(
                    width: 240,
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFCBD5E1)),
                    ),
                    child: TextField(
                      controller: _searchCtrl,
                      decoration: const InputDecoration(
                        hintText: 'بحث في القيود والبيان...',
                        hintStyle: TextStyle(fontSize: 12),
                        prefixIcon: Icon(Icons.search, size: 18),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 10),
                      ),
                      onChanged: (_) => setState(() {}),
                    ),
                  ),
                ],
              ),
              Row(
                children: [
                  ChoiceChip(
                    label: const Text('الكل'),
                    selected: _typeFilter == 'all',
                    onSelected: (val) => setState(() => _typeFilter = 'all'),
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: const Text('قبض (وارد)'),
                    selected: _typeFilter == 'income',
                    onSelected: (val) => setState(() => _typeFilter = 'income'),
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: const Text('صرف (مصروف)'),
                    selected: _typeFilter == 'expense',
                    onSelected: (val) => setState(() => _typeFilter = 'expense'),
                  ),
                  const SizedBox(width: 12),
                  ChoiceChip(
                    label: const Text('USD'),
                    selected: _currencyFilter == 'USD',
                    onSelected: (val) => setState(() => _currencyFilter = _currencyFilter == 'USD' ? 'all' : 'USD'),
                  ),
                  const SizedBox(width: 6),
                  ChoiceChip(
                    label: const Text('SYP'),
                    selected: _currencyFilter == 'SYP',
                    onSelected: (val) => setState(() => _currencyFilter = _currencyFilter == 'SYP' ? 'all' : 'SYP'),
                  ),
                  const SizedBox(width: 8),
                  IconButton(onPressed: _fetchAccounts, icon: const Icon(Icons.refresh), tooltip: 'تحديث القيود'),
                ],
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Ledger Table
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: filtered.isEmpty
                ? const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('لا توجد قيود مالية مسجلة تطابق الفلاتر.')))
                : SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: DataTable(
                      columns: const [
                        DataColumn(label: Text('رقم السند')),
                        DataColumn(label: Text('التاريخ')),
                        DataColumn(label: Text('نوع الحركة')),
                        DataColumn(label: Text('التصنيف')),
                        DataColumn(label: Text('المبلغ')),
                        DataColumn(label: Text('العملة')),
                        DataColumn(label: Text('البيان / الوصف')),
                        DataColumn(label: Text('تفاصيل')),
                      ],
                      rows: filtered.map((a) {
                        final isIncome = a['type'] == 'income';
                        final cur = a['currency'] ?? 'USD';

                        return DataRow(cells: [
                          DataCell(Text('#${a['id']}', style: const TextStyle(fontWeight: FontWeight.bold))),
                          DataCell(Text(a['transaction_date'] ?? a['date'] ?? '')),
                          DataCell(Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: isIncome ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              isIncome ? 'قبض (إيراد)' : 'صرف (مصروف)',
                              style: TextStyle(color: isIncome ? Colors.green : Colors.red, fontWeight: FontWeight.bold, fontSize: 11),
                            ),
                          )),
                          DataCell(Text(a['category'] ?? a['title'] ?? '—')),
                          DataCell(Text(_formatNumber(a['amount']), style: TextStyle(fontWeight: FontWeight.bold, color: isIncome ? Colors.green : Colors.red))),
                          DataCell(Text(cur, style: TextStyle(color: cur == 'USD' ? Colors.blue : const Color(0xFF059669), fontWeight: FontWeight.bold))),
                          DataCell(Text(a['description'] ?? a['notes'] ?? a['title'] ?? '—')),
                          DataCell(
                            IconButton(
                              icon: const Icon(Icons.visibility_outlined, size: 18, color: Color(0xFF1D4ED8)),
                              onPressed: () => _showVoucherDetails(a),
                              tooltip: 'معاينة السند',
                            ),
                          ),
                        ]);
                      }).toList(),
                    ),
                  ),
          ),
        ],
      ),
    ),
    );
  }
}

// ============================================================================
// 9. EMPLOYEES & CUSTOM PERMISSIONS TAB (Create Staff & Grant Privileges)
// ============================================================================
class EmployeesManagementTab extends StatefulWidget {
  const EmployeesManagementTab({super.key});

  @override
  State<EmployeesManagementTab> createState() => _EmployeesManagementTabState();
}

class _EmployeesManagementTabState extends State<EmployeesManagementTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _employees = [];
  Map<String, dynamic> _availablePermissions = {};
  List<dynamic> _availableRoles = [];

  @override
  void initState() {
    super.initState();
    _fetchEmployees();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchEmployees() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/employees'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _employees = body['data'] ?? [];
          _availablePermissions = body['available_permissions'] ?? {};
          _availableRoles = body['available_roles'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _openCreateEmployeeDialog() {
    if (AppState.isReadOnlyOwner()) return;

    final nameCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final passCtrl = TextEditingController(text: 'password123');
    String role = _availableRoles.isNotEmpty ? _availableRoles.first.toString() : 'reception';
    final selectedPerms = <String>[];

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.person_add, color: Color(0xFF1D4ED8)),
                SizedBox(width: 8),
                Text('إضافة حساب موظف جديد بالفندق', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
              ],
            ),
            content: SizedBox(
              width: 500,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: nameCtrl,
                      decoration: InputDecoration(labelText: 'اسم الموظف *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(labelText: 'البريد الإلكتروني الوظيفي *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: passCtrl,
                      obscureText: true,
                      decoration: InputDecoration(labelText: 'كلمة المرور *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: role,
                      decoration: InputDecoration(labelText: 'الدور الوظيفي الأساسي', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
                      items: _availableRoles.map<DropdownMenuItem<String>>((r) => DropdownMenuItem(value: r.toString(), child: Text(r.toString()))).toList(),
                      onChanged: (val) {
                        if (val != null) setDState(() => role = val);
                      },
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  if (nameCtrl.text.trim().isEmpty || emailCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx);
                  try {
                    final res = await http.post(
                      Uri.parse('${AppState.apiUrl}/staff/employees'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({
                        'name': nameCtrl.text.trim(),
                        'email': emailCtrl.text.trim(),
                        'password': passCtrl.text,
                        'role': role,
                        'permissions': selectedPerms,
                      }),
                    );
                    final body = jsonDecode(res.body);
                    if (res.statusCode == 200) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم إنشاء حساب الموظف بنجاح.'), backgroundColor: Colors.green));
                      _fetchEmployees();
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'فشل الإنشاء.'), backgroundColor: Colors.red));
                    }
                  } catch (_) {}
                },
                child: const Text('إنشاء الحساب وتفعيله'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _openPermissionsDialog(Map<String, dynamic> employee) {
    if (AppState.isReadOnlyOwner()) return;

    final selectedPerms = List<String>.from(employee['permissions'] ?? []);

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Row(
              children: [
                const Text('🛡️ ', style: TextStyle(fontSize: 22)),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('منح صلاحيات إضافية للموظف: ${employee['name']}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      Text('الدور الوظيفي الأساسي: ${employee['role']}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ),
              ],
            ),
            content: SizedBox(
              width: 500,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFBFDBFE)),
                      ),
                      child: const Text(
                        'يمكن لمدير الفندق تفعيل صلاحيات وميزات إضافية لهذا الموظف (مثلاً: منح موظف الاستقبال صلاحيات المحاسبة بجانب مهامه).',
                        style: TextStyle(fontSize: 12, color: Color(0xFF1E40AF)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    ..._availablePermissions.entries.map((entry) {
                      final permKey = entry.key;
                      final permLabel = entry.value.toString();
                      final isChecked = selectedPerms.contains(permKey);

                      return CheckboxListTile(
                        value: isChecked,
                        title: Text(permLabel, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                        subtitle: Text(permKey, style: const TextStyle(fontSize: 11, color: Colors.grey)),
                        dense: true,
                        activeColor: const Color(0xFF1D4ED8),
                        onChanged: (val) {
                          setDialogState(() {
                            if (val == true) {
                              selectedPerms.add(permKey);
                            } else {
                              selectedPerms.remove(permKey);
                            }
                          });
                        },
                      );
                    }),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1D4ED8), foregroundColor: Colors.white),
                onPressed: () async {
                  Navigator.pop(ctx);
                  try {
                    final res = await http.put(
                      Uri.parse('${AppState.apiUrl}/staff/employees/${employee['id']}/permissions'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({'permissions': selectedPerms}),
                    );
                    final body = jsonDecode(res.body);
                    if (res.statusCode == 200) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'تم حفظ الصلاحيات بنجاح.')));
                      _fetchEmployees();
                    }
                  } catch (_) {}
                },
                child: const Text('حفظ وتطبيق الصلاحيات'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _openCreateEmployeeDialog,
                  icon: const Icon(Icons.add, size: 20),
                  label: const Text('إضافة موظف جديد', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
                IconButton(onPressed: _fetchEmployees, icon: const Icon(Icons.refresh)),
              ],
            ),
            const SizedBox(height: 20),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: DataTable(
                  columns: const [
                DataColumn(label: Text('اسم الموظف')),
                DataColumn(label: Text('البريد الإلكتروني')),
                DataColumn(label: Text('الدور الأساسي')),
                DataColumn(label: Text('الصلاحيات الإضافية الممنوحة')),
                DataColumn(label: Text('الإجراء')),
              ],
              rows: _employees.map((emp) {
                final perms = (emp['permissions'] as List?)?.cast<String>() ?? [];
                return DataRow(cells: [
                  DataCell(Text(emp['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold))),
                  DataCell(Text(emp['email'] ?? '')),
                  DataCell(Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFBFDBFE)),
                    ),
                    child: Text(emp['role'] ?? '', style: const TextStyle(fontSize: 12, color: Color(0xFF1D4ED8), fontWeight: FontWeight.bold)),
                  )),
                  DataCell(
                    perms.isEmpty
                        ? const Text('— لا توجد صلاحيات مضافة', style: TextStyle(fontSize: 11, color: Colors.grey))
                        : Wrap(
                            spacing: 4,
                            children: perms.map((p) {
                              return Chip(
                                label: Text(p, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                                visualDensity: VisualDensity.compact,
                                backgroundColor: const Color(0xFFF1F5F9),
                              );
                            }).toList(),
                          ),
                  ),
                  DataCell(
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                      onPressed: () => _openPermissionsDialog(emp),
                      icon: const Icon(Icons.edit_attributes, size: 16),
                      label: const Text('منح / تعديل الصلاحيات', style: TextStyle(fontSize: 12)),
                    ),
                  ),
                ]);
              }).toList(),
            ),
          ),
        ),
      ],
    ),
  ),
);
  }
}

// ============================================================================
// 10. MESSENGER 1-ON-1 CHAT (True Messenger: Contacts Split View)
// Requested: البريد يجب ان يكون مثل مسنجر ليست محادثه جماعيه تدخل على حساب تراسله كدردشه في مكان واحد
// ============================================================================
class MessengerChatTab extends StatefulWidget {
  const MessengerChatTab({super.key});

  @override
  State<MessengerChatTab> createState() => _MessengerChatTabState();
}

class _MessengerChatTabState extends State<MessengerChatTab> {
  final ScrollController _contactsScrollController = ScrollController();
  final ScrollController _messagesScrollController = ScrollController();
  bool _isLoadingContacts = true;
  bool _isLoadingMessages = false;
  List<dynamic> _contacts = [];
  Map<String, dynamic>? _selectedContact;
  List<dynamic> _messages = [];
  final _msgCtrl = TextEditingController();
  final _searchCtrl = TextEditingController();
  String _searchFilter = '';

  @override
  void initState() {
    super.initState();
    _fetchContacts();
  }

  @override
  void dispose() {
    _contactsScrollController.dispose();
    _messagesScrollController.dispose();
    _msgCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchContacts() async {
    setState(() => _isLoadingContacts = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/chat/contacts'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final list = (body['data'] as List?) ?? [];
        setState(() {
          _contacts = list;
          if (_selectedContact == null && list.isNotEmpty) {
            _selectContact(list.first);
          }
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoadingContacts = false);
  }

  void _selectContact(Map<String, dynamic> contact) {
    setState(() {
      _selectedContact = contact;
    });
    _fetchMessages(contact['id']);
  }

  Future<void> _fetchMessages(int contactId) async {
    setState(() => _isLoadingMessages = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/chat/messages?contact_id=$contactId'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _messages = body['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoadingMessages = false);
  }

  Future<void> _sendMessage() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty || _selectedContact == null) return;
    _msgCtrl.clear();

    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/chat/send'),
        headers: {
          'Authorization': 'Bearer ${AppState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'receiver_id': _selectedContact!['id'],
          'body': text,
        }),
      );
      if (res.statusCode == 200) {
        _fetchMessages(_selectedContact!['id']);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final filteredContacts = _contacts.where((c) {
      final name = (c['name'] ?? '').toString().toLowerCase();
      final role = (c['role_label'] ?? '').toString().toLowerCase();
      final q = _searchFilter.toLowerCase();
      return name.contains(q) || role.contains(q);
    }).toList();

    return Padding(
      padding: const EdgeInsets.all(20),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 10, offset: const Offset(0, 4)),
          ],
        ),
        child: Row(
          children: [
            // Left (or Right in RTL): Contacts Sidebar
            SizedBox(
              width: 320,
              child: Column(
                children: [
                  // Search & Header
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: const BoxDecoration(
                      border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('صندوق المحادثات والرسائل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                            Icon(Icons.message_outlined, color: Color(0xFF1D4ED8), size: 20),
                          ],
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _searchCtrl,
                          onChanged: (val) => setState(() => _searchFilter = val),
                          decoration: InputDecoration(
                            hintText: 'بحث عن نزيل أو موظف...',
                            prefixIcon: const Icon(Icons.search, size: 18),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: const Color(0xFFF8FAFC),
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Contacts List
                  Expanded(
                    child: _isLoadingContacts
                        ? const Center(child: CircularProgressIndicator())
                        : filteredContacts.isEmpty
                            ? const Center(child: Text('لا توجد جهات اتصال مطابقة.'))
                            : Scrollbar(
                                controller: _contactsScrollController,
                                thumbVisibility: true,
                                trackVisibility: true,
                                interactive: true,
                                child: ListView.builder(
                                  controller: _contactsScrollController,
                                  itemCount: filteredContacts.length,
                                itemBuilder: (ctx, i) {
                                  final contact = filteredContacts[i];
                                  final isSelected = _selectedContact?['id'] == contact['id'];
                                  final unread = contact['unread_count'] ?? 0;

                                  return Container(
                                    decoration: BoxDecoration(
                                      color: isSelected ? const Color(0xFFEFF6FF) : Colors.transparent,
                                      border: Border(
                                        bottom: const BorderSide(color: Color(0xFFF1F5F9)),
                                        right: BorderSide(
                                          color: isSelected ? const Color(0xFF1D4ED8) : Colors.transparent,
                                          width: 3.5,
                                        ),
                                      ),
                                    ),
                                    child: ListTile(
                                      onTap: () => _selectContact(contact),
                                      leading: CircleAvatar(
                                        backgroundColor: isSelected ? const Color(0xFF1D4ED8) : const Color(0xFFE2E8F0),
                                        foregroundColor: isSelected ? Colors.white : const Color(0xFF334155),
                                        child: Text((contact['name'] ?? 'U').substring(0, 1)),
                                      ),
                                      title: Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              contact['name'] ?? '',
                                              style: TextStyle(
                                                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                                                fontSize: 13,
                                              ),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          if (unread > 0)
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(color: const Color(0xFFEF4444), borderRadius: BorderRadius.circular(10)),
                                              child: Text('$unread', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                                            ),
                                        ],
                                      ),
                                      subtitle: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            contact['role_label'] ?? '',
                                            style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            contact['last_message'] ?? '',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                  ),
                ],
              ),
            ),

            const VerticalDivider(width: 1, color: Color(0xFFE2E8F0)),

            // Right (or Left in RTL): Active 1-on-1 Messenger Conversation
            Expanded(
              child: _selectedContact == null
                  ? const Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.forum_outlined, size: 64, color: Color(0xFF94A3B8)),
                          SizedBox(height: 12),
                          Text('اختر جهة اتصال لبدء الدردشة الفورية 1-على-1', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                        ],
                      ),
                    )
                  : Column(
                      children: [
                        // Active Contact Header
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                          decoration: const BoxDecoration(
                            border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: const Color(0xFF1D4ED8),
                                    foregroundColor: Colors.white,
                                    radius: 20,
                                    child: Text((_selectedContact!['name'] ?? 'U').substring(0, 1)),
                                  ),
                                  const SizedBox(width: 12),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _selectedContact!['name'] ?? '',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                                      ),
                                      Text(
                                        '${_selectedContact!['role_label']} • ${_selectedContact!['email']}',
                                        style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                              IconButton(
                                onPressed: () => _fetchMessages(_selectedContact!['id']),
                                icon: const Icon(Icons.refresh, size: 20, color: Color(0xFF64748B)),
                                tooltip: 'تحديث المحادثة',
                              ),
                            ],
                          ),
                        ),

                        // Message Bubbles Area
                        Expanded(
                          child: _isLoadingMessages
                              ? const Center(child: CircularProgressIndicator())
                              : _messages.isEmpty
                                  ? Center(
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(Icons.chat_bubble_outline, size: 48, color: Color(0xFFCBD5E1)),
                                          const SizedBox(height: 8),
                                          Text('لا توجد رسائل سابقة مع ${_selectedContact!['name']}. ابدأ المحادثة الآن!'),
                                        ],
                                      ),
                                    )
                                  : Scrollbar(
                                      controller: _messagesScrollController,
                                      thumbVisibility: true,
                                      trackVisibility: true,
                                      interactive: true,
                                      child: ListView.builder(
                                        controller: _messagesScrollController,
                                        padding: const EdgeInsets.all(20),
                                      itemCount: _messages.length,
                                      itemBuilder: (ctx, i) {
                                        final m = _messages[i];
                                        final isMe = m['is_sender'] == true;

                                        return Align(
                                          alignment: isMe ? Alignment.centerLeft : Alignment.centerRight,
                                          child: Container(
                                            margin: const EdgeInsets.only(bottom: 12),
                                            constraints: const BoxConstraints(maxWidth: 480),
                                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                            decoration: BoxDecoration(
                                              color: isMe ? const Color(0xFF1D4ED8) : const Color(0xFFF1F5F9),
                                              borderRadius: BorderRadius.only(
                                                topLeft: const Radius.circular(16),
                                                topRight: const Radius.circular(16),
                                                bottomLeft: isMe ? const Radius.circular(4) : const Radius.circular(16),
                                                bottomRight: isMe ? const Radius.circular(16) : const Radius.circular(4),
                                              ),
                                              boxShadow: [
                                                BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 4, offset: const Offset(0, 2)),
                                              ],
                                            ),
                                            child: Column(
                                              crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  m['body'] ?? '',
                                                  style: TextStyle(
                                                    color: isMe ? Colors.white : const Color(0xFF0F172A),
                                                    fontSize: 13,
                                                  ),
                                                ),
                                                const SizedBox(height: 4),
                                                Text(
                                                  m['formatted_time'] ?? '',
                                                  style: TextStyle(
                                                    fontSize: 10,
                                                    color: isMe ? Colors.white70 : const Color(0xFF94A3B8),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                        ),

                        // Message Input Box
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: const BoxDecoration(
                            border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: _msgCtrl,
                                  decoration: InputDecoration(
                                    hintText: 'اكتب رسالتك لـ ${_selectedContact!['name']}...',
                                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                    filled: true,
                                    fillColor: const Color(0xFFF8FAFC),
                                  ),
                                  onSubmitted: (_) => _sendMessage(),
                                ),
                              ),
                              const SizedBox(width: 10),
                              ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF1D4ED8),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                ),
                                onPressed: _sendMessage,
                                icon: const Icon(Icons.send, size: 18),
                                label: const Text('إرسال', style: TextStyle(fontWeight: FontWeight.bold)),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// 10. CUSTOM CHART PAINTERS & VISUALIZATIONS
// ============================================================================

class MonthlyRevenueBarChart extends StatelessWidget {
  final List<double> sypValues;
  final List<double> usdValues;
  final List<String> months;

  const MonthlyRevenueBarChart({
    super.key,
    required this.sypValues,
    required this.usdValues,
    required this.months,
  });

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (ctx, constraints) {
        return CustomPaint(
          size: Size(constraints.maxWidth, constraints.maxHeight),
          painter: _RevenueBarPainter(
            sypValues: sypValues,
            usdValues: usdValues,
            months: months,
          ),
        );
      },
    );
  }
}

class _RevenueBarPainter extends CustomPainter {
  final List<double> sypValues;
  final List<double> usdValues;
  final List<String> months;

  _RevenueBarPainter({
    required this.sypValues,
    required this.usdValues,
    required this.months,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final count = months.length;
    if (count == 0) return;

    const double bottomMargin = 26.0;
    const double topMargin = 20.0;
    final chartHeight = size.height - bottomMargin - topMargin;
    final chartWidth = size.width;

    double maxSyp = sypValues.fold(0.0, (m, v) => v > m ? v : m);
    double maxUsd = usdValues.fold(0.0, (m, v) => v > m ? v : m);
    if (maxSyp == 0) maxSyp = 1000000;
    if (maxUsd == 0) maxUsd = 1000;

    final gridPaint = Paint()
      ..color = const Color(0xFFF1F5F9)
      ..strokeWidth = 1.0;
    for (int i = 0; i <= 3; i++) {
      final y = topMargin + (chartHeight / 3) * i;
      canvas.drawLine(Offset(0, y), Offset(chartWidth, y), gridPaint);
    }

    final sypPaint = Paint()
      ..color = const Color(0xFF10B981)
      ..style = PaintingStyle.fill;
    final usdPaint = Paint()
      ..color = const Color(0xFF2563EB)
      ..style = PaintingStyle.fill;

    final groupWidth = chartWidth / count;
    final barWidth = (groupWidth * 0.35).clamp(4.0, 16.0);

    const textStyle = TextStyle(color: Color(0xFF94A3B8), fontSize: 9, fontFamily: 'Segoe UI');

    for (int i = 0; i < count; i++) {
      final groupCenterX = (count - 1 - i) * groupWidth + (groupWidth / 2);

      final sypRatio = (sypValues[i] / maxSyp).clamp(0.0, 1.0);
      final usdRatio = (usdValues[i] / maxUsd).clamp(0.0, 1.0);

      final sypBarHeight = (chartHeight * sypRatio).clamp(2.0, chartHeight);
      final usdBarHeight = (chartHeight * usdRatio).clamp(2.0, chartHeight);

      final baseY = size.height - bottomMargin;

      final sypRect = RRect.fromRectAndCorners(
        Rect.fromLTWH(groupCenterX - barWidth - 1, baseY - sypBarHeight, barWidth, sypBarHeight),
        topLeft: const Radius.circular(4),
        topRight: const Radius.circular(4),
      );
      canvas.drawRRect(sypRect, sypPaint);

      final usdRect = RRect.fromRectAndCorners(
        Rect.fromLTWH(groupCenterX + 1, baseY - usdBarHeight, barWidth, usdBarHeight),
        topLeft: const Radius.circular(4),
        topRight: const Radius.circular(4),
      );
      canvas.drawRRect(usdRect, usdPaint);

      final tp = TextPainter(
        text: TextSpan(text: months[i].length > 4 ? months[i].substring(0, 4) : months[i], style: textStyle),
        textDirection: TextDirection.rtl,
      )..layout();
      tp.paint(canvas, Offset(groupCenterX - (tp.width / 2), baseY + 6));
    }
  }

  @override
  bool shouldRepaint(covariant _RevenueBarPainter oldDelegate) => true;
}

class MonthlyBookingsTrendChart extends StatelessWidget {
  final List<int> bookings;
  final List<String> months;

  const MonthlyBookingsTrendChart({
    super.key,
    required this.bookings,
    required this.months,
  });

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (ctx, constraints) {
        return CustomPaint(
          size: Size(constraints.maxWidth, constraints.maxHeight),
          painter: _BookingsBarPainter(
            bookings: bookings,
            months: months,
          ),
        );
      },
    );
  }
}

class _BookingsBarPainter extends CustomPainter {
  final List<int> bookings;
  final List<String> months;

  _BookingsBarPainter({
    required this.bookings,
    required this.months,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final count = months.length;
    if (count == 0) return;

    const double bottomMargin = 26.0;
    const double topMargin = 22.0;
    final chartHeight = size.height - bottomMargin - topMargin;
    final chartWidth = size.width;

    int maxBkg = bookings.fold(0, (m, v) => v > m ? v : m);
    if (maxBkg == 0) maxBkg = 10;

    final barPaint = Paint()
      ..color = const Color(0xFF6366F1)
      ..style = PaintingStyle.fill;

    final groupWidth = chartWidth / count;
    final barWidth = (groupWidth * 0.55).clamp(6.0, 22.0);

    const textStyle = TextStyle(color: Color(0xFF94A3B8), fontSize: 9, fontFamily: 'Segoe UI');
    const valStyle = TextStyle(color: Color(0xFF4338CA), fontSize: 9, fontWeight: FontWeight.bold, fontFamily: 'Segoe UI');

    for (int i = 0; i < count; i++) {
      final groupCenterX = (count - 1 - i) * groupWidth + (groupWidth / 2);
      final ratio = (bookings[i] / maxBkg).clamp(0.0, 1.0);
      final barH = (chartHeight * ratio).clamp(3.0, chartHeight);
      final baseY = size.height - bottomMargin;

      final rect = RRect.fromRectAndCorners(
        Rect.fromLTWH(groupCenterX - (barWidth / 2), baseY - barH, barWidth, barH),
        topLeft: const Radius.circular(5),
        topRight: const Radius.circular(5),
      );
      canvas.drawRRect(rect, barPaint);

      if (bookings[i] > 0) {
        final valTp = TextPainter(
          text: TextSpan(text: '${bookings[i]}', style: valStyle),
          textDirection: TextDirection.rtl,
        )..layout();
        valTp.paint(canvas, Offset(groupCenterX - (valTp.width / 2), baseY - barH - 14));
      }

      final tp = TextPainter(
        text: TextSpan(text: months[i].length > 4 ? months[i].substring(0, 4) : months[i], style: textStyle),
        textDirection: TextDirection.rtl,
      )..layout();
      tp.paint(canvas, Offset(groupCenterX - (tp.width / 2), baseY + 6));
    }
  }

  @override
  bool shouldRepaint(covariant _BookingsBarPainter oldDelegate) => true;
}

class OccupancyDonutChart extends StatelessWidget {
  final double occupancyRate;
  final int occupied;
  final int available;
  final int other;

  const OccupancyDonutChart({
    super.key,
    required this.occupancyRate,
    required this.occupied,
    required this.available,
    required this.other,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        CustomPaint(
          size: const Size(150, 150),
          painter: _DonutPainter(
            occupied: occupied,
            available: available,
            other: other,
          ),
        ),
        Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              '${occupancyRate.toStringAsFixed(1)}%',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1D4ED8),
              ),
            ),
            const Text(
              'نسبة الإشغال',
              style: TextStyle(
                fontSize: 10,
                color: Color(0xFF64748B),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _DonutPainter extends CustomPainter {
  final int occupied;
  final int available;
  final int other;

  _DonutPainter({
    required this.occupied,
    required this.available,
    required this.other,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final total = occupied + available + other;
    const strokeWidth = 18.0;
    final rect = Rect.fromCircle(
      center: Offset(size.width / 2, size.height / 2),
      radius: (size.width - strokeWidth) / 2,
    );

    if (total == 0) {
      final emptyPaint = Paint()
        ..color = const Color(0xFFE2E8F0)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth;
      canvas.drawArc(rect, 0, 6.28318, false, emptyPaint);
      return;
    }

    final occupiedAngle = (occupied / total) * 6.283185;
    final availableAngle = (available / total) * 6.283185;
    final otherAngle = (other / total) * 6.283185;

    double startAngle = -1.570796;

    if (occupiedAngle > 0) {
      final p = Paint()
        ..color = const Color(0xFF1D4ED8)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.butt;
      canvas.drawArc(rect, startAngle, occupiedAngle, false, p);
      startAngle += occupiedAngle;
    }

    if (availableAngle > 0) {
      final p = Paint()
        ..color = const Color(0xFF10B981)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.butt;
      canvas.drawArc(rect, startAngle, availableAngle, false, p);
      startAngle += availableAngle;
    }

    if (otherAngle > 0) {
      final p = Paint()
        ..color = const Color(0xFFF59E0B)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.butt;
      canvas.drawArc(rect, startAngle, otherAngle, false, p);
    }
  }

  @override
  bool shouldRepaint(covariant _DonutPainter oldDelegate) => true;
}

// ============================================================================
// 11. MODAL DIALOGS: SNAPSHOT PREVIEW & SCANNER LOOKUP
// ============================================================================

class SnapshotPreviewDialog extends StatelessWidget {
  final Map<String, dynamic> log;

  const SnapshotPreviewDialog({super.key, required this.log});

  @override
  Widget build(BuildContext context) {
    final rawSnapshot = log['snapshot'];
    final formattedJson = (rawSnapshot != null)
        ? const JsonEncoder.withIndent('  ').convert(rawSnapshot)
        : 'لا توجد بيانات مؤرشفة';

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 640, maxHeight: 600),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
                          child: const Text('🗑️', style: TextStyle(fontSize: 18)),
                        ),
                        const SizedBox(width: 10),
                        Text(
                          'نسخة البيانات المحذوفة (${log['reference_number'] ?? log['id']})',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                      ],
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF2F2),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFFCA5A5)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline, color: Color(0xFFDC2626), size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'سبب الحذف الموثق: ${log['reason'] ?? '—'}',
                          style: const TextStyle(color: Color(0xFF991B1B), fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(child: Text('النزيل: ${log['guest_name'] ?? '—'}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))),
                    Expanded(child: Text('القيمة: ${log['amount']} ${log['currency']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFFDC2626)))),
                    Expanded(child: Text('المسؤول: ${log['user_name'] ?? '—'}', style: const TextStyle(fontSize: 12))),
                  ],
                ),
                const SizedBox(height: 12),
                const Divider(),
                const Text('البيانات المسجلة لحظة الحذف (JSON Snapshot):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                const SizedBox(height: 8),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: SingleChildScrollView(
                      child: SelectableText(
                        formattedJson,
                        style: const TextStyle(
                          color: Color(0xFF38BDF8),
                          fontFamily: 'Consolas',
                          fontSize: 12,
                        ),
                        textDirection: TextDirection.ltr,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1E293B),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () => Navigator.pop(context),
                      child: const Text('إغلاق'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class ScannerLookupDialog extends StatefulWidget {
  const ScannerLookupDialog({super.key});

  @override
  State<ScannerLookupDialog> createState() => _ScannerLookupDialogState();
}

class _ScannerLookupDialogState extends State<ScannerLookupDialog> {
  final _codeCtrl = TextEditingController();
  bool _isLoading = false;
  Map<String, dynamic>? _result;
  String? _errorMessage;

  Future<void> _performLookup([String? prefilled]) async {
    final code = prefilled ?? _codeCtrl.text.trim();
    if (code.isEmpty) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _result = null;
    });

    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/scanner/lookup?code=${Uri.encodeComponent(code)}'),
        headers: {
          'Authorization': 'Bearer ${AppState.token}',
          'Accept': 'application/json',
        },
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        setState(() {
          _result = body;
        });
      } else {
        setState(() {
          _errorMessage = body['message'] ?? 'لم يتم العثور على أي نتيجة.';
        });
      }
    } catch (_) {
      setState(() => _errorMessage = 'تعذر الاتصال بالخادم للبحث.');
    }
    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 550),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.qr_code_scanner, color: Color(0xFF1D4ED8), size: 24),
                        SizedBox(width: 10),
                        Text('سكنر الباركود والـ QR - الاستعلام الفوري', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ],
                    ),
                    IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
                  ],
                ),
                const SizedBox(height: 12),
                const Text('قم بمسح الباركود بجهاز السكنر أو إدخال رقم الفاتورة أو الحجز أو هاتف النزيل مباشرة:', style: TextStyle(color: Color(0xFF64748B), fontSize: 12)),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _codeCtrl,
                        autofocus: true,
                        decoration: InputDecoration(
                          hintText: 'رمز الباركود / رقم الحجز / رقم الفاتورة...',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          prefixIcon: const Icon(Icons.barcode_reader),
                        ),
                        onSubmitted: (_) => _performLookup(),
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: _isLoading ? null : () => _performLookup(),
                      icon: const Icon(Icons.search, size: 18),
                      label: const Text('بحث'),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                if (_isLoading)
                  const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator()))
                else if (_errorMessage != null)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(12)),
                    child: Text(_errorMessage!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 13, fontWeight: FontWeight.bold)),
                  )
                else if (_result != null)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFCBD5E1)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(_result!['title'] ?? 'نتيجة البحث', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1D4ED8))),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(8)),
                              child: Text(_result!['type'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1D4ED8))),
                            ),
                          ],
                        ),
                        const Divider(height: 20),
                        SelectableText(
                          const JsonEncoder.withIndent('  ').convert(_result!['data']),
                          style: const TextStyle(fontFamily: 'Consolas', fontSize: 11, color: Color(0xFF1E293B)),
                          textDirection: TextDirection.ltr,
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 12. ROOM TYPES TAB (أنواع وتصنيفات الغرف)
// ============================================================================

class RoomTypesTab extends StatefulWidget {
  const RoomTypesTab({super.key});

  @override
  State<RoomTypesTab> createState() => _RoomTypesTabState();
}

class _RoomTypesTabState extends State<RoomTypesTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _roomTypes = [];

  @override
  void initState() {
    super.initState();
    _fetchRoomTypes();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchRoomTypes() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/room-types'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _roomTypes = body['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _openCreateOrEditDialog([Map<String, dynamic>? existing]) {
    final nameCtrl = TextEditingController(text: existing?['name'] ?? '');
    final descCtrl = TextEditingController(text: existing?['description'] ?? '');

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: Text(existing == null ? '➕ إضافة تصنيف/نوع غرفة جديد' : '✏️ تعديل نوع الغرفة'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameCtrl,
                decoration: InputDecoration(
                  labelText: 'اسم نوع الغرفة (مثال: جناح ملكي، غرفة مفردة ديلوكس)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: descCtrl,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: 'الوصف والمميزات',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1D4ED8),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () async {
                final name = nameCtrl.text.trim();
                if (name.isEmpty) return;

                Navigator.pop(ctx);
                setState(() => _isLoading = true);

                try {
                  final url = existing == null
                      ? '${AppState.apiUrl}/staff/room-types'
                      : '${AppState.apiUrl}/staff/room-types/${existing['id']}';
                  final method = existing == null ? http.post : http.put;

                  final res = await method(
                    Uri.parse(url),
                    headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: jsonEncode({'name': name, 'description': descCtrl.text.trim()}),
                  );

                  final data = jsonDecode(res.body);
                  if (res.statusCode == 200 && data['status'] == 'success') {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'تم الحفظ بنجاح')));
                    _fetchRoomTypes();
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'حدث خطأ أثناء الحفظ')));
                  }
                } catch (_) {}
                if (mounted) setState(() => _isLoading = false);
              },
              child: const Text('حفظ'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _deleteRoomType(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: const Text('تأكيد حذف نوع الغرفة'),
          content: const Text('هل أنت متأكد من رغبتك بحذف هذا التصنيف؟ لن تتم العملية إذا كانت هناك غرف مرتبطة به.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626), foregroundColor: Colors.white),
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('نعم، حذف'),
            ),
          ],
        ),
      ),
    );

    if (confirm != true) return;

    setState(() => _isLoading = true);
    try {
      final res = await http.delete(
        Uri.parse('${AppState.apiUrl}/staff/room-types/$id'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );

      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'تم الحذف بنجاح')));
        _fetchRoomTypes();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'لا يمكن حذف نوع الغرفة')));
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    final isOwner = AppState.isReadOnlyOwner();

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('🏷️ إدارة أنواع وتصنيفات الغرف', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  SizedBox(height: 4),
                  Text('تعريف وتعديل تصنيفات الغرف في الفندق وربطها بالأسعار والإشغال.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                ],
              ),
              if (!isOwner)
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => _openCreateOrEditDialog(),
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('إضافة نوع غرفة جديد', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          const SizedBox(height: 20),

          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _roomTypes.isEmpty
                      ? const Center(child: Text('لا توجد تصنيفات غرف مسجلة حالياً.'))
                      : Scrollbar(
                          controller: _scrollController,
                          thumbVisibility: true,
                          trackVisibility: true,
                          interactive: true,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            child: SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                columns: const [
                                  DataColumn(label: Text('# الرقم', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('اسم التصنيف', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('الوصف والمميزات', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('الإجراءات', style: TextStyle(fontWeight: FontWeight.bold))),
                                ],
                                rows: _roomTypes.map((t) {
                                  return DataRow(cells: [
                                    DataCell(Text('#${t['id']}')),
                                    DataCell(Text(t['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold))),
                                    DataCell(Text(t['description'] ?? '—', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)))),
                                    DataCell(Row(
                                      children: [
                                        if (!isOwner) ...[
                                          IconButton(
                                            onPressed: () => _openCreateOrEditDialog(t),
                                            icon: const Icon(Icons.edit, size: 18, color: Color(0xFF2563EB)),
                                            tooltip: 'تعديل',
                                          ),
                                          IconButton(
                                            onPressed: () => _deleteRoomType(t['id']),
                                            icon: const Icon(Icons.delete_outline, size: 18, color: Color(0xFFDC2626)),
                                            tooltip: 'حذف',
                                          ),
                                        ] else
                                          const Text('معاينة فقط', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                                      ],
                                    )),
                                  ]);
                                }).toList(),
                              ),
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

// ============================================================================
// 13. DELETION LOGS AUDIT TAB (سجل المحذوفات والرقابة الإدارية المستقل)
// ============================================================================

class DeletionLogsTab extends StatefulWidget {
  const DeletionLogsTab({super.key});

  @override
  State<DeletionLogsTab> createState() => _DeletionLogsTabState();
}

class _DeletionLogsTabState extends State<DeletionLogsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _logs = [];
  Map<String, dynamic> _summary = {};
  String _recordType = 'all';
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchLogs();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchLogs() async {
    setState(() => _isLoading = true);
    try {
      final q = Uri.encodeComponent(_searchCtrl.text.trim());
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/deletion-logs?record_type=$_recordType&q=$q'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _logs = body['data']['data'] ?? [];
          _summary = body['summary'] ?? {};
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header Summary Row
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFFCA5A5)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(12)),
                        child: const Text('🗑️', style: TextStyle(fontSize: 22)),
                      ),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('إجمالي المحذوفات الموثقة', style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('${_summary['total_count'] ?? 0} حركة', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFDC2626))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: const Color(0xFFECFDF5), borderRadius: BorderRadius.circular(12)),
                        child: const Text('🇸🇾', style: TextStyle(fontSize: 22)),
                      ),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('إجمالي المبالغ المحذوفة (ل.س)', style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('${_formatNumber(_summary['total_syp'])} ل.س', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(12)),
                        child: const Text('🇺🇸', style: TextStyle(fontSize: 22)),
                      ),
                      const SizedBox(width: 14),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('إجمالي المبالغ المحذوفة (USD)', style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                          Text('\$${_formatNumber(_summary['total_usd'])} USD', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Search and Filters Bar
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchCtrl,
                  decoration: InputDecoration(
                    hintText: 'بحث في سجل المحذوفات (رقم المرجع، النزيل، الغرفة، السبب)...',
                    prefixIcon: const Icon(Icons.search),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.white,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  ),
                  onSubmitted: (_) => _fetchLogs(),
                ),
              ),
              const SizedBox(width: 12),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFCBD5E1)),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _recordType,
                    items: const [
                      DropdownMenuItem(value: 'all', child: Text('جميع المحذوفات')),
                      DropdownMenuItem(value: 'invoice', child: Text('الفواتير فقط')),
                      DropdownMenuItem(value: 'booking', child: Text('حجوزات النزلاء فقط')),
                    ],
                    onChanged: (v) {
                      if (v != null) {
                        setState(() => _recordType = v);
                        _fetchLogs();
                      }
                    },
                  ),
                ),
              ),
              const SizedBox(width: 12),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1D4ED8),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: _fetchLogs,
                icon: const Icon(Icons.filter_alt, size: 18),
                label: const Text('تطبيق الفلتر'),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Table
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _logs.isEmpty
                      ? const Center(child: Text('لا توجد حركات محذوفة تطابق معايير البحث.'))
                      : Scrollbar(
                          controller: _scrollController,
                          thumbVisibility: true,
                          trackVisibility: true,
                          interactive: true,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            child: SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                headingRowColor: WidgetStateProperty.all(const Color(0xFFFFF1F2)),
                            columns: const [
                              DataColumn(label: Text('نوع الحركة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('رقم المرجع', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('النزيل / الغرفة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('القيمة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('سبب الحذف الموثق', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('الموظف المسؤول', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('التاريخ والوقت', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('معاينة', style: TextStyle(fontWeight: FontWeight.bold))),
                            ],
                            rows: _logs.map((log) {
                              final isInvoice = log['record_type'] == 'invoice';
                              return DataRow(cells: [
                                DataCell(
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: isInvoice ? const Color(0xFFFEE2E2) : const Color(0xFFFEF3C7),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      isInvoice ? 'فاتورة' : 'حجز نزيل',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: isInvoice ? const Color(0xFFB91C1C) : const Color(0xFF92400E),
                                      ),
                                    ),
                                  ),
                                ),
                                DataCell(Text(log['reference_number'] ?? '#${log['id']}', style: const TextStyle(fontWeight: FontWeight.bold))),
                                DataCell(Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(log['guest_name'] ?? '—', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                                    if ((log['room_number'] ?? '').toString().isNotEmpty)
                                      Text('غرفة ${log['room_number']}', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                                  ],
                                )),
                                DataCell(Text(
                                  '${_formatNumber(log['amount'])} ${log['currency']}',
                                  style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFDC2626)),
                                )),
                                DataCell(
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFFF1F2),
                                      borderRadius: BorderRadius.circular(6),
                                      border: const Border(right: BorderSide(color: Color(0xFFDC2626), width: 3)),
                                    ),
                                    child: Text(log['reason'] ?? '', style: const TextStyle(fontSize: 11, color: Color(0xFF7F1D1D))),
                                  ),
                                ),
                                DataCell(Text(log['user']?['name'] ?? 'مدير الفندق', style: const TextStyle(fontSize: 12))),
                                DataCell(Text(log['deleted_at'] ?? '', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)))),
                                DataCell(
                                  OutlinedButton(
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      minimumSize: const Size(60, 30),
                                    ),
                                    onPressed: () {
                                      showDialog(
                                        context: context,
                                        builder: (ctx) => SnapshotPreviewDialog(log: {
                                          ...log,
                                          'user_name': log['user']?['name'] ?? 'مدير الفندق',
                                        }),
                                      );
                                    },
                                    child: const Text('معاينة', style: TextStyle(fontSize: 11)),
                                  ),
                                ),
                              ]);
                            }).toList(),
                          ),
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

// ============================================================================
// 14. COMPLAINTS TAB (الشكاوى والمقترحات)
// ============================================================================

class ComplaintsTab extends StatefulWidget {
  const ComplaintsTab({super.key});

  @override
  State<ComplaintsTab> createState() => _ComplaintsTabState();
}

class _ComplaintsTabState extends State<ComplaintsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  List<dynamic> _complaints = [];

  @override
  void initState() {
    super.initState();
    _fetchComplaints();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchComplaints() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/complaints'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _complaints = body['data']['data'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _openCreateComplaintDialog() {
    final titleCtrl = TextEditingController();
    final msgCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: const Text('📢 تسجيل شكوى أو مقترح نزيل جديد'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: titleCtrl,
                decoration: InputDecoration(
                  labelText: 'عنوان الشكوى / المقترح',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: msgCtrl,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: 'تفاصيل الشكوى أو طلب النزيل',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1D4ED8),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () async {
                final title = titleCtrl.text.trim();
                final msg = msgCtrl.text.trim();
                if (title.isEmpty || msg.isEmpty) return;

                Navigator.pop(ctx);
                setState(() => _isLoading = true);

                try {
                  final res = await http.post(
                    Uri.parse('${AppState.apiUrl}/staff/complaints'),
                    headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: jsonEncode({'title': title, 'message': msg}),
                  );
                  final data = jsonDecode(res.body);
                  if (res.statusCode == 200 && data['status'] == 'success') {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'تم تسجيل الشكوى بنجاح')));
                    _fetchComplaints();
                  }
                } catch (_) {}
                if (mounted) setState(() => _isLoading = false);
              },
              child: const Text('تسجيل الشكوى'),
            ),
          ],
        ),
      ),
    );
  }

  void _openReplyDialog(Map<String, dynamic> c) {
    String selectedStatus = c['status'] ?? 'in_progress';
    final replyCtrl = TextEditingController(text: c['response'] ?? '');

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
            title: Text('الرد ومعالجة الشكوى #${c['id']}'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(c['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 4),
                Text(c['message'] ?? '', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                const SizedBox(height: 16),
                const Text('حالة الشكوى:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  initialValue: selectedStatus,
                  items: const [
                    DropdownMenuItem(value: 'new', child: Text('جديدة')),
                    DropdownMenuItem(value: 'in_progress', child: Text('قيد المعالجة والمتابعة')),
                    DropdownMenuItem(value: 'closed', child: Text('تم الحل وإغلاق الشكوى')),
                  ],
                  onChanged: (v) {
                    if (v != null) setDialogState(() => selectedStatus = v);
                  },
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: replyCtrl,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: 'الرد الإداري والإجراء المتخذ',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: () async {
                  Navigator.pop(ctx);
                  setState(() => _isLoading = true);

                  try {
                    final res = await http.put(
                      Uri.parse('${AppState.apiUrl}/staff/complaints/${c['id']}'),
                      headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                      body: jsonEncode({'status': selectedStatus, 'response': replyCtrl.text.trim()}),
                    );
                    final data = jsonDecode(res.body);
                    if (res.statusCode == 200 && data['status'] == 'success') {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'تم تحديث الشكوى بنجاح')));
                      _fetchComplaints();
                    }
                  } catch (_) {}
                  if (mounted) setState(() => _isLoading = false);
                },
                child: const Text('حفظ الرد'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isOwner = AppState.isReadOnlyOwner();

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('📢 إدارة الشكاوى والمقترحات', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  SizedBox(height: 4),
                  Text('متابعة طلبات وملاحظات النزلاء وتسجيل الإجراءات الإدارية لمعالجتها.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                ],
              ),
              if (!isOwner)
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _openCreateComplaintDialog,
                  icon: const Icon(Icons.add_comment, size: 18),
                  label: const Text('تسجيل شكوى / مقترح جديد', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          const SizedBox(height: 20),

          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _complaints.isEmpty
                      ? const Center(child: Text('لا توجد شكاوى أو مقترحات مسجلة.'))
                      : Scrollbar(
                          controller: _scrollController,
                          thumbVisibility: true,
                          trackVisibility: true,
                          interactive: true,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            child: SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                columns: const [
                                  DataColumn(label: Text('# الرقم', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('العنوان', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('النزيل / الغرفة', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('الحالة', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('الرد الإداري', style: TextStyle(fontWeight: FontWeight.bold))),
                                  DataColumn(label: Text('الإجراء', style: TextStyle(fontWeight: FontWeight.bold))),
                                ],
                                rows: _complaints.map((c) {
                                  final status = c['status'] ?? 'new';
                                  final statusColor = status == 'closed'
                                      ? const Color(0xFF059669)
                                      : (status == 'in_progress' ? const Color(0xFF2563EB) : const Color(0xFFD97706));
                                  final statusLabel = status == 'closed' ? 'مغلقة ومحلولة' : (status == 'in_progress' ? 'قيد المعالجة' : 'جديدة');

                                  return DataRow(cells: [
                                    DataCell(Text('#${c['id']}')),
                                    DataCell(Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Text(c['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                                        Text(c['message'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                                      ],
                                    )),
                                    DataCell(Text(c['guest']?['name'] ?? (c['room'] != null ? 'غرفة ${c['room']['room_number']}' : '—'))),
                                    DataCell(
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: statusColor.withValues(alpha: 0.12),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(statusLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: statusColor)),
                                      ),
                                    ),
                                    DataCell(Text(c['response'] ?? '—', style: const TextStyle(fontSize: 12, color: Color(0xFF475569)))),
                                    DataCell(
                                      !isOwner
                                          ? ElevatedButton(
                                              style: ElevatedButton.styleFrom(
                                                backgroundColor: const Color(0xFF1E293B),
                                                foregroundColor: Colors.white,
                                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                minimumSize: const Size(60, 30),
                                              ),
                                              onPressed: () => _openReplyDialog(c),
                                              child: const Text('معالجة / رد', style: TextStyle(fontSize: 11)),
                                            )
                                          : const Text('معاينة فقط', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                                    ),
                                  ]);
                                }).toList(),
                              ),
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

// ============================================================================
// 15. REPORTS TAB (التقارير الشاملة والإشغال)
// ============================================================================

class ReportsTab extends StatefulWidget {
  const ReportsTab({super.key});

  @override
  State<ReportsTab> createState() => _ReportsTabState();
}

class _ReportsTabState extends State<ReportsTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  Map<String, dynamic> _reportData = {};
  
  String _period = 'monthly'; // daily, weekly, monthly, yearly, custom
  String _currency = 'all'; // all, SYP, USD
  String _type = 'all'; // all, income, expense
  
  DateTime _date = DateTime.now();
  DateTime _weekDate = DateTime.now();
  int _month = DateTime.now().month;
  int _year = DateTime.now().year;
  DateTime _fromDate = DateTime(DateTime.now().year, DateTime.now().month, 1);
  DateTime _toDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchReport();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchReport() async {
    setState(() => _isLoading = true);
    try {
      final queryParams = <String, String>{
        'period': _period,
        'currency': _currency,
        'type': _type,
      };

      if (_period == 'daily') {
        queryParams['date'] = '${_date.year}-${_date.month.toString().padLeft(2, '0')}-${_date.day.toString().padLeft(2, '0')}';
      } else if (_period == 'weekly') {
        queryParams['week_date'] = '${_weekDate.year}-${_weekDate.month.toString().padLeft(2, '0')}-${_weekDate.day.toString().padLeft(2, '0')}';
      } else if (_period == 'monthly') {
        queryParams['month'] = '$_year-${_month.toString().padLeft(2, '0')}';
      } else if (_period == 'yearly') {
        queryParams['year'] = '$_year';
      } else if (_period == 'custom') {
        queryParams['from'] = '${_fromDate.year}-${_fromDate.month.toString().padLeft(2, '0')}-${_fromDate.day.toString().padLeft(2, '0')}';
        queryParams['to'] = '${_toDate.year}-${_toDate.month.toString().padLeft(2, '0')}-${_toDate.day.toString().padLeft(2, '0')}';
      }

      final uri = Uri.parse('${AppState.apiUrl}/staff/reports/accounting').replace(queryParameters: queryParams);
      final res = await http.get(
        uri,
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _reportData = body['data'] ?? {};
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  void _openPrintPreviewDialog() {
    if (_reportData.isEmpty) return;
    showDialog(
      context: context,
      builder: (ctx) => AccountingStatementPrintDialog(report: _reportData),
    );
  }

  @override
  Widget build(BuildContext context) {
    final rows = (_reportData['statement_rows'] as List?) ?? [];
    final hotelName = _reportData['hotel_name'] ?? 'الفندق';
    final periodLabel = _reportData['period_label'] ?? 'كشف حساب';
    final from = _reportData['from'] ?? '';
    final to = _reportData['to'] ?? '';

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Top Toolbar Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Row(
                      children: [
                        Text('🏦', style: TextStyle(fontSize: 22)),
                        SizedBox(width: 8),
                        Text('كشف الحساب والتقارير المالية (Account Statement)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      ],
                    ),
                    SizedBox(height: 4),
                    Text('كشف حساب بنكي تفصيلي للحركات، الصادر، الوارد، الأرصدة الافتتاحية والختامية، وتصدير PDF', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  ],
                ),
                Row(
                  children: [
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1D4ED8),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.print, size: 18),
                      label: const Text('🖨️ طباعة الكشف المالي', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      onPressed: _openPrintPreviewDialog,
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.download, size: 18),
                      label: const Text('📥 تحميل PDF', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      onPressed: _openPrintPreviewDialog,
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      onPressed: _fetchReport,
                      icon: const Icon(Icons.refresh, color: Color(0xFF64748B)),
                      tooltip: 'تحديث الكشف',
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Period Filter Bar
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('⚙️ خيارات وتخصيص الفترة والعملة:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B))),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      // Period Type Dropdown
                      SizedBox(
                        width: 190,
                        child: DropdownButtonFormField<String>(
                          value: _period,
                          decoration: InputDecoration(
                            labelText: 'نوع الفترة الزمنية',
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          items: const [
                            DropdownMenuItem(value: 'daily', child: Text('📅 تقرير يومي')),
                            DropdownMenuItem(value: 'weekly', child: Text('🗓️ تقرير أسبوعي')),
                            DropdownMenuItem(value: 'monthly', child: Text('📊 تقرير شهري')),
                            DropdownMenuItem(value: 'yearly', child: Text('📈 تقرير سنوي')),
                            DropdownMenuItem(value: 'custom', child: Text('⚙️ مخصص (من - إلى)')),
                          ],
                          onChanged: (val) {
                            if (val != null) {
                              setState(() => _period = val);
                              _fetchReport();
                            }
                          },
                        ),
                      ),

                      // Dynamic Input based on period
                      if (_period == 'daily') ...[
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () async {
                            final d = await showDatePicker(
                              context: context,
                              initialDate: _date,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2030),
                            );
                            if (d != null) {
                              setState(() => _date = d);
                              _fetchReport();
                            }
                          },
                          icon: const Icon(Icons.calendar_today, size: 16),
                          label: Text('اليوم: ${_date.year}-${_date.month.toString().padLeft(2, '0')}-${_date.day.toString().padLeft(2, '0')}'),
                        ),
                      ] else if (_period == 'weekly') ...[
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () async {
                            final d = await showDatePicker(
                              context: context,
                              initialDate: _weekDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2030),
                            );
                            if (d != null) {
                              setState(() => _weekDate = d);
                              _fetchReport();
                            }
                          },
                          icon: const Icon(Icons.date_range, size: 16),
                          label: Text('تاريخ الأسبوع: ${_weekDate.year}-${_weekDate.month.toString().padLeft(2, '0')}-${_weekDate.day.toString().padLeft(2, '0')}'),
                        ),
                      ] else if (_period == 'monthly') ...[
                        SizedBox(
                          width: 130,
                          child: DropdownButtonFormField<int>(
                            value: _month,
                            decoration: InputDecoration(
                              labelText: 'الشهر',
                              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            items: List.generate(12, (i) => i + 1).map((m) {
                              return DropdownMenuItem(value: m, child: Text('شهر $m'));
                            }).toList(),
                            onChanged: (val) {
                              if (val != null) {
                                setState(() => _month = val);
                                _fetchReport();
                              }
                            },
                          ),
                        ),
                        SizedBox(
                          width: 110,
                          child: DropdownButtonFormField<int>(
                            value: _year,
                            decoration: InputDecoration(
                              labelText: 'السنة',
                              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            items: [2024, 2025, 2026, 2027, 2028].map((y) {
                              return DropdownMenuItem(value: y, child: Text('$y'));
                            }).toList(),
                            onChanged: (val) {
                              if (val != null) {
                                setState(() => _year = val);
                                _fetchReport();
                              }
                            },
                          ),
                        ),
                      ] else if (_period == 'yearly') ...[
                        SizedBox(
                          width: 120,
                          child: DropdownButtonFormField<int>(
                            value: _year,
                            decoration: InputDecoration(
                              labelText: 'تحديد السنة',
                              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            items: [2024, 2025, 2026, 2027, 2028].map((y) {
                              return DropdownMenuItem(value: y, child: Text('$y'));
                            }).toList(),
                            onChanged: (val) {
                              if (val != null) {
                                setState(() => _year = val);
                                _fetchReport();
                              }
                            },
                          ),
                        ),
                      ] else if (_period == 'custom') ...[
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () async {
                            final d = await showDatePicker(
                              context: context,
                              initialDate: _fromDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2030),
                            );
                            if (d != null) {
                              setState(() => _fromDate = d);
                              _fetchReport();
                            }
                          },
                          icon: const Icon(Icons.calendar_today, size: 14),
                          label: Text('من: ${_fromDate.year}-${_fromDate.month.toString().padLeft(2, '0')}-${_fromDate.day.toString().padLeft(2, '0')}'),
                        ),
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () async {
                            final d = await showDatePicker(
                              context: context,
                              initialDate: _toDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2030),
                            );
                            if (d != null) {
                              setState(() => _toDate = d);
                              _fetchReport();
                            }
                          },
                          icon: const Icon(Icons.event, size: 14),
                          label: Text('إلى: ${_toDate.year}-${_toDate.month.toString().padLeft(2, '0')}-${_toDate.day.toString().padLeft(2, '0')}'),
                        ),
                      ],

                      // Currency Filter
                      SizedBox(
                        width: 170,
                        child: DropdownButtonFormField<String>(
                          value: _currency,
                          decoration: InputDecoration(
                            labelText: 'العملة المحددة',
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          items: const [
                            DropdownMenuItem(value: 'all', child: Text('الكل (SYP + USD)')),
                            DropdownMenuItem(value: 'SYP', child: Text('الليرة السورية (ل.س)')),
                            DropdownMenuItem(value: 'USD', child: Text('الدولار الأمريكي (\$)')),
                          ],
                          onChanged: (val) {
                            if (val != null) {
                              setState(() => _currency = val);
                              _fetchReport();
                            }
                          },
                        ),
                      ),

                      // Transaction Type Filter
                      SizedBox(
                        width: 160,
                        child: DropdownButtonFormField<String>(
                          value: _type,
                          decoration: InputDecoration(
                            labelText: 'نوع الحركة',
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          items: const [
                            DropdownMenuItem(value: 'all', child: Text('كل الحركات')),
                            DropdownMenuItem(value: 'income', child: Text('الوارد فقط (إيراد)')),
                            DropdownMenuItem(value: 'expense', child: Text('الصادر فقط (مصروف)')),
                          ],
                          onChanged: (val) {
                            if (val != null) {
                              setState(() => _type = val);
                              _fetchReport();
                            }
                          },
                        ),
                      ),

                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0F172A),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: _fetchReport,
                        child: const Text('تحديث الكشف', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            if (_isLoading)
              const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator()))
            else ...[
              // Official Bank Statement Container
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: const Color(0xFFCBD5E1), width: 1.5),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 3)),
                  ],
                ),
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Statement Bank-Style Header
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 52,
                              height: 52,
                              decoration: BoxDecoration(color: const Color(0xFF0F172A), borderRadius: BorderRadius.circular(12)),
                              child: const Center(child: Text('🏢', style: TextStyle(fontSize: 24))),
                            ),
                            const SizedBox(width: 14),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(hotelName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A))),
                                const SizedBox(height: 2),
                                const Text('قسم الإدارة المالية والمحاسبية · FINANCIAL DEPARTMENT', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                                Text('${_reportData['hotel_address'] ?? 'الجمهورية العربية السورية'} · هاتف: ${_reportData['hotel_phone'] ?? '—'}', style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                              ],
                            ),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(8), border: Border.all(color: const Color(0xFFCBD5E1))),
                              child: const Text('DOCUMENT TYPE: ACCOUNT STATEMENT', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                            ),
                            const SizedBox(height: 4),
                            Text('REF: STMT-${DateTime.now().year}${DateTime.now().month.toString().padLeft(2, '0')}${DateTime.now().day.toString().padLeft(2, '0')}', style: const TextStyle(fontSize: 11, fontFamily: 'monospace', color: Color(0xFF64748B))),
                          ],
                        ),
                      ],
                    ),
                    const Divider(height: 28, thickness: 1.5, color: Color(0xFF0F172A)),

                    // Period Ribbon Tags
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(10), border: Border.all(color: const Color(0xFFE2E8F0))),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('نوع التقرير: $periodLabel', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                          Text('الفترة: $from ⬅️ $to', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF475569))),
                          Text('العملة: ${_currency == 'all' ? 'جميع العملات (SYP + USD)' : _currency}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1D4ED8))),
                          Text('عدد العمليات: ${rows.length}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF475569))),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // 4 Financial Summary KPI Cards
                    LayoutBuilder(
                      builder: (ctx, constraints) {
                        return Row(
                          children: [
                            _buildStatementKpiCard('الرصيد الافتتاحي (السابق)', _reportData, 'opening', const Color(0xFF475569), const Color(0xFFF8FAFC)),
                            const SizedBox(width: 12),
                            _buildStatementKpiCard('إجمالي الوارد (دائن / إيرادات)', _reportData, 'income', const Color(0xFF059669), const Color(0xFFDCFCE7)),
                            const SizedBox(width: 12),
                            _buildStatementKpiCard('إجمالي الصادر (مدين / مصروفات)', _reportData, 'expense', const Color(0xFFDC2626), const Color(0xFFFEE2E2)),
                            const SizedBox(width: 12),
                            _buildStatementKpiCard('الرصيد الختامي الحالي', _reportData, 'closing', const Color(0xFF1D4ED8), const Color(0xFFDBEAFE)),
                          ],
                        );
                      },
                    ),
                    const SizedBox(height: 24),

                    // Ledger Table
                    const Text('📑 حركات كشف الحساب التفصيلي (Transactions Ledger):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A))),
                    const SizedBox(height: 10),

                    Container(
                      decoration: BoxDecoration(
                        border: Border.all(color: const Color(0xFFCBD5E1)),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(minWidth: 800),
                          child: DataTable(
                            headingRowColor: WidgetStateProperty.all(const Color(0xFFF1F5F9)),
                            columns: const [
                              DataColumn(label: Text('#', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('التاريخ', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('المرجع / الفاتورة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('البيان وتفاصيل الحركة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('العملة', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('الوارد (+) دائن', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('الصادر (-) مدين', style: TextStyle(fontWeight: FontWeight.bold))),
                              DataColumn(label: Text('الرصيد التراكمي', style: TextStyle(fontWeight: FontWeight.bold))),
                            ],
                            rows: rows.asMap().entries.map((entry) {
                              final i = entry.key + 1;
                              final r = entry.value;
                              final isIncome = r['type'] == 'income';
                              return DataRow(
                                color: WidgetStateProperty.all(entry.key % 2 == 0 ? Colors.white : const Color(0xFFF8FAFC)),
                                cells: [
                                  DataCell(Text('$i')),
                                  DataCell(Text(r['date'] ?? '—')),
                                  DataCell(Text(r['ref'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold))),
                                  DataCell(Text('${r['title'] ?? ''}${r['notes'] != null ? ' (${r['notes']})' : ''}')),
                                  DataCell(Text(r['currency'] ?? '—')),
                                  DataCell(Text(
                                    isIncome ? '+${_formatNumber(r['inflow'])}' : '—',
                                    style: TextStyle(fontWeight: FontWeight.bold, color: isIncome ? const Color(0xFF059669) : null),
                                  )),
                                  DataCell(Text(
                                    !isIncome ? '-${_formatNumber(r['outflow'])}' : '—',
                                    style: TextStyle(fontWeight: FontWeight.bold, color: !isIncome ? const Color(0xFFDC2626) : null),
                                  )),
                                  DataCell(Text(
                                    _formatNumber(r['balance']),
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                                  )),
                                ],
                              );
                            }).toList(),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatementKpiCard(String title, Map<String, dynamic> data, String type, Color color, Color bg) {
    String sypVal = '0.00';
    String usdVal = '0.00';

    if (type == 'opening') {
      sypVal = _formatNumber(data['opening_balance_syp']);
      usdVal = _formatNumber(data['opening_balance_usd']);
    } else if (type == 'income') {
      sypVal = _formatNumber(data['income_syp']);
      usdVal = _formatNumber(data['income_usd']);
    } else if (type == 'expense') {
      sypVal = _formatNumber(data['expense_syp']);
      usdVal = _formatNumber(data['expense_usd']);
    } else if (type == 'closing') {
      sypVal = _formatNumber(data['closing_balance_syp']);
      usdVal = _formatNumber(data['closing_balance_usd']);
    }

    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.25)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 8),
            if (_currency == 'all' || _currency == 'SYP')
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(sypVal, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: color)),
                  const Text('ل.س', style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                ],
              ),
            if (_currency == 'all' || _currency == 'USD')
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('\$$usdVal', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: color)),
                  const Text('USD', style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// 15.1 OFFICIAL ACCOUNTING STATEMENT PRINT PREVIEW DIALOG (طباعة وتصدير PDF)
// ============================================================================
class AccountingStatementPrintDialog extends StatefulWidget {
  final Map<String, dynamic> report;
  const AccountingStatementPrintDialog({super.key, required this.report});

  @override
  State<AccountingStatementPrintDialog> createState() => _AccountingStatementPrintDialogState();
}

class _AccountingStatementPrintDialogState extends State<AccountingStatementPrintDialog> {
  bool _isSaving = false;

  String _formatNumber(dynamic val) {
    if (val == null) return '0.00';
    final num n = (val is num) ? val : (double.tryParse(val.toString()) ?? 0);
    return n.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  Future<void> _handlePrint() async {
    try {
      final html = NativePrintAndSaveService.buildStatementHtml(widget.report);
      await NativePrintAndSaveService.printWithNativeDialog(html);
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء طباعة كشف الحساب',
          message: 'تعذر تشغيل أمر الطباعة: $e',
        );
      }
    }
  }

  Future<void> _handleSavePdf() async {
    try {
      setState(() => _isSaving = true);
      final report = AppState.safeMap(widget.report);
      final defaultFileName = 'كشف_حساب_${report['from'] ?? ''}_${report['to'] ?? ''}.pdf'.replaceAll(' ', '_');

      final chosenPath = await NativePrintAndSaveService.showSaveFileDialog(
        defaultFileName: defaultFileName,
        filter: 'ملفات PDF (*.pdf)|*.pdf|ملفات HTML (*.html)|*.html|جميع الملفات (*.*)|*.*',
      );

      if (chosenPath != null && chosenPath.isNotEmpty) {
        final html = NativePrintAndSaveService.buildStatementHtml(report);
        final success = await NativePrintAndSaveService.saveDocument(html, chosenPath);
        if (mounted) {
          if (success) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('✓ تم حفظ كشف الحساب بنجاح في:\n$chosenPath'),
                backgroundColor: const Color(0xFF059669),
                duration: const Duration(seconds: 6),
                action: SnackBarAction(
                  label: '📂 فتح الملف',
                  textColor: Colors.white,
                  onPressed: () => NativePrintAndSaveService.openFile(chosenPath),
                ),
              ),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('تعذر حفظ كشف الحساب، يرجى التحقق من أذونات المجلد.'), backgroundColor: Color(0xFFDC2626)),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        AppState.showGlobalErrorDialog(
          title: 'تنبيه أثناء حفظ كشف الحساب',
          message: 'تعذر حفظ كشف الحساب: $e',
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  void _openPrinterOptions() {
    final report = widget.report;
    final defaultFileName = 'كشف_حساب_${report['from'] ?? ''}_${report['to'] ?? ''}.pdf'.replaceAll(' ', '_');
    final html = NativePrintAndSaveService.buildStatementHtml(report);
    showDialog(
      context: context,
      builder: (ctx) => PrinterOptionsDialog(
        title: 'إعدادات وخيارات طباعة كشف الحساب',
        documentType: 'statement',
        defaultFormat: 'a4',
        htmlContent: html,
        defaultFileName: defaultFileName,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final report = widget.report;
    final rows = (report['statement_rows'] as List?) ?? [];
    final hotelName = report['hotel_name'] ?? 'الفندق';
    final periodLabel = report['period_label'] ?? 'كشف حساب';
    final from = report['from'] ?? '';
    final to = report['to'] ?? '';
    final currency = report['currency'] ?? 'all';

    final printScrollCtrl = ScrollController();

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(horizontal: 30, vertical: 20),
        child: Container(
          width: 960,
          constraints: const BoxConstraints(maxHeight: 850),
          color: Colors.white,
          child: Column(
            children: [
              // Dialog Top Action Bar
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                decoration: const BoxDecoration(
                  color: Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20)),
                  border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: const [
                        Icon(Icons.print_outlined, color: Color(0xFF1D4ED8)),
                        SizedBox(width: 8),
                        Text('معاينة طباعة الكشف المالي الرسمي (Print & PDF)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
                      ],
                    ),
                    Row(
                      children: [
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF475569),
                            side: const BorderSide(color: Color(0xFFCBD5E1)),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: _openPrinterOptions,
                          icon: const Icon(Icons.settings, size: 16),
                          label: const Text('⚙️ خيارات الطابعة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                        ),
                        const SizedBox(width: 8),
                        OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF1D4ED8),
                            side: const BorderSide(color: Color(0xFF1D4ED8)),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: _isSaving ? null : _handleSavePdf,
                          icon: _isSaving
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                              : const Icon(Icons.save_alt, size: 16),
                          label: const Text('💾 حفظ PDF (اختيار المكان)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF1D4ED8),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: _handlePrint,
                          icon: const Icon(Icons.print, size: 16),
                          label: const Text('🖨️ طباعة الآن (نافذة الطابعات)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                        ),
                        const SizedBox(width: 10),
                        IconButton(
                          onPressed: () => Navigator.pop(context),
                          icon: const Icon(Icons.close),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Printable Document Area
              Expanded(
                child: Scrollbar(
                  controller: printScrollCtrl,
                  thumbVisibility: true,
                  trackVisibility: true,
                  interactive: true,
                  child: SingleChildScrollView(
                    controller: printScrollCtrl,
                    padding: const EdgeInsets.all(28),
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFCBD5E1), width: 1.5),
                      ),
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Document Header
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(color: const Color(0xFF0F172A), borderRadius: BorderRadius.circular(12)),
                                    child: const Center(child: Text('🏢', style: TextStyle(fontSize: 24))),
                                  ),
                                  const SizedBox(width: 12),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(hotelName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A))),
                                      const SizedBox(height: 2),
                                      const Text('قسم الإدارة المالية والمحاسبية · FINANCIAL DEPARTMENT', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                                      Text('${report['hotel_address'] ?? 'الجمهورية العربية السورية'} · هاتف: ${report['hotel_phone'] ?? '—'}', style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                                    ],
                                  ),
                                ],
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(8), border: Border.all(color: const Color(0xFFCBD5E1))),
                                    child: const Text('ACCOUNT STATEMENT', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                                  ),
                                  const SizedBox(height: 4),
                                  Text('REF: STMT-${DateTime.now().year}${DateTime.now().month.toString().padLeft(2, '0')}${DateTime.now().day.toString().padLeft(2, '0')}', style: const TextStyle(fontSize: 11, fontFamily: 'monospace', color: Color(0xFF64748B))),
                                  Text('تاريخ المستند: ${DateTime.now().toString().substring(0, 16)}', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                                ],
                              ),
                            ],
                          ),
                          const Divider(height: 28, thickness: 1.5, color: Color(0xFF0F172A)),

                          // Period Info
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(8), border: Border.all(color: const Color(0xFFE2E8F0))),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('نوع التقرير: $periodLabel', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                                Text('الفترة الزمنية: $from ⬅️ $to', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF475569))),
                                Text('العملة: ${currency == 'all' ? 'جميع العملات (SYP + USD)' : currency}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1D4ED8))),
                                Text('عدد العمليات: ${rows.length}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF475569))),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),

                          // 4 Financial Summary KPI Cards
                          Row(
                            children: [
                              _buildPrintKpi('الرصيد الافتتاحي (السابق)', report, 'opening', const Color(0xFF475569), const Color(0xFFF1F5F9)),
                              const SizedBox(width: 8),
                              _buildPrintKpi('إجمالي الوارد (دائن / إيرادات)', report, 'income', const Color(0xFF059669), const Color(0xFFDCFCE7)),
                              const SizedBox(width: 8),
                              _buildPrintKpi('إجمالي الصادر (مدين / مصروفات)', report, 'expense', const Color(0xFFDC2626), const Color(0xFFFEE2E2)),
                              const SizedBox(width: 8),
                              _buildPrintKpi('الرصيد الختامي الحالي', report, 'closing', const Color(0xFF1D4ED8), const Color(0xFFDBEAFE)),
                            ],
                          ),
                          const SizedBox(height: 20),

                          // Table Title
                          const Text('📋 جدول حركات كشف الحساب التفصيلي:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                          const SizedBox(height: 8),

                          // Table
                          Table(
                            border: TableBorder.all(color: const Color(0xFFCBD5E1), width: 1),
                            columnWidths: const {
                              0: FlexColumnWidth(0.8),
                              1: FlexColumnWidth(1.8),
                              2: FlexColumnWidth(2.2),
                              3: FlexColumnWidth(4.2),
                              4: FlexColumnWidth(1.2),
                              5: FlexColumnWidth(2.2),
                              6: FlexColumnWidth(2.2),
                              7: FlexColumnWidth(2.4),
                            },
                            children: [
                              TableRow(
                                decoration: const BoxDecoration(color: Color(0xFFF1F5F9)),
                                children: [
                                  _tableHeader('#'),
                                  _tableHeader('التاريخ'),
                                  _tableHeader('المرجع / الفاتورة'),
                                  _tableHeader('البيان وتفاصيل العملية'),
                                  _tableHeader('العملة'),
                                  _tableHeader('الوارد (+)'),
                                  _tableHeader('الصادر (-)'),
                                  _tableHeader('الرصيد'),
                                ],
                              ),
                              if (rows.isEmpty)
                                TableRow(
                                  children: [
                                    _tableCell(''),
                                    _tableCell(''),
                                    _tableCell(''),
                                    _tableCell('لا توجد حركات مالية مسجلة في هذه الفترة.', align: TextAlign.center, isBold: true),
                                    _tableCell(''),
                                    _tableCell(''),
                                    _tableCell(''),
                                    _tableCell(''),
                                  ],
                                )
                              else
                                ...rows.asMap().entries.map((entry) {
                                  final i = entry.key + 1;
                                  final r = entry.value;
                                  final isIncome = r['type'] == 'income';
                                  return TableRow(
                                    decoration: BoxDecoration(color: entry.key % 2 == 0 ? Colors.white : const Color(0xFFFAFAFA)),
                                    children: [
                                      _tableCell('$i', align: TextAlign.center),
                                      _tableCell(r['date'] ?? '', align: TextAlign.center),
                                      _tableCell(r['ref'] ?? '', isBold: true),
                                      _tableCell('${r['title'] ?? ''}${r['notes'] != null ? ' (${r['notes']})' : ''}'),
                                      _tableCell(r['currency'] ?? '', align: TextAlign.center),
                                      _tableCell(isIncome ? '+${_formatNumber(r['inflow'])}' : '—', color: isIncome ? const Color(0xFF059669) : null, isBold: isIncome),
                                      _tableCell(!isIncome ? '-${_formatNumber(r['outflow'])}' : '—', color: !isIncome ? const Color(0xFFDC2626) : null, isBold: !isIncome),
                                      _tableCell(_formatNumber(r['balance']), isBold: true, color: const Color(0xFF1E293B)),
                                    ],
                                  );
                                }),
                            ],
                          ),
                          const SizedBox(height: 36),

                          // Signatures Section
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceAround,
                            children: [
                              Column(
                                children: const [
                                  Text('المحاسب المعتمد', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                                  SizedBox(height: 36),
                                  Text('........................................', style: TextStyle(color: Color(0xFF94A3B8))),
                                ],
                              ),
                              Column(
                                children: const [
                                  Text('الختم الرسمي للفندق', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                                  SizedBox(height: 36),
                                  Text('........................................', style: TextStyle(color: Color(0xFF94A3B8))),
                                ],
                              ),
                              Column(
                                children: const [
                                  Text('مدير الفندق / الإدارة المالية', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF475569))),
                                  SizedBox(height: 36),
                                  Text('........................................', style: TextStyle(color: Color(0xFF94A3B8))),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPrintKpi(String label, Map<String, dynamic> report, String type, Color color, Color bg) {
    String sypVal = '0.00';
    String usdVal = '0.00';

    if (type == 'opening') {
      sypVal = _formatNumber(report['opening_balance_syp']);
      usdVal = _formatNumber(report['opening_balance_usd']);
    } else if (type == 'income') {
      sypVal = _formatNumber(report['income_syp']);
      usdVal = _formatNumber(report['income_usd']);
    } else if (type == 'expense') {
      sypVal = _formatNumber(report['expense_syp']);
      usdVal = _formatNumber(report['expense_usd']);
    } else if (type == 'closing') {
      sypVal = _formatNumber(report['closing_balance_syp']);
      usdVal = _formatNumber(report['closing_balance_usd']);
    }

    final currency = report['currency'] ?? 'all';

    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8), border: Border.all(color: color.withValues(alpha: 0.3))),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 4),
            if (currency == 'all' || currency == 'SYP')
              Text('$sypVal ل.س', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: color)),
            if (currency == 'all' || currency == 'USD')
              Text('\$$usdVal USD', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _tableHeader(String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
      child: Text(text, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF0F172A))),
    );
  }

  Widget _tableCell(String text, {TextAlign align = TextAlign.start, bool isBold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 7),
      child: Text(
        text,
        textAlign: align,
        style: TextStyle(
          fontSize: 10.5,
          fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
          color: color ?? const Color(0xFF334155),
        ),
      ),
    );
  }
}

// ============================================================================
// 16. PRINTER & SCANNER SETTINGS TAB (إعدادات الطباعة والسكنر)
// ============================================================================

class PrinterScannerTab extends StatefulWidget {
  const PrinterScannerTab({super.key});

  @override
  State<PrinterScannerTab> createState() => _PrinterScannerTabState();
}

class _PrinterScannerTabState extends State<PrinterScannerTab> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = true;
  final _invoicePrinterCtrl = TextEditingController();
  final _reportPrinterCtrl = TextEditingController();
  String _invoiceFormat = 'thermal_80mm';
  String _scannerSource = 'scanner_hardware';
  bool _autoPrint = false;

  List<String> _connectedPrinters = [];
  bool _isDetectingPrinters = false;
  bool _isTestingPrint = false;

  final _testCodeCtrl = TextEditingController();
  Map<String, dynamic>? _scanResult;
  bool _isSearching = false;

  @override
  void initState() {
    super.initState();
    _fetchSettings();
    _detectHardwarePrinters();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _invoicePrinterCtrl.dispose();
    _reportPrinterCtrl.dispose();
    _testCodeCtrl.dispose();
    super.dispose();
  }

  Future<void> _detectHardwarePrinters() async {
    setState(() => _isDetectingPrinters = true);
    final list = await NativePrintAndSaveService.getConnectedPrinters();
    if (mounted) {
      setState(() {
        _connectedPrinters = list;
        _isDetectingPrinters = false;
      });
    }
  }

  Future<void> _testPrintInvoice() async {
    setState(() => _isTestingPrint = true);
    final sampleInvoice = {
      'invoice_number': 'INV-TEST-001',
      'guest_name': 'نزيل تجريبي',
      'guest_phone': '+963 988 123 456',
      'room_number': '101',
      'room_type': 'جناح فندقي تنفيذي',
      'amount': 150.00,
      'service_amount': 25.00,
      'service_description': 'إفطار وضيافة',
      'tax': 0.00,
      'total': 175.00,
      'currency': 'USD',
      'status': 'paid',
      'paid_at': DateTime.now().toString().substring(0, 16),
    };
    final html = NativePrintAndSaveService.buildInvoiceHtml(
      sampleInvoice,
      _invoiceFormat == 'thermal_80mm' ? 'thermal' : 'a4',
    );
    await NativePrintAndSaveService.printWithNativeDialog(html);
    if (mounted) setState(() => _isTestingPrint = false);
  }

  Future<void> _testSaveInvoicePdf() async {
    final sampleInvoice = {
      'invoice_number': 'INV-TEST-001',
      'guest_name': 'نزيل تجريبي',
      'guest_phone': '+963 988 123 456',
      'room_number': '101',
      'room_type': 'جناح فندقي تنفيذي',
      'amount': 150.00,
      'service_amount': 25.00,
      'service_description': 'إفطار وضيافة',
      'tax': 0.00,
      'total': 175.00,
      'currency': 'USD',
      'status': 'paid',
      'paid_at': DateTime.now().toString().substring(0, 16),
    };
    final html = NativePrintAndSaveService.buildInvoiceHtml(
      sampleInvoice,
      _invoiceFormat == 'thermal_80mm' ? 'thermal' : 'a4',
    );
    final chosenPath = await NativePrintAndSaveService.showSaveFileDialog(
      defaultFileName: 'فاتورة_تجريبية_${_invoiceFormat == 'thermal_80mm' ? 'حراري' : 'A4'}.pdf',
    );
    if (chosenPath != null && chosenPath.isNotEmpty) {
      final success = await NativePrintAndSaveService.saveDocument(html, chosenPath);
      if (mounted) {
        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✓ تم حفظ الفاتورة التجريبية بنجاح في:\n$chosenPath'),
              backgroundColor: const Color(0xFF059669),
              duration: const Duration(seconds: 6),
              action: SnackBarAction(
                label: '📂 فتح الملف',
                textColor: Colors.white,
                onPressed: () => NativePrintAndSaveService.openFile(chosenPath),
              ),
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('تعذر حفظ الملف.'), backgroundColor: Color(0xFFDC2626)),
          );
        }
      }
    }
  }

  Future<void> _fetchSettings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/printer-settings'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final data = body['data'] ?? {};
        setState(() {
          _invoicePrinterCtrl.text = data['invoice_printer'] ?? 'طابعة الفواتير الافتراضية';
          _reportPrinterCtrl.text = data['report_printer'] ?? 'طابعة التقارير الافتراضية';
          _invoiceFormat = data['invoice_format'] ?? 'thermal_80mm';
          _scannerSource = data['scanner_source'] ?? 'scanner_hardware';
          _autoPrint = data['auto_print'] == true;
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _saveSettings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.post(
        Uri.parse('${AppState.apiUrl}/staff/printer-settings'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'invoice_printer': _invoicePrinterCtrl.text.trim(),
          'report_printer': _reportPrinterCtrl.text.trim(),
          'invoice_format': _invoiceFormat,
          'scanner_source': _scannerSource,
          'auto_print': _autoPrint,
        }),
      );

      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'تم الحفظ بنجاح')));
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _testLookup([String? code]) async {
    final search = code ?? _testCodeCtrl.text.trim();
    if (search.isEmpty) return;

    setState(() {
      _isSearching = true;
      _scanResult = null;
    });

    try {
      final res = await http.get(
        Uri.parse('${AppState.apiUrl}/staff/scanner/lookup?code=${Uri.encodeComponent(search)}'),
        headers: {'Authorization': 'Bearer ${AppState.token}', 'Accept': 'application/json'},
      );
      final body = jsonDecode(res.body);
      setState(() {
        _scanResult = body;
      });
    } catch (_) {}
    if (mounted) setState(() => _isSearching = false);
  }

  @override
  Widget build(BuildContext context) {
    final isOwner = AppState.isReadOnlyOwner();

    return Scrollbar(
      controller: _scrollController,
      thumbVisibility: true,
      trackVisibility: true,
      interactive: true,
      child: SingleChildScrollView(
        controller: _scrollController,
        padding: const EdgeInsets.all(24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('🖨️ إعدادات الطباعة والسكنر وأجهزة الاستقبال', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  SizedBox(height: 4),
                  Text('تهيئة طابعات الفواتير والتقارير، تخصيص مقاس الورق الحراري وقارئ الباركود/QR.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                ],
              ),
              if (!isOwner)
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1D4ED8),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _saveSettings,
                  icon: const Icon(Icons.save, size: 18),
                  label: const Text('حفظ الإعدادات', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          const SizedBox(height: 24),

          if (_isLoading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator()))
          else ...[
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Settings Form
                Expanded(
                  flex: 5,
                  child: Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('تكوين الطابعات ومقاس الفواتير', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _invoicePrinterCtrl,
                          enabled: !isOwner,
                          decoration: InputDecoration(
                            labelText: 'اسم طابعة الفواتير (Thermal Printer)',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            prefixIcon: const Icon(Icons.receipt),
                          ),
                        ),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _reportPrinterCtrl,
                          enabled: !isOwner,
                          decoration: InputDecoration(
                            labelText: 'اسم طابعة التقارير والمستندات (Standard Printer)',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            prefixIcon: const Icon(Icons.print),
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Real connected printers detected on Windows
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: const [
                                      Icon(Icons.devices, size: 16, color: Color(0xFF1D4ED8)),
                                      SizedBox(width: 6),
                                      Text(
                                        'الطابعات المتصلة بنظام Windows حالياً:',
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1E293B)),
                                      ),
                                    ],
                                  ),
                                  TextButton.icon(
                                    onPressed: _isDetectingPrinters ? null : _detectHardwarePrinters,
                                    icon: _isDetectingPrinters
                                        ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2))
                                        : const Icon(Icons.refresh, size: 14),
                                    label: const Text('كشف وتحديث الطابعات', style: TextStyle(fontSize: 11)),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              if (_connectedPrinters.isEmpty)
                                const Text('جاري الكشف عن الطابعات المتصلة بالكمبيوتر...', style: TextStyle(fontSize: 11, color: Color(0xFF64748B)))
                              else
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: _connectedPrinters.map((p) {
                                    return Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                      decoration: BoxDecoration(
                                        color: Colors.white,
                                        borderRadius: BorderRadius.circular(8),
                                        border: Border.all(color: const Color(0xFFCBD5E1)),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(Icons.print, size: 14, color: Color(0xFF475569)),
                                          const SizedBox(width: 6),
                                          Text(p, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                          const SizedBox(width: 8),
                                          InkWell(
                                            onTap: () {
                                              setState(() => _invoicePrinterCtrl.text = p);
                                            },
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(4)),
                                              child: const Text('للفواتير', style: TextStyle(fontSize: 10, color: Color(0xFF1D4ED8), fontWeight: FontWeight.bold)),
                                            ),
                                          ),
                                          const SizedBox(width: 4),
                                          InkWell(
                                            onTap: () {
                                              setState(() => _reportPrinterCtrl.text = p);
                                            },
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(4)),
                                              child: const Text('للتقارير', style: TextStyle(fontSize: 10, color: Color(0xFF0F172A), fontWeight: FontWeight.bold)),
                                            ),
                                          ),
                                        ],
                                      ),
                                    );
                                  }).toList(),
                                ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),
                        const Text('مقاس ورقة الفاتورة المعتمد:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Expanded(
                              child: InkWell(
                                onTap: isOwner ? null : () => setState(() => _invoiceFormat = 'thermal_80mm'),
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: _invoiceFormat == 'thermal_80mm' ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(color: _invoiceFormat == 'thermal_80mm' ? const Color(0xFF1D4ED8) : const Color(0xFFE2E8F0)),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(_invoiceFormat == 'thermal_80mm' ? Icons.radio_button_checked : Icons.radio_button_unchecked, color: const Color(0xFF1D4ED8), size: 18),
                                      const SizedBox(width: 8),
                                      const Text('حراري 80 ملم (Thermal POS)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: InkWell(
                                onTap: isOwner ? null : () => setState(() => _invoiceFormat = 'a4'),
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: _invoiceFormat == 'a4' ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(color: _invoiceFormat == 'a4' ? const Color(0xFF1D4ED8) : const Color(0xFFE2E8F0)),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(_invoiceFormat == 'a4' ? Icons.radio_button_checked : Icons.radio_button_unchecked, color: const Color(0xFF1D4ED8), size: 18),
                                      const SizedBox(width: 8),
                                      const Text('ورق عادي A4 قياسي', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        const Text('مصدر وقارئ الباركود / السكنر:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue: _scannerSource,
                          items: const [
                            DropdownMenuItem(value: 'scanner_hardware', child: Text('قارئ باركود ليزري مادي (USB / Bluetooth Scanner)')),
                            DropdownMenuItem(value: 'camera', child: Text('كاميرا الكمبيوتر المدمجة (Webcam)')),
                            DropdownMenuItem(value: 'custom', child: Text('منفذ تسلسلي مخصص (COM Port / Custom)')),
                          ],
                          onChanged: isOwner ? null : (v) => setState(() => _scannerSource = v!),
                          decoration: InputDecoration(
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          ),
                        ),
                        const SizedBox(height: 16),
                        SwitchListTile(
                          title: const Text('الطباعة التلقائية فور سداد الفاتورة'),
                          subtitle: const Text('إرسال أمر الطباعة تلقائياً للطابعة المحددة بمجرد تأكيد الدفع.'),
                          value: _autoPrint,
                          onChanged: isOwner ? null : (v) => setState(() => _autoPrint = v),
                        ),
                        const SizedBox(height: 16),
                        const Divider(),
                        const SizedBox(height: 8),
                        const Text('🧪 اختبار عمليات الطباعة والحفظ المباشر:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 10,
                          runSpacing: 10,
                          children: [
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF059669),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              ),
                              onPressed: _isTestingPrint ? null : _testPrintInvoice,
                              icon: _isTestingPrint
                                  ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.print, size: 16),
                              label: const Text('🖨️ اختبار طباعة فاتورة (نافذة الطابعات)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                            OutlinedButton.icon(
                              style: OutlinedButton.styleFrom(
                                foregroundColor: const Color(0xFF1D4ED8),
                                side: const BorderSide(color: Color(0xFF1D4ED8)),
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              ),
                              onPressed: _testSaveInvoicePdf,
                              icon: const Icon(Icons.save_alt, size: 16),
                              label: const Text('💾 اختبار حفظ PDF (مع تحديد المكان)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 20),

                // Live Scanner Lookup Tester
                Expanded(
                  flex: 4,
                  child: Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.qr_code_scanner, color: Color(0xFF1D4ED8)),
                            SizedBox(width: 8),
                            Text('اختبار السكنر والبحث الفوري', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                          ],
                        ),
                        const SizedBox(height: 8),
                        const Text('يمكنك إدخال رمز الباركود أو مسحه للتحقق الفوري من الربط مع قاعدة البيانات:', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _testCodeCtrl,
                          decoration: InputDecoration(
                            hintText: 'رمز الباركود أو رقم الفاتورة أو الحجز...',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            suffixIcon: IconButton(
                              icon: const Icon(Icons.search),
                              onPressed: () => _testLookup(),
                            ),
                          ),
                          onSubmitted: (v) => _testLookup(v),
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          children: [
                            ActionChip(label: const Text('INV-'), onPressed: () => _testLookup('INV-')),
                            ActionChip(label: const Text('09'), onPressed: () => _testLookup('09')),
                          ],
                        ),
                        const SizedBox(height: 16),
                        if (_isSearching)
                          const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()))
                        else if (_scanResult != null)
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: _scanResult!['status'] == 'success' ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: _scanResult!['status'] == 'success' ? const Color(0xFFBBF7D0) : const Color(0xFFFECACA)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _scanResult!['title'] ?? _scanResult!['message'] ?? '',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: _scanResult!['status'] == 'success' ? const Color(0xFF166534) : const Color(0xFF991B1B),
                                  ),
                                ),
                                if (_scanResult!['data'] != null) ...[
                                  const SizedBox(height: 8),
                                  SelectableText(
                                    const JsonEncoder.withIndent('  ').convert(_scanResult!['data']),
                                    style: const TextStyle(fontFamily: 'Consolas', fontSize: 11),
                                    textDirection: TextDirection.ltr,
                                  ),
                                ],
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    ),
    );
  }
}

