<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Payment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    use HasFactory;

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function items()
    {
        return $this->belongsToMany(Product::class, 'orders_products' , 'order_id' , 'product_id')->withPivot('quantity');
    }

    public function getStripeOrderSummary(): array
    {
         $products= $this->items()->get();
        $stripeOrders = collect();
        $products->each(function($product) use($stripeOrders){

            $stripeOrders->push([
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $product->name,
                    ],
                    'unit_amount' => $product->price,
                ],
                'quantity' => $product->pivot->quantity
            ]);
        });
        return $stripeOrders->toArray();

    }

    public function calculateAmount()
    {
        $products = $this->items()->get();
        $products->map(function ($item){
           $item->total_amount = $item->price * + $item->pivot->quantity;
           return $item;
        });

        return $products->sum('total_amount');
    }
}
