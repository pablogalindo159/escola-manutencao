import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:flutter_app/providers/auth_provider.dart';
import 'package:flutter_app/services/api_service.dart';

@GenerateMocks([ApiService])
void main() {
  group('AuthProvider Tests', () {
    late AuthProvider authProvider;
    late MockApiService mockApiService;

    setUp(() {
      mockApiService = MockApiService();
      authProvider = AuthProvider();
      // Injetar mock na API
    });

    group('Login', () {
      test('Login com credenciais válidas deve retornar true', () async {
        // Arrange
        const email = 'test@example.com';
        const password = 'password123';
        const expectedToken = 'fake-jwt-token';

        when(mockApiService.login(email, password)).thenAnswer(
          (_) async => {'token': expectedToken, 'user': {}},
        );

        // Act
        final result = await authProvider.login(email, password);

        // Assert
        expect(result, true);
        expect(authProvider.currentUser, isNotNull);
      });

      test('Login com email inválido deve retornar false', () async {
        // Arrange
        const email = 'invalid-email';
        const password = 'password123';

        when(mockApiService.login(email, password))
            .throwException('Invalid email');

        // Act
        final result = await authProvider.login(email, password);

        // Assert
        expect(result, false);
        expect(authProvider.errorMessage, isNotNull);
      });

      test('Login com senha incorreta deve retornar false', () async {
        // Arrange
        const email = 'test@example.com';
        const password = 'wrongpassword';

        when(mockApiService.login(email, password))
            .throwException('Invalid credentials');

        // Act
        final result = await authProvider.login(email, password);

        // Assert
        expect(result, false);
      });
    });

    group('Register', () {
      test('Registro com dados válidos deve retornar true', () async {
        // Arrange
        const name = 'Test User';
        const email = 'test@example.com';
        const password = 'password123';
        const cpf = '12345678900';
        const phone = '11999999999';

        when(mockApiService.register(
          name: name,
          email: email,
          password: password,
          cpf: cpf,
          phone: phone,
        )).thenAnswer(
          (_) async => {'token': 'fake-token', 'user': {}},
        );

        // Act
        final result = await authProvider.register(
          name: name,
          email: email,
          password: password,
          cpf: cpf,
          phone: phone,
        );

        // Assert
        expect(result, true);
      });

      test('Registro com email duplicado deve retornar false', () async {
        // Arrange
        const email = 'existing@example.com';

        when(mockApiService.register(
          name: anyNamed('name'),
          email: email,
          password: anyNamed('password'),
          cpf: anyNamed('cpf'),
          phone: anyNamed('phone'),
        )).throwException('Email already exists');

        // Act
        final result = await authProvider.register(
          name: 'Test',
          email: email,
          password: 'pass123',
          cpf: '12345678900',
          phone: '11999999999',
        );

        // Assert
        expect(result, false);
      });
    });

    group('Logout', () {
      test('Logout deve limpar token e usuário', () async {
        // Arrange
        authProvider.currentUser =
            null; // Simular login anterior

        // Act
        await authProvider.logout();

        // Assert
        expect(authProvider.currentUser, isNull);
        expect(authProvider.token, isEmpty);
      });
    });

    group('Token Refresh', () {
      test('Refresh token com token válido deve retornar novo token',
          () async {
        // Arrange
        const oldToken = 'old-token';
        const newToken = 'new-token';

        when(mockApiService.refreshToken(oldToken)).thenAnswer(
          (_) async => {'token': newToken},
        );

        // Act
        authProvider.token = oldToken;
        final result = await authProvider.refreshToken();

        // Assert
        expect(result, true);
        expect(authProvider.token, newToken);
      });

      test('Refresh token expirado deve fazer logout', () async {
        // Arrange
        const expiredToken = 'expired-token';

        when(mockApiService.refreshToken(expiredToken))
            .throwException('Token expired');

        // Act
        authProvider.token = expiredToken;
        final result = await authProvider.refreshToken();

        // Assert
        expect(result, false);
      });
    });

    group('Update Profile', () {
      test('Update profile com dados válidos deve retornar true', () async {
        // Arrange
        const name = 'Updated Name';
        const phone = '11999999999';
        const bio = 'New bio';

        when(mockApiService.updateProfile(
          name: name,
          phone: phone,
          bio: bio,
        )).thenAnswer(
          (_) async => {'user': {}},
        );

        // Act
        final result = await authProvider.updateProfile(
          name: name,
          phone: phone,
          bio: bio,
        );

        // Assert
        expect(result, true);
      });
    });

    group('Loading States', () {
      test('isLoading deve ser true durante login', () async {
        // Arrange
        when(mockApiService.login(any, any)).thenAnswer(
          (_) => Future.delayed(
            const Duration(seconds: 1),
            () => {'token': 'fake-token', 'user': {}},
          ),
        );

        // Act
        authProvider.login('test@example.com', 'password');

        // Assert
        expect(authProvider.isLoading, true);
      });
    });

    group('Error Messages', () {
      test('clearError deve limpar mensagem de erro', () {
        // Arrange
        authProvider.errorMessage = 'Some error';

        // Act
        authProvider.clearError();

        // Assert
        expect(authProvider.errorMessage, isNull);
      });
    });
  });
}
