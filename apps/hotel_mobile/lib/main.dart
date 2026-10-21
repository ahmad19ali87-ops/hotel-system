import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:google_sign_in/google_sign_in.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await MobileState.loadSession();
  await MobileNotificationService.init();
  runApp(const HotelMobileApp());
}

// Background & Local Push Notifications Service (Outside App)
class MobileNotificationService {
  static final FlutterLocalNotificationsPlugin _notificationsPlugin = FlutterLocalNotificationsPlugin();
  static final Set<int> _knownNotifIds = {};
  static final Set<int> _knownMsgIds = {};
  static Timer? _pollingTimer;

  static Future<void> init() async {
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
    );

    await _notificationsPlugin.initialize(settings: initializationSettings);

    try {
      await _notificationsPlugin
          .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();
    } catch (_) {}

    _startBackgroundPolling();
  }

  static void _startBackgroundPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 15), (timer) async {
      if (MobileState.isLoggedIn) {
        await checkNewNotifications();
      }
    });
  }

  static Future<void> checkNewNotifications() async {
    if (!MobileState.isLoggedIn) return;

    // 1. Fetch system & broadcast notifications
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/notifications?unread_only=1'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final List<dynamic> notifs = body['data'] ?? [];
        MobileState.unreadNotificationsNotifier.value = notifs.length;
        for (final n in notifs) {
          final id = (n['id'] is num) ? (n['id'] as num).toInt() : 1;
          if (!_knownNotifIds.contains(id)) {
            _knownNotifIds.add(id);
            await showNotification(
              id: id,
              title: n['title'] ?? 'إشعار فندقي جديد 🛎️',
              body: n['message'] ?? '',
            );
          }
        }
      }
    } catch (_) {}

    // 2. Fetch direct messages & conversations from hotels
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/chat/conversations'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final List<dynamic> convs = body['data'] ?? [];
        int totalUnread = 0;
        for (final c in convs) {
          final unread = (c['unread_count'] is num) ? (c['unread_count'] as num).toInt() : 0;
          totalUnread += unread;
          final lastId = (c['last_message_id'] is num)
              ? (c['last_message_id'] as num).toInt()
              : (c['id'] is num ? (c['id'] as num).toInt() : 0);
          if (unread > 0 && lastId > 0 && !_knownMsgIds.contains(lastId)) {
            _knownMsgIds.add(lastId);
            await showNotification(
              id: 700000 + lastId,
              title: 'رسالة جديدة من ${c['name']} 💬',
              body: c['last_message'] ?? 'أرسل لك رسالة جديدة',
            );
          }
        }
        MobileState.unreadMessagesNotifier.value = totalUnread;
      }
    } catch (_) {}
  }

  static Future<void> showNotification({
    required int id,
    required String title,
    required String body,
    String? payload,
  }) async {
    try {
      const AndroidNotificationDetails androidNotificationDetails = AndroidNotificationDetails(
        'hotel_notifications_channel',
        'تنبيهات الفندق والحجوزات',
        channelDescription: 'تنبيهات حالة الحجز، العروض الخاصة، والرسائل المباشرة',
        importance: Importance.max,
        priority: Priority.high,
        ticker: 'تنبيه فندقي جديد',
        icon: '@mipmap/ic_launcher',
      );

      const NotificationDetails notificationDetails = NotificationDetails(
        android: androidNotificationDetails,
      );

      await _notificationsPlugin.show(
        id: id,
        title: title,
        body: body,
        notificationDetails: notificationDetails,
        payload: payload,
      );
    } catch (_) {}
  }
}

class HotelMobileApp extends StatelessWidget {
  const HotelMobileApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'بوابة حجز الفنادق - تطبيق الجوال',
      debugShowCheckedModeBanner: false,
      locale: const Locale('ar'),
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'Segoe UI',
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF003580), // Booking.com Navy
          primary: const Color(0xFF003580),
          secondary: const Color(0xFFFEBB02), // Booking.com Gold
          surface: Colors.white,
        ),
        scaffoldBackgroundColor: const Color(0xFFF5F7FA),
      ),
      home: const Directionality(
        textDirection: TextDirection.rtl,
        child: MobileMainScreen(),
      ),
    );
  }
}

// Global Mobile State with Persistent Session Storage
class MobileState {
  static const String defaultProductionApiUrl = 'https://hotel.ahmadinsights.online/api';
  static String apiUrl = defaultProductionApiUrl;
  static String? token;
  static Map<String, dynamic>? currentUser;

  static final ValueNotifier<int> unreadMessagesNotifier = ValueNotifier<int>(0);
  static final ValueNotifier<int> unreadNotificationsNotifier = ValueNotifier<int>(0);

  static bool get isLoggedIn => token != null && currentUser != null;

  static Future<void> loadSession() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final customUrl = prefs.getString('custom_api_url');
      if (customUrl != null && customUrl.trim().isNotEmpty) {
        apiUrl = customUrl.trim();
      } else {
        apiUrl = defaultProductionApiUrl;
      }
      token = prefs.getString('auth_token');
      final userStr = prefs.getString('auth_user');
      if (userStr != null) {
        currentUser = jsonDecode(userStr);
      }
    } catch (_) {}
  }

  static Future<void> refreshProfile() async {
    if (token == null) return;
    try {
      final res = await http.get(
        Uri.parse('$apiUrl/profile'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['user'] != null) {
          currentUser = body['user'];
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_user', jsonEncode(currentUser));
          if (body['user']['unread_messages'] is num) {
            unreadMessagesNotifier.value = (body['user']['unread_messages'] as num).toInt();
          }
          if (body['user']['unread_notifications'] is num) {
            unreadNotificationsNotifier.value = (body['user']['unread_notifications'] as num).toInt();
          }
        }
      }
    } catch (_) {}
  }

  static Future<void> setApiUrl(String newUrl) async {
    apiUrl = newUrl.trim();
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('custom_api_url', apiUrl);
    } catch (_) {}
  }

  static Future<void> saveSession(String newToken, Map<String, dynamic> user) async {
    token = newToken;
    currentUser = user;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', newToken);
      await prefs.setString('auth_user', jsonEncode(user));
    } catch (_) {}
  }

  static Future<void> clearSession() async {
    token = null;
    currentUser = null;
    unreadMessagesNotifier.value = 0;
    unreadNotificationsNotifier.value = 0;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      await prefs.remove('auth_user');
    } catch (_) {}
  }
}

class MobileMainScreen extends StatefulWidget {
  const MobileMainScreen({super.key});

  @override
  State<MobileMainScreen> createState() => _MobileMainScreenState();
}

class _MobileMainScreenState extends State<MobileMainScreen> {
  int _currentNavIndex = 0;

  @override
  void initState() {
    super.initState();
    if (MobileState.isLoggedIn) {
      MobileState.refreshProfile();
      MobileNotificationService.checkNewNotifications();
    }
  }

  void _onLoginChanged() {
    if (MobileState.isLoggedIn) {
      MobileState.refreshProfile();
      MobileNotificationService.checkNewNotifications();
    }
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      ExploreScreen(onOpenAuth: _openAuthModal),
      const OffersScreen(),
      MyBookingsScreen(onOpenAuth: _openAuthModal),
      MessengerScreen(onOpenAuth: _openAuthModal),
      ProfileScreen(onOpenAuth: _openAuthModal, onLoginChanged: _onLoginChanged),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentNavIndex,
        children: screens,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentNavIndex,
        onDestinationSelected: (idx) {
          setState(() => _currentNavIndex = idx);
        },
        backgroundColor: Colors.white,
        elevation: 8,
        indicatorColor: const Color(0xFFE6EFFD),
        destinations: [
          const NavigationDestination(icon: Icon(Icons.search), label: 'استكشاف'),
          const NavigationDestination(icon: Icon(Icons.local_offer_outlined), label: 'العروض'),
          const NavigationDestination(icon: Icon(Icons.calendar_month_outlined), label: 'حجوزاتي'),
          ValueListenableBuilder<int>(
            valueListenable: MobileState.unreadMessagesNotifier,
            builder: (ctx, unreadCount, _) => NavigationDestination(
              icon: Badge.count(
                count: unreadCount,
                isLabelVisible: unreadCount > 0,
                backgroundColor: Colors.redAccent,
                child: const Icon(Icons.chat_bubble_outline),
              ),
              selectedIcon: Badge.count(
                count: unreadCount,
                isLabelVisible: unreadCount > 0,
                backgroundColor: Colors.redAccent,
                child: const Icon(Icons.chat_bubble),
              ),
              label: 'الرسائل',
            ),
          ),
          const NavigationDestination(icon: Icon(Icons.person_outline), label: 'حسابي'),
        ],
      ),
    );
  }

  void _openAuthModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AuthBottomSheet(onSuccess: () {
          Navigator.pop(ctx);
          _onLoginChanged();
        }),
      ),
    );
  }
}

// ============================================================================
// 1. EXPLORE SCREEN (Booking.com style UX)
// ============================================================================
class ExploreScreen extends StatefulWidget {
  final VoidCallback onOpenAuth;
  const ExploreScreen({super.key, required this.onOpenAuth});

  @override
  State<ExploreScreen> createState() => _ExploreScreenState();
}

class _ExploreScreenState extends State<ExploreScreen> {
  final _searchCtrl = TextEditingController();
  final _scrollController = ScrollController();
  String _selectedCity = '';
  String _selectedAmenity = '';
  String _sortBy = 'default';
  DateTime? _checkInDate;
  DateTime? _checkOutDate;
  int _unreadCount = 0;
  bool _isLoading = true;
  List<dynamic> _hotels = [];
  List<dynamic> _destinations = [];
  List<dynamic> _banners = [];

