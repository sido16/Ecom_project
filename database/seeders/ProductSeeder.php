<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductPicture;
use App\Models\ProductFeature;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class ProductSeeder extends Seeder
{
    private function extractFeaturesBatch($imagesData)
    {
        try {
            $client = new Client();
            
            // Prepare multipart data
            $multipart = [];
            foreach ($imagesData as $data) {
                // Construct the full path for each image
                $fullPath = 'E:/PFE/easycom_backend/public/storage/images/products/' . $data['path'];
                
                if (!file_exists($fullPath)) {
                    \Log::warning('Image file not found for feature extraction', ['path' => $fullPath]);
                    continue;
                }

                // Add image file
                $multipart[] = [
                    'name' => 'images',
                    'contents' => fopen($fullPath, 'r'),
                    'filename' => $data['path']
                ];

                // Add corresponding image ID
                $multipart[] = [
                    'name' => 'image_ids[]',
                    'contents' => (string)$data['id']
                ];
            }

            if (empty($multipart)) {
                \Log::warning('No valid images to process');
                return;
            }

            // Send batch request to Flask API
            \Log::info('Sending batch request to Flask', ['image_count' => count($imagesData)]);
            $response = $client->post('http://127.0.0.1:5000/extract-features', [
                'multipart' => $multipart,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 60, // Increased timeout for batch processing
            ]);

            $featureData = json_decode($response->getBody(), true);
            
            if (isset($featureData['features']) && is_array($featureData['features'])) {
                foreach ($featureData['features'] as $imgId => $features) {
                    ProductFeature::create([
                        'image_id' => $imgId,
                        'features' => $features
                    ]);
                }
            } else {
                \Log::warning('No features found in response', ['response' => $featureData]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to extract features for batch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function run()
    {
        $imagesToProcess = [];

        // All products in a single array
        $products = [
            [
                'supplier_id' => 1,
                'category_id' => 1,
                'name' => 'Glasses',
                'price' => 250.00,
                'description' => 'Handcrafted glasses',
                'quantity' => 10,
                'minimum_quantity' => 1,
                'image' => 'product-1.jpg'
            ],
            [
                'supplier_id' => 1,
                'category_id' => 1,
                'name' => 'Clocks',
                'price' => 150.00,
                'description' => 'Modular wooden clocks with adjustable shelves',
                'quantity' => 15,
                'minimum_quantity' => 1,
                'image' => 'product-2.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Bag',
                'price' => 500.00,
                'description' => 'Durable bags with anti-rust coating',
                'quantity' => 5,
                'minimum_quantity' => 1,
                'image' => 'product-3.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Watch',
                'price' => 200.00,
                'description' => 'Custom watches',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-4.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Cups',
                'price' => 200.00,
                'description' => 'Custom cups',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-5.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Laptop',
                'price' => 200.00,
                'description' => 'New laptop',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-6.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Tablet',
                'price' => 200.00,
                'description' => 'New tablet',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-7.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Phone Case',
                'price' => 200.00,
                'description' => 'New phone case',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-8.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'T-Shirt',
                'price' => 200.00,
                'description' => 'New t-shirt',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-9.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Shoes',
                'price' => 200.00,
                'description' => 'New shoes',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-10.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Mini Laptop',
                'price' => 200.00,
                'description' => 'New mini laptop',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-11.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'Dell Laptop',
                'price' => 200.00,
                'description' => 'New Dell laptop',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-12.jpg'
            ],
            [
                'supplier_id' => 2,
                'category_id' => 2,
                'name' => 'HP ProBook Laptop',
                'price' => 200.00,
                'description' => 'New HP ProBook laptop',
                'quantity' => 8,
                'minimum_quantity' => 1,
                'image' => 'product-15.png'
            ]
        ];

        foreach ($products as $productData) {
            $product = Product::create([
                'supplier_id' => $productData['supplier_id'],
                'category_id' => $productData['category_id'],
                'name' => $productData['name'],
                'price' => $productData['price'],
                'description' => $productData['description'],
                'visibility' => 'public',
                'quantity' => $productData['quantity'],
                'minimum_quantity' => $productData['minimum_quantity'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $picture = ProductPicture::create([
                'product_id' => $product->id,
                'picture' => 'products/' . $productData['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $imagesToProcess[] = [
                'id' => $picture->id,
                'path' => $productData['image']
            ];
        }

        // Process all images in a single batch request after all products are created
        $this->extractFeaturesBatch($imagesToProcess);
    }
}