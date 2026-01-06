<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilEtudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'bio',
        'cv_url',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'disponibilite',
        'localisation_actuelle',
        'localisation_souhaitee',
        'mobilite',
        'salaire_minimum_souhaite',
        'types_contrat_souhaites',
        'profil_public',
        'date_mise_a_jour',
    ];

    protected function casts(): array
    {
        return [
            'localisation_souhaitee' => 'array',
            'types_contrat_souhaites' => 'array',
            'profil_public' => 'boolean',
            'date_mise_a_jour' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function profilCompetences()
    {
        return $this->hasMany(ProfilCompetence::class, 'profil_etudiant_id');
    }
}