  static const List<String> _syrianProvinces = [
    'دمشق',
    'ريف دمشق',
    'اللاذقية',
    'طرطوس',
    'حلب',
    'حمص',
    'حماة',
    'السويداء',
    'درعا',
    'القنيطرة',
    'دير الزور',
    'الحسكة',
    'الرقة',
    'إدلب',
  ];

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _fetchDestinations();
    _fetchBanners();
    _fetchHotels();
    _fetchUnreadNotifications();
  }

  Future<void> _fetchUnreadNotifications() async {
    if (!MobileState.isLoggedIn) return;
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/notifications'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _unreadCount = body['unread_count'] ?? 0;
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _fetchDestinations() async {
    try {
      final res = await http.get(Uri.parse('${MobileState.apiUrl}/destinations'));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _destinations = body['data'] ?? []);
      }
    } catch (_) {}
  }

  Future<void> _fetchBanners() async {
    try {
      final res = await http.get(Uri.parse('${MobileState.apiUrl}/app-banners'));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _banners = body['data'] ?? []);
      }
    } catch (_) {}
  }

  String _formatDate(DateTime d) {
    return '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  }

  String _formatDateArabic(DateTime d) {
    const months = [
      'كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران',
      'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
    ];
    return '${d.day} ${months[d.month - 1]}';
  }

  Future<void> _pickBookingDates() async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final firstDate = today;
    final lastDate = today.add(const Duration(days: 365));
    final initialRange = DateTimeRange(
      start: (_checkInDate != null && !_checkInDate!.isBefore(today)) ? DateTime(_checkInDate!.year, _checkInDate!.month, _checkInDate!.day) : today,
      end: (_checkOutDate != null && !_checkOutDate!.isBefore(today)) ? DateTime(_checkOutDate!.year, _checkOutDate!.month, _checkOutDate!.day) : today.add(const Duration(days: 2)),
    );

    final picked = await showDateRangePicker(
      context: context,
      firstDate: firstDate,
      lastDate: lastDate,
      initialDateRange: initialRange,
      helpText: 'اختر تاريخ الوصول وتاريخ المغادرة',
      cancelText: 'إلغاء',
      confirmText: 'تطبيق الفلتر',
      saveText: 'تأكيد',
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF003580),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Color(0xFF0F172A),
              secondary: Color(0xFFFEBB02),
            ),
          ),
          child: Directionality(
            textDirection: TextDirection.rtl,
            child: child!,
          ),
        );
      },
    );

    if (picked != null) {
      setState(() {
        _checkInDate = picked.start;
        _checkOutDate = picked.end;
      });
      _fetchHotels();
    }
  }

  void _selectProvince(String prov) {
    setState(() {
      if (_selectedCity == prov) {
        _selectedCity = '';
      } else {
        _selectedCity = prov;
      }
    });
    _fetchHotels();
    if (_selectedCity.isNotEmpty && _scrollController.hasClients) {
      Future.delayed(const Duration(milliseconds: 150), () {
        if (_scrollController.hasClients) {
          _scrollController.animateTo(
            490.0,
            duration: const Duration(milliseconds: 400),
            curve: Curves.easeOutCubic,
          );
        }
      });
    }
  }

  void _openProvinceSelector() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: Container(
          height: MediaQuery.of(context).size.height * 0.75,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.only(top: 10, bottom: 8),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.location_city, color: Color(0xFF003580)),
                        SizedBox(width: 8),
                        Text(
                          'تحديد المحافظة والوجهة',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              ListTile(
                leading: CircleAvatar(
                  backgroundColor: _selectedCity.isEmpty ? const Color(0xFF003580) : const Color(0xFFF1F5F9),
                  child: Icon(
                    Icons.map,
                    color: _selectedCity.isEmpty ? Colors.white : const Color(0xFF003580),
                    size: 20,
                  ),
                ),
                title: const Text('جميع المحافظات السورية', style: TextStyle(fontWeight: FontWeight.bold)),
                subtitle: const Text('عرض جميع الفنادق والمنتجعات دون تصفية', style: TextStyle(fontSize: 11)),
                trailing: _selectedCity.isEmpty
                    ? const Icon(Icons.check_circle, color: Color(0xFF003580))
                    : null,
                onTap: () {
                  Navigator.pop(ctx);
                  _selectProvince('');
                },
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView.separated(
                  itemCount: _syrianProvinces.length,
                  separatorBuilder: (c, i) => const Divider(height: 1, indent: 64),
                  itemBuilder: (ctx, idx) {
                    final prov = _syrianProvinces[idx];
                    final isSel = _selectedCity == prov;
                    final dest = _destinations.firstWhere(
                      (d) => d['name'] == prov,
                      orElse: () => null,
                    );
                    final count = dest != null ? (dest['count'] ?? 0) : null;

                    return ListTile(
                      leading: CircleAvatar(
                        backgroundColor: isSel ? const Color(0xFF003580) : const Color(0xFFF1F5F9),
                        child: Text(
                          prov.characters.take(1).toString(),
                          style: TextStyle(
                            color: isSel ? Colors.white : const Color(0xFF003580),
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      title: Text(
                        'محافظة $prov',
                        style: TextStyle(
                          fontWeight: isSel ? FontWeight.bold : FontWeight.w500,
                          color: isSel ? const Color(0xFF003580) : const Color(0xFF0F172A),
                        ),
                      ),
                      subtitle: count != null ? Text('$count أماكن إقامة متوفرة', style: const TextStyle(fontSize: 11)) : null,
                      trailing: isSel
                          ? const Icon(Icons.check_circle, color: Color(0xFF003580))
                          : const Icon(Icons.arrow_back_ios_new, size: 14, color: Colors.grey),
                      onTap: () {
                        Navigator.pop(ctx);
                        _selectProvince(prov);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _openComplaintsSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: ComplaintsBottomSheet(
          hotels: _hotels,
          onOpenAuth: widget.onOpenAuth,
        ),
      ),
    );
  }

  void _openNotificationsSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: NotificationsBottomSheet(
          onOpenAuth: widget.onOpenAuth,
          onUpdated: _fetchUnreadNotifications,
        ),
      ),
    );
  }

  Future<void> _fetchHotels() async {
    setState(() => _isLoading = true);
    try {
      final uri = Uri.parse('${MobileState.apiUrl}/hotels').replace(queryParameters: {
        if (_searchCtrl.text.isNotEmpty) 'search': _searchCtrl.text.trim(),
        if (_selectedCity.isNotEmpty) 'province': _selectedCity,
        if (_selectedAmenity.isNotEmpty) 'amenity': _selectedAmenity,
        if (_sortBy != 'default') 'sort': _sortBy,
        if (_checkInDate != null) 'check_in': _formatDate(_checkInDate!),
        if (_checkOutDate != null) 'check_out': _formatDate(_checkOutDate!),
      });

      final res = await http.get(uri);
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _hotels = body['data']['data'] ?? []);
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _openSettingsMenu() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'خيارات وإعدادات التطبيق',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(ctx),
                    icon: const Icon(Icons.close, color: Colors.grey),
                  ),
                ],
              ),
              const Divider(height: 20),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.language, color: Color(0xFF003580)),
                ),
                title: const Text('اللغة (Language)', style: TextStyle(fontWeight: FontWeight.bold)),
                subtitle: const Text('العربية (افتراضي) / English', style: TextStyle(fontSize: 12)),
                trailing: const Icon(Icons.chevron_left),
                onTap: () {
                  Navigator.pop(ctx);
                  _showLanguageDialog();
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: Colors.amber.shade50, borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.info_outline, color: Color(0xFFD97706)),
                ),
                title: const Text('حول التطبيق (About)', style: TextStyle(fontWeight: FontWeight.bold)),
                subtitle: const Text('إصدار التطبيق وبيانات المطور المهندس أحمد العلي', style: TextStyle(fontSize: 12)),
                trailing: const Icon(Icons.chevron_left),
                onTap: () {
                  Navigator.pop(ctx);
                  _showAboutDialog();
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.help_outline, color: Colors.green),
                ),
                title: const Text('مساعدة وتعليمات الاستخدام (Help)', style: TextStyle(fontWeight: FontWeight.bold)),
                subtitle: const Text('شرح مفصل لطريقة الفلترة والحجز وميزات المنظومة', style: TextStyle(fontSize: 12)),
                trailing: const Icon(Icons.chevron_left),
                onTap: () {
                  Navigator.pop(ctx);
                  _showHelpDialog();
                },
              ),
              const Divider(height: 16),
              ListTile(
                dense: true,
                leading: const Icon(Icons.dns_outlined, size: 20, color: Colors.grey),
                title: const Text('إعدادات متقدمة (عنوان خادم API)', style: TextStyle(fontSize: 13, color: Colors.grey)),
                subtitle: Text(MobileState.apiUrl, style: const TextStyle(fontSize: 11, color: Colors.grey), maxLines: 1, overflow: TextOverflow.ellipsis),
                onTap: () {
                  Navigator.pop(ctx);
                  _showServerSettings();
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showLanguageDialog() {
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Row(
            children: [
              Icon(Icons.language, color: Color(0xFF003580)),
              SizedBox(width: 8),
              Text('اختر لغة التطبيق'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              RadioListTile<String>(
                title: const Text('العربية (Arabic) 🇸🇾'),
                value: 'ar',
                groupValue: 'ar',
                activeColor: const Color(0xFF003580),
                onChanged: (_) {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('اللغة العربية مفعلة حالياً.')),
                  );
                },
              ),
              RadioListTile<String>(
                title: const Text('English 🇬🇧'),
                value: 'en',
                groupValue: 'ar',
                activeColor: const Color(0xFF003580),
                onChanged: (_) {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('سيتم تفعيل الواجهة الإنجليزية بالكامل في التحديث القادم.')),
                  );
                },
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إغلاق'),
            ),
          ],
        ),
      ),
    );
  }

  void _showAboutDialog() {
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: const Color(0x1A003580), borderRadius: BorderRadius.circular(12)),
                child: const Text('🏨', style: TextStyle(fontSize: 22)),
              ),
              const SizedBox(width: 10),
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('حول التطبيق', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF003580))),
                  Text('بوابة حجز الفنادق الذكية', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.verified, size: 18, color: Color(0xFF003580)),
                        SizedBox(width: 6),
                        Text('رقم الإصدار: ', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        Text('v1.2.0 (Build 2026.10)', style: TextStyle(color: Color(0xFF003580), fontWeight: FontWeight.bold, fontSize: 13)),
                      ],
                    ),
                    const SizedBox(height: 10),
                    const Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.engineering, size: 18, color: Color(0xFFD97706)),
                        SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'بناء وتطوير المهندس احمد العلي',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        const Icon(Icons.email_outlined, size: 18, color: Colors.blueGrey),
                        const SizedBox(width: 6),
                        const Expanded(
                          child: Text(
                            'ahmad19ali87@gmail.com',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF003580)),
                          ),
                        ),
                        InkWell(
                          onTap: () async {
                            final uri = Uri.parse('mailto:ahmad19ali87@gmail.com');
                            if (await canLaunchUrl(uri)) {
                              await launchUrl(uri);
                            }
                          },
                          child: const Padding(
                            padding: EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                            child: Icon(Icons.send, size: 16, color: Color(0xFF003580)),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'المنظومة السحابية المتكاملة لحجز الفنادق وتشغيل المنشآت السياحية في سوريا، تدعم استعراض الغرف الشاغرة بالتواريخ، والأسعار المزدوجة (ل.س / USD)، وتوجيه الخرائط والمحادثات المباشرة.',
                style: TextStyle(fontSize: 12, height: 1.5, color: Color(0xFF475569)),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF003580),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () => Navigator.pop(ctx),
              child: const Text('حسناً'),
            ),
          ],
        ),
      ),
    );
  }

  void _showHelpDialog() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: Container(
          height: MediaQuery.of(context).size.height * 0.85,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.help_outline, color: Color(0xFF003580), size: 24),
                        SizedBox(width: 8),
                        Text(
                          'دليل المساعدة واستخدام التطبيق',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                        ),
                      ],
                    ),
                    IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
                  ],
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    _buildHelpCard(
                      icon: '🔍',
                      title: '1. البحث وفلترة الغرف المتاحة بالتواريخ',
                      content: 'من شريط البحث أعلى الشاشة الرئيسية، حدد تاريخ الوصول وتاريخ المغادرة ثم انقر "تطبيق الفلتر". سيقوم التطبيق باستبعاد جميع الغرف المحجوزة مسبقاً وعرض الغرف الشاغرة فقط لتلك المدة، وعند فتح الفندق ستظهر لك فقط الغرف القابلة للحجز وفق تاريخك.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '💵',
                      title: '2. الأسعار المزدوجة (ل.س / USD)',
                      content: 'تتيح لك المنظومة استعراض تكاليف الإقامة بالليرة السورية أو الدولار الأمريكي. يتم التحويل تلقائياً وفق أحدث نشرة أسعار معتمدة رسمياً بالنظام.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '📍',
                      title: '3. الوصول للفندق عبر خرائط Google',
                      content: 'عند استعراض أي فندق، انقر على زر "الموقع والاتجاهات (Google Maps)" وسيتم فتح تطبيق الخرائط فورياً ليرسم لك المسار الدقيق للوصول إلى بوابة الفندق.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '💬',
                      title: '4. الرسائل والتواصل المباشر مع الفندق',
                      content: 'يمكنك إرسال استفسار خاص لإدارة كل فندق عبر زر "مراسلة الفندق". وتظهر كافة محادثاتك منظمة في تبويب "الرسائل" أسفل الشاشة مثل الماسنجر لتلقي الردود ومتابعتها بسهولة.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '⭐',
                      title: '5. التقييمات والردود',
                      content: 'بعد إتمام إقامتك، يمكنك تقييم الفندق وإضافة رأيك وتجربتك. كما تستطيع إدارة الفندق الرد على تقييمك والتعليق عليه بما يضمن أفضل جودة خدمة.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '🔔',
                      title: '6. الإشعارات خارج التطبيق',
                      content: 'يعمل نظام الإشعارات حتى عند إغلاق التطبيق لينبهك فوراً بأي تحديث على حجزك، أو استلام رسالة جديدة من الفندق، أو وصول عروض وخصومات مميزة.',
                    ),
                    const SizedBox(height: 12),
                    _buildHelpCard(
                      icon: '🔑',
                      title: '7. التسجيل والتفعيل وGoogle',
                      content: 'عند التسجيل بالبريد، يصلك كود التحقق المكون من 6 أرقام من info@ahmadinsights.online. كما يمكنك التسجيل السريع المباشر بحسابك الحقيقي على Google بنقرة زر واحدة.',
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHelpCard({required String icon, required String title, required String content}) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(icon, style: const TextStyle(fontSize: 20)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF003580)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            content,
            style: const TextStyle(fontSize: 12.5, height: 1.5, color: Color(0xFF334155)),
          ),
        ],
      ),
    );
  }

  void _showServerSettings() {
    final ctrl = TextEditingController(text: MobileState.apiUrl);
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text('إعدادات خادم النظام (API)'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('يعمل التطبيق افتراضياً وسحابياً على:', style: TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 4),
              const Text(
                MobileState.defaultProductionApiUrl,
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: ctrl,
                decoration: const InputDecoration(
                  labelText: 'عنوان الخادم الحالي',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.link),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                ctrl.text = MobileState.defaultProductionApiUrl;
              },
              child: const Text('استعادة الافتراضي'),
            ),
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
              onPressed: () async {
                await MobileState.setApiUrl(ctrl.text.trim());
                if (!ctx.mounted) return;
                Navigator.pop(ctx);
                _fetchHotels();
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('تم حفظ وتحديث عنوان الخادم: ${MobileState.apiUrl}')),
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
    return CustomScrollView(
      controller: _scrollController,
      slivers: [
        // Booking.com Navy Header & Top Actions
        SliverAppBar(
          pinned: true,
          backgroundColor: const Color(0xFF003580),
          title: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Text('Booking', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20)),
                  Text('.sy', style: TextStyle(color: Color(0xFFFEBB02), fontWeight: FontWeight.bold, fontSize: 20)),
                ],
              ),
              Row(
                children: [
                  // Complaints Button (زر الشكاوي)
                  IconButton(
                    icon: const Icon(Icons.support_agent, color: Colors.white),
                    tooltip: 'الشكاوى والمقترحات',
                    onPressed: _openComplaintsSheet,
                  ),
                  // Notifications Button (زر الإشعارات)
                  ValueListenableBuilder<int>(
                    valueListenable: MobileState.unreadNotificationsNotifier,
                    builder: (ctx, notifCount, _) {
                      final effectiveCount = notifCount > 0 ? notifCount : _unreadCount;
                      return Badge.count(
                        count: effectiveCount,
                        isLabelVisible: effectiveCount > 0,
                        backgroundColor: Colors.redAccent,
                        child: IconButton(
                          icon: const Icon(Icons.notifications_outlined, color: Colors.white),
                          tooltip: 'الإشعارات والتنبيهات',
                          onPressed: _openNotificationsSheet,
                        ),
                      );
                    },
                  ),
                  IconButton(
                    onPressed: _openSettingsMenu,
                    tooltip: 'خيارات وإعدادات التطبيق',
                    icon: const Icon(Icons.settings_outlined, color: Colors.white70),
                  ),
                  if (!MobileState.isLoggedIn)
                    TextButton(
                      onPressed: widget.onOpenAuth,
                      style: TextButton.styleFrom(
                        backgroundColor: const Color(0xFFFEBB02),
                        foregroundColor: const Color(0xFF003580),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      ),
                      child: const Text('دخول', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    )
                  else
                    const CircleAvatar(
                      radius: 15,
                      backgroundColor: Color(0xFFFEBB02),
                      child: Icon(Icons.person, color: Color(0xFF003580), size: 18),
                    ),
                ],
              ),
            ],
          ),
        ),

        // Hero Header: Search Box & Booking Date Filter Card (فلتر تاريخ الحجز)
        SliverToBoxAdapter(
          child: Container(
            color: const Color(0xFF003580),
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'اعثر على مكان إقامتك المثالي',
                  style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 2),
                const Text(
                  'اختر مواعيد الحجز، تصفح التقييمات، وتواصل مباشرة مع إدارة الفندق',
                  style: TextStyle(color: Color(0xFFE2E8F0), fontSize: 11),
                ),
                const SizedBox(height: 12),

                // Search Input Box
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFFEBB02), width: 1.5),
                    boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4)],
                  ),
                  child: TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'إلى أين وجهتك؟ (اسم الفندق أو المدينة)',
                      hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                      prefixIcon: const Icon(Icons.search, color: Color(0xFF003580), size: 20),
                      suffixIcon: IconButton(
                        icon: const Icon(Icons.arrow_back_ios_new, size: 14, color: Color(0xFF003580)),
                        onPressed: _fetchHotels,
                      ),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onSubmitted: (_) => _fetchHotels(),
                  ),
                ),
                const SizedBox(height: 8),

                // Province Filter Card (فلتر تحديد المحافظة والوجهة)
                Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(10),
                    onTap: _openProvinceSelector,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: _selectedCity.isNotEmpty ? const Color(0xFFFEBB02) : const Color(0xFFCBD5E1),
                          width: _selectedCity.isNotEmpty ? 2 : 1,
                        ),
                        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4)],
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.location_city_rounded,
                            color: _selectedCity.isNotEmpty ? const Color(0xFF003580) : const Color(0xFF64748B),
                            size: 22,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('المحافظة والوجهة السياحية (فلتر المحافظة):', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                Text(
                                  _selectedCity.isNotEmpty ? 'محافظة $_selectedCity' : 'جميع المحافظات السورية (اضغط للتحديد)',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: _selectedCity.isNotEmpty ? FontWeight.bold : FontWeight.w500,
                                    color: _selectedCity.isNotEmpty ? const Color(0xFF003580) : const Color(0xFF334155),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (_selectedCity.isNotEmpty)
                            IconButton(
                              icon: const Icon(Icons.cancel, size: 18, color: Colors.red),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              tooltip: 'إلغاء فلتر المحافظة',
                              onPressed: () => _selectProvince(''),
                            )
                          else
                            const Icon(Icons.keyboard_arrow_down, color: Color(0xFF003580), size: 22),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 8),

                // Booking Date Filter Card (فلتر تاريخ الحجز)
                Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(10),
                    onTap: _pickBookingDates,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: _checkInDate != null ? const Color(0xFFFEBB02) : const Color(0xFFCBD5E1),
                          width: _checkInDate != null ? 2 : 1,
                        ),
                        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4)],
                      ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_month, color: Color(0xFF003580), size: 22),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('تواريخ الإقامة والحجز (فلتر التوفر):', style: TextStyle(fontSize: 10, color: Colors.grey)),
                              Text(
                                _checkInDate != null && _checkOutDate != null
                                    ? '${_formatDateArabic(_checkInDate!)}  ➔  ${_formatDateArabic(_checkOutDate!)} (${_checkOutDate!.difference(_checkInDate!).inDays} ليالٍ)'
                                    : 'اضغط لاختيار تاريخ الوصول وتاريخ المغادرة',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: _checkInDate != null ? FontWeight.bold : FontWeight.w500,
                                  color: _checkInDate != null ? const Color(0xFF003580) : const Color(0xFF334155),
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (_checkInDate != null)
                          IconButton(
                            icon: const Icon(Icons.cancel, size: 18, color: Colors.red),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            tooltip: 'إلغاء فلتر التواريخ',
                            onPressed: () {
                              setState(() {
                                _checkInDate = null;
                                _checkOutDate = null;
                              });
                              _fetchHotels();
                            },
                          )
                        else
                          const Icon(Icons.arrow_drop_down, color: Color(0xFF003580), size: 22),
                      ],
                    ),
                  ),
                ),
              ),
            ],
            ),
          ),
        ),

        // Filter Chips Row
        SliverToBoxAdapter(
          child: Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  if (_selectedCity.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(left: 6),
                      child: InputChip(
                        avatar: const Icon(Icons.location_on, size: 16, color: Color(0xFF003580)),
                        label: Text(
                          '📍 محافظة $_selectedCity',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                        ),
                        backgroundColor: const Color(0xFFFEBB02).withValues(alpha: 0.25),
                        onDeleted: () => _selectProvince(''),
                      ),
                    ),
                  if (_checkInDate != null)
                    Padding(
                      padding: const EdgeInsets.only(left: 6),
                      child: InputChip(
                        avatar: const Icon(Icons.calendar_month, size: 16, color: Color(0xFF003580)),
                        label: Text(
                          '📅 ${_formatDateArabic(_checkInDate!)} - ${_formatDateArabic(_checkOutDate!)}',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                        ),
                        backgroundColor: const Color(0xFFFEBB02).withValues(alpha: 0.25),
                        onDeleted: () {
                          setState(() {
                            _checkInDate = null;
                            _checkOutDate = null;
                          });
                          _fetchHotels();
                        },
                      ),
                    ),
                  _buildFilterChip('الكل', _selectedCity.isEmpty && _selectedAmenity.isEmpty && _checkInDate == null, () {
                    setState(() {
                      _selectedCity = '';
                      _selectedAmenity = '';
                      _sortBy = 'default';
                      _checkInDate = null;
                      _checkOutDate = null;
                    });
                    _fetchHotels();
                  }),
                  _buildFilterChip('🎧 الشكاوى والمقترحات', false, _openComplaintsSheet),
                  _buildFilterChip('🔔 الإشعارات', false, _openNotificationsSheet),
                  _buildFilterChip('⭐ الأعلى تقييماً', _sortBy == 'rating_desc' || _sortBy == 'stars_desc', () {
                    setState(() => _sortBy = (_sortBy == 'rating_desc') ? 'default' : 'rating_desc');
                    _fetchHotels();
                  }),
                  _buildFilterChip('🏷️ الأقل سعراً', _sortBy == 'price_asc', () {
                    setState(() => _sortBy = (_sortBy == 'price_asc') ? 'default' : 'price_asc');
                    _fetchHotels();
                  }),
                  _buildFilterChip('🏊 مسبح', _selectedAmenity == 'مسبح', () {
                    setState(() => _selectedAmenity = (_selectedAmenity == 'مسبح') ? '' : 'مسبح');
                    _fetchHotels();
                  }),
                  _buildFilterChip('📶 واي فاي', _selectedAmenity == 'واي فاي', () {
                    setState(() => _selectedAmenity = (_selectedAmenity == 'واي فاي') ? '' : 'واي فاي');
                    _fetchHotels();
                  }),
                  _buildFilterChip('☕ إفطار', _selectedAmenity == 'إفطار', () {
                    setState(() => _selectedAmenity = (_selectedAmenity == 'إفطار') ? '' : 'إفطار');
                    _fetchHotels();
                  }),
                ],
              ),
            ),
          ),
        ),

        // Destinations Carousel Section
        if (_destinations.isNotEmpty) ...[
          const SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.fromLTRB(16, 20, 16, 10),
              child: Text(
                'استكشف أشهر الوجهات والمحافظات',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: SizedBox(
              height: 125,
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                scrollDirection: Axis.horizontal,
                itemCount: _destinations.length,
                itemBuilder: (ctx, idx) {
                  final d = _destinations[idx];
                  final isSelected = _selectedCity == d['name'];
                  return GestureDetector(
                    onTap: () => _selectProvince(d['name'] ?? ''),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      width: 145,
                      margin: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: isSelected ? const Color(0xFFFEBB02) : Colors.transparent,
                          width: isSelected ? 3 : 1,
                        ),
                        boxShadow: isSelected
                            ? [
                                BoxShadow(
                                  color: const Color(0xFFFEBB02).withValues(alpha: 0.5),
                                  blurRadius: 8,
                                  spreadRadius: 1,
                                )
                              ]
                            : [
                                const BoxShadow(
                                  color: Colors.black12,
                                  blurRadius: 4,
                                  offset: Offset(0, 2),
                                )
                              ],
                        image: DecorationImage(
                          image: NetworkImage(d['image'] ?? 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?w=600'),
                          fit: BoxFit.cover,
                          colorFilter: ColorFilter.mode(
                            isSelected ? Colors.black.withValues(alpha: 0.2) : Colors.black.withValues(alpha: 0.38),
                            BlendMode.darken,
                          ),
                        ),
                      ),
                      child: Stack(
                        children: [
                          if (isSelected)
                            Positioned(
                              top: 8,
                              right: 8,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEBB02),
                                  borderRadius: BorderRadius.circular(12),
                                  boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 4)],
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.check, size: 12, color: Color(0xFF003580)),
                                    SizedBox(width: 3),
                                    Text(
                                      'محددة',
                                      style: TextStyle(
                                        color: Color(0xFF003580),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 10,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          Padding(
                            padding: const EdgeInsets.all(10),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.end,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  d['name'] ?? '',
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                                ),
                                Text(
                                  '${d['count'] ?? 0} فندق متوفر',
                                  style: const TextStyle(color: Colors.white70, fontSize: 10),
                                ),
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

        // Promotional Ads & Banners Section ("أشهر الواجهات والكلمات الإعلانية")
        if (_banners.isNotEmpty) ...[
          const SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.fromLTRB(16, 20, 16, 10),
              child: Row(
                children: [
                  Text(
                    'أشهر الواجهات والكلمات الإعلانية',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                  SizedBox(width: 6),
                  Text('✨', style: TextStyle(fontSize: 16)),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: SizedBox(
              height: 165,
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                scrollDirection: Axis.horizontal,
                itemCount: _banners.length,
                itemBuilder: (ctx, idx) {
                  final b = _banners[idx];
                  return GestureDetector(
                    onTap: () {
                      if (b['link_type'] == 'destination' && b['link_value'] != null) {
                        _selectProvince(b['link_value'].toString());
                      } else if (b['link_type'] == 'hotel' && b['link_value'] != null) {
                        final hotelId = int.tryParse(b['link_value'].toString());
                        if (hotelId != null) {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => Directionality(
                                textDirection: TextDirection.rtl,
                                child: HotelDetailScreen(
                                  hotelId: hotelId,
                                  onOpenAuth: widget.onOpenAuth,
                                  initialCheckInDate: _checkInDate,
                                  initialCheckOutDate: _checkOutDate,
                                ),
                              ),
                            ),
                          );
                        }
                      }
                    },
                    child: Container(
                      width: 290,
                      margin: const EdgeInsets.symmetric(horizontal: 5),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.1),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                        image: b['image'] != null
                            ? DecorationImage(
                                image: NetworkImage(b['image']),
                                fit: BoxFit.cover,
                                colorFilter: ColorFilter.mode(Colors.black.withValues(alpha: 0.45), BlendMode.darken),
                              )
                            : null,
                        color: b['image'] == null ? const Color(0xFF003580) : null,
                      ),
                      child: Stack(
                        children: [
                          // Badge
                          Positioned(
                            top: 10,
                            right: 10,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEBB02),
                                borderRadius: BorderRadius.circular(8),
                                boxShadow: [
                                  BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 4),
                                ],
                              ),
                              child: Text(
                                b['badge'] ?? 'أشهر الواجهات',
                                style: const TextStyle(
                                  color: Color(0xFF003580),
                                  fontWeight: FontWeight.bold,
                                  fontSize: 10,
                                ),
                              ),
                            ),
                          ),
                          // Content (Words & Taglines)
                          Positioned(
                            bottom: 12,
                            left: 12,
                            right: 12,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  b['title'] ?? '',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                    shadows: [Shadow(color: Colors.black54, blurRadius: 4)],
                                  ),
                                ),
                                if (b['subtitle'] != null && b['subtitle'].toString().isNotEmpty) ...[
                                  const SizedBox(height: 3),
                                  Text(
                                    b['subtitle'],
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Colors.white70,
                                      fontSize: 11,
                                      height: 1.25,
                                      shadows: [Shadow(color: Colors.black54, blurRadius: 4)],
                                    ),
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

        // Hotels List Title
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      Text(
                        _selectedCity.isEmpty ? 'جميع الفنادق والمنتجعات' : 'فنادق محافظة $_selectedCity',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      if (_selectedCity.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        InkWell(
                          onTap: () => _selectProvince(''),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text('إلغاء الفلتر ✕', style: TextStyle(fontSize: 10, color: Colors.red, fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                Text('${_hotels.length} مكان إقامة', style: const TextStyle(fontSize: 12, color: Colors.grey)),
              ],
            ),
          ),
        ),

        // Hotels List
        if (_isLoading)
          const SliverFillRemaining(
            child: Center(child: CircularProgressIndicator()),
          )
        else if (_hotels.isEmpty)
          SliverFillRemaining(
            hasScrollBody: false,
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.hotel_outlined, size: 64, color: Colors.grey),
                    const SizedBox(height: 12),
                    Text(
                      _selectedCity.isNotEmpty
                          ? 'لا توجد فنادق متاحة حالياً في محافظة $_selectedCity.'
                          : 'لا توجد فنادق تطابق معايير البحث الحالية.',
                      style: const TextStyle(color: Colors.grey, fontSize: 14),
                      textAlign: TextAlign.center,
                    ),
                    if (_selectedCity.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      ElevatedButton.icon(
                        onPressed: () => _selectProvince(''),
                        icon: const Icon(Icons.clear_all, size: 16),
                        label: const Text('عرض جميع المحافظات'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF003580),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          )
        else
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (ctx, idx) {
                final h = _hotels[idx];
                return HotelCard(
                  hotel: h,
                  onOpenAuth: widget.onOpenAuth,
                  checkInDate: _checkInDate,
                  checkOutDate: _checkOutDate,
                );
              },
              childCount: _hotels.length,
            ),
          ),
      ],
    );
  }

  Widget _buildFilterChip(String label, bool isSelected, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: FilterChip(
        label: Text(label, style: TextStyle(fontSize: 12, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
        selected: isSelected,
        onSelected: (_) => onTap(),
        selectedColor: const Color(0xFF003580),
        labelStyle: TextStyle(color: isSelected ? Colors.white : const Color(0xFF0F172A)),
        backgroundColor: const Color(0xFFF1F5F9),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
    );
  }
}

// ============================================================================
// 2. HOTEL CARD COMPONENT (Booking.com style)
// ============================================================================
class HotelCard extends StatelessWidget {
  final Map<String, dynamic> hotel;
  final VoidCallback onOpenAuth;
  final DateTime? checkInDate;
  final DateTime? checkOutDate;

  const HotelCard({
    super.key,
    required this.hotel,
    required this.onOpenAuth,
    this.checkInDate,
    this.checkOutDate,
  });

  String _ratingQuality(double r) {
    if (r >= 4.7) return 'استثنائي';
    if (r >= 4.3) return 'رائع';
    if (r >= 4.0) return 'جيد جداً';
    if (r >= 3.5) return 'جيد';
    return 'مقبول';
  }

  @override
  Widget build(BuildContext context) {
    final double ratingNum = (hotel['rating'] is num) ? (hotel['rating'] as num).toDouble() : 4.8;
    final int reviewsCount = (hotel['reviews_count'] is num) ? (hotel['reviews_count'] as num).toInt() : 14;
    final recentReviews = (hotel['recent_reviews'] as List?) ?? [];

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (ctx) => Directionality(
                textDirection: TextDirection.rtl,
                child: HotelDetailScreen(
                  hotelId: hotel['id'],
                  onOpenAuth: onOpenAuth,
                  initialCheckInDate: checkInDate,
                  initialCheckOutDate: checkOutDate,
                ),
              ),
            ),
          );
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Hotel Image & Rating Tag
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                  child: AspectRatio(
                    aspectRatio: 16 / 9,
                    child: Image.network(
                      hotel['cover_image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => Container(
                        color: const Color(0xFF003580),
                        child: const Center(child: Icon(Icons.hotel, color: Colors.white, size: 48)),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF003580),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.star, color: Color(0xFFFEBB02), size: 14),
                        const SizedBox(width: 4),
                        Text(
                          '${hotel['stars'] ?? 5} نجوم',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),

            // Info
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          hotel['name'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF003580),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.star, color: Color(0xFFFEBB02), size: 12),
                            const SizedBox(width: 4),
                            Text(
                              ratingNum.toStringAsFixed(1),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.location_on, size: 14, color: Color(0xFF64748B)),
                      const SizedBox(width: 4),
                      Text(
                        hotel['province'] ?? '',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                      ),
                      const SizedBox(width: 10),
                      Container(width: 4, height: 4, decoration: const BoxDecoration(color: Colors.grey, shape: BoxShape.circle)),
                      const SizedBox(width: 10),
                      Text(
                        '${_ratingQuality(ratingNum)} ($reviewsCount تقييم)',
                        style: const TextStyle(color: Color(0xFF003580), fontSize: 12, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    hotel['tagline'] ?? 'إقامة متميزة بأرقى معايير الضيافة',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF475569)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),

                  // Hotel Review Snippet (ظهور الريفيو والتقييمات للفندق)
                  if (recentReviews.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.format_quote_rounded, size: 14, color: Color(0xFF003580)),
                              const SizedBox(width: 4),
                              Text(
                                'تقييم النزيل (${recentReviews[0]['guest_name'] ?? 'نزيل'}):',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1E293B)),
                              ),
                              const Spacer(),
                              Row(
                                children: List.generate(
                                  (recentReviews[0]['rating'] is int ? recentReviews[0]['rating'] as int : 5),
                                  (_) => const Icon(Icons.star, size: 11, color: Color(0xFFFEBB02)),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            '"${recentReviews[0]['comment'] ?? ''}"',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF475569), fontStyle: FontStyle.italic),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ],

                  // Button to view all reviews modal
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: InkWell(
                      onTap: () {
                        showModalBottomSheet(
                          context: context,
                          isScrollControlled: true,
                          backgroundColor: Colors.transparent,
                          builder: (ctx) => Directionality(
                            textDirection: TextDirection.rtl,
                            child: HotelReviewsBottomSheet(
                              hotelId: hotel['id'],
                              hotelName: hotel['name'] ?? '',
                              currentRating: ratingNum,
                              reviewsCount: reviewsCount,
                              initialReviews: recentReviews,
                            ),
                          ),
                        );
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.rate_review_outlined, size: 14, color: Color(0xFF003580)),
                            const SizedBox(width: 6),
                            Text(
                              'عرض كافة التقييمات والريفيو ($reviewsCount)',
                              style: const TextStyle(color: Color(0xFF003580), fontWeight: FontWeight.bold, fontSize: 11),
                            ),
                            const SizedBox(width: 4),
                            const Icon(Icons.arrow_forward_ios, size: 10, color: Color(0xFF003580)),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 10),

                  // Dual Currency Pricing Display (Booking.com style)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('يبدأ من لليلة الواحدة:', style: TextStyle(fontSize: 10, color: Colors.grey)),
                          Text('شامل الضرائب والرسوم', style: TextStyle(fontSize: 10, color: Colors.green, fontWeight: FontWeight.w600)),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          if (hotel['min_price_usd'] != null)
                            Text(
                              '\$${hotel['min_price_usd']}',
                              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                            ),
                          if (hotel['min_price_syp'] != null)
                            Text(
                              '${hotel['min_price_syp']} ل.س',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF059669)),
                            ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  const Divider(height: 1),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF003580),
                            side: const BorderSide(color: Color(0xFF003580)),
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: () => openHotelMessageDialog(context, hotel, onOpenAuth),
                          icon: const Icon(Icons.chat_bubble_outline, size: 15),
                          label: const Text('مراسلة الفندق', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF16A34A),
                          side: const BorderSide(color: Color(0xFF16A34A)),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: () => openGoogleMaps(context, hotel),
                        icon: const Icon(Icons.location_on, size: 15),
                        label: const Text('الخريطة ↗', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ],
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
// 3. HOTEL DETAIL & BOOKING SCREEN
// ============================================================================
class HotelDetailScreen extends StatefulWidget {
  final int hotelId;
  final VoidCallback onOpenAuth;
  final DateTime? initialCheckInDate;
  final DateTime? initialCheckOutDate;

