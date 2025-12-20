<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'titre',
        'entreprise',
        'localisation',
        'date_debut',
        'date_fin',
        'poste_actuel',
        'description',
        'realisations',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'poste_actuel' => 'boolean',
            'realisations' => 'array',
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
