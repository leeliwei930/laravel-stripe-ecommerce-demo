<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\CartRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    protected $cartRepository;
    public function __construct(CartRepository  $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function all()
    {
        $cartItems = $this->cartRepository->items();
        return response()->json(['cart_items' => $cartItems->toArray()]);
    }
    public function add(Request $request)
    {

        $product = Product::find($request->product_id);
            $validator = \Validator::make($request->toArray(), [
           'product_id' => [
               'required',
               'exists:products,id'
           ],
            'quantity' => [
                'required',
                'numeric',
                'min:1',
                function($attributes, $value, $fail) use($request, $product) {
                        if(!is_null($product) && !$product->isAvailable($value)){
                            $fail("The requested quantity amount is over the product stock availability");
                        }
                }
            ]
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toArray(),422);
        }
        $cartItem = $this->cartRepository->add($product, $request->quantity);
        return response()->json(['cart_item' => $cartItem->toArray()], 202);
    }
    public function remove(Request $request)
    {
        $validator = \Validator::make($request->toArray(), [
            'cart_item_id' => [
                'required',
                function($attributes, $value, $fail) {
                    $userCartItem = \Auth::user()->cartItems()->find($value);
                    if(is_null($userCartItem)){
                        $fail("Unable to remove this cart item");
                    }
                }
            ],
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toArray(),422);
        }
        $cartItemDeleted = $this->cartRepository->remove($request->cart_item_id);
        return response()->json(['cart_item' => [
            'id' => $request->cart_item_id,
            'deleted' => $cartItemDeleted
        ]], 202);
    }

}