  const HotelDetailScreen({
    super.key,
    required this.hotelId,
    required this.onOpenAuth,
    this.initialCheckInDate,
    this.initialCheckOutDate,
  });

  @override
  State<HotelDetailScreen> createState() => _HotelDetailScreenState();
}

class _HotelDetailScreenState extends State<HotelDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _hotel;
  DateTime? _checkInDate;
  DateTime? _checkOutDate;

  @override
  void initState() {
    super.initState();
    _checkInDate = widget.initialCheckInDate;
    _checkOutDate = widget.initialCheckOutDate;
    _fetchDetails();
  }

  String _formatDate(DateTime d) {
    return '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  }

  String _formatDateArabic(DateTime d) {
    const months = [
      'كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران',
      'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
    ];
    return '${d.day} ${months[d.month - 1]}';
  }

  Future<void> _pickDates() async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final firstDate = today;
    final lastDate = today.add(const Duration(days: 365));
    final initialRange = DateTimeRange(
      start: (_checkInDate != null && !_checkInDate!.isBefore(today)) ? DateTime(_checkInDate!.year, _checkInDate!.month, _checkInDate!.day) : today,
      end: (_checkOutDate != null && !_checkOutDate!.isBefore(today)) ? DateTime(_checkOutDate!.year, _checkOutDate!.month, _checkOutDate!.day) : today.add(const Duration(days: 2)),
    );

    final picked = await showDateRangePicker(
      context: context,
      firstDate: firstDate,
      lastDate: lastDate,
      initialDateRange: initialRange,
      helpText: 'اختر مواعيد الحجز لعرض الغرف المتاحة',
      cancelText: 'إلغاء',
      confirmText: 'تطبيق الفلتر',
      saveText: 'تأكيد',
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF003580),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Color(0xFF0F172A),
              secondary: Color(0xFFFEBB02),
            ),
          ),
          child: Directionality(
            textDirection: TextDirection.rtl,
            child: child!,
          ),
        );
      },
    );

    if (picked != null) {
      setState(() {
        _checkInDate = picked.start;
        _checkOutDate = picked.end;
      });
      _fetchDetails();
    }
  }

  Future<void> _fetchDetails() async {
    setState(() => _isLoading = true);
    try {
      final uri = Uri.parse('${MobileState.apiUrl}/hotels/${widget.hotelId}').replace(
        queryParameters: {
          if (_checkInDate != null) 'check_in': _formatDate(_checkInDate!),
          if (_checkOutDate != null) 'check_out': _formatDate(_checkOutDate!),
        },
      );
      final res = await http.get(uri);
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _hotel = body['hotel']);
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _bookRoom(Map<String, dynamic> room) {
    if (!MobileState.isLoggedIn) {
      widget.onOpenAuth();
      return;
    }

    if (_checkInDate == null || _checkOutDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تحديد تواريخ الوصول والمغادرة أولاً لعرض التوفر وتأكيد الحجز.')),
      );
      _pickDates();
      return;
    }

    final nights = _checkOutDate!.difference(_checkInDate!).inDays;
    final pricePerNight = (room['price_usd'] is num) ? (room['price_usd'] as num).toDouble() : 0.0;
    final totalPrice = pricePerNight * (nights > 0 ? nights : 1);

    // Direct booking flow
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: Text('تأكيد حجز غرفة ${room['room_number']}'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('نوع الغرفة: ${room['room_type']}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.calendar_month, size: 16, color: Color(0xFF003580)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'الفترة: ${_formatDateArabic(_checkInDate!)} ➔ ${_formatDateArabic(_checkOutDate!)} ($nights ليالٍ)',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF003580)),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              Text('سعر الليلة: \$${room['price_usd']} / ${room['price_syp']} ل.س'),
              const SizedBox(height: 4),
              Text('السعر الإجمالي: \$$totalPrice', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 14)),
              const SizedBox(height: 12),
              const Text('هل ترغب بتأكيد الحجز الفوري وإرسال الإشعار لإدارة الفندق؟', style: TextStyle(fontSize: 13)),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF003580),
                foregroundColor: Colors.white,
              ),
              onPressed: () async {
                Navigator.pop(ctx);
                await _submitBooking(room['id']);
              },
              child: const Text('تأكيد الحجز الآن'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitBooking(int roomId) async {
    try {
      final checkIn = _formatDate(_checkInDate!);
      final checkOut = _formatDate(_checkOutDate!);

      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/bookings'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'hotel_id': widget.hotelId,
          'room_id': roomId,
          'check_in': checkIn,
          'check_out': checkOut,
          'currency': 'USD',
        }),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.green,
            content: Text(body['message'] ?? 'تم تأكيد حجزك بنجاح!'),
          ),
        );
        _fetchDetails();
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(body['message'] ?? 'فشل الحجز.')),
        );
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_hotel == null) {
      return const Scaffold(body: Center(child: Text('تعذر تحميل بيانات الفندق.')));
    }

    final h = _hotel!;
    final rooms = (h['rooms'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(
        title: Text(h['name'] ?? ''),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Image.network(
                h['cover_image'] ?? '',
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) => Container(color: Colors.grey),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          h['name'] ?? '',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: const Color(0xFF003580), borderRadius: BorderRadius.circular(8)),
                        child: Text('${h['rating'] ?? 9.2}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('📍 ${h['province']} - ${h['address'] ?? ''}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF16A34A),
                            side: const BorderSide(color: Color(0xFF16A34A)),
                            padding: const EdgeInsets.symmetric(vertical: 9),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () => openGoogleMaps(context, h),
                          icon: const Icon(Icons.directions, color: Color(0xFF16A34A), size: 18),
                          label: const Text('الموقع والاتجاهات (Google Maps)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF003580),
                          side: const BorderSide(color: Color(0xFF003580)),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () => openHotelMessageDialog(context, h, widget.onOpenAuth),
                        icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF003580), size: 16),
                        label: const Text('مراسلة الفندق', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Booking Dates Filter / Indicator in HotelDetailScreen
                  Material(
                    color: Colors.transparent,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: _pickDates,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 14),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(
                          color: _checkInDate != null ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _checkInDate != null ? const Color(0xFF003580) : const Color(0xFFCBD5E1),
                            width: _checkInDate != null ? 1.5 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_month, color: Color(0xFF003580), size: 22),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _checkInDate != null
                                        ? 'الغرف المتاحة لفترة إقامتك المحددة:'
                                        : 'تواريخ الحجز (اضغط لعرض الغرف المتاحة وفق التاريخ):',
                                    style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    _checkInDate != null && _checkOutDate != null
                                        ? '${_formatDateArabic(_checkInDate!)}  ➔  ${_formatDateArabic(_checkOutDate!)} (${_checkOutDate!.difference(_checkInDate!).inDays} ليالٍ)'
                                        : 'اضغط لاختيار تاريخ الوصول وتاريخ المغادرة',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: _checkInDate != null ? const Color(0xFF003580) : const Color(0xFF334155),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: const Color(0xFF003580),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.edit_calendar, size: 14, color: Colors.white),
                                  const SizedBox(width: 4),
                                  Text(
                                    _checkInDate != null ? 'تغيير' : 'تحديد',
                                    style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                            ),
                            if (_checkInDate != null)
                              Padding(
                                padding: const EdgeInsets.only(right: 6),
                                child: IconButton(
                                  icon: const Icon(Icons.cancel, size: 20, color: Colors.red),
                                  tooltip: 'إلغاء فلتر التواريخ',
                                  constraints: const BoxConstraints(),
                                  padding: EdgeInsets.zero,
                                  onPressed: () {
                                    setState(() {
                                      _checkInDate = null;
                                      _checkOutDate = null;
                                    });
                                    _fetchDetails();
                                  },
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  if (_checkInDate == null) ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 20),
                      margin: const EdgeInsets.symmetric(vertical: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFBFDBFE), width: 1.5),
                        boxShadow: [
                          BoxShadow(color: const Color(0xFF003580).withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 4)),
                        ],
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: const Color(0xFF003580).withValues(alpha: 0.1),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.date_range, size: 48, color: Color(0xFF003580)),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'الرجاء اختيار تاريخ وصولك لمعرفة الغرف المتاحة',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E3A8A)),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'حدد موعد الوصول والمغادرة لعرض كافة الغرف الشاغرة والأسعار الدقيقة لإقامتك.',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 12, color: Color(0xFF475569)),
                          ),
                          const SizedBox(height: 20),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF003580),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              elevation: 2,
                            ),
                            onPressed: _pickDates,
                            icon: const Icon(Icons.calendar_month, size: 18),
                            label: const Text('اختيار تاريخ الوصول والإقامة 📅', style: TextStyle(fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ),
                  ] else ...[
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('الغرف والخيارات المتاحة:', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: Colors.green.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                          ),
                          child: Text(
                            '${rooms.length} غرف متاحة',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),

                    if (rooms.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        margin: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF2F2),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFFECACA)),
                        ),
                        child: Column(
                          children: [
                            const Icon(Icons.event_busy, size: 44, color: Color(0xFFDC2626)),
                            const SizedBox(height: 8),
                            const Text(
                              'لا توجد غرف متاحة للحجز في هذه الفترة',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF991B1B)),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _checkInDate != null && _checkOutDate != null
                                  ? 'جميع غرف هذا الفندق محجوزة بين ${_formatDateArabic(_checkInDate!)} و ${_formatDateArabic(_checkOutDate!)}. يرجى تجربة مواعيد أخرى.'
                                  : 'لا توجد غرف شاغرة حالياً في هذا الفندق.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 12, color: Color(0xFF7F1D1D)),
                            ),
                            const SizedBox(height: 12),
                            ElevatedButton.icon(
                              onPressed: _pickDates,
                              icon: const Icon(Icons.calendar_month, size: 16),
                              label: const Text('اختيار تواريخ بديلة'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFFDC2626),
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ...rooms.map((r) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 60,
                                height: 60,
                                decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(12)),
                                child: const Center(child: Text('🚪', style: TextStyle(fontSize: 24))),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Text('غرفة ${r['room_number']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.green.withValues(alpha: 0.1),
                                            borderRadius: BorderRadius.circular(4),
                                          ),
                                          child: const Text('متاحة للحجز ✓', style: TextStyle(color: Colors.green, fontSize: 10, fontWeight: FontWeight.bold)),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(r['room_type'] ?? 'قياسية', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                                    Text('\$${r['price_usd']} / ${r['price_syp']} ل.س', style: const TextStyle(color: Color(0xFF003580), fontWeight: FontWeight.bold, fontSize: 13)),
                                  ],
                                ),
                              ),
                              ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF003580),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: () => _bookRoom(r),
                                child: const Text('احجز'),
                              ),
                            ],
                          ),
                        );
                      }),
                  ],
                  const SizedBox(height: 20),
                  const Divider(height: 1),
                  const SizedBox(height: 16),

                  // Hotel Reviews Section in HotelDetailScreen (ظهور الريفيو والتقييمات للفندق)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'آراء وتقييمات النزلاء (الريفيو):',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      TextButton.icon(
                        icon: const Icon(Icons.rate_review_outlined, size: 16),
                        label: const Text('أضف تقييمك'),
                        onPressed: () {
                          showModalBottomSheet(
                            context: context,
                            isScrollControlled: true,
                            backgroundColor: Colors.transparent,
                            builder: (ctx) => Directionality(
                              textDirection: TextDirection.rtl,
                              child: HotelReviewsBottomSheet(
                                hotelId: widget.hotelId,
                                hotelName: h['name'] ?? '',
                                currentRating: (h['rating'] is num) ? (h['rating'] as num).toDouble() : 4.8,
                                reviewsCount: (h['reviews_count'] is num) ? (h['reviews_count'] as num).toInt() : 14,
                                initialReviews: (h['reviews'] as List?) ?? [],
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),

                  // Rating Summary Card
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF003580).withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFF003580).withValues(alpha: 0.15)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFF003580),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.star, color: Color(0xFFFEBB02), size: 16),
                              const SizedBox(width: 4),
                              Text(
                                '${h['rating'] ?? 4.8}',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                ((h['rating'] is num) && (h['rating'] as num) >= 4.5) ? 'تقييم استثنائي' : 'تقييم ممتاز',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF003580)),
                              ),
                              Text(
                                'بناءً على ${h['reviews_count'] ?? 14} تقييم موثق من النزلاء والضيوف',
                                style: const TextStyle(color: Colors.grey, fontSize: 11),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Reviews List
                  ...((h['reviews'] as List?) ?? []).take(5).map((rev) {
                    final int s = (rev['rating'] is int) ? rev['rating'] : 5;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 4, offset: const Offset(0, 2)),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 14,
                                backgroundColor: const Color(0xFF003580),
                                child: Text((rev['guest_name'] ?? 'ن')[0], style: const TextStyle(color: Colors.white, fontSize: 12)),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(rev['guest_name'] ?? 'نزيل', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    Text(rev['time_ago'] ?? rev['date'] ?? 'مؤخراً', style: const TextStyle(color: Colors.grey, fontSize: 10)),
                                  ],
                                ),
                              ),
                              Row(
                                children: List.generate(s, (_) => const Icon(Icons.star, size: 12, color: Color(0xFFFEBB02))),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(rev['comment'] ?? '', style: const TextStyle(fontSize: 12, color: Color(0xFF334155), height: 1.3)),
                          if (rev['hotel_reply'] != null && rev['hotel_reply'].toString().trim().isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEFF6FF),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: const Color(0xFFBFDBFE)),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Row(
                                    children: [
                                      Icon(Icons.reply_all, size: 14, color: Color(0xFF003580)),
                                      SizedBox(width: 4),
                                      Text(
                                        'رد إدارة الفندق 🏨',
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF003580)),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    rev['hotel_reply'].toString(),
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF1E3A8A)),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    );
                  }),
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
// HOTEL REVIEWS BOTTOM SHEET (عرض التقييمات والريفيو وإضافة تقييم جديد)
// ============================================================================
class HotelReviewsBottomSheet extends StatefulWidget {
  final int hotelId;
  final String hotelName;
  final double currentRating;
  final int reviewsCount;
  final List<dynamic> initialReviews;

