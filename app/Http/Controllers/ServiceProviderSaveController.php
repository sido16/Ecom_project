<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class ServiceProviderSaveController extends Controller
{
    /**
     * Save a service provider for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'service_provider_id' => 'required|exists:service_providers,id',
        ]);

        $user = Auth::user();
        $serviceProviderId = $request->service_provider_id;

        // Prevent users from saving their own service provider
        if ($user->serviceProvider && $user->serviceProvider->id == $serviceProviderId) {
            return response()->json([
                'message' => 'You cannot save your own service provider',
            ], 403);
        }

        // Check if already saved
        if ($user->savedServiceProviders()->where('service_provider_id', $serviceProviderId)->exists()) {
            return response()->json([
                'message' => 'Service provider already saved',
            ], 409);
        }

        // Save the service provider
        $user->savedServiceProviders()->attach($serviceProviderId);

        return response()->json([
            'message' => 'Service provider saved successfully',
        ], 201);
    }

    /**
     * Unsave a service provider for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unsave(Request $request): JsonResponse
    {
        $request->validate([
            'service_provider_id' => 'required|exists:service_providers,id',
        ]);

        $user = Auth::user();
        $serviceProviderId = $request->service_provider_id;

        // Check if saved
        if (!$user->savedServiceProviders()->where('service_provider_id', $serviceProviderId)->exists()) {
            return response()->json([
                'message' => 'Service provider not saved',
            ], 404);
        }

        // Unsave the service provider
        $user->savedServiceProviders()->detach($serviceProviderId);

        return response()->json([
            'message' => 'Service provider unsaved successfully',
        ], 200);
    }

    /**
     * Get all saved service providers for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getSaved(): JsonResponse
    {
        $user = Auth::user();
        $savedServiceProviders = $user->savedServiceProviders()
            ->with(['user', 'skills', 'pictures', 'skillDomain', 'reviews'])
            ->get()
            ->map(function ($provider) {
                $providerData = $provider->toArray();
                $providerData['user'] = $provider->user ? [
                    'full_name' => $provider->user->full_name,
                    'picture' => $provider->user->picture
                ] : null;
                return $providerData;
            });

        return response()->json([
            'message' => 'Saved service providers retrieved successfully',
            'data' => $savedServiceProviders,
        ], 200);
    }

    /**
     * Check if a service provider is saved by the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function isSaved(Request $request): JsonResponse
    {
        $request->validate([
            'service_provider_id' => 'required|exists:service_providers,id',
        ]);

        $user = Auth::user();
        $isSaved = $user->savedServiceProviders()
            ->where('service_provider_id', $request->service_provider_id)
            ->exists();

        return response()->json([
            'message' => 'Save status retrieved successfully',
            'is_saved' => $isSaved,
        ], 200);
    }
}
?>
