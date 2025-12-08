<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'nom_normalise',
        'categorie',
        'description',
        'referentiel_externe_id',
        'synonymes',
        'popularite',
    ];

    protected function casts(): array
    {
        return [
            'synonymes' => 'array',
            'date_creation' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function profilCompetences()
    {
        return $this->hasMany(ProfilCompetence::class, 'competence_id');
    }

    public function offreCompetences()
    {
        return $this->hasMany(OffreCompetence::class, 'competence_id');
    }
}