  const HotelReviewsBottomSheet({
    super.key,
    required this.hotelId,
    required this.hotelName,
    required this.currentRating,
    required this.reviewsCount,
    this.initialReviews = const [],
  });

  @override
  State<HotelReviewsBottomSheet> createState() => _HotelReviewsBottomSheetState();
}

class _HotelReviewsBottomSheetState extends State<HotelReviewsBottomSheet> {
  bool _isLoading = true;
  List<dynamic> _reviews = [];
  double _avgRating = 4.8;
  int _count = 0;

  @override
  void initState() {
    super.initState();
    _avgRating = widget.currentRating;
    _count = widget.reviewsCount;
    _reviews = List.from(widget.initialReviews);
    _fetchReviews();
  }

  Future<void> _fetchReviews() async {
    try {
      final res = await http.get(Uri.parse('${MobileState.apiUrl}/hotels/${widget.hotelId}/reviews'));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _reviews = body['data']['data'] ?? [];
            _avgRating = (body['average_rating'] is num) ? (body['average_rating'] as num).toDouble() : widget.currentRating;
            _count = (body['reviews_count'] is num) ? (body['reviews_count'] as num).toInt() : widget.reviewsCount;
          });
        }
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  void _showAddReviewDialog() {
    int selectedRating = 5;
    final nameCtrl = TextEditingController(text: MobileState.currentUser?['name'] ?? '');
    final commentCtrl = TextEditingController();
    bool isSubmitting = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Row(
              children: [
                Icon(Icons.star, color: Color(0xFFFEBB02)),
                SizedBox(width: 8),
                Text('أضف تقييمك وتجربتك', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('فندق: ${widget.hotelName}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF003580))),
                  const SizedBox(height: 12),
                  const Text('تقييمك الإجمالي:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(5, (index) {
                      final star = index + 1;
                      return IconButton(
                        icon: Icon(
                          star <= selectedRating ? Icons.star : Icons.star_border,
                          color: const Color(0xFFFEBB02),
                          size: 32,
                        ),
                        onPressed: () => setDialogState(() => selectedRating = star),
                      );
                    }),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(
                      labelText: 'اسمك (النزيل)',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: commentCtrl,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'اكتب رأيك وتجربتك في الإقامة...',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: isSubmitting ? null : () => Navigator.pop(ctx),
                child: const Text('إلغاء'),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF003580),
                  foregroundColor: Colors.white,
                ),
                onPressed: isSubmitting ? null : () async {
                  if (commentCtrl.text.trim().isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('يرجى كتابة تعليق أو رأي حول الإقامة.')),
                    );
                    return;
                  }
                  final messenger = ScaffoldMessenger.of(context);
                  final nav = Navigator.of(ctx);
                  setDialogState(() => isSubmitting = true);
                  try {
                    final res = await http.post(
                      Uri.parse('${MobileState.apiUrl}/hotels/${widget.hotelId}/reviews'),
                      headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        if (MobileState.token != null) 'Authorization': 'Bearer ${MobileState.token}',
                      },
                      body: jsonEncode({
                        'rating': selectedRating,
                        'guest_name': nameCtrl.text.trim().isNotEmpty ? nameCtrl.text.trim() : 'نزيل موثوق',
                        'comment': commentCtrl.text.trim(),
                      }),
                    );
                    if (res.statusCode == 200) {
                      nav.pop();
                      _fetchReviews();
                      messenger.showSnackBar(
                        const SnackBar(
                          backgroundColor: Colors.green,
                          content: Text('شكراً لمشاركتك! تم تسجيل تقييمك بنجاح.'),
                        ),
                      );
                    }
                  } catch (_) {}
                  setDialogState(() => isSubmitting = false);
                },
                child: isSubmitting
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('إرسال التقييم'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showReplyDialog(Map<String, dynamic> review) {
    if (!MobileState.isLoggedIn) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تسجيل الدخول أولاً للرد على تقييمات النزلاء.')),
      );
      return;
    }

    final replyCtrl = TextEditingController(text: review['hotel_reply']?.toString() ?? '');
    bool isSubmitting = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => Directionality(
          textDirection: TextDirection.rtl,
          child: AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Row(
              children: [
                const Icon(Icons.reply, color: Color(0xFF003580)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'الرد على تقييم (${review['guest_name'] ?? 'النزيل'})',
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Text(
                      '"${review['comment'] ?? ''}"',
                      style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: Color(0xFF475569)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Text('رد إدارة الفندق المعتمد:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: replyCtrl,
                    maxLines: 4,
                    decoration: const InputDecoration(
                      hintText: 'اكتب رد الفندق الترحيبي أو التوضيحي للنزيل...',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.all(10),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('إلغاء'),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
                onPressed: isSubmitting
                    ? null
                    : () async {
                        final replyText = replyCtrl.text.trim();
                        if (replyText.isEmpty) return;
                        final messenger = ScaffoldMessenger.of(context);
                        final nav = Navigator.of(ctx);
                        setDialogState(() => isSubmitting = true);
                        try {
                          final res = await http.post(
                            Uri.parse('${MobileState.apiUrl}/hotels/${widget.hotelId}/reviews/${review['id']}/reply'),
                            headers: {
                              'Authorization': 'Bearer ${MobileState.token}',
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                            },
                            body: jsonEncode({'reply': replyText}),
                          );
                          if (res.statusCode == 200) {
                            nav.pop();
                            _fetchReviews();
                            messenger.showSnackBar(
                              const SnackBar(
                                backgroundColor: Colors.green,
                                content: Text('تم نشر رد إدارة الفندق بنجاح!'),
                              ),
                            );
                          } else {
                            final err = jsonDecode(res.body);
                            messenger.showSnackBar(
                              SnackBar(content: Text(err['message'] ?? 'فشل نشر الرد.')),
                            );
                          }
                        } catch (_) {
                          messenger.showSnackBar(
                            const SnackBar(content: Text('حدث خطأ أثناء إرسال الرد.')),
                          );
                        }
                        if (ctx.mounted) setDialogState(() => isSubmitting = false);
                      },
                child: Text(isSubmitting ? 'جارٍ النشر...' : 'نشر الرد'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Drag handle
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 10, bottom: 6),
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'تقييمات وآراء النزلاء (الريفيو)',
                        style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        widget.hotelName,
                        style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          // Overall Rating Box
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF003580), Color(0xFF0052B4)],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEBB02),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.star, color: Color(0xFF003580), size: 18),
                      const SizedBox(width: 4),
                      Text(
                        _avgRating.toStringAsFixed(1),
                        style: const TextStyle(color: Color(0xFF003580), fontWeight: FontWeight.bold, fontSize: 17),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _avgRating >= 4.5 ? 'تقييم ممتاز جداً' : (_avgRating >= 4.0 ? 'تقييم رائع' : 'تقييم جيد'),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Text(
                        'بناءً على $_count تقييم موثق من النزلاء',
                        style: const TextStyle(color: Color(0xFFE2E8F0), fontSize: 11),
                      ),
                    ],
                  ),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF003580),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  onPressed: _showAddReviewDialog,
                  child: const Text('أضف تقييمك', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const Divider(height: 1),
          // Reviews List
          Expanded(
            child: _isLoading && _reviews.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _reviews.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.rate_review_outlined, size: 54, color: Colors.grey),
                            const SizedBox(height: 10),
                            const Text('لا توجد تقييمات مكتوبة لهذا الفندق حتى الآن.', style: TextStyle(color: Colors.grey)),
                            const SizedBox(height: 12),
                            ElevatedButton(
                              onPressed: _showAddReviewDialog,
                              child: const Text('كن أول من يكتب تقييماً'),
                            ),
                          ],
                        ),
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _reviews.length,
                        separatorBuilder: (ctx, idx) => const Divider(height: 16),
                        itemBuilder: (ctx, idx) {
                          final r = _reviews[idx];
                          final int stars = (r['rating'] is int) ? r['rating'] : 5;
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const CircleAvatar(
                                    radius: 16,
                                    backgroundColor: Color(0xFFE2E8F0),
                                    child: Icon(Icons.person, size: 18, color: Color(0xFF003580)),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          r['guest_name'] ?? 'نزيل تم التحقق منه',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                        ),
                                        Text(
                                          r['time_ago'] ?? r['date'] ?? 'مؤخراً',
                                          style: const TextStyle(color: Colors.grey, fontSize: 11),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Row(
                                    children: List.generate(
                                      stars,
                                      (_) => const Icon(Icons.star, size: 14, color: Color(0xFFFEBB02)),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                r['comment'] ?? '',
                                style: const TextStyle(fontSize: 13, color: Color(0xFF334155), height: 1.4),
                              ),
                              if (r['hotel_reply'] != null && r['hotel_reply'].toString().trim().isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFEFF6FF),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: const Color(0xFFBFDBFE)),
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Row(
                                        children: [
                                          Icon(Icons.reply_all, size: 14, color: Color(0xFF003580)),
                                          SizedBox(width: 4),
                                          Text(
                                            'رد إدارة الفندق 🏨',
                                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF003580)),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        r['hotel_reply'].toString(),
                                        style: const TextStyle(fontSize: 12, color: Color(0xFF1E3A8A)),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                              Align(
                                alignment: Alignment.centerLeft,
                                child: TextButton.icon(
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                  onPressed: () => _showReplyDialog(r),
                                  icon: const Icon(Icons.reply, size: 14, color: Color(0xFF003580)),
                                  label: Text(
                                    (r['hotel_reply'] != null && r['hotel_reply'].toString().trim().isNotEmpty) ? 'تعديل رد الفندق' : 'الرد كإدارة الفندق',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF003580), fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ),
                            ],
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

// ============================================================================
// COMPLAINTS BOTTOM SHEET (مركز الشكاوى والمقترحات للنزلاء)
// ============================================================================
class ComplaintsBottomSheet extends StatefulWidget {
  final List<dynamic> hotels;
  final VoidCallback onOpenAuth;

  const ComplaintsBottomSheet({
    super.key,
    required this.hotels,
    required this.onOpenAuth,
  });

  @override
  State<ComplaintsBottomSheet> createState() => _ComplaintsBottomSheetState();
}

class _ComplaintsBottomSheetState extends State<ComplaintsBottomSheet> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int? _selectedHotelId;
  String _selectedCategory = 'service';
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _roomCtrl = TextEditingController();
  final _titleCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  bool _isSubmitting = false;
  bool _isLoadingComplaints = true;
  List<dynamic> _myComplaints = [];

  final List<Map<String, String>> _categories = [
    {'key': 'service', 'label': 'خدمة الغرف'},
    {'key': 'cleanliness', 'label': 'النظافة'},
    {'key': 'maintenance', 'label': 'صيانة'},
    {'key': 'reception', 'label': 'الاستقبال'},
    {'key': 'suggestion', 'label': 'اقتراح تحسين'},
    {'key': 'other', 'label': 'أخرى'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    if (widget.hotels.isNotEmpty) {
      _selectedHotelId = widget.hotels[0]['id'];
    }
    if (MobileState.currentUser != null) {
      _nameCtrl.text = MobileState.currentUser!['name'] ?? '';
      _phoneCtrl.text = MobileState.currentUser!['phone'] ?? '';
    }
    _fetchMyComplaints();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _roomCtrl.dispose();
    _titleCtrl.dispose();
    _messageCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchMyComplaints() async {
    setState(() => _isLoadingComplaints = true);
    try {
      final phone = _phoneCtrl.text.trim();
      final uri = Uri.parse('${MobileState.apiUrl}/my-complaints').replace(queryParameters: {
        if (phone.isNotEmpty) 'phone': phone,
      });

      final res = await http.get(
        uri,
        headers: {
          'Accept': 'application/json',
          if (MobileState.token != null) 'Authorization': 'Bearer ${MobileState.token}',
        },
      );

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _myComplaints = body['data'] ?? [];
          });
        }
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoadingComplaints = false);
  }

  Future<void> _submitComplaint() async {
    if (_titleCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى إدخال موضوع أو عنوان الشكوى / المقترح.')),
      );
      return;
    }
    if (_messageCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى كتابة تفاصيل الشكوى أو المقترح.')),
      );
      return;
    }
    if (_selectedHotelId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى اختيار الفندق المعني.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/complaints'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          if (MobileState.token != null) 'Authorization': 'Bearer ${MobileState.token}',
        },
        body: jsonEncode({
          'hotel_id': _selectedHotelId,
          'category': _selectedCategory,
          'title': _titleCtrl.text.trim(),
          'message': _messageCtrl.text.trim(),
          'guest_name': _nameCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'room_number': _roomCtrl.text.trim(),
        }),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200) {
        _titleCtrl.clear();
        _messageCtrl.clear();
        _roomCtrl.clear();
        _fetchMyComplaints();
        _tabController.animateTo(1);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: Colors.green,
              content: Text(body['message'] ?? 'تم إرسال الشكوى بنجاح وسيتم التواصل معك.'),
            ),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: Colors.red,
              content: Text(body['message'] ?? 'حدث خطأ أثناء الإرسال.'),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(backgroundColor: Colors.red, content: Text('تعذر الاتصال بالخادم.')),
        );
      }
    }
    if (mounted) setState(() => _isSubmitting = false);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.88,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Drag handle
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 10, bottom: 6),
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.support_agent, color: Color(0xFF003580), size: 24),
                    SizedBox(width: 8),
                    Text(
                      'مركز الشكاوى والمقترحات للنزلاء',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
              ],
            ),
          ),
          TabBar(
            controller: _tabController,
            labelColor: const Color(0xFF003580),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFF003580),
            indicatorWeight: 3,
            tabs: const [
              Tab(icon: Icon(Icons.edit_note), text: 'تقديم شكوى / مقترح'),
              Tab(icon: Icon(Icons.track_changes), text: 'متابعة شكاويي'),
            ],
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                // Tab 1: Submit Form
                SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEBB02).withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFFFEBB02)),
                        ),
                        child: const Row(
                          children: [
                            Icon(Icons.info_outline, color: Color(0xFF003580), size: 20),
                            SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'صوتك مسموع! يتم تحويل شكواك فورياً إلى إدارة الفندق وقسم الاستقبال لمعالجتها على وجه السرعة.',
                                style: TextStyle(fontSize: 11, color: Color(0xFF0F172A), fontWeight: FontWeight.w500),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Select Hotel
                      const Text('الفندق المعني:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade300),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int>(
                            isExpanded: true,
                            value: _selectedHotelId,
                            items: widget.hotels.map((h) {
                              return DropdownMenuItem<int>(
                                value: h['id'],
                                child: Text('${h['name']} (${h['province'] ?? ''})'),
                              );
                            }).toList(),
                            onChanged: (val) => setState(() => _selectedHotelId = val),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Category Selector
                      const Text('نوع الشكوى / التصنيف:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 6),
                      Wrap(
                        spacing: 8,
                        children: _categories.map((c) {
                          final isSelected = _selectedCategory == c['key'];
                          return ChoiceChip(
                            label: Text(c['label']!, style: TextStyle(fontSize: 12, color: isSelected ? Colors.white : Colors.black87)),
                            selected: isSelected,
                            selectedColor: const Color(0xFF003580),
                            backgroundColor: const Color(0xFFF1F5F9),
                            onSelected: (_) => setState(() => _selectedCategory = c['key']!),
                          );
                        }).toList(),
                      ),
                      const SizedBox(height: 12),

                      // Guest info
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _nameCtrl,
                              decoration: const InputDecoration(
                                labelText: 'اسم النزيل',
                                border: OutlineInputBorder(),
                                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: TextField(
                              controller: _phoneCtrl,
                              keyboardType: TextInputType.phone,
                              decoration: const InputDecoration(
                                labelText: 'رقم الهاتف للتواصل',
                                border: OutlineInputBorder(),
                                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      TextField(
                        controller: _roomCtrl,
                        decoration: const InputDecoration(
                          labelText: 'رقم الغرفة (اختياري)',
                          hintText: 'مثال: 204 أو جناح 10',
                          border: OutlineInputBorder(),
                          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        ),
                      ),
                      const SizedBox(height: 12),

                      TextField(
                        controller: _titleCtrl,
                        decoration: const InputDecoration(
                          labelText: 'عنوان الشكوى أو المقترح *',
                          hintText: 'مثال: ضعف التكييف، خدمة الغرف، اقتراح تحسين الإفطار',
                          border: OutlineInputBorder(),
                          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        ),
                      ),
                      const SizedBox(height: 12),

                      TextField(
                        controller: _messageCtrl,
                        maxLines: 4,
                        decoration: const InputDecoration(
                          labelText: 'تفاصيل الشكوى أو المقترح بالكامل *',
                          hintText: 'يرجى كتابة التفاصيل الدقيقة لنتمكن من مساعدتك بأفضل شكل...',
                          border: OutlineInputBorder(),
                          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        ),
                      ),
                      const SizedBox(height: 18),

                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF003580),
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: _isSubmitting ? null : _submitComplaint,
                          icon: _isSubmitting
                              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Icon(Icons.send_rounded),
                          label: Text(_isSubmitting ? 'جارٍ الإرسال...' : 'إرسال الشكوى / المقترح للإدارة', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),

                // Tab 2: My Complaints List
                _isLoadingComplaints
                    ? const Center(child: CircularProgressIndicator())
                    : _myComplaints.isEmpty
                        ? Center(
                            child: Padding(
                              padding: const EdgeInsets.all(24),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.inbox_outlined, size: 56, color: Colors.grey),
                                  const SizedBox(height: 12),
                                  const Text('لا توجد شكاوى أو مقترحات مسجلة باسمك حالياً.', style: TextStyle(color: Colors.grey, fontSize: 14)),
                                  const SizedBox(height: 8),
                                  TextButton.icon(
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('تحديث'),
                                    onPressed: _fetchMyComplaints,
                                  ),
                                ],
                              ),
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _fetchMyComplaints,
                            child: ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _myComplaints.length,
                              separatorBuilder: (ctx, idx) => const SizedBox(height: 12),
                              itemBuilder: (ctx, idx) {
                                final item = _myComplaints[idx];
                                final status = item['status'] ?? 'new';
                                Color statusColor = Colors.blue;
                                String statusText = item['status_label'] ?? 'جديدة';
                                if (status == 'closed') {
                                  statusColor = Colors.green;
                                } else if (status == 'in_progress') {
                                  statusColor = Colors.orange;
                                }

                                return Container(
                                  padding: const EdgeInsets.all(14),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Colors.grey.shade200),
                                    boxShadow: [
                                      BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 6, offset: const Offset(0, 2)),
                                    ],
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              item['hotel_name'] ?? 'فندق',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF003580)),
                                            ),
                                          ),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: statusColor.withValues(alpha: 0.12),
                                              borderRadius: BorderRadius.circular(6),
                                              border: Border.all(color: statusColor),
                                            ),
                                            child: Text(
                                              statusText,
                                              style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.bold),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        item['title'] ?? '',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        item['message'] ?? '',
                                        style: const TextStyle(fontSize: 12, color: Color(0xFF475569)),
                                      ),
                                      if (item['response'] != null && item['response'].toString().trim().isNotEmpty) ...[
                                        const SizedBox(height: 10),
                                        Container(
                                          padding: const EdgeInsets.all(10),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFF0FDF4),
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFF86EFAC)),
                                          ),
                                          child: Row(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              const Icon(Icons.mark_chat_read, color: Color(0xFF16A34A), size: 16),
                                              const SizedBox(width: 6),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    const Text('رد إدارة الفندق الرسمي:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF15803D))),
                                                    const SizedBox(height: 2),
                                                    Text(
                                                      item['response'],
                                                      style: const TextStyle(fontSize: 12, color: Color(0xFF166534)),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                      const SizedBox(height: 8),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text(
                                            item['time_ago'] ?? item['created_at'] ?? '',
                                            style: const TextStyle(color: Colors.grey, fontSize: 10),
                                          ),
                                          if (item['room_number'] != null)
                                            Text('غرفة: ${item['room_number']}', style: const TextStyle(color: Colors.grey, fontSize: 10)),
                                        ],
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
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
// NOTIFICATIONS BOTTOM SHEET (مركز الإشعارات والتنبيهات)
// ============================================================================
class NotificationsBottomSheet extends StatefulWidget {
  final VoidCallback onOpenAuth;
  final VoidCallback onUpdated;

  const NotificationsBottomSheet({
    super.key,
    required this.onOpenAuth,
    required this.onUpdated,
  });

  @override
  State<NotificationsBottomSheet> createState() => _NotificationsBottomSheetState();
}

class _NotificationsBottomSheetState extends State<NotificationsBottomSheet> {
  bool _isLoading = true;
  List<dynamic> _notifications = [];
  int _unreadCount = 0;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    if (!MobileState.isLoggedIn) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _notifications = [
            {
              'id': 101,
              'title' : 'أهلاً بك في بوابة الحجز الفندقي',
              'message': 'استكشف الفنادق الفاخرة، العروض الترويجية، وميزات الحجز المباشر بالليرة والدولار.',
              'type': 'welcome',
              'icon': '🏨',
              'is_read': false,
              'time_ago': 'الآن',
            },
            {
              'id': 102,
              'title' : 'عروض الخريف والمنتجعات',
              'message': 'خصومات تصل إلى 30% على حجوزات اللاذقية وطرطوس هذا الشهر.',
              'type': 'offer',
              'icon': '🏷️',
              'is_read': true,
              'time_ago': 'منذ يومين',
            },
          ];
          _unreadCount = 1;
        });
      }
      return;
    }

    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/notifications'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _notifications = body['data'] ?? [];
            _unreadCount = body['unread_count'] ?? 0;
          });
        }
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _markAllAsRead() async {
    if (!MobileState.isLoggedIn) {
      setState(() {
        for (var n in _notifications) {
          n['is_read'] = true;
        }
        _unreadCount = 0;
      });
      return;
    }

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/notifications/read-all'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        setState(() {
          for (var n in _notifications) {
            n['is_read'] = true;
          }
          _unreadCount = 0;
        });
        widget.onUpdated();
      }
    } catch (_) {}
  }

  Future<void> _markSingleRead(int id) async {
    if (!MobileState.isLoggedIn) return;
    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/notifications/$id/read'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        setState(() {
          final idx = _notifications.indexWhere((n) => n['id'] == id);
          if (idx != -1) {
            _notifications[idx]['is_read'] = true;
            if (_unreadCount > 0) _unreadCount--;
          }
        });
        widget.onUpdated();
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Drag handle
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 10, bottom: 6),
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.notifications_active, color: Color(0xFF003580), size: 24),
                    const SizedBox(width: 8),
                    const Text(
                      'الإشعارات والتنبيهات',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    if (_unreadCount > 0) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(color: Colors.redAccent, borderRadius: BorderRadius.circular(10)),
                        child: Text('$_unreadCount جديد', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ],
                ),
                Row(
                  children: [
                    if (_unreadCount > 0)
                      TextButton(
                        onPressed: _markAllAsRead,
                        child: const Text('تحديد الكل كمقروء', style: TextStyle(fontSize: 11)),
                      ),
                    IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
                  ],
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          if (!MobileState.isLoggedIn)
            Container(
              margin: const EdgeInsets.all(12),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: Color(0xFF003580), size: 20),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text('سجل الدخول لاستلام إشعارات حجوزاتك والرد على الشكاوى في الوقت الفعلي.', style: TextStyle(fontSize: 11, color: Color(0xFF1E3A8A))),
                  ),
                  TextButton(
                    onPressed: () {
                      Navigator.pop(context);
                      widget.onOpenAuth();
                    },
                    child: const Text('دخول', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _notifications.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.notifications_none, size: 54, color: Colors.grey),
                            const SizedBox(height: 10),
                            const Text('لا توجد إشعارات جديدة حالياً.', style: TextStyle(color: Colors.grey, fontSize: 14)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _fetchNotifications,
                        child: ListView.separated(
                          padding: const EdgeInsets.all(14),
                          itemCount: _notifications.length,
                          separatorBuilder: (ctx, idx) => const Divider(height: 12),
                          itemBuilder: (ctx, idx) {
                            final n = _notifications[idx];
                            final isRead = (n['is_read'] == true || n['is_read'] == 1);
                            return InkWell(
                              borderRadius: BorderRadius.circular(10),
                              onTap: () {
                                if (!isRead && n['id'] != null) {
                                  _markSingleRead(n['id']);
                                }
                              },
                              child: Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: isRead ? Colors.transparent : const Color(0xFFF0F7FF),
                                  borderRadius: BorderRadius.circular(10),
                                  border: isRead ? null : Border.all(color: const Color(0xFFBAE6FD)),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      width: 38,
                                      height: 38,
                                      decoration: BoxDecoration(
                                        color: isRead ? Colors.grey.shade100 : const Color(0xFF003580).withValues(alpha: 0.1),
                                        shape: BoxShape.circle,
                                      ),
                                      child: Center(
                                        child: Text(
                                          n['icon'] ?? '🔔',
                                          style: const TextStyle(fontSize: 18),
                                        ),
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
                                              Expanded(
                                                child: Text(
                                                  n['title'] ?? '',
                                                  style: TextStyle(
                                                    fontWeight: isRead ? FontWeight.w600 : FontWeight.bold,
                                                    fontSize: 13,
                                                    color: isRead ? const Color(0xFF334155) : const Color(0xFF0F172A),
                                                  ),
                                                ),
                                              ),
                                              if (!isRead)
                                                Container(
                                                  width: 8,
                                                  height: 8,
                                                  decoration: const BoxDecoration(color: Color(0xFF003580), shape: BoxShape.circle),
                                                ),
                                            ],
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            n['message'] ?? '',
                                            style: TextStyle(
                                              fontSize: 12,
                                              color: isRead ? Colors.grey.shade600 : const Color(0xFF334155),
                                            ),
                                          ),
                                          const SizedBox(height: 6),
                                          Text(
                                            n['time_ago'] ?? n['created_at'] ?? '',
                                            style: const TextStyle(color: Colors.grey, fontSize: 10),
                                          ),
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
    );
  }
}
// ============================================================================
// ============================================================================
// GOOGLE MAPS & MESSAGING HELPERS
// ============================================================================
Future<void> openGoogleMaps(BuildContext context, Map<String, dynamic> hotel) async {
  String? urlStr = hotel['google_maps_url']?.toString().trim();
  if (urlStr == null || urlStr.isEmpty) {
    final query = Uri.encodeComponent('${hotel['name'] ?? ''} ${hotel['province'] ?? ''} ${hotel['address'] ?? ''}');
    urlStr = 'https://www.google.com/maps/search/?api=1&query=$query';
  }

  try {
    final uri = Uri.parse(urlStr);
    final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!launched) {
      await launchUrl(uri, mode: LaunchMode.platformDefault);
    }
  } catch (_) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تعذر فتح تطبيق الخرائط. يرجى التحقق من الرابط أو اتصالك بالإنترنت.')),
      );
    }
  }
}

