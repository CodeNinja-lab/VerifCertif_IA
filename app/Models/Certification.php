<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'nom',
        'organisme',
        'date_obtention',
        'date_expiration',
        'identifiant',
        'url_verification',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'date_obtention' => 'date',
            'date_expiration' => 'date',
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
