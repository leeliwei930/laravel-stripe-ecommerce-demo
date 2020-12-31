<?php
namespace App\Repositories;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class CartRepository implements \App\Interfaces\CartRepositoryInterface {

    protected $user;
    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function add($product, $quantity): \Illuminate\Database\Eloquent\Model
    {
        return $this->user->cartItems()->updateOrCreate([
            'product_id' => $product->id
        ], ['quantity' => $quantity]);
    }

    public function remove($cartItemID)
    {
        $cartItem = $this->user->cartItems()->find($cartItemID);

        if(!is_null($cartItem)){
            return $cartItem->delete();
        }
        return false;
    }

    public function items()
    {
        return $this->user->cartItems()->with('product')->get();
    }

    public function checkout($items = []) : ?Order
    {
        $cartItems = collect($items);
        if(!$cartItems->isEmpty()){
            $selectedCheckoutItems = $this->user
                                    ->cartItems()
                                    ->whereIn('id' , $cartItems->toArray())
                                    ->get();
            return $this->user->createOrder($selectedCheckoutItems);
        }
        return null;
    }



}
