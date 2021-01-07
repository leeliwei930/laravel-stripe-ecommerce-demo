<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <script src="https://js.stripe.com/v3/"></script>

        <link rel="stylesheet" href="{{mix('css/app.css')}}">


    </head>
    <body class="antialiased bg-gray-200">
        <div id="app">
            <div class="mx-5 my-2">
            <h2 class="text-lg text-black font-bold">Products</h2>
            <div class="flex flex-col">
                <div v-for="product in products">
                    <div class="flex flex-row items-center justify-between my-2">

                        <div class="flex flex-col w-6/12">
                            <h2 class="text-lg">@{{ product.name }}</h2>
                            <h2 class="text-lg">@{{ product.price_label }}</h2>
                            <h3>Available quantity @{{ product.quantity }}</h3>
                        </div>
                        <input type="number" :max="product.quantity" v-model="product.selectedQuantity" class="form-input w-2/12 mr-3"/>
                        <button @click="addToCart(product)" v-if="product.selectedQuantity > 0" class="w-4/12 bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">Add To Cart</button>
                    </div>
                </div>
            </div>
            </div>
            <div class="mx-5 my-2">
                <h2 class="text-lg text-black font-bold">Cart List</h2>

                <div class="flex flex-col">
                    <div v-for="item in cartItems">
                        <div class=" py-3 flex flex-row items-center justify-between">
                            <input type="checkbox"  class="form-checkbox"   v-model="item.selected" />
                            <div class="flex flex-col w-8/12">
                                <h2 class="text-lg">@{{ item.product.name }}</h2>
                                <h2 class="text-lg">@{{ item.product.price_label }}</h2>
                                <h3>Available quantity @{{ item.product.quantity }}</h3>
                            </div>
                            <input type="number" min="1" :max="item.product.quantity" class="form-input w-3/12" v-model="item.new_quantity" @change="updateCartItem(item)" placeholder="Enter your quantity"/>
                        </div>
                    </div>
                    <button :disabled="cartItems.length == 0 || paymentMethods.length === 0 || disableCheckout" @click="checkout" class="ml-auto bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">Checkout</button>
                </div>
            </div>
            <div class="mx-5 my-2">
                <h2 class="text-lg text-black font-bold">Payment Methods</h2>
                <button @click="showCreditCardForm = !showCreditCardForm" class="my-2 ml-auto bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">
                    @{{ (showCreditCardForm) ? 'Close' : 'Add Credit Card' }}
                </button>
                <template  v-if="showCreditCardForm">
                    <stripe-card-form
                        setup-intent-url="/api/payment-methods/setup-intent"
                        publishable-key="{{ config('services.stripe.publishable_key') }}"
                        @submit="createPaymentMethod"
                    ></stripe-card-form>
                </template>
                <template v-else>
                    <div v-for="card in paymentMethods" class="rounded px-3 py-2 rounded shadow bg-white my-1">
                        <div class="flex flex-row items-center">
                            <div class="mx-2">
                                <input type="radio" v-model="selectedPaymentMethod" :value="card.id" :id="`card-${card.id}`"  />
                            </div>
                            <label :for="`card-${card.id}`" class="flex flex-col">
                                <div class="text-lg">....@{{card.card_last4}}</div>
                                <div class="text-lg">@{{card.card_expiry_date}}</div>
                            </label>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mx-5 my-2">
                <h2 class="text-lg text-black font-bold">Orders</h2>
                <div v-for="order in orders" class="rounded px-3 py-2 rounded shadow bg-white my-1">
                    <div class="flex flex-col items-start">
                        <h2 class="font-semibold">ORDER ID: @{{order.id}}</h2>
                        <table class="w-full">
                            <thead>
                               <tr>
                                   <th class="text-left">Product Name</th>
                                   <th class="text-left">Quantity</th>
                                   <th class="text-right">Amount</th>
                               </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items">
                                    <td>@{{ item.name }}</td>
                                    <td>@{{ item.pivot.quantity }}</td>
                                    <td class="text-right">RM @{{ (item.price * item.pivot.quantity) / 100 }}</td>
                                </tr>

                            </tbody>
                        </table>
                        <div class="self-end inline-flex flex-col items-end">
                            <h2 class="font-bold">Total Amount</h2>
                            <span>RM @{{ order.amount / 100}}</span>
                        </div>
                        <div class="flex flex-row justify-between w-full">
                            <div class="flex flex-col">
                                <h2 class="uppercase text-sm">Payment Method</h2>
                                <span >@{{ order.payment.payment_method.type }} ····@{{ order.payment.payment_method.card_last4 }}</span>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="uppercase text-sm">Payment Status</h2>
                                <span class="capitalize">@{{ order.payment.status }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 w-full">
                                <button
                                    class="w-full bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded my-1"
                                    v-if="canReattemptPayment(order)"
                                    @click="reconfirmPayment(order)"
                                    :disabled="order.disabled_buttons.reconfirm"
                                >
                                    Reattempt Payment
                                </button>
                                <button
                                    class="w-full bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded my-1"
                                    v-if="canReauthorizePayment(order)"
                                    @click="reconfirmPayment(order)"
                                    :disabled="order.disabled_buttons.reconfirm"
                                >
                                    Authorize Payment
                                </button>
                                <button
                                    class="w-full bg-blue-500 hover:bg-blue-400 text-white py-2 px-4 rounded my-1"
                                    v-if="canChangePaymentMethod(order) && !order.form.changePaymentMethod"
                                    @click="order.form.changePaymentMethod = !order.form.changePaymentMethod"
                                >
                                    Change Payment Method
                                </button>
                                <div class="flex flex-col w-full border rounded p-3" v-if="order.form.changePaymentMethod">
                                    <h2 class="uppercase text-sm font-bold text-gray-500">CHANGE PAYMENT METHOD</h2>
                                    <div class="my-3">
                                    <select
                                        name="payment_method"
                                        class="p-3 bg-white border w-full"
                                        @change="(e) => updatePaymentMethod(order, e.target.value)"
                                    >
                                        <option :value="paymentMethod.id" v-for="paymentMethod in paymentMethods">
                                            ····@{{paymentMethod.card_last4}}
                                        </option>
                                    </select>
                                    <button
                                        class="w-full bg-blue-500 hover:bg-blue-400 text-white py-2 px-4 rounded my-2"
                                        @click="order.form.changePaymentMethod = false">
                                        Done
                                    </button>
                                    </div>
                                </div>
                                <button
                                    class="w-full bg-green-500 hover:bg-green-400 text-white py-2 px-4 rounded my-1"
                                    v-if="order.refundable"
                                    :disabled="order.disabled_buttons.refund"
                                    @click="refundPayment(order)"
                                >
                                    Make Refund
                                </button>
                                <button
                                    class="w-full bg-red-500 hover:bg-red-400 text-white py-2 px-4 rounded my-1"
                                    v-if="order.cancellable"
                                    :disabled="order.disabled_buttons.cancel"
                                    @click="cancelOrder(order)"
                                >
                                    Cancel Order
                                </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </body>
    <script>
        window.auth = "{{(!auth()->guest()) ? true : false}}"
    </script>
    <script type="text/javascript" src="{{mix('js/app.js')}}"></script>

</html>
