<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administration extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_administration',
        'pays',
        'ville',
        'adresse',
        'numero_accreditation',
        'email_contact',
        'telephone_contact',
        'cle_publique_ed25519',
        'private_key_path',
        'logo_url',
        'site_web',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'datetime',
        ];
    }

    /**
     * Relations
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'administration_id');
    }

    public function revocations()
    {
        return $this->hasMany(Revocation::class, 'administration_id');
    }
}