Future<void> openHotelMessageDialog(BuildContext context, Map<String, dynamic> hotel, VoidCallback onOpenAuth) async {
  if (!MobileState.isLoggedIn) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('يرجى تسجيل الدخول أولاً للتواصل ومراسلة إدارة الفندق.')),
    );
    onOpenAuth();
    return;
  }

  int? contactUserId;
  try {
    final res = await http.get(
      Uri.parse('${MobileState.apiUrl}/hotels/${hotel['id']}/contact-user'),
      headers: {
        'Authorization': 'Bearer ${MobileState.token}',
        'Accept': 'application/json',
      },
    );
    if (res.statusCode == 200) {
      final body = jsonDecode(res.body);
      contactUserId = body['contact_user']?['id'];
    }
  } catch (_) {}

  if (contactUserId == null) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تعذر تحديد حساب إدارة هذا الفندق حالياً.')),
      );
    }
    return;
  }

  final msgCtrl = TextEditingController();
  if (!context.mounted) return;

  showDialog(
    context: context,
    builder: (ctx) => Directionality(
      textDirection: TextDirection.rtl,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.chat_bubble_outline, color: Color(0xFF003580)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'مراسلة إدارة ${hotel['name']}',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('اكتب رسالتك أو استفسارك وسيصل فوراً إلى إدارة واستقبال الفندق:', style: TextStyle(fontSize: 12, color: Colors.grey)),
            const SizedBox(height: 12),
            TextField(
              controller: msgCtrl,
              maxLines: 4,
              decoration: const InputDecoration(
                hintText: 'اكتب نص رسالتك هنا...',
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.all(12),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
            onPressed: () async {
              final text = msgCtrl.text.trim();
              if (text.isEmpty) return;
              Navigator.pop(ctx);
              try {
                final sendRes = await http.post(
                  Uri.parse('${MobileState.apiUrl}/chat/send'),
                  headers: {
                    'Authorization': 'Bearer ${MobileState.token}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                  },
                  body: jsonEncode({
                    'receiver_id': contactUserId,
                    'body': text,
                  }),
                );
                if (sendRes.statusCode == 200 || sendRes.statusCode == 201) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('تم إرسال رسالتك بنجاح!')),
                    );
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => Directionality(
                          textDirection: TextDirection.rtl,
                          child: ConversationChatScreen(
                            contact: {
                              'id': contactUserId,
                              'name': hotel['name'] ?? 'إدارة الفندق',
                              'role': 'hotel_manager',
                              'hotel_name': hotel['name'],
                            },
                          ),
                        ),
                      ),
                    );
                  }
                }
              } catch (_) {}
            },
            child: const Text('إرسال الآن'),
          ),
        ],
      ),
    ),
  );
}

