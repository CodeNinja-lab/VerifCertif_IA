<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'destinataire_id',
        'type',
        'priorite',
        'titre',
        'message',
        'lien_action',
        'icone',
        'lue',
        'date_lecture',
        'archivee',
        'date_expiration',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'lue' => 'boolean',
            'date_lecture' => 'datetime',
            'archivee' => 'boolean',
            'date_envoi' => 'datetime',
            'date_expiration' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Relations
     */
    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }
}
