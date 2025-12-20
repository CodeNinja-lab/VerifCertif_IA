<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidature extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'offre_id',
        'statut',
        'lettre_motivation',
        'cv_url',
        'date_candidature',
        'date_entretien',
        'notes_recruteur',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'date_candidature' => 'datetime',
            'date_entretien' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function etudiant()
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function offre()
    {
        return $this->belongsTo(Offre::class, 'offre_id');
    }

    /**
     * Scopes
     */
    public function scopeForEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    public function scopeForOffre($query, $offreId)
    {
        return $query->where('offre_id', $offreId);
    }

    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Helpers
     */
    public function getStatutLabelAttribute()
    {
        return match($this->statut) {
            'envoyee' => 'Candidature envoyée',
            'vue' => 'Vue par le recruteur',
            'en_cours' => 'En cours d\'examen',
            'entretien' => 'Entretien prévu',
            'acceptee' => 'Acceptée',
            'refusee' => 'Non retenue',
            'annulee' => 'Annulée',
            default => 'Inconnu',
        };
    }
}