// ============================================================================
// 4. MESSENGER SCREEN (قائمة المحادثات السابقة فقط مثل الماسنجر)
// ============================================================================
class MessengerScreen extends StatefulWidget {
  final VoidCallback onOpenAuth;
  const MessengerScreen({super.key, required this.onOpenAuth});

  @override
  State<MessengerScreen> createState() => _MessengerScreenState();
}

class _MessengerScreenState extends State<MessengerScreen> {
  List<dynamic> _conversations = [];
  bool _isLoading = false;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    if (MobileState.isLoggedIn) {
      _fetchConversations();
      _refreshTimer = Timer.periodic(const Duration(seconds: 8), (_) {
        if (mounted && MobileState.isLoggedIn) {
          _fetchConversations(silent: true);
        }
      });
    }
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchConversations({bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/chat/conversations'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _conversations = body['data'] ?? [];
          });
        }
      }
    } catch (_) {}
    if (!silent && mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (!MobileState.isLoggedIn) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('الرسائل والمحادثات'),
          backgroundColor: const Color(0xFF003580),
          foregroundColor: Colors.white,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.forum_outlined, size: 68, color: Color(0xFF003580)),
                const SizedBox(height: 16),
                const Text('صندوق الرسائل والمحادثات المباشرة', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text(
                  'سجل الدخول للتواصل مع إدارات الفنادق ومتابعة استفساراتك وحجوزاتك السابقة مثل الماسنجر.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.grey, fontSize: 13),
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF003580),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                  ),
                  onPressed: widget.onOpenAuth,
                  icon: const Icon(Icons.login),
                  label: const Text('تسجيل الدخول / إنشاء حساب'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('💬 الرسائل والمحادثات'),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            onPressed: () => _fetchConversations(),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _isLoading && _conversations.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : _conversations.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(28),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.chat_bubble_outline, size: 64, color: Colors.grey),
                        const SizedBox(height: 14),
                        const Text('لا توجد محادثات سابقة حالياً', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        const Text(
                          'يظهر هنا فقط من قمت بمراسلتهم سابقاً أو من أرسلوا لك رسائل (مثل الماسنجر).\nيمكنك بدء محادثة جديدة عبر الضغط على "مراسلة الفندق" في بطاقة الفندق.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.grey, fontSize: 12),
                        ),
                        const SizedBox(height: 20),
                        OutlinedButton.icon(
                          onPressed: () => _fetchConversations(),
                          icon: const Icon(Icons.refresh),
                          label: const Text('تحديث المحادثات'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () => _fetchConversations(),
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _conversations.length,
                    separatorBuilder: (ctx, i) => const Divider(height: 1),
                    itemBuilder: (ctx, i) {
                      final c = _conversations[i];
                      final unread = (c['unread_count'] is int) ? c['unread_count'] as int : 0;
                      return ListTile(
                        leading: Stack(
                          children: [
                            CircleAvatar(
                              radius: 24,
                              backgroundColor: const Color(0xFF003580),
                              child: Text(
                                (c['name'] ?? 'ف')[0],
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                            ),
                            if (unread > 0)
                              Positioned(
                                right: 0,
                                top: 0,
                                child: Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                  child: Text(
                                    '$unread',
                                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ),
                          ],
                        ),
                        title: Row(
                          children: [
                            Expanded(
                              child: Text(
                                c['name'] ?? '',
                                style: TextStyle(
                                  fontWeight: unread > 0 ? FontWeight.bold : FontWeight.w600,
                                  fontSize: 15,
                                ),
                              ),
                            ),
                            if (c['last_message_at'] != null)
                              Text(
                                c['last_message_at'],
                                style: TextStyle(fontSize: 11, color: unread > 0 ? const Color(0xFF003580) : Colors.grey),
                              ),
                          ],
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (c['is_broadcast'] == true || c['allow_reply'] == false)
                              Container(
                                margin: const EdgeInsets.only(top: 2, bottom: 2),
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF2F2),
                                  borderRadius: BorderRadius.circular(4),
                                  border: Border.all(color: Colors.red.shade300, width: 0.8),
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.lock_outline, size: 11, color: Color(0xFFDC2626)),
                                    SizedBox(width: 4),
                                    Text('تعميم رسمي • لا يمكن الرد', style: TextStyle(fontSize: 10, color: Color(0xFFDC2626), fontWeight: FontWeight.bold)),
                                  ],
                                ),
                              )
                            else if (c['hotel_name'] != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 2, bottom: 2),
                                child: Text(
                                  '🏨 ${c['hotel_name']}',
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF003580), fontWeight: FontWeight.bold),
                                ),
                              ),
                            Text(
                              c['last_message'] ?? 'لا توجد رسائل',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 13,
                                color: unread > 0 ? Colors.black87 : Colors.grey.shade600,
                                fontWeight: unread > 0 ? FontWeight.bold : FontWeight.normal,
                              ),
                            ),
                          ],
                        ),
                        onTap: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => Directionality(
                                textDirection: TextDirection.rtl,
                                child: ConversationChatScreen(contact: c),
                              ),
                            ),
                          );
                          _fetchConversations(silent: true);
                        },
                      );
                    },
                  ),
                ),
    );
  }
}

// ============================================================================
// CONVERSATION CHAT SCREEN (محادثة 1-on-1 فورية مع الفندق أو الحساب)
// ============================================================================
class ConversationChatScreen extends StatefulWidget {
  final Map<String, dynamic> contact;
  const ConversationChatScreen({super.key, required this.contact});

  @override
  State<ConversationChatScreen> createState() => _ConversationChatScreenState();
}

class _ConversationChatScreenState extends State<ConversationChatScreen> {
  final _msgCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  List<dynamic> _messages = [];
  bool _isLoading = false;
  Timer? _pollTimer;

  bool get _isReplyForbidden {
    if (widget.contact['is_broadcast'] == true && widget.contact['allow_reply'] == false) {
      return true;
    }
    return false;
  }

