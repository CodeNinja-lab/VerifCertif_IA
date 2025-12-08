<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matching extends Model
{
    use HasFactory;

    protected $fillable = [
        'offre_id',
        'etudiant_id',
        'score_global',
        'score_competences',
        'score_localisation',
        'score_experience',
        'competences_matchees',
        'competences_manquantes',
        'points_forts',
        'points_amelioration',
        'algorithme_version',
        'seuil_notification',
        'notifie',
        'date_notification',
        'vu_par_etudiant',
        'date_vue_etudiant',
        'interesse',
        'vu_par_recruteur',
    ];

    protected function casts(): array
    {
        return [
            'score_global' => 'decimal:2',
            'score_competences' => 'decimal:2',
            'score_localisation' => 'decimal:2',
            'score_experience' => 'decimal:2',
            'competences_matchees' => 'array',
            'competences_manquantes' => 'array',
            'points_forts' => 'array',
            'points_amelioration' => 'array',
            'seuil_notification' => 'decimal:2',
            'notifie' => 'boolean',
            'date_notification' => 'datetime',
            'date_matching' => 'datetime',
            'vu_par_etudiant' => 'boolean',
            'date_vue_etudiant' => 'datetime',
            'interesse' => 'boolean',
            'vu_par_recruteur' => 'boolean',
        ];
    }

    /**
     * Relations
     */
    public function offre()
    {
        return $this->belongsTo(Offre::class, 'offre_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }
}
