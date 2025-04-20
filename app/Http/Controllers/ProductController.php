<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPicture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a new product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"supplier_id", "product_name", "price", "quantity", "minimum_quantity"},
     *                 @OA\Property(
     *                     property="supplier_id",
     *                     type="integer",
     *                     description="ID of the supplier",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="product_name",
     *                     type="string",
     *                     maxLength=100,
     *                     description="Name of the product",
     *                     example="Smartphone X"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Description of the product",
     *                     example="Latest model with 128GB storage",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer",
     *                     description="ID of the category",
     *                     example=1,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float",
     *                     description="Price of the product",
     *                     example=599.99
     *                 ),
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="integer",
     *                     description="Stock quantity",
     *                     example=100
     *                 ),
     *                 @OA\Property(
     *                     property="minimum_quantity",
     *                     type="integer",
     *                     description="Minimum stock quantity",
     *                     example=10
     *                 ),
     *                 @OA\Property(
     *                     property="pictures[]",
     *                     type="array",
     *                     description="Array of product images (JPEG, PNG, JPG, max 2MB each)",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Product created successfully"
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
     *                     property="supplier_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="product_name",
     *                     type="string",
     *                     example="Smartphone X"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Latest model with 128GB storage",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer",
     *                     example=1,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float",
     *                     example=599.99
     *                 ),
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="integer",
     *                     example=100
     *                 ),
     *                 @OA\Property(
     *                     property="minimum_quantity",
     *                     type="integer",
     *                     example=10
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
     *                     property="pictures",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="product_id",
     *                             type="integer",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="picture",
     *                             type="string",
     *                             example="product_pictures/image1.jpg"
     *                         )
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
     *                 example={"supplier_id": "The supplier_id field is required."}
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'product_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'pictures.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->only([
            'supplier_id',
            'product_name',
            'description',
            'category_id',
            'price',
            'quantity',
            'minimum_quantity'
        ]));

        if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
            foreach ($request->file('pictures') as $picture) {
                if ($picture->isValid()) {
                    ProductPicture::create([
                        'product_id' => $product->id,
                        'picture' => $picture->store('product_pictures', 'public')
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product->load('pictures')
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/products/{id}",
     *     summary="Update a product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             description="Product ID",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="supplier_id",
     *                     type="integer",
     *                     description="ID of the supplier",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="product_name",
     *                     type="string",
     *                     maxLength=100,
     *                     description="Name of the product",
     *                     example="Smartphone X"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Description of the product",
     *                     example="Updated model with 256GB storage",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer",
     *                     description="ID of the category",
     *                     example=1,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float",
     *                     description="Price of the product",
     *                     example=699.99
     *                 ),
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="integer",
     *                     description="Stock quantity",
     *                     example=50
     *                 ),
     *                 @OA\Property(
     *                     property="minimum_quantity",
     *                     type="integer",
     *                     description="Minimum stock quantity",
     *                     example=5
     *                 ),
     *                 @OA\Property(
     *                     property="pictures[]",
     *                     type="array",
     *                     description="Array of product images (JPEG, PNG, JPG, max 2MB each)",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Product updated successfully"
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
     *                     property="supplier_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="product_name",
     *                     type="string",
     *                     example="Smartphone X"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Updated model with 256GB storage",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer",
     *                     example=1,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float",
     *                     example=699.99
     *                 ),
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="integer",
     *                     example=50
     *                 ),
     *                 @OA\Property(
     *                     property="minimum_quantity",
     *                     type="integer",
     *                     example=5
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
     *                 ),
     *                 @OA\Property(
     *                     property="pictures",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="product_id",
     *                             type="integer",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="picture",
     *                             type="string",
     *                             example="product_pictures/image1.jpg"
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Product not found"
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
     *                 example={"price": "The price must be a number."}
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'product_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'minimum_quantity' => 'sometimes|integer|min:0',
            'pictures.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'supplier_id',
            'product_name',
            'description',
            'category_id',
            'price',
            'quantity',
            'minimum_quantity'
        ]);

        $product->update($data);

        if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
            ProductPicture::where('product_id', $product->id)->delete();
            foreach ($request->file('pictures') as $picture) {
                if ($picture->isValid()) {
                    ProductPicture::create([
                        'product_id' => $product->id,
                        'picture' => $picture->store('product_pictures', 'public')
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->load('pictures')
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Delete a product",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             description="Product ID",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Product deleted successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Product not found"
     *             )
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}












