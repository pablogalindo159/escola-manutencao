class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? cpf;
  final String? bio;
  final String? avatarUrl;
  final String role; // student, instructor, admin
  final String status; // active, inactive
  final DateTime? emailVerifiedAt;
  final DateTime? lastLoginAt;
  final DateTime createdAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.cpf,
    this.bio,
    this.avatarUrl,
    required this.role,
    required this.status,
    this.emailVerifiedAt,
    this.lastLoginAt,
    required this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String?,
      cpf: json['cpf'] as String?,
      bio: json['bio'] as String?,
      avatarUrl: json['avatar_url'] as String?,
      role: json['role'] as String? ?? 'student',
      status: json['status'] as String? ?? 'active',
      emailVerifiedAt: json['email_verified_at'] != null
          ? DateTime.tryParse(json['email_verified_at'] as String)
          : null,
      lastLoginAt: json['last_login_at'] != null
          ? DateTime.tryParse(json['last_login_at'] as String)
          : null,
      createdAt: json['created_at'] != null
          ? (DateTime.tryParse(json['created_at'] as String) ?? DateTime.now())
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'cpf': cpf,
      'bio': bio,
      'avatar_url': avatarUrl,
      'role': role,
      'status': status,
      'email_verified_at': emailVerifiedAt?.toIso8601String(),
      'last_login_at': lastLoginAt?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
    };
  }

  User copyWith({
    int? id,
    String? name,
    String? email,
    String? phone,
    String? cpf,
    String? bio,
    String? avatarUrl,
    String? role,
    String? status,
    DateTime? emailVerifiedAt,
    DateTime? lastLoginAt,
    DateTime? createdAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      cpf: cpf ?? this.cpf,
      bio: bio ?? this.bio,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      role: role ?? this.role,
      status: status ?? this.status,
      emailVerifiedAt: emailVerifiedAt ?? this.emailVerifiedAt,
      lastLoginAt: lastLoginAt ?? this.lastLoginAt,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  bool get isAdmin => role == 'admin';
  bool get isInstructor => role == 'instructor';
  bool get isStudent => role == 'student';
  bool get isActive => status == 'active';

  String get roleLabel {
    switch (role) {
      case 'admin':
        return 'Administrador';
      case 'instructor':
        return 'Professor';
      case 'student':
      default:
        return 'Aluno';
    }
  }
}
