<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'administration_id',
        'operateur_id',
        'motif_categorie',
        'motif_detail',
        'document_justificatif_url',
        'notification_titulaire',
        'date_notification',
        'irreversible',
    ];

    protected function casts(): array
    {
        return [
            'notification_titulaire' => 'boolean',
            'date_notification' => 'datetime',
            'date_revocation' => 'datetime',
            'irreversible' => 'boolean',
        ];
    }

    /**
     * Relations
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function administration()
    {
        return $this->belongsTo(Administration::class, 'administration_id');
    }

    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }
}
