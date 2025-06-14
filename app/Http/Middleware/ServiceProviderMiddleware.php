<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceProvider;

class ServiceProviderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        // Vérifier si l'utilisateur est un service provider
        $user = Auth::user();
        $serviceProvider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$serviceProvider) {
            return redirect()->route('home')->with('error', 'Accès refusé. Vous n\'êtes pas un prestataire de services.');
        }

        return $next($request);
    }
}