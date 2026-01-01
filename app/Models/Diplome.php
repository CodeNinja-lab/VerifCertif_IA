<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diplome extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'code',
        'actif',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * Relations
     */
    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'diplome_competence')
                    ->withPivot('ordre')
                    ->withTimestamps()
                    ->orderBy('diplome_competence.ordre');
    }
}