<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PaymentMethod
 *
 * @property int $id
 * @property int $user_id
 * @property string $payment_gateway_id
 * @property string $token
 * @property string $type
 * @property string|null $card_last4
 * @property string|null $card_expiry_date
 * @property string|null $card_issue_country
 * @property string|null $bank_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PaymentGateway $paymentGateway
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereCardExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereCardIssueCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereCardLast4($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod wherePaymentGatewayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereUserId($value)
 * @mixin \Eloquent
 * @property-read \App\Models\User $user
 */
class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = ['payment_gateway_id', 'card_fingerprint','user_id', 'token', 'type', 'card_last4', 'card_expiry_date', 'card_issue_country'];

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
