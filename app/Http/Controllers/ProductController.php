<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPicture;
use App\Models\workshop;
use App\Models\Importer;
use App\Models\Merchant;
use App\Models\ProductFeature;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Imports\ProductImport;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use ZipArchive;

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
     *                 required={"supplier_id", "name", "price", "quantity", "minimum_quantity"},
     *                 @OA\Property(
     *                     property="supplier_id",
     *                     type="integer",
     *                     description="ID of the supplier",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="name",
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
     *                     property="clearance",
     *                     type="boolean",
     *                     description="Whether the product is on clearance",
     *                     example=false
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
     *                     property="name",
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
     *                     property="clearance",
     *                     type="boolean",
     *                     example=false
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
        \Log::info('Starting product creation', ['request_data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'minimum_quantity' => 'required|integer|min:0',
            'clearance' => 'nullable|boolean',
            'pictures.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Creating product');
        $product = Product::create($request->only([
            'supplier_id',
            'name',
            'description',
            'category_id',
            'price',
            'quantity',
            'minimum_quantity',
            'clearance'
        ]));

        $imagePaths = [];
        $imageIds = [];
        $featureData = [];
        if ($request->hasFile('pictures') && is_array($request->file('pictures'))) {
            \Log::info('Processing product images', ['image_count' => count($request->file('pictures'))]);
            $multipartData = [];
            foreach ($request->file('pictures') as $picture) {
                if ($picture->isValid()) {
                    $path = $picture->store('product_pictures', 'public');
                    $productPicture = ProductPicture::create([
                        'product_id' => $product->id,
                        'picture' => $path
                    ]);
                    $imagePaths[] = $path;
                    $imageIds[] = $productPicture->id;
                    \Log::info('Image saved', ['path' => $path, 'product_picture_id' => $productPicture->id]);

                    $multipartData[] = [
                        'name' => 'images',
                        'contents' => file_get_contents(storage_path('app/public/' . $path)),
                        'filename' => basename($path)
                    ];
                }
            }

            if (!empty($multipartData)) {
                foreach ($imageIds as $id) {
                    $multipartData[] = [
                        'name' => 'image_ids[]',
                        'contents' => (string)$id
                    ];
                }

                \Log::info('Sending all images to Flask for feature extraction', ['image_count' => count($multipartData) - count($imageIds)]);
                $client = new \GuzzleHttp\Client();
                try {
                    $response = $client->post('http://127.0.0.1:5000/extract-features', [
                        'multipart' => $multipartData
                    ]);
                    $featureData = json_decode($response->getBody(), true);
                    \Log::info('Feature extraction successful', ['response' => $featureData]);

                    // Save features to the product_features table
                    if (isset($featureData['features']) && is_array($featureData['features'])) {
                        foreach ($featureData['features'] as $imageId => $features) {
                            \Log::info('Saving features for image', ['image_id' => $imageId]);
                            ProductFeature::create([
                                'image_id' => $imageId,
                                'features' => $features
                            ]);
                        }
                    } else {
                        \Log::warning('No features found in Flask response', ['response' => $featureData]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to extract features', ['error' => $e->getMessage(), 'image_paths' => $imagePaths]);
                }
            }
        }

        \Log::info('Product creation completed', ['product_id' => $product->id, 'image_count' => count($imagePaths)]);
        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product->load('pictures'),
            'features' => $featureData ?: null
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
     *                     property="name",
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
     *                     property="clearance",
     *                     type="boolean",
     *                     description="Whether the product is on clearance",
     *                     example=true
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
     *                 ),
     *                 @OA\Property(
     *                     property="images_to_delete[]",
     *                     type="array",
     *                     description="Array of image IDs to delete",
     *                     @OA\Items(
     *                         type="integer",
     *                         example=1
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
     *                     property="name",
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
     *                     property="clearance",
     *                     type="boolean",
     *                     example=true
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
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'minimum_quantity' => 'sometimes|integer|min:0',
            'clearance' => 'nullable|boolean',
            'pictures.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'images_to_delete.*' => 'sometimes|integer|exists:product_pictures,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'supplier_id',
            'name',
            'description',
            'category_id',
            'price',
            'quantity',
            'minimum_quantity',
            'clearance'
        ]);

        $product->update($data);

        // Handle image deletions
        if ($request->has('images_to_delete') && is_array($request->input('images_to_delete'))) {
            ProductPicture::whereIn('id', $request->input('images_to_delete'))
                ->where('product_id', $product->id)
                ->delete();
        }

        // Handle new image uploads
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

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get Product by ID",
     *     description="Retrieves a single product by its ID, including associated images.",
     *     operationId="showProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the product to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Smartphone"),
     *                 @OA\Property(property="price", type="number", format="float", example=599.99),
     *                 @OA\Property(property="supplier_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-03T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-03T12:00:00Z"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="picture", type="string", example="/images/smartphone_front.jpg")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $product = Product::with('pictures')->findOrFail($id);

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
    }

/**
 * @OA\Get(
 *     path="/api/products/{type}",
 *     summary="List Products by Supplier Sub-type",
 *     description="Retrieves all public products filtered by a single supplier sub-type (workshop, importer, or merchant).",
 *     operationId="listProductsBySubType",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="type",
 *         in="path",
 *         description="Supplier sub-type (workshop, importer, or merchant)",
 *         required=true,
 *         @OA\Schema(type="string", enum={"workshop", "importer", "merchant"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Products retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="supplier_id", type="integer", example=1),
 *                 @OA\Property(property="category_id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Red T-shirt"),
 *                 @OA\Property(property="price", type="number", example=10.00),
 *                 @OA\Property(property="description", type="string", example="Comfortable cotton t-shirt"),
 *                 @OA\Property(property="visibility", type="string", example="public"),
 *                 @OA\Property(property="quantity", type="integer", example=100),
 *                 @OA\Property(property="minimum_quantity", type="integer", example=1),
 *                 @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z"),
 *                 @OA\Property(property="pictures", type="array", @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="product_id", type="integer", example=1),
 *                     @OA\Property(property="picture", type="string", example="/storage/product_pictures/red1.jpg"),
 *                     @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z")
 *                 ))
 *             ))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid supplier type",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid supplier type")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to retrieve products"),
 *             @OA\Property(property="error", type="string", example="Database error occurred")
 *         )
 *     ),
 *     security={{"sanctum": {}}}
 * )
 */
    public function index(Request $request, $type)
    {
        try {
            if (!in_array($type, ['workshop', 'importer', 'merchant'])) {
                return response()->json(['message' => 'Invalid supplier type'], 400);
            }

            $products = Product::with('pictures')
                ->where('visibility', 'public')
                ->whereHas('supplier', fn ($q) => $q->has($type))
                ->get();

            return response()->json(['data' => $products], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve products',
                'error' => 'Database error occurred'
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/products/{id}/supplier",
     *     summary="Get Product with Supplier",
     *     description="Retrieves a public product with its supplier information by ID.",
     *     operationId="getProductWithSupplier",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Red T-shirt"),
     *                 @OA\Property(property="price", type="number", example=10.00),
     *                 @OA\Property(property="description", type="string", example="Comfortable cotton t-shirt"),
     *                 @OA\Property(property="pictures", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="picture", type="string", example="/storage/product_pictures/red1.jpg")
     *                 )),
     *                 @OA\Property(property="supplier", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="ABC Imports"),
     *                     @OA\Property(property="type", type="string", example="importer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve product"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function showWithSupplier($id)
    {
        try {
            $product = Product::with(['pictures', 'supplier', 'reviews'])
                ->where('visibility', 'public')
                ->findOrFail($id);

            return response()->json(['data' => $product], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Product not found: ID ' . $id);
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve product',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

       /**
     * @OA\Get(
     *     path="/api/products/{id}/store",
     *     summary="Get Supplier by Product",
     *     description="Retrieves the supplier information for a public product by ID.",
     *     operationId="getSupplierByProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="ABC Imports"),
     *                 @OA\Property(property="type", type="string", example="importer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product or supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product or supplier not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve supplier"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function getStore($id)
    {
        try {
            $product = Product::where('visibility', 'public')
                ->with('supplier')
                ->findOrFail($id);

            if (!$product->supplier) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Supplier not found');
            }

            return response()->json([
                'data' => $product->supplier
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Product or supplier not found: ID ' . $id);
            return response()->json([
                'message' => 'Product or supplier not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve supplier: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve supplier',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

        /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="List All Products",
     *     description="Retrieves all public products with their pictures.",
     *     operationId="listAllProducts",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="supplier_id", type="integer", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Red T-shirt"),
     *                 @OA\Property(property="price", type="number", example=10.00),
     *                 @OA\Property(property="description", type="string", example="Comfortable cotton t-shirt"),
     *                 @OA\Property(property="visibility", type="string", example="public"),
     *                 @OA\Property(property="quantity", type="integer", example=100),
     *                 @OA\Property(property="minimum_quantity", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="pictures", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="picture", type="string", example="/storage/product_pictures/red1.jpg"),
     *                     @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z")
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve products"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function all(Request $request)
    {
        try {
            $user = auth()->user();
            $products = Product::with('pictures','reviews')
                ->where('visibility', 'public')
                ->get();

            // Add saved status for each product if user is authenticated
            if ($user) {
                $products->each(function ($product) use ($user) {
                    $product->is_saved = $product->isSavedByUser($user->id);
                });
            }

            return response()->json(['data' => $products], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve products',
                'error' => 'Database error occurred'
            ], 500);
        }
    }

    /**
     * Search for similar products based on an uploaded image.
     *
     * Upload an image to find similar products using image feature extraction and FAISS index search.
     *
     * @OA\Post(
     *     path="/api/search",
     *     summary="Search for similar products",
     *     description="Upload an image to search for similar products based on image features.",
     *     operationId="searchProducts",
     *     tags={"Product Search"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="The image file to search for similar products (JPEG, PNG, JPG, max 2MB)",
     *                     example="sample.jpg"
     *                 ),
     *                 required={"image"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with similar products",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Similar products found"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=7),
     *                     @OA\Property(property="product_id", type="integer", example=3),
     *                     @OA\Property(property="picture", type="string", example="product_pictures/image1.png"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="supplier_id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Sample Product"),
     *                         @OA\Property(property="description", type="string", example="A sample product"),
     *                         @OA\Property(property="category_id", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=99.99),
     *                         @OA\Property(property="quantity", type="integer", example=10),
     *                         @OA\Property(property="minimum_quantity", type="integer", example=5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No similar products or images found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No matching products found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="image",
     *                     type="array",
     *                     @OA\Items(type="string", example="The image field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search failed"),
     *             @OA\Property(property="error", type="string", example="FAISS index not available")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function search(Request $request)
{
    \Log::info('Starting image search', ['request_data' => $request->all()]);

    $validator = Validator::make($request->all(), [
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
    ]);

    if ($validator->fails()) {
        \Log::warning('Validation failed', ['errors' => $validator->errors()]);
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $image = $request->file('image');
    $client = new \GuzzleHttp\Client();
    try {
        $response = $client->post('http://127.0.0.1:5000/search', [
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => file_get_contents($image->getRealPath()),
                    'filename' => $image->getClientOriginalName()
                ]
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        \Log::info('Search successful', ['response' => $data]);

        if (isset($data['similar_image_ids']) && !empty($data['similar_image_ids'])) {
            $productPictures = ProductPicture::whereIn('id', $data['similar_image_ids'])
                ->with('product')
                ->get();
            \Log::info('Fetched similar products', ['product_pictures' => $productPictures->toArray()]);

            if ($productPictures->isEmpty()) {
                \Log::info('No matching products found for image IDs', ['image_ids' => $data['similar_image_ids']]);
                return response()->json(['message' => 'No matching products found'], 404);
            }

            return response()->json([
                'message' => 'Similar products found',
                'data' => $productPictures
            ], 200);
        }

        \Log::info('No similar image IDs returned from Flask', ['response' => $data]);
        return response()->json(['message' => 'No similar images found'], 404);
    } catch (\Exception $e) {
        \Log::error('Search failed', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Search failed', 'error' => $e->getMessage()], 500);
    }
}



/**
     * Search for similar products based on an existing image ID.
     *
     * @OA\Post(
     *     path="/api/products/search-by-id",
     *     summary="Search for similar products by image ID",
     *     description="Search for similar products using the ID of an existing product image.",
     *     operationId="searchProductsById",
     *     tags={"Product Search"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     description="The ID of the product picture to search with",
     *                     example=7
     *                 ),
     *                 required={"id"}
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer",
     *                     description="The ID of the product picture to search with",
     *                     example=7
     *                 ),
     *                 required={"id"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with similar products",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Similar products found"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=7),
     *                     @OA\Property(property="product_id", type="integer", example=3),
     *                     @OA\Property(property="picture", type="string", example="product_pictures/image1.png"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="supplier_id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Sample Product"),
     *                         @OA\Property(property="description", type="string", example="A sample product"),
     *                         @OA\Property(property="category_id", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=99.99),
     *                         @OA\Property(property="quantity", type="integer", example=10),
     *                         @OA\Property(property="minimum_quantity", type="integer", example=5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No similar products or image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No matching products found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search failed"),
     *             @OA\Property(property="error", type="string", example="FAISS index not available")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
public function searchById(Request $request)
    {
        \Log::info('Starting image search by ID', ['request_data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_pictures,id'
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch the ProductPicture by ID
        $productPicture = ProductPicture::find($request->input('id'));
        if (!$productPicture) {
            \Log::warning('Product picture not found', ['id' => $request->input('id')]);
            return response()->json(['message' => 'Product picture not found'], 404);
        }

        // Get the image file path from storage
        $imagePath = $productPicture->picture; // e.g., "product_pictures/image1.png"
        $fullPath = storage_path('app/public/' . $imagePath);

        // Check if the image file exists
        if (!file_exists($fullPath)) {
            \Log::error('Image file not found on server', ['path' => $fullPath]);
            return response()->json(['message' => 'Image file not found on server'], 404);
        }

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post('http://127.0.0.1:5000/search', [
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => file_get_contents($fullPath),
                        'filename' => basename($imagePath)
                    ]
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            \Log::info('Search by ID successful', ['response' => $data]);

            if (isset($data['similar_image_ids']) && !empty($data['similar_image_ids'])) {
                $productPictures = ProductPicture::whereIn('id', $data['similar_image_ids'])
                    ->with('product')
                    ->get();
                \Log::info('Fetched similar products by ID', ['product_pictures' => $productPictures->toArray()]);

                if ($productPictures->isEmpty()) {
                    \Log::info('No matching products found for image IDs', ['image_ids' => $data['similar_image_ids']]);
                    return response()->json(['message' => 'No matching products found'], 404);
                }

                return response()->json([
                    'message' => 'Similar products found',
                    'data' => $productPictures
                ], 200);
            }

            \Log::info('No similar image IDs returned from Flask', ['response' => $data]);
            return response()->json(['message' => 'No similar images found'], 404);
        } catch (\Exception $e) {
            \Log::error('Search by ID failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Search failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/clearance/all",
     *     summary="Get Clearance Products",
     *     description="Retrieves all products that are marked for clearance.",
     *     operationId="getClearanceProducts",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Clearance products retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="supplier_id", type="integer", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Red T-shirt"),
     *                 @OA\Property(property="price", type="number", example=10.00),
     *                 @OA\Property(property="description", type="string", example="Comfortable cotton t-shirt"),
     *                 @OA\Property(property="visibility", type="string", example="public"),
     *                 @OA\Property(property="quantity", type="integer", example=100),
     *                 @OA\Property(property="minimum_quantity", type="integer", example=1),
     *                 @OA\Property(property="clearance", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="pictures", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="picture", type="string", example="/storage/product_pictures/red1.jpg"),
     *                     @OA\Property(property="created_at", type="string", example="2025-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2025-01-01T00:00:00.000000Z")
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve clearance products"),
     *             @OA\Property(property="error", type="string", example="Database error occurred")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function getClearanceProducts()
    {
        try {
            $products = Product::with('pictures')
                ->where('visibility', 'public')
                ->where('clearance', true)
                ->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No clearance products found'
                ], 200);
            }

            return response()->json([
                'message' => 'Clearance products retrieved successfully',
                'data' => $products
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve clearance products: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve clearance products',
                'error' => 'Database error occurred'
            ], 500);
        }
    }
}











