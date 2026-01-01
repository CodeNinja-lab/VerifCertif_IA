<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOfferEmbedding extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'job_offers';

    protected $fillable = [
        'id',
        'title',
        'description',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public $timestamps = false;

    /**
     * Définir l'embedding en tant que tableau
     */
    public function setEmbeddingAttribute($value)
    {
        if (is_array($value)) {
            // Convertir le tableau en format pgvector
            $vectorString = '[' . implode(',', $value) . ']';
            $this->attributes['embedding'] = $vectorString;
        } else {
            $this->attributes['embedding'] = $value;
        }
    }

    /**
     * Récupérer l'embedding en tant que tableau
     */
    public function getEmbeddingAttribute($value)
    {
        if (is_string($value)) {
            // Convertir le format pgvector en tableau
            $value = trim($value, '[]');
            return array_map('floatval', explode(',', $value));
        }
        return $value;
    }
}
