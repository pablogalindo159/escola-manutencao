<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'issued_at',
        'certificate_url',
        'completion_percentage',
        'grade',
        'qr_code_data',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'completion_percentage' => 'float',
        'grade' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('issued_at', 'desc');
    }

    // Methods
    public static function generateCertificateNumber()
    {
        return 'CERT-' . strtoupper(uniqid(date('YmdHis')));
    }

    public function generateQRCode()
    {
        // QR Code will contain certificate number and verification URL
        $data = "https://escoladamanutencao.com.br/verify/{$this->certificate_number}";
        
        return $data;
    }

    public function getVerificationUrl()
    {
        return url("/verify/{$this->certificate_number}");
    }

    public function isValid()
    {
        // Certificate is valid if completion is >= 80%
        return $this->completion_percentage >= 80;
    }

    public function downloadUrl()
    {
        return url("/api/certificates/{$this->id}/download");
    }
}
