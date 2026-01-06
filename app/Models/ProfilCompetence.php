<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilCompetence extends Model
{
    use HasFactory;

    protected $fillable = [
        'profil_etudiant_id',
        'competence_id',
        'niveau',
        'source',
        'source_document_id',
        'score_confiance',
        'annees_experience',
        'validee_par_etudiant',
        'date_extraction',
        'date_validation',
    ];

    protected function casts(): array
    {
        return [
            'score_confiance' => 'decimal:2',
            'annees_experience' => 'decimal:1',
            'validee_par_etudiant' => 'boolean',
            'date_extraction' => 'datetime',
            'date_validation' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function profilEtudiant()
    {
        return $this->belongsTo(ProfilEtudiant::class, 'profil_etudiant_id');
    }

    public function competence()
    {
        return $this->belongsTo(Competence::class, 'competence_id');
    }

    public function sourceDocument()
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }
}
