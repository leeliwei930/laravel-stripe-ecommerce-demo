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
                    <button :disabled="cartItems.length == 0 || paymentMethods.length === 0" @click="checkout" class="ml-auto bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">Checkout</button>
                </div>
            </div>

            <div class="mx-5 my-2">
                <h2 class="text-lg text-black font-bold">Payment Methods</h2>
                <button @click="showCreditCardForm = !showCreditCardForm" class="ml-auto bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">
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

        </div>
    </body>
    <script>
        window.auth = "{{(!auth()->guest()) ? true : false}}"
    </script>
    <script type="text/javascript" src="{{mix('js/app.js')}}"></script>

</html>