  @override
  void initState() {
    super.initState();
    _fetchMessages();
    _pollTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted) _fetchMessages(silent: true);
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchMessages({bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/chat/messages?contact_id=${widget.contact['id']}'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Accept': 'application/json',
        },
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _messages = body['data'] ?? [];
          });
        }
      }
    } catch (_) {}
    if (!silent && mounted) setState(() => _isLoading = false);
  }

  Future<void> _sendMessage() async {
    if (_isReplyForbidden) return;
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;
    _msgCtrl.clear();

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/chat/send'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'receiver_id': widget.contact['id'],
          'body': text,
        }),
      );
      if (res.statusCode == 200 || res.statusCode == 201) {
        _fetchMessages(silent: true);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.contact['name'] ?? 'محادثة', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            if (widget.contact['hotel_name'] != null)
              Text('إدارة ${widget.contact['hotel_name']}', style: const TextStyle(fontSize: 11, color: Colors.white70))
            else if (widget.contact['role_label'] != null)
              Text(widget.contact['role_label'], style: const TextStyle(fontSize: 11, color: Colors.white70)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _fetchMessages(),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _isLoading && _messages.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _messages.isEmpty
                    ? const Center(child: Text('لا توجد رسائل بعد. ابدأ المحادثة الآن.'))
                    : ListView.builder(
                        controller: _scrollCtrl,
                        padding: const EdgeInsets.all(16),
                        itemCount: _messages.length,
                        itemBuilder: (ctx, i) {
                          final m = _messages[i];
                          final isMe = m['is_sender'] == true;
                          final isBroadcast = m['is_broadcast'] == true;
                          return Align(
                            alignment: isMe ? Alignment.centerLeft : Alignment.centerRight,
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              decoration: BoxDecoration(
                                color: isMe
                                    ? const Color(0xFF003580)
                                    : (isBroadcast ? const Color(0xFFFFFBEB) : Colors.white),
                                borderRadius: BorderRadius.circular(16),
                                border: isBroadcast
                                    ? Border.all(color: const Color(0xFFFDE68A), width: 1.2)
                                    : null,
                                boxShadow: [
                                  BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 4, offset: const Offset(0, 2)),
                                ],
                              ),
                              child: Column(
                                crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                                children: [
                                  if (isBroadcast) ...[
                                    Container(
                                      margin: const EdgeInsets.only(bottom: 6),
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: isMe ? Colors.white.withValues(alpha: 0.15) : const Color(0xFFFEF3C7),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(Icons.campaign, size: 14, color: Color(0xFFD97706)),
                                          const SizedBox(width: 4),
                                          Text(
                                            (m['subject'] != null && m['subject'].toString().isNotEmpty)
                                                ? m['subject']
                                                : 'تعميم رسمي من إدارة التطبيق',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.bold,
                                              color: isMe ? Colors.white : const Color(0xFF92400E),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ] else ...[
                                    Text(
                                      m['sender_name'] ?? '',
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                        color: isMe ? Colors.white70 : const Color(0xFF003580),
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                  ],
                                  Text(
                                    m['body'] ?? '',
                                    style: TextStyle(
                                      color: isMe ? Colors.white : (isBroadcast ? const Color(0xFF78350F) : Colors.black87),
                                      fontSize: 13,
                                      height: 1.35,
                                    ),
                                  ),
                                  if (m['attachment_url'] != null && m['attachment_url'].toString().isNotEmpty) ...[
                                    const SizedBox(height: 8),
                                    if (m['is_image'] == true) ...[
                                      ClipRRect(
                                        borderRadius: BorderRadius.circular(10),
                                        child: InkWell(
                                          onTap: () async {
                                            final uri = Uri.parse(m['attachment_url']);
                                            if (await canLaunchUrl(uri)) {
                                              await launchUrl(uri, mode: LaunchMode.externalApplication);
                                            }
                                          },
                                          child: Image.network(
                                            m['attachment_url'],
                                            height: 160,
                                            width: double.infinity,
                                            fit: BoxFit.cover,
                                            errorBuilder: (ctx, err, stack) => Container(
                                              padding: const EdgeInsets.all(10),
                                              color: Colors.grey.shade200,
                                              child: const Row(
                                                children: [
                                                  Icon(Icons.broken_image, size: 18),
                                                  SizedBox(width: 6),
                                                  Text('صورة مرفقة (اضغط للعرض)', style: TextStyle(fontSize: 11)),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ),
                                      ),
                                    ] else ...[
                                      InkWell(
                                        borderRadius: BorderRadius.circular(8),
                                        onTap: () async {
                                          final uri = Uri.parse(m['attachment_url']);
                                          if (await canLaunchUrl(uri)) {
                                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                                          }
                                        },
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                          decoration: BoxDecoration(
                                            color: isMe ? Colors.white.withValues(alpha: 0.15) : const Color(0xFFF1F5F9),
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: isMe ? Colors.white24 : Colors.grey.shade300),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Icon(Icons.attachment, size: 18, color: isMe ? Colors.white : const Color(0xFF003580)),
                                              const SizedBox(width: 6),
                                              Flexible(
                                                child: Text(
                                                  'فتح وتحميل المرفق 📎',
                                                  style: TextStyle(
                                                    fontSize: 12,
                                                    fontWeight: FontWeight.bold,
                                                    color: isMe ? Colors.white : const Color(0xFF003580),
                                                    decoration: TextDecoration.underline,
                                                  ),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                              const SizedBox(width: 4),
                                              Icon(Icons.open_in_new, size: 13, color: isMe ? Colors.white70 : Colors.grey),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ],
                                  const SizedBox(height: 4),
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      if (m['allow_reply'] == false) ...[
                                        const Icon(Icons.lock_outline, size: 10, color: Colors.grey),
                                        const SizedBox(width: 3),
                                        const Text('بدون رد  • ', style: TextStyle(fontSize: 9, color: Colors.grey)),
                                      ],
                                      Text(
                                        m['time'] ?? '',
                                        style: TextStyle(fontSize: 9, color: isMe ? Colors.white60 : Colors.grey),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
          _isReplyForbidden
              ? Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF2F2),
                    border: Border(top: BorderSide(color: Colors.red.shade200, width: 1.5)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.red.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.lock_rounded, color: Color(0xFFDC2626), size: 18),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              'رسالة تعميمية من إدارة التطبيق',
                              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF991B1B)),
                            ),
                            SizedBox(height: 2),
                            Text(
                              'خاصية الرد معطلة من قبل الإدارة لهذه الرسالة الرسمية.',
                              style: TextStyle(fontSize: 11, color: Color(0xFFB91C1C)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                )
              : Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  color: Colors.white,
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _msgCtrl,
                          decoration: InputDecoration(
                            hintText: 'اكتب رسالتك...',
                            hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                            filled: true,
                            fillColor: const Color(0xFFF1F5F9),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide.none),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          ),
                          onSubmitted: (_) => _sendMessage(),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: _sendMessage,
                        icon: const Icon(Icons.send),
                        style: IconButton.styleFrom(backgroundColor: const Color(0xFF003580)),
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
// 5. OFFERS SCREEN
// ============================================================================
class OffersScreen extends StatefulWidget {
  const OffersScreen({super.key});

  @override
  State<OffersScreen> createState() => _OffersScreenState();
}

class _OffersScreenState extends State<OffersScreen> {
  bool _isLoading = true;
  List<dynamic> _offers = [];

  @override
  void initState() {
    super.initState();
    _fetchOffers();
  }

  Future<void> _fetchOffers() async {
    try {
      final res = await http.get(Uri.parse('${MobileState.apiUrl}/offers'));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _offers = body['data'] ?? []);
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('🏷️ العروض والباقات الترويجية'),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _offers.isEmpty
              ? const Center(child: Text('لا توجد عروض حالية.'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _offers.length,
                  itemBuilder: (ctx, i) {
                    final o = _offers[i];
                    return Card(
                      elevation: 2,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (o['image_url'] != null)
                            ClipRRect(
                              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                              child: AspectRatio(
                                aspectRatio: 16 / 8,
                                child: Image.network(o['image_url'], fit: BoxFit.cover, errorBuilder: (context, error, stackTrace) => Container(color: Colors.grey)),
                              ),
                            ),
                          Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(o['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                    ),
                                    if ((o['discount_percentage'] ?? 0) > 0)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(color: Colors.red, borderRadius: BorderRadius.circular(8)),
                                        child: Text('خصم ${o['discount_percentage']}%', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11)),
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(o['hotel_name'] ?? '', style: const TextStyle(color: Color(0xFF003580), fontWeight: FontWeight.w600, fontSize: 12)),
                                const SizedBox(height: 6),
                                Text(o['description'] ?? '', style: const TextStyle(color: Colors.black87, fontSize: 12)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}

// ============================================================================
// 6. MY BOOKINGS SCREEN
// ============================================================================
class MyBookingsScreen extends StatefulWidget {
  final VoidCallback onOpenAuth;
  const MyBookingsScreen({super.key, required this.onOpenAuth});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> {
  bool _isLoading = false;
  List<dynamic> _bookings = [];

  @override
  void initState() {
    super.initState();
    if (MobileState.isLoggedIn) {
      _fetchBookings();
    }
  }

  Future<void> _fetchBookings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${MobileState.apiUrl}/my-bookings'),
        headers: {'Authorization': 'Bearer ${MobileState.token}', 'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (mounted) setState(() => _bookings = body['data'] ?? []);
      }
    } catch (_) {}
    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (!MobileState.isLoggedIn) {
      return Scaffold(
        appBar: AppBar(title: const Text('حجوزاتي وإقاماتي'), backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.hotel_class_outlined, size: 64, color: Color(0xFF003580)),
                const SizedBox(height: 16),
                const Text('إدارة ومتابعة حجوزاتك', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text('سجل الدخول لمشاهدة تفاصيل حجزك، الفواتير، ومواعيد الدخول والمغادرة.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
                const SizedBox(height: 20),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
                  onPressed: widget.onOpenAuth,
                  child: const Text('تسجيل الدخول'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('حجوزاتي وإقاماتي'),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
        actions: [
          IconButton(onPressed: _fetchBookings, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _bookings.isEmpty
              ? const Center(child: Text('لا توجد لديك حجوزات مسجلة حالياً.'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _bookings.length,
                  itemBuilder: (ctx, i) {
                    final b = _bookings[i];
                    return Card(
                      elevation: 2,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(b['hotel_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(color: const Color(0xFFDCFCE7), borderRadius: BorderRadius.circular(6)),
                                  child: Text(b['status_label'] ?? '', style: const TextStyle(color: Color(0xFF166534), fontSize: 11, fontWeight: FontWeight.bold)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text('غرفة: ${b['room_number']} (${b['room_type']})', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('من: ${b['check_in']} إلى ${b['check_out']}', style: const TextStyle(fontSize: 12)),
                                Text(b['total_price_formatted'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF003580))),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

// ============================================================================
// 7. PROFILE SCREEN & EDIT PROFILE SCREEN
// ============================================================================
class ProfileScreen extends StatelessWidget {
  final VoidCallback onOpenAuth;
  final VoidCallback onLoginChanged;
  const ProfileScreen({super.key, required this.onOpenAuth, required this.onLoginChanged});

  @override
  Widget build(BuildContext context) {
    final user = MobileState.currentUser;

    return Scaffold(
      appBar: AppBar(
        title: const Text('الملف الشخصي'),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
        actions: [
          if (MobileState.isLoggedIn)
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'تحديث البيانات',
              onPressed: () async {
                await MobileState.refreshProfile();
                onLoginChanged();
              },
            ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: !MobileState.isLoggedIn
            ? Center(
                child: Column(
                  children: [
                    const SizedBox(height: 40),
                    const Icon(Icons.account_circle, size: 80, color: Color(0xFF003580)),
                    const SizedBox(height: 16),
                    const Text('حسابك في بوابة الفنادق', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    const Text('سجل الدخول لإدارة بياناتك، الحجوزات، والرسائل.', style: TextStyle(color: Colors.grey)),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white),
                      onPressed: onOpenAuth,
                      child: const Text('تسجيل الدخول / إنشاء حساب'),
                    ),
                  ],
                ),
              )
            : Column(
                children: [
                  CircleAvatar(
                    radius: 40,
                    backgroundColor: const Color(0xFF003580),
                    child: Text(
                      (user?['name'] != null && user!['name'].toString().isNotEmpty)
                          ? user['name'].toString().substring(0, 1)
                          : 'U',
                      style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(user?['name'] ?? '', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  Text(user?['email'] ?? '', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                  const SizedBox(height: 16),

                  // زر تعديل الملف الشخصي
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF003580),
                      foregroundColor: Colors.white,
                      minimumSize: const Size.fromHeight(46),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 2,
                    ),
                    onPressed: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => Directionality(
                            textDirection: TextDirection.rtl,
                            child: EditProfileScreen(
                              onSaved: onLoginChanged,
                            ),
                          ),
                        ),
                      );
                      onLoginChanged();
                    },
                    icon: const Icon(Icons.edit, size: 18),
                    label: const Text('تعديل الملف الشخصي ✏️', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),

                  const SizedBox(height: 20),

                  // بطاقة البيانات الشخصية
                  Card(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    child: Column(
                      children: [
                        ListTile(
                          leading: const Icon(Icons.person, color: Color(0xFF003580)),
                          title: const Text('الاسم الكامل للعرض', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(user?['name'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.badge_outlined, color: Color(0xFF003580)),
                          title: const Text('الاسم الأول والأب والكنية', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(
                            '${user?['first_name'] ?? ''} ${user?['father_name'] ?? ''} ${user?['last_name'] ?? ''}'.trim().isNotEmpty
                                ? '${user?['first_name'] ?? ''} ${user?['father_name'] ?? ''} ${user?['last_name'] ?? ''}'.trim()
                                : '—',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.phone, color: Color(0xFF003580)),
                          title: const Text('رقم الهاتف / الجوال', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(user?['phone'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.flag_outlined, color: Color(0xFF003580)),
                          title: const Text('الجنسية', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(user?['nationality'] ?? 'سوري', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.cake_outlined, color: Color(0xFF003580)),
                          title: const Text('تاريخ الميلاد', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(user?['birth_date'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.home_outlined, color: Color(0xFF003580)),
                          title: const Text('عنوان الإقامة / السكن', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(user?['address'] ?? '—', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.credit_card, color: Color(0xFF003580)),
                          title: const Text('الوثيقة الثبوتية والرقم الوطني', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(
                            '${(user?['id_type'] == 'passport' ? 'جواز سفر' : (user?['id_type'] == 'residence' ? 'إقامة' : (user?['id_type'] == 'driver_license' ? 'رخصة قيادة' : 'الهوية الوطنية')))}: ${user?['id_number'] ?? user?['national_id'] ?? '—'}',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                        ),
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.verified_user_outlined, color: Color(0xFF003580)),
                          title: const Text('نوع الحساب وحالة التفعيل', style: TextStyle(fontSize: 12, color: Colors.grey)),
                          subtitle: Text(
                            '${user?['role'] ?? 'نزيل'}  •  ${user?['is_verified'] == true ? 'حساب مؤكد ومفعل ✓' : 'بانتظار التفعيل'}',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red.shade700,
                      foregroundColor: Colors.white,
                      minimumSize: const Size.fromHeight(48),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () async {
                      await MobileState.clearSession();
                      onLoginChanged();
                    },
                    icon: const Icon(Icons.logout),
                    label: const Text('تسجيل الخروج'),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
      ),
    );
  }
}

// ============================================================================
// EDIT PROFILE SCREEN (تعديل الملف الشخصي بنفس حقول وترتيب الموقع)
// ============================================================================
class EditProfileScreen extends StatefulWidget {
  final VoidCallback onSaved;
  const EditProfileScreen({super.key, required this.onSaved});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameCtrl = TextEditingController();
  final _fatherNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _nationalityCtrl = TextEditingController(text: 'سوري');
  final _birthDateCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  final _idNumberCtrl = TextEditingController();
  String _idType = 'national_id';

  bool _isLoading = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    final u = MobileState.currentUser;
    if (u != null) {
      _firstNameCtrl.text = u['first_name'] ?? '';
      _fatherNameCtrl.text = u['father_name'] ?? '';
      _lastNameCtrl.text = u['last_name'] ?? '';
      _nameCtrl.text = u['name'] ?? '';
      _phoneCtrl.text = u['phone'] ?? '';
      _nationalityCtrl.text = u['nationality'] ?? 'سوري';
      _birthDateCtrl.text = u['birth_date'] ?? '';
      _addressCtrl.text = u['address'] ?? '';
      _idNumberCtrl.text = u['id_number'] ?? u['national_id'] ?? '';
      if (u['id_type'] != null && u['id_type'].toString().isNotEmpty) {
        _idType = u['id_type'];
      }
    }
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _fatherNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _nationalityCtrl.dispose();
    _birthDateCtrl.dispose();
    _addressCtrl.dispose();
    _idNumberCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickBirthDate() async {
    final now = DateTime.now();
    DateTime initial = DateTime(1995, 1, 1);
    if (_birthDateCtrl.text.isNotEmpty) {
      try {
        initial = DateTime.parse(_birthDateCtrl.text);
      } catch (_) {}
    }
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1930),
      lastDate: now,
      helpText: 'اختر تاريخ الميلاد',
      cancelText: 'إلغاء',
      confirmText: 'تأكيد',
    );
    if (picked != null) {
      setState(() {
        _birthDateCtrl.text =
            '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
      });
    }
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/profile'),
        headers: {
          'Authorization': 'Bearer ${MobileState.token}',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'first_name': _firstNameCtrl.text.trim(),
          'father_name': _fatherNameCtrl.text.trim(),
          'last_name': _lastNameCtrl.text.trim(),
          'name': _nameCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'nationality': _nationalityCtrl.text.trim(),
          'birth_date': _birthDateCtrl.text.trim().isNotEmpty ? _birthDateCtrl.text.trim() : null,
          'address': _addressCtrl.text.trim().isNotEmpty ? _addressCtrl.text.trim() : null,
          'id_type': _idType,
          'id_number': _idNumberCtrl.text.trim().isNotEmpty ? _idNumberCtrl.text.trim() : null,
          'national_id': _idNumberCtrl.text.trim().isNotEmpty ? _idNumberCtrl.text.trim() : null,
        }),
      );

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        if (body['user'] != null) {
          await MobileState.saveSession(MobileState.token!, body['user']);
        }
        widget.onSaved();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: Colors.green,
              content: Text(body['message'] ?? 'تم حفظ وتحديث الملف الشخصي بنجاح'),
            ),
          );
          Navigator.pop(context);
        }
      } else {
        setState(() {
          _errorMessage = body['message'] ?? 'تعذر حفظ البيانات، يرجى التحقق من المدخلات.';
        });
      }
    } catch (_) {
      setState(() {
        _errorMessage = 'حدث خطأ في الاتصال بالسيرفر.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('تعديل الملف الشخصي'),
        backgroundColor: const Color(0xFF003580),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(18),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (_errorMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.red.shade200),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline, color: Colors.red),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(_errorMessage!, style: const TextStyle(color: Colors.red, fontSize: 13)),
                      ),
                    ],
                  ),
                ),
              ],

              // القسم الأول: البيانات الشخصية وبيانات الاتصال
              Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.person, color: Color(0xFF003580), size: 20),
                          SizedBox(width: 8),
                          Text('1. البيانات الشخصية وبيانات الاتصال', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF003580))),
                        ],
                      ),
                      const Divider(height: 24),

                      // الاسم الأول
                      TextFormField(
                        controller: _firstNameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'الاسم الأول *',
                          prefixIcon: Icon(Icons.person_outline),
                          border: OutlineInputBorder(),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'الاسم الأول مطلوب' : null,
                      ),
                      const SizedBox(height: 12),

                      // اسم الأب
                      TextFormField(
                        controller: _fatherNameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'اسم الأب *',
                          prefixIcon: Icon(Icons.person_outline),
                          border: OutlineInputBorder(),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'اسم الأب مطلوب' : null,
                      ),
                      const SizedBox(height: 12),

                      // النسبة / الكنية
                      TextFormField(
                        controller: _lastNameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'النسبة / الكنية *',
                          prefixIcon: Icon(Icons.person_outline),
                          border: OutlineInputBorder(),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'النسبة / الكنية مطلوبة' : null,
                      ),
                      const SizedBox(height: 12),

                      // الاسم الكامل للعرض
                      TextFormField(
                        controller: _nameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'الاسم الكامل للعرض',
                          hintText: 'يتم تكوينه تلقائياً إذا ترك فارغاً',
                          prefixIcon: Icon(Icons.badge_outlined),
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // رقم الهاتف / الجوال
                      TextFormField(
                        controller: _phoneCtrl,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(
                          labelText: 'رقم الهاتف / الجوال *',
                          prefixIcon: Icon(Icons.phone),
                          border: OutlineInputBorder(),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'رقم الهاتف مطلوب' : null,
                      ),
                      const SizedBox(height: 12),

                      // الجنسية
                      TextFormField(
                        controller: _nationalityCtrl,
                        decoration: const InputDecoration(
                          labelText: 'الجنسية',
                          prefixIcon: Icon(Icons.flag_outlined),
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // تاريخ الميلاد
                      TextFormField(
                        controller: _birthDateCtrl,
                        readOnly: true,
                        onTap: _pickBirthDate,
                        decoration: InputDecoration(
                          labelText: 'تاريخ الميلاد',
                          hintText: 'YYYY-MM-DD',
                          prefixIcon: const Icon(Icons.cake_outlined),
                          suffixIcon: IconButton(
                            icon: const Icon(Icons.calendar_month),
                            onPressed: _pickBirthDate,
                          ),
                          border: const OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // عنوان السكن
                      TextFormField(
                        controller: _addressCtrl,
                        maxLines: 2,
                        decoration: const InputDecoration(
                          labelText: 'عنوان السكن / الإقامة الدائم',
                          prefixIcon: Icon(Icons.home_outlined),
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 16),

              // القسم الثاني: الوثائق الثبوتية
              Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.assignment_ind, color: Color(0xFF003580), size: 20),
                          SizedBox(width: 8),
                          Text('2. الوثائق الثبوتية', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF003580))),
                        ],
                      ),
                      const Divider(height: 24),

                      // نوع الوثيقة
                      DropdownButtonFormField<String>(
                        value: _idType,
                        decoration: const InputDecoration(
                          labelText: 'نوع الوثيقة',
                          prefixIcon: Icon(Icons.credit_card),
                          border: OutlineInputBorder(),
                        ),
                        items: const [
                          DropdownMenuItem(value: 'national_id', child: Text('الهوية الوطنية / البطاقة الشخصية')),
                          DropdownMenuItem(value: 'passport', child: Text('جواز السفر')),
                          DropdownMenuItem(value: 'residence', child: Text('بطاقة إقامة')),
                          DropdownMenuItem(value: 'driver_license', child: Text('رخصة قيادة')),
                        ],
                        onChanged: (val) {
                          if (val != null) setState(() => _idType = val);
                        },
                      ),
                      const SizedBox(height: 12),

                      // رقم الوثيقة
                      TextFormField(
                        controller: _idNumberCtrl,
                        decoration: const InputDecoration(
                          labelText: 'رقم الوثيقة / الجواز / الرقم الوطني',
                          prefixIcon: Icon(Icons.numbers),
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 24),

              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF003580),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: _isLoading ? null : _saveProfile,
                icon: _isLoading
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Icon(Icons.save),
                label: const Text('حفظ التعديلات', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 8. AUTH BOTTOM SHEET (Login, Register with Email/Gmail & Code Verification)
// ============================================================================
class AuthBottomSheet extends StatefulWidget {
  final VoidCallback onSuccess;
  const AuthBottomSheet({super.key, required this.onSuccess});

  @override
  State<AuthBottomSheet> createState() => _AuthBottomSheetState();
}

class _AuthBottomSheetState extends State<AuthBottomSheet> {
  bool _isRegister = false;
  bool _isVerifyingCode = false;
  String _pendingEmail = '';

  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _passConfirmCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();
  bool _loading = false;
  String? _err;

  Future<void> _submitLogin() async {
    setState(() {
      _loading = true;
      _err = null;
    });

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/auth/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': _emailCtrl.text.trim(),
          'password': _passCtrl.text,
          'client_type': 'mobile',
        }),
      );

      final body = jsonDecode(res.body);

      if (res.statusCode == 200 && body['status'] == 'success') {
        await MobileState.saveSession(body['token'], body['user']);
        widget.onSuccess();
      } else if (body['status'] == 'unverified') {
        setState(() {
          _isVerifyingCode = true;
          _pendingEmail = body['email'] ?? _emailCtrl.text.trim();
        });
      } else {
        setState(() => _err = body['message'] ?? 'فشل تسجيل الدخول.');
      }
    } catch (_) {
      setState(() => _err = 'تعذر الاتصال بالخادم.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submitRegister() async {
    final name = _nameCtrl.text.trim();
    final email = _emailCtrl.text.trim();
    final phone = _phoneCtrl.text.trim();
    final pass = _passCtrl.text;
    final passConfirm = _passConfirmCtrl.text;

    if (name.isEmpty || email.isEmpty || pass.isEmpty || passConfirm.isEmpty) {
      setState(() => _err = 'يرجى ملء جميع الحقول المطلوبة (*).');
      return;
    }

    if (pass.length < 6) {
      setState(() => _err = 'يجب ألا تقل كلمة المرور عن 6 أحرف.');
      return;
    }

    if (pass != passConfirm) {
      setState(() => _err = 'تأكيد كلمة المرور غير متطابق.');
      return;
    }

    setState(() {
      _loading = true;
      _err = null;
    });

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/auth/register'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'phone': phone,
          'password': pass,
          'password_confirmation': passConfirm,
        }),
      );

      Map<String, dynamic>? body;
      try {
        body = jsonDecode(res.body);
      } catch (_) {}

      if (res.statusCode == 200 && body != null && body['status'] == 'success') {
        setState(() {
          _isVerifyingCode = true;
          _pendingEmail = body?['email'] ?? email;
        });
      } else {
        String msg = body?['message'] ?? 'فشل إنشاء الحساب.';
        if (body?['errors'] != null && body!['errors'] is Map && (body['errors'] as Map).isNotEmpty) {
          final firstErr = (body['errors'] as Map).values.first;
          if (firstErr is List && firstErr.isNotEmpty) {
            msg = firstErr.first.toString();
          }
        }
        setState(() => _err = msg);
      }
    } catch (_) {
      setState(() => _err = 'تعذر الاتصال بالخادم.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submitVerifyCode() async {
    setState(() {
      _loading = true;
      _err = null;
    });

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/auth/verify-code'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': _pendingEmail,
          'code': _codeCtrl.text.trim(),
        }),
      );

      final body = jsonDecode(res.body);

      if (res.statusCode == 200 && body['status'] == 'success') {
        await MobileState.saveSession(body['token'], body['user']);
        widget.onSuccess();
      } else {
        setState(() => _err = body['message'] ?? 'رمز التحقق غير صحيح.');
      }
    } catch (_) {
      setState(() => _err = 'تعذر الاتصال بالخادم.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resendCode() async {
    if (_pendingEmail.isEmpty) return;
    setState(() {
      _loading = true;
      _err = null;
    });
    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/auth/resend-code'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': _pendingEmail}),
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تم إرسال كود تفعيل جديد من info@ahmadinsights.online بنجاح.')),
        );
      } else {
        setState(() => _err = body['message'] ?? 'فشل إعادة الإرسال.');
      }
    } catch (_) {
      setState(() => _err = 'تعذر الاتصال بالخادم.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openForgotPasswordDialog() {
    final resetEmailCtrl = TextEditingController(text: _emailCtrl.text.trim());
    final resetCodeCtrl = TextEditingController();
    final newPassCtrl = TextEditingController();
    final newPassConfirmCtrl = TextEditingController();

    int step = 1;
    bool dialogLoading = false;
    String? dialogErr;
    String? dialogSuccess;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) {
          Future<void> sendResetEmail() async {
            final email = resetEmailCtrl.text.trim();
            if (email.isEmpty) {
              setDialogState(() => dialogErr = 'يرجى إدخال البريد الإلكتروني.');
              return;
            }
            setDialogState(() {
              dialogLoading = true;
              dialogErr = null;
              dialogSuccess = null;
            });

            try {
              final res = await http.post(
                Uri.parse('${MobileState.apiUrl}/auth/forgot-password'),
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: jsonEncode({'email': email}),
              );
              Map<String, dynamic>? data;
              try { data = jsonDecode(res.body); } catch (_) {}

              if (res.statusCode == 200 && data != null && data['status'] == 'success') {
                setDialogState(() {
                  step = 2;
                  dialogLoading = false;
                  dialogSuccess = 'تم إرسال كود الاستعادة المكون من 6 أرقام من (info@ahmadinsights.online) إلى بريدك.';
                });
              } else {
                setDialogState(() {
                  dialogLoading = false;
                  dialogErr = data?['message'] ?? 'هذا البريد غير مسجل كحساب نزيل لدينا.';
                });
              }
            } catch (_) {
              setDialogState(() {
                dialogLoading = false;
                dialogErr = 'تعذر الاتصال بالخادم.';
              });
            }
          }

          Future<void> submitNewPassword() async {
            final email = resetEmailCtrl.text.trim();
            final code = resetCodeCtrl.text.trim();
            final pass = newPassCtrl.text;
            final passConfirm = newPassConfirmCtrl.text;

            if (code.isEmpty || pass.isEmpty || passConfirm.isEmpty) {
              setDialogState(() => dialogErr = 'يرجى إكمال جميع الحقول المطلوبة.');
              return;
            }
            if (pass.length < 6) {
              setDialogState(() => dialogErr = 'يجب ألا تقل كلمة المرور عن 6 أحرف.');
              return;
            }
            if (pass != passConfirm) {
              setDialogState(() => dialogErr = 'تأكيد كلمة المرور غير متطابق.');
              return;
            }

            setDialogState(() {
              dialogLoading = true;
              dialogErr = null;
            });

            try {
              final res = await http.post(
                Uri.parse('${MobileState.apiUrl}/auth/reset-password'),
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: jsonEncode({
                  'email': email,
                  'code': code,
                  'password': pass,
                  'password_confirmation': passConfirm,
                }),
              );

              Map<String, dynamic>? data;
              try { data = jsonDecode(res.body); } catch (_) {}

              if (res.statusCode == 200 && data != null && data['status'] == 'success') {
                if (ctx.mounted) Navigator.pop(ctx);
                if (mounted) {
                  _emailCtrl.text = email;
                  _passCtrl.text = pass;
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('تم تعيين كلمة المرور الجديدة بنجاح! يمكنك الآن تسجيل الدخول.')),
                  );
                }
              } else {
                setDialogState(() {
                  dialogLoading = false;
                  dialogErr = data?['message'] ?? 'كود الاستعادة غير صحيح أو منتهي الصلاحية.';
                });
              }
            } catch (_) {
              setDialogState(() {
                dialogLoading = false;
                dialogErr = 'تعذر الاتصال بالخادم.';
              });
            }
          }

          return Directionality(
            textDirection: TextDirection.rtl,
            child: AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                    child: const Icon(Icons.lock_reset, color: Color(0xFF003580), size: 24),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text('استعادة كلمة المرور', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (dialogErr != null)
                      Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(8)),
                        child: Text(dialogErr!, style: TextStyle(color: Colors.red.shade700, fontSize: 12), textAlign: TextAlign.center),
                      ),
                    if (dialogSuccess != null)
                      Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(8)),
                        child: Text(dialogSuccess!, style: TextStyle(color: Colors.green.shade800, fontSize: 12), textAlign: TextAlign.center),
                      ),

                    if (step == 1) ...[
                      const Text(
                        'خاص بالزبائن المسجلين: أدخل بريدك الإلكتروني وسنرسل لك كود الاستعادة المكون من 6 أرقام من (info@ahmadinsights.online):',
                        style: TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
                      ),
                      const SizedBox(height: 14),
                      TextField(
                        controller: resetEmailCtrl,
                        keyboardType: TextInputType.emailAddress,
                        decoration: InputDecoration(
                          labelText: 'البريد الإلكتروني المسجل *',
                          prefixIcon: const Icon(Icons.email_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ] else ...[
                      const Text(
                        'أدخل كود الاستعادة المكون من 6 أرقام المرسل إلى بريدك، ثم أدخل كلمة المرور الجديدة:',
                        style: TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
                      ),
                      const SizedBox(height: 14),
                      TextField(
                        controller: resetCodeCtrl,
                        keyboardType: TextInputType.number,
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 4),
                        decoration: InputDecoration(
                          labelText: 'كود الاستعادة (6 أرقام) *',
                          prefixIcon: const Icon(Icons.vpn_key_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: newPassCtrl,
                        obscureText: true,
                        decoration: InputDecoration(
                          labelText: 'كلمة المرور الجديدة *',
                          prefixIcon: const Icon(Icons.lock_outline),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: newPassConfirmCtrl,
                        obscureText: true,
                        decoration: InputDecoration(
                          labelText: 'تأكيد كلمة المرور الجديدة *',
                          prefixIcon: const Icon(Icons.lock_reset),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('إلغاء'),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF003580),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: dialogLoading ? null : (step == 1 ? sendResetEmail : submitNewPassword),
                  child: dialogLoading
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : Text(step == 1 ? 'إرسال كود الاستعادة' : 'حفظ كلمة المرور'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _signInWithGoogleNative() async {
    setState(() {
      _loading = true;
      _err = null;
    });

    try {
      try {
        await GoogleSignIn.instance.initialize(
          clientId: '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com',
          serverClientId: '467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com',
        );
      } catch (_) {}

      try {
        await GoogleSignIn.instance.signOut();
      } catch (_) {}

      final account = await GoogleSignIn.instance.authenticate(
        scopeHint: ['email', 'profile'],
      );

      final email = account.email;
      final name = account.displayName ?? email.split('@')[0];
      final googleId = account.id;
      String? idToken;
      try {
        idToken = account.authentication.idToken;
      } catch (_) {}

      await _executeGoogleLogin(email, name, googleId: googleId, idToken: idToken);
    } on GoogleSignInException catch (e) {
      if (e.code == GoogleSignInExceptionCode.canceled) {
        if (mounted) setState(() => _loading = false);
        return;
      }
      _openGoogleSignInDialogFallback(e.description ?? e.code.toString());
    } catch (e) {
      // If native SDK fails or Google Play Services is missing, fall back to Google dialog
      _openGoogleSignInDialogFallback(e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openGoogleSignInDialogFallback([String? originalError]) {
    final gmailCtrl = TextEditingController(text: _emailCtrl.text.isNotEmpty ? _emailCtrl.text : '');
    final gnameCtrl = TextEditingController(text: _nameCtrl.text.isNotEmpty ? _nameCtrl.text : '');

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(10)),
                child: const Text('G', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.red)),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text('تسجيل الدخول بحساب Google الرسمي', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'يمكنك التوجه مباشرة لصفحة الدخول الرسمية لـ Google Accounts بحسابك الحقيقي أو تأكيد بريدك على Gmail:',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
              ),
              const SizedBox(height: 14),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF1E293B),
                  side: BorderSide(color: Colors.grey.shade300),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.open_in_browser, color: Color(0xFF003580), size: 20),
                label: const Text('التوجه المباشر لحساب Google في المتصفح 🌐', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                onPressed: () async {
                  final webGoogleUrl = Uri.parse('${MobileState.apiUrl.replaceAll('/api', '')}/auth/google');
                  if (await canLaunchUrl(webGoogleUrl)) {
                    await launchUrl(webGoogleUrl, mode: LaunchMode.externalApplication);
                  }
                },
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: Text('أو تأكيد Gmail سريعاً', style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                  ),
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                ],
              ),
              const SizedBox(height: 10),
              TextField(
                controller: gmailCtrl,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(
                  labelText: 'بريدك على Gmail الحقيقي *',
                  hintText: 'yourname@gmail.com',
                  prefixIcon: const Icon(Icons.email_outlined),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: gnameCtrl,
                decoration: InputDecoration(
                  labelText: 'اسمك الكامل',
                  hintText: 'الاسم كما في حساب جوجل',
                  prefixIcon: const Icon(Icons.person_outline),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF003580),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () {
                final email = gmailCtrl.text.trim();
                final name = gnameCtrl.text.trim();
                if (email.isEmpty) return;
                Navigator.pop(ctx);
                _executeGoogleLogin(email, name);
              },
              child: const Text('متابعة الدخول'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _executeGoogleLogin(String email, String name, {String? googleId, String? idToken}) async {
    setState(() {
      _loading = true;
      _err = null;
    });

    try {
      final res = await http.post(
        Uri.parse('${MobileState.apiUrl}/auth/google-login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': email,
          'name': name.isNotEmpty ? name : email.split('@')[0],
          'google_id': (googleId != null && googleId.isNotEmpty) ? googleId : 'google_${email.hashCode}',
          if (idToken != null && idToken.isNotEmpty) 'id_token': idToken,
        }),
      );

      Map<String, dynamic>? body;
      try {
        body = jsonDecode(res.body);
      } catch (_) {}

      if (res.statusCode == 200 && body != null && body['status'] == 'success') {
        await MobileState.saveSession(body['token'], body['user']);
        await MobileNotificationService.checkNewNotifications();
        widget.onSuccess();
      } else {
        setState(() => _err = body?['message'] ?? 'تعذر تسجيل الدخول بحساب Google.');
      }
    } catch (_) {
      setState(() => _err = 'تعذر الاتصال بالخادم.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
        top: 24,
        left: 20,
        right: 20,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
            ),
            const SizedBox(height: 16),

            // Step 1: Verification Code
            if (_isVerifyingCode) ...[
              const Text('رمز تأكيد وتفعيل الحساب 🔑', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFBFDBFE)),
                ),
                child: Text(
                  'تم إرسال كود التفعيل المكون من 6 أرقام من البريد الرسمي (info@ahmadinsights.online) إلى بريدك:\n$_pendingEmail\nيرجى مراجعة صندوق الوارد لإدخال الكود أدناه.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF003580), fontWeight: FontWeight.w600, height: 1.5),
                ),
              ),
              const SizedBox(height: 16),
              if (_err != null) Text(_err!, style: const TextStyle(color: Colors.red, fontSize: 12), textAlign: TextAlign.center),
              const SizedBox(height: 10),
              TextField(
                controller: _codeCtrl,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, letterSpacing: 8),
                decoration: InputDecoration(
                  hintText: '------',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
                onPressed: _loading ? null : _submitVerifyCode,
                child: _loading ? const CircularProgressIndicator(color: Colors.white) : const Text('تأكيد الدخول والتفعيل', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 8),
              TextButton.icon(
                onPressed: _loading ? null : _resendCode,
                icon: const Icon(Icons.refresh, size: 16),
                label: const Text('إعادة إرسال رمز التفعيل إلى بريدك'),
              ),
            ]
            // Step 2: Login or Register Form
            else ...[
              Text(
                _isRegister ? 'إنشاء حساب نزيل جديد' : 'تسجيل الدخول إلى حسابك',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              if (_err != null) Text(_err!, style: const TextStyle(color: Colors.red, fontSize: 12), textAlign: TextAlign.center),

              if (_isRegister) ...[
                TextField(
                  controller: _nameCtrl,
                  decoration: const InputDecoration(labelText: 'الاسم الكامل *', prefixIcon: Icon(Icons.person_outline)),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'البريد الإلكتروني *', prefixIcon: Icon(Icons.email_outlined)),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _phoneCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(labelText: 'رقم الهاتف / الجوال', prefixIcon: Icon(Icons.phone_outlined)),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _passCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'كلمة المرور *', prefixIcon: Icon(Icons.lock_outline)),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _passConfirmCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور *', prefixIcon: Icon(Icons.lock_reset)),
                ),
              ] else ...[
                TextField(
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'البريد الإلكتروني *', prefixIcon: Icon(Icons.email_outlined)),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _passCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'كلمة المرور *', prefixIcon: Icon(Icons.lock_outline)),
                ),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton(
                    onPressed: _openForgotPasswordDialog,
                    child: const Text('نسيت كلمة المرور؟', style: TextStyle(fontSize: 12, color: Color(0xFF003580), fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
              const SizedBox(height: 16),

              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF003580), foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
                onPressed: _loading ? null : (_isRegister ? _submitRegister : _submitLogin),
                child: _loading ? const CircularProgressIndicator(color: Colors.white) : Text(_isRegister ? 'إكمال التسجيل واستلام كود التفعيل 📨' : 'تسجيل الدخول', style: const TextStyle(fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 12),

              // Google Login Option
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  side: BorderSide(color: Colors.grey.shade300),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: _loading ? null : _signInWithGoogleNative,
                icon: const Text('G', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.red)),
                label: const Text('المتابعة بحساب Google الرسمي'),
              ),
              const SizedBox(height: 12),

              TextButton(
                onPressed: () {
                  setState(() {
                    _isRegister = !_isRegister;
                    _err = null;
                  });
                },
                child: Text(_isRegister ? 'لديك حساب بالفعل؟ سجل الدخول' : 'ليس لديك حساب؟ سجل حساباً جديداً'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
