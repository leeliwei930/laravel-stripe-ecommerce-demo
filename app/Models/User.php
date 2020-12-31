<?php

namespace App\Models;

use App\Adapters\Stripe\StripePaymentAdapter;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeObject;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $stripe_customer_id
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\CartItem[] $cartItems
 * @property-read int|null $cart_items_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStripeCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read Collection|\App\Models\PaymentMethod[] $paymentMethods
 * @property-read int|null $payment_methods_count
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function retrieveStripeCustomerAccount(): Customer
    {
        $stripe = new StripePaymentAdapter();

        return $stripe->retrieveCustomer($this);

    }

    public function createStripeCustomerAccount(StripePaymentAdapter $stripe): Customer
    {
        $customer = $stripe->createCustomer($this);
        $this->stripe_customer_id = $customer->id;
        $this->save();
        return $customer;
    }

    protected static function booted()
    {
        static::created(function (User $user) {
            // auto create a stripe customer when user is created
            $stripeAdapter = new StripePaymentAdapter();
            $user->createStripeCustomerAccount($stripeAdapter);
        });
    }




    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function createOrder(Collection $items) : Order
    {
        $order = $this->orders()->create();
        $orderItems = [];
        $items->each(function($item) use(&$orderItems) {
            $product_id = (int) $item->product_id;
            $orderItems[$product_id] = [
                'quantity' => $item->quantity
            ];
            $product = Product::find($product_id);
            if(!is_null($product)){
                $product->withdrawQuantity($item->quantity);
            }
        });

        $order->items()->attach($orderItems);
        $order->load('items');
        return $order;
    }

    public function retrieveCartItem($cartItem_id)
    {
        return $this->cartItems()->find($cartItem_id);
    }

    public function setPrimaryPaymentMethod($paymentMethodID) : ?PaymentMethod
    {
        if(!is_null($previousPrimaryPaymentMethod = $this->getPrimaryPaymentMethod())){
            $previousPrimaryPaymentMethod->is_primary = false;
            $previousPrimaryPaymentMethod->save();
        }
        $paymentMethod = $this->paymentMethods()->find($paymentMethodID);
        $paymentMethod->is_primary = true;
        $paymentMethod->save();


        return $paymentMethod;
    }

    public function getPrimaryPaymentMethod()
    {
        return $this->paymentMethods()->firstWhere('is_primary', true);
    }

    public function presetPrimaryPaymentMethod()
    {
        if($this->paymentMethods()->count() == 1){
            $paymentMethod = $this->paymentMethods()->orderBy('created_at' , 'desc')->first();
            $this->setPrimaryPaymentMethod($paymentMethod->id);
        }
    }

}
