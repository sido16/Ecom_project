<!-- <?php

// namespace App\Imports;

// use App\Models\Product;
// use App\Models\ProductPicture;
// use Illuminate\Support\Facades\Storage;
// use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;

// class ProductImport implements ToModel, WithHeadingRow
// {
//     protected $supplierId;
//     protected $imagePaths;

//     public function __construct($supplierId, $imagePaths)
//     {
//         $this->supplierId = $supplierId;
//         $this->imagePaths = $imagePaths;
//     }

//     public function model(array $row)
//     {
//         $product = Product::create([
//             'supplierId' => $this->supplierId,
//             'category_id' => $row['category_id'] ?? null,
//             'name' => $row['name'],
//             'price' => $row['price'] ?? null,
//             'description' => $row['description'] ?? null,
//             'visibility' => 'public',
//             'quantity' => $row['quantity'] ?? null,
//             'minimum_quantity' => $row['minimum_quantity'] ?? null,
//         ]);

//         if (!empty($row['images'])) {
//             $images = array_filter(explode(',', $row['images']));
//             foreach ($images as $image) {
//                 $image = trim($image);
//                 if (isset($this->imagePaths[$image])) {
//                     ProductPicture::create([
//                         'product_id' => $product->id,
//                         'picture' => $this->imagePaths[$image],
//                     ]);
//                 }
//             }
//         }

//         return $product;
//     }
// }
?> -->