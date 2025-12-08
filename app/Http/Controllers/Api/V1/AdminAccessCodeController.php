<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAccessCodeController extends Controller
{
    /**
     * Génère un nouveau code d'accès admin à usage unique.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $code = Str::upper(Str::random(10));

        $accessCode = AdminAccessCode::create([
            'code' => $code,
            'created_by_user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Code d\'accès généré avec succès',
            'code' => $accessCode->code,
        ], 201);
    }
}


