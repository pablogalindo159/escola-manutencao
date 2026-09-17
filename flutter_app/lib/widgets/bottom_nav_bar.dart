import 'package:flutter/material.dart';

class BottomNavBar extends StatelessWidget {
  final int currentIndex;
  final Function(int) onTap;

  const BottomNavBar({
    Key? key,
    required this.currentIndex,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return BottomNavigationBar(
      currentIndex: currentIndex,
      onTap: onTap,
      type: BottomNavigationBarType.fixed,
      backgroundColor: Colors.white,
      selectedItemColor: const Color(0xFF0066FF),
      unselectedItemColor: Colors.grey[400],
      elevation: 8,
      items: [
        BottomNavigationBarItem(
          icon: Icon(
            currentIndex == 0 ? Icons.home : Icons.home_outlined,
          ),
          label: 'Home',
        ),
        BottomNavigationBarItem(
          icon: Icon(
            currentIndex == 1 ? Icons.play_circle : Icons.play_circle_outlined,
          ),
          label: 'Meus Cursos',
        ),
        BottomNavigationBarItem(
          icon: Icon(
            currentIndex == 2 ? Icons.build : Icons.build_outlined,
          ),
          label: 'Reparos',
        ),
        BottomNavigationBarItem(
          icon: Icon(
            currentIndex == 3
                ? Icons.people_alt
                : Icons.people_alt_outlined,
          ),
          label: 'Comunidade',
        ),
        BottomNavigationBarItem(
          icon: Icon(
            currentIndex == 4 ? Icons.person : Icons.person_outlined,
          ),
          label: 'Perfil',
        ),
      ],
    );
  }
}
