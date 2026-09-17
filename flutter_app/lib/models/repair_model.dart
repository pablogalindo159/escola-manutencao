class Repair {
  final int id;
  final int userId;
  final String equipmentType;
  final String? customerName;
  final String defectDescription;
  final String? diagnosis;
  final Map<String, dynamic>? measurements;
  final Map<String, dynamic>? componentsReplaced;
  final String? solution;
  final String? notes;
  final String status; // draft, pending_review, reviewed, approved, rejected
  final double? rating;
  final String? instructorFeedback;
  final List<RepairPhoto>? photos;
  final DateTime createdAt;
  final DateTime updatedAt;

  Repair({
    required this.id,
    required this.userId,
    required this.equipmentType,
    this.customerName,
    required this.defectDescription,
    this.diagnosis,
    this.measurements,
    this.componentsReplaced,
    this.solution,
    this.notes,
    required this.status,
    this.rating,
    this.instructorFeedback,
    this.photos,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Repair.fromJson(Map<String, dynamic> json) {
    return Repair(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      equipmentType: json['equipment_type'] as String,
      customerName: json['customer_name'] as String?,
      defectDescription: json['defect_description'] as String,
      diagnosis: json['diagnosis'] as String?,
      measurements: json['measurements'] as Map<String, dynamic>?,
      componentsReplaced: json['components_replaced'] as Map<String, dynamic>?,
      solution: json['solution'] as String?,
      notes: json['notes'] as String?,
      status: json['status'] as String,
      rating: (json['rating'] as num?)?.toDouble(),
      instructorFeedback: json['instructor_feedback'] as String?,
      photos: (json['photos'] as List<dynamic>?)
          ?.map((p) => RepairPhoto.fromJson(p as Map<String, dynamic>))
          .toList(),
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: DateTime.parse(json['updated_at'] as String),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'equipment_type': equipmentType,
      'customer_name': customerName,
      'defect_description': defectDescription,
      'diagnosis': diagnosis,
      'measurements': measurements,
      'components_replaced': componentsReplaced,
      'solution': solution,
      'notes': notes,
      'status': status,
      'rating': rating,
      'instructor_feedback': instructorFeedback,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  bool get isDraft => status == 'draft';
  bool get isPendingReview => status == 'pending_review';
  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';
  bool get isEditable => status == 'draft';

  String get statusLabel {
    switch (status) {
      case 'draft':
        return 'Rascunho';
      case 'pending_review':
        return 'Aguardando Análise';
      case 'reviewed':
        return 'Revisado';
      case 'approved':
        return 'Aprovado';
      case 'rejected':
        return 'Rejeitado';
      default:
        return status;
    }
  }

  int get photoCount => photos?.length ?? 0;
}

class RepairPhoto {
  final int id;
  final int repairId;
  final String photoUrl;
  final String stage; // before, during, after, diagnostic
  final String? description;
  final DateTime createdAt;

  RepairPhoto({
    required this.id,
    required this.repairId,
    required this.photoUrl,
    required this.stage,
    this.description,
    required this.createdAt,
  });

  factory RepairPhoto.fromJson(Map<String, dynamic> json) {
    return RepairPhoto(
      id: json['id'] as int,
      repairId: json['repair_id'] as int,
      photoUrl: json['photo_url'] as String,
      stage: json['stage'] as String,
      description: json['description'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }

  String get stageLabel {
    switch (stage) {
      case 'before':
        return 'Antes';
      case 'during':
        return 'Durante';
      case 'after':
        return 'Depois';
      case 'diagnostic':
        return 'Diagnóstico';
      default:
        return stage;
    }
  }
}
