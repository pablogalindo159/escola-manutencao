<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_id',
        'photo_url',
        'stage',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    // Scopes
    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    // Methods
    public function getStageLabel()
    {
        return match($this->stage) {
            'before' => 'Antes',
            'during' => 'Durante',
            'after' => 'Depois',
            'diagnostic' => 'Diagnóstico',
            default => $this->stage,
        };
    }
}
