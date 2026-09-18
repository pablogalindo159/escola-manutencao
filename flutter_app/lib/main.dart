import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/course_provider.dart';
import 'providers/repair_provider.dart';
import 'providers/community_provider.dart';
import 'screens/auth/splash_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/home/home_screen.dart';
import 'screens/home/course_detail_screen.dart';
import 'screens/video/course_progress_screen.dart';
import 'screens/video/video_player_screen.dart';
import 'screens/repairs/repairs_list_screen.dart';
import 'screens/repairs/repair_detail_screen.dart';
import 'screens/repairs/repair_form_screen.dart';
import 'screens/repairs/repair_photo_screen.dart';
import 'screens/community/posts_screen.dart';
import 'screens/community/post_detail_screen.dart';
import 'screens/community/post_form_screen.dart';
import 'screens/profile/profile_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CourseProvider()),
        ChangeNotifierProvider(create: (_) => VideoProgressProvider()),
        ChangeNotifierProvider(create: (_) => RepairProvider()),
        ChangeNotifierProvider(create: (_) => CommunityProvider()),
      ],
      child: MaterialApp(
        title: 'Escola da Manutenção',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          primaryColor: const Color(0xFF0066FF),
          useMaterial3: true,
          appBarTheme: const AppBarTheme(
            elevation: 0,
            backgroundColor: Color(0xFF0066FF),
            foregroundColor: Colors.white,
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0066FF),
              foregroundColor: Colors.white,
              textStyle: const TextStyle(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 14,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(
                color: Color(0xFFE0E0E0),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(
                color: Color(0xFF0066FF),
                width: 2,
              ),
            ),
          ),
        ),
        home: const SplashScreen(),
        routes: {
          '/login': (context) => const LoginScreen(),
          '/register': (context) => const RegisterScreen(),
          '/home': (context) => const HomeScreen(),
          '/profile': (context) => const ProfileScreen(),

          '/course-detail': (context) {
            final courseId = ModalRoute.of(context)!.settings.arguments as int;
            return CourseDetailScreen(courseId: courseId);
          },
          '/course-progress': (context) {
            final courseId = ModalRoute.of(context)!.settings.arguments as int;
            return CourseProgressScreen(courseId: courseId);
          },
          '/video-player': (context) {
            final args = ModalRoute.of(context)!.settings.arguments as Map;
            return VideoPlayerScreen(
              courseId: args['courseId'] as int,
              videoId: args['videoId'] as int,
            );
          },

          '/repairs': (context) => const RepairsListScreen(),
          '/repair-form': (context) => const RepairFormScreen(),
          '/repair-detail': (context) {
            final repairId = ModalRoute.of(context)!.settings.arguments as int;
            return RepairDetailScreen(repairId: repairId);
          },
          '/repair-photo': (context) {
            final repairId = ModalRoute.of(context)!.settings.arguments as int;
            return RepairPhotoScreen(repairId: repairId);
          },

          '/posts': (context) {
            final courseId = ModalRoute.of(context)?.settings.arguments as int?;
            return PostsScreen(courseId: courseId);
          },
          '/post-detail': (context) {
            final postId = ModalRoute.of(context)!.settings.arguments as int;
            return PostDetailScreen(postId: postId);
          },
          '/post-form': (context) {
            final courseId = ModalRoute.of(context)?.settings.arguments as int?;
            return PostFormScreen(courseId: courseId);
          },
        },
      ),
    );
  }
}
