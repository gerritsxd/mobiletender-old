<?php
namespace App\Traits;

use App\Models\UnicentaModels\Product;
use Illuminate\Support\Facades\Log;

trait ProductTrait{



    public function getCategoryProducts($id)
    {
        // A parent category shows its own products plus its children's,
        // so tapping "Bebidas" lists everything and sub-chips refine.
        $categoryIds = array_merge(
            [(string) $id],
            \App\Models\UnicentaModels\Category::where('parentid', $id)->pluck('id')->map(fn ($v) => (string) $v)->all()
        );

        $products = Product::whereIn('category', $categoryIds)->orderBy('name')->paginate(200);

         foreach ($products as $product) {
            // Log::debug('productos en product controller getproductsformcategory'.$product);
            if (!empty($product->image)) {
                $product->image = base64_encode($product->image);
            }

        }
        return $products;
    }
}
