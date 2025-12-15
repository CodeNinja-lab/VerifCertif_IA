<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prenom',
        'nom',
        'nom_entreprise',
        'email',
        'numero_etudiant',
        'mot_de_passe_hash',
        'role',
        'telephone',
        'photo_url',
        'langue',
        'token_2fa_secret',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe_hash',
        'token_2fa_secret',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_creation' => 'datetime',
            'derniere_connexion' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accessors
     */
    public function getNameAttribute()
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Relations
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'etudiant_id');
    }

    public function profilEtudiant()
    {
        return $this->hasOne(ProfilEtudiant::class, 'utilisateur_id');
    }

    public function offres()
    {
        return $this->hasMany(Offre::class, 'recruteur_id');
    }

    public function matchings()
    {
        return $this->hasMany(Matching::class, 'etudiant_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'destinataire_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'utilisateur_id');
    }
}
