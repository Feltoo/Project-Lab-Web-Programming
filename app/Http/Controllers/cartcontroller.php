<?php

namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\cart_items;
use App\Models\category;
use App\Models\product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class cartcontroller extends Controller
{
    public function addtoCart(Request $request){

       $validate = $request->validate([
            'quantity' => ['required']
        ]);

       $checkifitemexist = DB::table('carts')->select('id')->where('user_id','=', auth()->id())->latest()->get();
      
        
       $getCartId = DB::table('carts')->select('id')->where('user_id','=', auth()->id())->latest()->get();

       $newItem = new cart_items();

       $newItem->cart_id = $getCartId->first()->id;

       $newItem->product_id = $request->id;

       $newItem->quantity = $request->quantity;

        // dd($getCartId->first()->id);
       $newItem->save();
    //    dd($newItem);

    return redirect('/home')->with('addedproduct', '<b>' . $request->quantity .'</b>'  .' '. $request->productname . ' added to your cart');
    }


    public function checkCart(){
        $latestcart = DB::table('carts')->where('carts.user_id','=',auth()->id())->latest()->first();

        $cartItems = DB::table('cart_items')
        ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
        ->join('products','cart_items.product_id','=','products.id')
        ->where('carts.user_id' ,'=', auth()->id())
        ->where('carts.id','=',$latestcart->id)->get();

        if($cartItems->isEmpty()) return response()->json(array('empty'=> true), 200);
    }


    public function showCart(){
        $latestcart = DB::table('carts')->where('carts.user_id','=',auth()->id())->latest()->first();

        $cartItems = DB::table('cart_items')
        ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
        ->join('products','cart_items.product_id','=','products.id')
        ->where('carts.user_id' ,'=', auth()->id())
        ->where('carts.id','=',$latestcart->id)->get();
        // dd($cartItems);

        // if($cartItems->isEmpty()) return redirect('/home');
        $categories = category::all();
        
        $totalprice = 0;
        foreach ($cartItems as $item) {
            $totalprice+=$item->price*$item->quantity;
        }

        // dd($cartItems);

        return view('cart',[
            'category' => $categories,
            'title' => 'Cart',
            'cartitems' => $cartItems,
            'totalprice' => $totalprice,
            'cartcount' => $cartItems->count()
        ]);
    }


    public function destroycartitems($cart_id, $product_id){

     

        $product = product::findOrfail($product_id);

        $deleted = DB::table('cart_items')
                   ->where('cart_id','=',$cart_id)
                   ->where('product_id','=',$product_id)
                   ->delete();

        $latestcart = DB::table('carts')->where('carts.user_id','=',auth()->id())->latest()->first();

        $cartItems = DB::table('cart_items')
        ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
        ->join('products','cart_items.product_id','=','products.id')
        ->where('carts.user_id' ,'=', auth()->id())
        ->where('carts.id','=',$latestcart->id)->get();
                   

        // dd($deleted);

        return response()->json(array('productname'=> $product->productname, 'cartcount' => count($cartItems)), 200);
    }


    public function purchasecartitems(){

        $currentcartid = DB::table('carts')->where('carts.user_id','=',auth()->id())->latest()->first();

        $currentcart = cart::find($currentcartid->id);
        $currentcart->updated_at = Carbon::now();
        $currentcart->save();
        $newcart = new cart();

        $newcart->user_id = auth()->id();

        $newcart->save();


        return redirect('/home')->with('purchasesuccess', 'All items have been purchased');
    }
}
