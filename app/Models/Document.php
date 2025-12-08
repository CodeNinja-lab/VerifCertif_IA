<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid_document',
        'etudiant_id',
        'administration_id',
        'type_document',
        'titre',
        'file_url',
        'file_size_kb',
        'hash_sha256',
        'signature_ed25519',
        'qr_code_url',
        'blockchain_tx_hash',
        'blockchain_network',
        'statut',
        'date_emission',
        'date_expiration',
        'metadata',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->uuid_document)) {
                $document->uuid_document = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_certification' => 'datetime',
            'date_expiration' => 'date',
            'metadata' => 'array',
        ];
    }

    /**
     * Relations
     */
    public function etudiant()
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function administration()
    {
        return $this->belongsTo(Administration::class, 'administration_id');
    }

    public function verificationLogs()
    {
        return $this->hasMany(VerificationLog::class, 'document_id');
    }

    public function revocations()
    {
        return $this->hasMany(Revocation::class, 'document_id');
    }

    public function profilCompetences()
    {
        return $this->hasMany(ProfilCompetence::class, 'source_document_id');
    }
}
