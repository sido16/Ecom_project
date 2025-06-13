<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

abstract class SaveController extends Controller
{
    protected $model;
    protected $relationName;
    protected $idField;

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            $this->idField => 'required|exists:' . $this->getTableName() . ',id',
        ]);

        $user = Auth::user();
        $itemId = $request->{$this->idField};

        // Check if already saved
        if ($user->{$this->relationName}()->where($this->idField, $itemId)->exists()) {
            return response()->json([
                'message' => 'Item already saved',
            ], 409);
        }

        // Save the item
        $user->{$this->relationName}()->attach($itemId);

        return response()->json([
            'message' => 'Item saved successfully',
        ], 201);
    }

    public function unsave(Request $request): JsonResponse
    {
        $request->validate([
            $this->idField => 'required|exists:' . $this->getTableName() . ',id',
        ]);

        $user = Auth::user();
        $itemId = $request->{$this->idField};

        // Check if saved
        if (!$user->{$this->relationName}()->where($this->idField, $itemId)->exists()) {
            return response()->json([
                'message' => 'Item not saved',
            ], 404);
        }

        // Unsave the item
        $user->{$this->relationName}()->detach($itemId);

        return response()->json([
            'message' => 'Item unsaved successfully',
        ], 200);
    }

    public function getSaved(): JsonResponse
    {
        $user = Auth::user();
        $savedItems = $user->{$this->relationName}()->get();

        return response()->json([
            'message' => 'Saved items retrieved successfully',
            'data' => $savedItems,
        ], 200);
    }

    public function isSaved(Request $request): JsonResponse
    {
        $request->validate([
            $this->idField => 'required|exists:' . $this->getTableName() . ',id',
        ]);

        $user = Auth::user();
        $isSaved = $user->{$this->relationName}()
            ->where($this->idField, $request->{$this->idField})
            ->exists();

        return response()->json([
            'message' => 'Save status retrieved successfully',
            'is_saved' => $isSaved,
        ], 200);
    }

    protected function getTableName(): string
    {
        return (new $this->model)->getTable();
    }
}
