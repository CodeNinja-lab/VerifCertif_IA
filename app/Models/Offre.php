<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offre extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruteur_id',
        'titre',
        'description',
        'missions_principales',
        'profil_recherche',
        'nice_to_have',
        'avantages',
        'processus_recrutement',
        'entreprise',
        'secteur_activite',
        'lieu',
        'type_contrat',
        'duree_contrat_mois',
        'teletravail',
        'salaire_min',
        'salaire_max',
        'devise',
        'niveau_etudes_requis',
        'annees_experience_min',
        'date_expiration',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_publication' => 'datetime',
            'date_expiration' => 'date',
            'avantages' => 'array',
            'processus_recrutement' => 'array',
        ];
    }

    /**
     * Relations
     */
    public function recruteur()
    {
        return $this->belongsTo(User::class, 'recruteur_id');
    }

    public function offreCompetences()
    {
        return $this->hasMany(OffreCompetence::class, 'offre_id');
    }

    public function matchings()
    {
        return $this->hasMany(Matching::class, 'offre_id');
    }
}
