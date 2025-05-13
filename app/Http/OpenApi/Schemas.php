<?php

namespace App\Http\OpenApi;

/**
 * @OA\Schema(
 *     schema="Workspace",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="business_name", type="string", example="PhotoSnap Studio"),
 *     @OA\Property(property="type", type="string", example="studio", enum={"studio", "coworking"}),
 *     @OA\Property(property="phone_number", type="string", example="1234567890"),
 *     @OA\Property(property="email", type="string", example="contact@photosnap.com"),
 *     @OA\Property(property="location", type="string", example="Downtown", nullable=true),
 *     @OA\Property(property="address", type="string", example="123 Main St"),
 *     @OA\Property(property="description", type="string", example="Professional photography studio", nullable=true),
 *     @OA\Property(property="opening_hours", type="string", example="9AM-5PM", nullable=true),
 *     @OA\Property(property="picture", type="string", example="workspace_pictures/studio.jpg", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(
 *         property="studio",
 *         ref="#/components/schemas/Studio",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="coworking",
 *         ref="#/components/schemas/Coworking",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/WorkspaceImage")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Studio",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="workspace_id", type="integer", example=1),
 *     @OA\Property(property="price_per_hour", type="number", format="float", example=50.00),
 *     @OA\Property(property="price_per_day", type="number", format="float", example=200.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(
 *         property="services",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/StudioService")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StudioService",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="service", type="string", example="Lighting Equipment"),
 *     @OA\Property(property="description", type="string", example="Professional lighting setups", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Coworking",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="workspace_id", type="integer", example=1),
 *     @OA\Property(property="price_per_day", type="number", format="float", example=25.00),
 *     @OA\Property(property="price_per_month", type="number", format="float", example=400.00),
 *     @OA\Property(property="seating_capacity", type="integer", example=50),
 *     @OA\Property(property="meeting_rooms", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="WorkspaceImage",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="workspace_id", type="integer", example=1),
 *     @OA\Property(property="image_url", type="string", example="workspace_images/image1.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-12T12:00:00.000000Z")
 * )
 */