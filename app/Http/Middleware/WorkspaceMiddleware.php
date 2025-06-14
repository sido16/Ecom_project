<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Workspace;

class WorkspaceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        // Vérifier si l'utilisateur est associé à un workspace
        $user = Auth::user();
        $workspace = Workspace::where('user_id', $user->id)->first();

        if (!$workspace) {
            return redirect()->route('home')->with('error', 'Accès refusé. Vous n\'êtes pas associé à un espace de travail.');
        }

        return $next($request);
    }
}