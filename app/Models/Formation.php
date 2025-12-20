<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'diplome',
        'etablissement',
        'localisation',
        'date_debut',
        'date_fin',
        'en_cours',
        'description',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'en_cours' => 'boolean',
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
