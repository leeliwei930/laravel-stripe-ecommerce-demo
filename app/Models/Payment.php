<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $amount
 * @property int $order_id
 * @property string|null $tx_no
 * @property string $checkout_id
 * @property int|null $payment_method_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCheckoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTxNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $status
 * @property string|null $payment_status_message
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentStatusMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @property-read \App\Models\PaymentMethod|null $paymentMethod
 * @property string|null $refund_id
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereRefundId($value)
 */
class Payment extends Model
{
    const PENDING = "pending";
    const SUCCESS = "success";
    const FAILED = "failed";

    use HasFactory;
    protected $fillable = ['amount', 'checkout_id', 'tx_no', 'order_id', 'payment_method_id', 'status', 'payment_status_message'];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }



}
