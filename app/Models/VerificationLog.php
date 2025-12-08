<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'verificateur_type',
        'verificateur_id',
        'ip_hash',
        'user_agent',
        'pays',
        'ville',
        'methode_verification',
        'resultat',
        'details_erreur',
        'duree_ms',
        'date_verification',
    ];

    protected function casts(): array
    {
        return [
            'date_verification' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function verificateur()
    {
        return $this->belongsTo(User::class, 'verificateur_id');
    }
}
