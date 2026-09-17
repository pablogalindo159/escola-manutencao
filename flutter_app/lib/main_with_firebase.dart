import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:firebase_core/firebase_core.dart';
import 'firebase_options.dart'; // TODO: Gerar com: flutterfire configure
import 'providers/auth_provider.dart';
import 'providers/course_provider.dart';
import 'providers/repair_provider.dart';
import 'providers/community_provider.dart';
import 'providers/certificate_provider.dart';
import 'providers/payment_provider.dart';
import 'providers/notification_provider.dart';
import 'providers/video_progress_provider.dart';
import 'screens/auth/splash_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/home/home_screen.dart';
import 'screens/home/course_detail_screen.dart';
import 'screens/video/video_player_screen.dart';
import 'screens/video/course_progress_screen.dart';
import 'screens/repairs/repairs_list_screen.dart';
import 'screens/repairs/repair_detail_screen.dart';
import 'screens/repairs/repair_form_screen.dart';
import 'screens/repairs/repair_photo_screen.dart';
import 'screens/profile/profile_screen.dart';
import 'screens/community/posts_screen.dart';
import 'screens/community/post_detail_screen.dart';
import 'screens/community/post_form_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  // Initialize Notification Provider
  final notificationProvider = NotificationProvider();
  await notificationProvider.initializeFirebaseMessaging();

  runApp(
    MyApp(
      notificationProvider: notificationProvider,
    ),
  );
}

class MyApp extends StatelessWidget {
  final NotificationProvider notificationProvider;

  const MyApp({
    Key? key,
    required this.notificationProvider,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        // Auth Provider
        ChangeNotifierProvider(create: (_) => AuthProvider()),

        // Course Providers
        ChangeNotifierProvider(create: (_) => CourseProvider()),
        ChangeNotifierProvider(create: (_) => VideoProgressProvider()),

        // Repair Provider
        ChangeNotifierProvider(create: (_) => RepairProvider()),

        // Community Provider
        ChangeNotifierProvider(create: (_) => CommunityProvider()),

        // Certificate Provider
        ChangeNotifierProvider(create: (_) => CertificateProvider()),

        // Payment Provider
        ChangeNotifierProvider(create: (_) => PaymentProvider()),

        // Notification Provider
        ChangeNotifierProvider.value(
          value: notificationProvider,
        ),
      ],
      child: MaterialApp(
        title: 'Escola da Manutenção',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          primaryColor: const Color(0xFF0066FF),
          scaffoldBackgroundColor: Colors.white,
          appBarTheme: const AppBarTheme(
            elevation: 0,
            backgroundColor: Color(0xFF0066FF),
            foregroundColor: Colors.white,
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.grey[50],
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey[300]!),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(
                color: Color(0xFF0066FF),
                width: 2,
              ),
            ),
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0066FF),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          fontFamily: 'Inter',
        ),
        initialRoute: '/splash',
        routes: {
          '/splash': (context) => const SplashScreen(),
          '/login': (context) => const LoginScreen(),
          '/register': (context) => const RegisterScreen(),
          '/home': (context) => const HomeScreen(),
          '/course-detail': (context) => const CourseDetailScreen(
                courseId: 0,
              ),
          '/video-player': (context) => const VideoPlayerScreen(
                courseId: 0,
                videoId: 0,
              ),
          '/course-progress': (context) => const CourseProgressScreen(
                courseId: 0,
              ),
          '/repairs': (context) => const RepairsListScreen(),
          '/repair-detail': (context) => const RepairDetailScreen(
                repairId: 0,
              ),
          '/repair-form': (context) => const RepairFormScreen(),
          '/repair-photo': (context) => const RepairPhotoScreen(
                repairId: 0,
              ),
          '/profile': (context) => const ProfileScreen(),
          '/posts': (context) => const PostsScreen(),
          '/post-detail': (context) => const PostDetailScreen(
                postId: 0,
              ),
          '/post-form': (context) => const PostFormScreen(),
        },
        onGenerateRoute: (settings) {
          // Handle dynamic routes
          if (settings.name == '/course-detail') {
            final courseId = settings.arguments as int?;
            return MaterialPageRoute(
              builder: (context) =>
                  CourseDetailScreen(courseId: courseId ?? 0),
            );
          }
          if (settings.name == '/video-player') {
            final args = settings.arguments as Map<String, int>?;
            return MaterialPageRoute(
              builder: (context) => VideoPlayerScreen(
                courseId: args?['courseId'] ?? 0,
                videoId: args?['videoId'] ?? 0,
              ),
            );
          }
          if (settings.name == '/repair-detail') {
            final repairId = settings.arguments as int?;
            return MaterialPageRoute(
              builder: (context) =>
                  RepairDetailScreen(repairId: repairId ?? 0),
            );
          }
          if (settings.name == '/repair-photo') {
            final repairId = settings.arguments as int?;
            return MaterialPageRoute(
              builder: (context) =>
                  RepairPhotoScreen(repairId: repairId ?? 0),
            );
          }
          if (settings.name == '/post-detail') {
            final postId = settings.arguments as int?;
            return MaterialPageRoute(
              builder: (context) => PostDetailScreen(postId: postId ?? 0),
            );
          }
          return null;
        },
      ),
    );
  }
}
