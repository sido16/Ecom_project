<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Importer;
use App\Models\Workshop;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/suppliers/{id}",
     *     summary="Get a supplier by ID",
     *     tags={"Suppliers"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             description="Supplier ID",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier retrieved successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     example="Tech Shop"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="123 Main St, City"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Electronics and repair services"
     *                 ),
     *                 @OA\Property(
     *                     property="picture",
     *                     type="string",
     *                     nullable=true,
     *                     example="pictures/supplier_1.jpg"
     *                 ),
     *                 @OA\Property(
     *                     property="domain_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="full_name",
     *                         type="string",
     *                         example="John Doe"
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         example="john@example.com"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="domain",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Electronics"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="workshop",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="importer",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="merchant",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier not found"
     *             )
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $supplier = Supplier::with(['user', 'domain', 'workshop', 'importer', 'merchant'])->findOrFail($id);

        return response()->json([
            'message' => 'Supplier retrieved successfully',
            'data' => $supplier
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/suppliers",
     *     summary="Create a new supplier",
     *     tags={"Suppliers"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"user_id", "business_name", "description", "address", "domain_id", "type"},
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="integer",
     *                     description="ID of the user",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     maxLength=100,
     *                     description="Name of the business",
     *                     example="Tech Shop"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Description of the supplier",
     *                     example="Electronics and repair services"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Physical address of the supplier",
     *                     example="123 Main St, City"
     *                 ),
     *                 @OA\Property(
     *                     property="domain_id",
     *                     type="integer",
     *                     description="ID of the domain",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"workshop", "importer", "merchant"},
     *                     description="Type of supplier",
     *                     example="workshop"
     *                 ),
     *                 @OA\Property(
     *                     property="picture",
     *                     type="string",
     *                     format="binary",
     *                     description="Image file for supplier profile (JPEG, PNG, JPG, max 2MB)",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Supplier created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier created successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     example="Tech Shop"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Electronics and repair services"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="123 Main St, City"
     *                 ),
     *                 @OA\Property(
     *                     property="domain_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="picture",
     *                     type="string",
     *                     nullable=true,
     *                     example="pictures/supplier_1.jpg"
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="full_name",
     *                         type="string",
     *                         example="John Doe"
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         example="john@example.com"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="domain",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Electronics"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="workshop",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="importer",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="merchant",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="supplier_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Validation failed"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"user_id": "The user_id field is required."}
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_name' => 'required|string|max:100',
            'description' => 'required|string',
            'address' => 'required|string|max:255',
            'domain_id' => 'required|exists:domains,id',
            'type' => 'required|in:workshop,importer,merchant',
            'picture' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['user_id', 'business_name', 'description', 'address', 'domain_id']);

        if ($request->hasFile('picture')) {
            $data['picture'] = $request->file('picture')->store('pictures', 'public');
        }

        $supplier = Supplier::create($data);

        $type = $request->type;
        if ($type === 'workshop') {
            Workshop::create(['supplier_id' => $supplier->id]);
        } elseif ($type === 'importer') {
            Importer::create(['supplier_id' => $supplier->id]);
        } elseif ($type === 'merchant') {
            Merchant::create(['supplier_id' => $supplier->id]);
        }

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier->load(['user', 'domain', $type])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/suppliers/{id}",
     *     summary="Update a supplier",
     *     tags={"Suppliers"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             description="Supplier ID",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     maxLength=100,
     *                     description="Name of the business",
     *                     example="Updated Shop"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Description of the supplier",
     *                     example="Updated description"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Physical address of the supplier",
     *                     example="456 Oak Ave, City"
     *                 ),
     *                 @OA\Property(
     *                     property="domain_id",
     *                     type="integer",
     *                     description="ID of the domain",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"workshop", "importer", "merchant"},
     *                     description="Type of supplier",
     *                     example="merchant"
     *                 ),
     *                 @OA\Property(
     *                     property="picture",
     *                     type="string",
     *                     format="binary",
     *                     description="Image file for supplier profile (JPEG, PNG, JPG, max 2MB)",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier updated successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     example="Updated Shop"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Updated description"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="456 Oak Ave, City"
     *                 ),
     *                 @OA\Property(
     *                     property="domain_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="picture",
     *                     type="string",
     *                     nullable=true,
     *                     example="pictures/supplier_1_updated.jpg"
     *                 ),
     *                 @OA\Property(
     *                     property="created_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:00:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="updated_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-04-14T12:01:00Z"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Validation failed"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"business_name": "The business_name must be a string."}
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string|max:255',
            'domain_id' => 'sometimes|exists:domains,id',
            'type' => 'sometimes|in:workshop,importer,merchant',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['business_name', 'description', 'address', 'domain_id']);

        if ($request->hasFile('picture')) {
            $data['picture'] = $request->file('picture')->store('pictures', 'public');
        }

        $supplier->update($data);

        if ($request->has('type')) {
            $type = $request->type;
            Workshop::where('supplier_id', $supplier->id)->delete();
            Importer::where('supplier_id', $supplier->id)->delete();
            Merchant::where('supplier_id', $supplier->id)->delete();

            if ($type === 'workshop') {
                Workshop::create(['supplier_id' => $supplier->id]);
            } elseif ($type === 'importer') {
                Importer::create(['supplier_id' => $supplier->id]);
            } elseif ($type === 'merchant') {
                Merchant::create(['supplier_id' => $supplier->id]);
            }
        }

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier->load(['user', 'domain', $request->type ?? 'workshop'])
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/suppliers/{id}",
     *     summary="Delete a supplier",
     *     tags={"Suppliers"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             description="Supplier ID",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier deleted successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Supplier not found"
     *             )
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        Supplier::findOrFail($id)->delete();
        return response()->json([
            'message' => 'Supplier deleted successfully'
        ], 200);
    }
}