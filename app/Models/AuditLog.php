<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'ip_hash',
        'action',
        'objet_type',
        'objet_id',
        'statut',
        'details',
        'message_erreur',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'date_action' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
