class Post {
  final int id;
  final int courseId;
  final int userId;
  final String title;
  final String content;
  final int likesCount;
  final int commentsCount;
  final bool isPinned;
  final String status;
  final String? authorName;
  final String? authorAvatar;
  final bool? isLikedByUser;
  final List<Comment>? comments;
  final DateTime createdAt;
  final DateTime updatedAt;

  Post({
    required this.id,
    required this.courseId,
    required this.userId,
    required this.title,
    required this.content,
    required this.likesCount,
    required this.commentsCount,
    required this.isPinned,
    required this.status,
    this.authorName,
    this.authorAvatar,
    this.isLikedByUser,
    this.comments,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Post.fromJson(Map<String, dynamic> json) {
    return Post(
      id: json['id'] as int,
      courseId: json['course_id'] as int,
      userId: json['user_id'] as int,
      title: json['title'] as String,
      content: json['content'] as String,
      likesCount: json['likes_count'] as int? ?? 0,
      commentsCount: json['comments_count'] as int? ?? 0,
      isPinned: json['is_pinned'] as bool? ?? false,
      status: json['status'] as String? ?? 'published',
      authorName: json['author']?['name'] as String?,
      authorAvatar: json['author']?['avatar_url'] as String?,
      isLikedByUser: json['is_liked_by_user'] as bool?,
      comments: (json['comments'] as List<dynamic>?)
          ?.map((c) => Comment.fromJson(c as Map<String, dynamic>))
          .toList(),
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: DateTime.parse(json['updated_at'] as String),
    );
  }

  bool get isPublished => status == 'published';
}

class Comment {
  final int id;
  final int postId;
  final int userId;
  final String content;
  final int likesCount;
  final int? parentCommentId;
  final String? authorName;
  final String? authorAvatar;
  final bool? isLikedByUser;
  final List<Comment>? replies;
  final DateTime createdAt;
  final DateTime updatedAt;

  Comment({
    required this.id,
    required this.postId,
    required this.userId,
    required this.content,
    required this.likesCount,
    this.parentCommentId,
    this.authorName,
    this.authorAvatar,
    this.isLikedByUser,
    this.replies,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Comment.fromJson(Map<String, dynamic> json) {
    return Comment(
      id: json['id'] as int,
      postId: json['post_id'] as int,
      userId: json['user_id'] as int,
      content: json['content'] as String,
      likesCount: json['likes_count'] as int? ?? 0,
      parentCommentId: json['parent_comment_id'] as int?,
      authorName: json['author']?['name'] as String?,
      authorAvatar: json['author']?['avatar_url'] as String?,
      isLikedByUser: json['is_liked_by_user'] as bool?,
      replies: (json['replies'] as List<dynamic>?)
          ?.map((r) => Comment.fromJson(r as Map<String, dynamic>))
          .toList(),
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: DateTime.parse(json['updated_at'] as String),
    );
  }

  bool get isReply => parentCommentId != null;
}

class Certificate {
  final int id;
  final int userId;
  final int courseId;
  final String certificateNumber;
  final String? courseName;
  final String? studentName;
  final DateTime issuedAt;
  final double completionPercentage;
  final double? grade;
  final String? qrCodeData;
  final bool? isValid;
  final String? verificationUrl;
  final DateTime createdAt;

  Certificate({
    required this.id,
    required this.userId,
    required this.courseId,
    required this.certificateNumber,
    this.courseName,
    this.studentName,
    required this.issuedAt,
    required this.completionPercentage,
    this.grade,
    this.qrCodeData,
    this.isValid,
    this.verificationUrl,
    required this.createdAt,
  });

  factory Certificate.fromJson(Map<String, dynamic> json) {
    return Certificate(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      courseId: json['course_id'] as int,
      certificateNumber: json['certificate_number'] as String,
      courseName: json['course']?['title'] as String?,
      studentName: json['user']?['name'] as String?,
      issuedAt: DateTime.parse(json['issued_at'] as String),
      completionPercentage:
          (json['completion_percentage'] as num?)?.toDouble() ?? 0.0,
      grade: (json['grade'] as num?)?.toDouble(),
      qrCodeData: json['qr_code_data'] as String?,
      isValid: json['is_valid'] as bool?,
      verificationUrl: json['verification_url'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }

  String get formattedNumber => certificateNumber.replaceAllMapped(
        RegExp(r'.{4}'),
        (match) => '${match.group(0)} ',
      );

  int get daysAgo => DateTime.now().difference(issuedAt).inDays;
}
