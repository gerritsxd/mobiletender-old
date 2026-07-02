<?php
/**
 * Created by PhpStorm.
 * User: Gerrit
 * Date: 16/10/2020
 * Time: 12:09
 */
namespace Tests\Unit;
use Tests\TestCase;
use App\Models\UnicentaModels\Category;
use App\Models\UnicentaModels\Product;
use App\Models\UnicentaModels\Products_Cat;
use App\Models\UnicentaModels\SharedTicket;
use App\Models\UnicentaModels\SharedTicketUser;
use Illuminate\Support\Str;
use Symfony\Component\Translation\Dumper\PoFileDumper;


class ModelsTest extends TestCase
{


    public function testSharedTicketUser(){
        $sharedTicketUser = new SharedTicketUser();
        self::assertTrue($sharedTicketUser->m_sName == 'Guest');
    }

    public function testSharedTicket(){
        $sharedTicket = new SharedTicket();
        self::assertTrue($sharedTicket->ticketstatus==0);
    }
    public function testgetfirstProduct(){
        $firstProduct = Product::all()->first();
        self::assertNotEmpty($firstProduct);
    }
    public function testCreateTestProduct(){
        $cat = Category::find('0484675e-1baf-415a-b1a0-897fbb1fa14c');
//dd($cat->id);
        $product = new Product();
        $product->id = Str::uuid()->toString();
        $product->reference = 'tP';
        $product->code = 'tP';
        $product->taxcat = '001';
        $product->category = $cat->id;
        $product->name = 'testProduct';
        $product->pricebuy = '1';
        $product->pricesell = '2';
        $product->save();
        self::assertNotEmpty(Product::where('reference','=','tP')->get());
    }

    public function testDropTestProdcut(){
        Product::where('reference','=','tP')->delete();
        self::assertEmpty(Product::where('reference','=','tP')->get());

    }
}
