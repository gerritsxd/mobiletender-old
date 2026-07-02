<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    /**
     * Regression test: $product->category is the raw id column (it shadows
     * the relation), so the endpoint must resolve names via a map — reading
     * ->category->name fatals with "property on string".
     */
    public function test_catalog_json_returns_products(): void
    {
        $response = $this->getJson('/order/catalog.json');

        $response->assertStatus(200);
        $products = $response->json('products');
        self::assertNotEmpty($products);

        $first = collect($products)->firstWhere('id', self::PRODUCT_ID);
        self::assertNotNull($first, 'seeded test product missing from catalog');
        self::assertIsString($first['name']);
        self::assertIsNumeric($first['price']);
        self::assertArrayHasKey('category', $first);
        self::assertArrayHasKey('category_id', $first);
    }
}
