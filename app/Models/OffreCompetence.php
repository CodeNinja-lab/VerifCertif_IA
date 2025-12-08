<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffreCompetence extends Model
{
    use HasFactory;

    protected $fillable = [
        'offre_id',
        'competence_id',
        'niveau_requis',
        'importance',
        'poids',
    ];

    /**
     * Relations
     */
    public function offre()
    {
        return $this->belongsTo(Offre::class, 'offre_id');
    }

    public function competence()
    {
        return $this->belongsTo(Competence::class, 'competence_id');
    }
}
