import Vue from 'vue'

window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
let stripe = window.Stripe(process.env.MIX_STRIPE_PUBLISHABLE_KEY)

Vue.component('stripe-card-form', {
    props:{
      publishableKey: {
          type: String,
          required: true
      },
      buttonText: {
          type: String,
          default: "Create Card"
      },
      setupIntentUrl: {
          type: String,
          required: true
      }
    },
    template:
    `<div class="stripe-card-form" >
    <span v-if="stripe === null">Stripe.JS not Loaded</span>
        <div class="flex flex-col ">
            <div class="flex flex-col my-2">
                <label :for="uniqueDOMId('stripe-card-number')">Card Number</label>
                <div :id="uniqueDOMId('stripe-card-number')"></div>
            </div>
            <div class="flex flex-row justify-between my-2">
                <div class="flex flex-col w-full mr-3">
                    <label :for="uniqueDOMId('stripe-card-expiry')">Card Expiry</label>
                    <div :id="uniqueDOMId('stripe-card-expiry')"></div>
                </div>
                <div class="flex flex-col w-full">
                    <label :for="uniqueDOMId('stripe-card-cvc')">Card CVC</label>
                    <div :id="uniqueDOMId('stripe-card-cvc')"></div>
                </div>
            </div>
            <div class="flex flex-col w-full">
                <label :for="uniqueDOMId('stripe-zip-code')">ZIP Code</label>
                <div :id="uniqueDOMId('stripe-zip-code')"></div>
            </div>
            <div class="flex flex-row w-full">
              <label :for="uniqueDOMId('stripe-set-primary-card')">
                  <input type="checkbox" v-model="set_primary" :id="uniqueDOMId('stripe-set-primary-card')"/>
                  Make This Card As Primary
              </label>
            </div>
            <button @click="submit" class="my-2 bg-indigo-500 hover:bg-indigo-400 text-white py-2 px-4 rounded">Add Card</button>
        </div>
    </div>`,
    data(){
        return {
            stripe: null,
            stripeElements: null,
            zipCode: "",
            set_primary: false
        }
    },
     mounted(){
            if(window.Stripe !== null && this.publishableKey.length > 0){
                this.$nextTick(() => {
                    this.initializeStripe();
                    this.mountStripeElements();
                })
            }
    },
    methods: {
        initializeStripe(){
            this.stripe = window.Stripe(this.publishableKey);
        },
        submit(){
            let cardNumberElement = this.stripeElements.getElement('cardNumber')
            axios.get(this.setupIntentUrl).then((response) => {
                if(response.data.setup_intent.client_secret){
                    let clientSecret = response.data.setup_intent.client_secret;
                    this.stripe.confirmCardSetup(clientSecret, {
                        payment_method: {
                            card: cardNumberElement
                        }
                    }).then(result => {
                        if(!result.error){
                            this.$emit('submit' , {
                                payment_method: result.setupIntent.payment_method,
                                set_primary: this.set_primary
                            })
                        }
                    })
                }
            })

            // confirm setup intent

            // create payment method

        },
        unmountStripeElements(){
            let elements = this.stripeElements;
            let cardNumberElement = elements.getElement('cardNumber')
            let cardExpiryElement = elements.getElement('cardExpiry')
            let cardCvcElement = elements.getElement('cardCvc')
            let cardZipElement = elements.getElement('postalCode')
            cardNumberElement.unmount();
            cardExpiryElement.unmount();
            cardCvcElement.unmount();
            cardZipElement.unmount();
        },
        mountStripeElements(){
            let classes = {
                base: 'form-input'
            }
            let style  = {
                base: {
                    iconColor: '#666EE8',
                    color: '#31325F',
                    lineHeight: '40px',
                    fontWeight: 300,
                    fontFamily: 'Helvetica Neue',
                    fontSize: '15px',

                    '::placeholder': {
                        color: '#CFD7E0',
                    },
                },
            };
            this.stripeElements = this.stripe.elements();
            let elements = this.stripeElements;
            let cardNumberElement = elements.create('cardNumber', {style, classes} )
            cardNumberElement.mount(`#${this.uniqueDOMId('stripe-card-number')}`);

            let cardExpiryElement = elements.create('cardExpiry', {style, classes})
            cardExpiryElement.mount(`#${this.uniqueDOMId('stripe-card-expiry')}`);

            let cardCvcElement = elements.create('cardCvc', {style, classes})
            cardCvcElement.mount(`#${this.uniqueDOMId('stripe-card-cvc')}`);

            let cardZipCodeElement = elements.create('postalCode', {style, classes})
            cardZipCodeElement.mount(`#${this.uniqueDOMId('stripe-zip-code')}`);


        },
        uniqueDOMId(domName){
            return `${domName}-${this._uid}`
        }
    },
    beforeDestroy() {
        this.unmountStripeElements()
    }
})
let app = new Vue({
    el: '#app',
    data: {
            products: [],
            orders: [],
            cartItems :[],
            paymentMethods:[],
            showCreditCardForm: false,
            selectedPaymentMethod: null,
            disableCheckout: false,
    },
    methods: {
        checkout(){
            this.disableCheckout = true;
            axios.post('/api/checkout',{
                cart_items: [...this.selectedCartItems], payment_method_id: this.selectedPaymentMethod
            }).then((response) => {
                let paymentIntentStatus = response.data.payment_intent.status
                let order = response.data.order;
                // Status of this PaymentIntent, one of requires_payment_method, requires_confirmation, requires_action, processing, requires_capture, canceled, or succeeded
                // if the payment intent required extra user authorization
                this.fetchCartItems();
                if(paymentIntentStatus === 'requires_confirmation' || paymentIntentStatus ==='requires_action' ){
                    order.disabled_buttons = {
                        reconfirm: false,
                        cancel: false,
                        refund: false,
                    }
                    order.form =  {
                        changePaymentMethod: false,
                    }
                    this.reconfirmPayment(order)

                } else if (paymentIntentStatus === 'succeeded') {
                    this.disableCheckout = false;
                    this.fetchOrders();
                } else if (paymentIntentStatus === 'failed'){
                    this.disableCheckout = false;
                    this.fetchOrders();

                }

            }).catch((error) => {
                this.fetchCartItems();
                console.log(error)
                alert(error.response.data.message)
                this.disableCheckout = false;

            })
        },
        fetchOrders(){
            axios.get('/api/orders').then((response) => {

                this.orders = response.data.orders.map((order) => {
                    order.disabled_buttons = {
                        reconfirm: false,
                        cancel: false,
                        refund: false,
                    }
                    order.form =  {
                        changePaymentMethod: false,
                    }
                    return order;
                });
            })
        },
        fetchProducts(){
            axios.get('/api/products').then((response) => {
                let products = response.data
                products.map((product) => {
                    product.selectedQuantity = 1;
                    return product;
                })
                this.products = products;
            }).catch(error => {

            });
        },
        fetchCartItems(){
            axios.get('/api/cart').then((response) => {
                let cartItems = response.data.cart_items;
                cartItems.map((item) => {
                    item.new_quantity = item.quantity;
                    item.selected = false;
                    return item;
                })
                this.cartItems = cartItems;
            })
        },
        fetchPaymentMethods(){
            axios.get('/api/payment-methods').then((response) => {
                this.paymentMethods = response.data.payment_methods;
                this.showCreditCardForm = false;
            })
        },

        createPaymentMethod(stripePaymentMethod){
            console.log(stripePaymentMethod)
            axios.post('/api/payment-methods/create', {
                'stripe_payment_method_id' :stripePaymentMethod.payment_method,
                'set_primary' : stripePaymentMethod.set_primary
            }).then((response) => {
                this.fetchPaymentMethods();
            })
        },
        addToCart(product){
            axios.put('/api/cart/add', {
                product_id: product.id,
                quantity: product.selectedQuantity
            }).then((response) => {
                this.fetchCartItems();
            })
        },
        updateCartItem(cartItem){
            axios.put('/api/cart/add', {
                product_id: cartItem.product_id,
                quantity: cartItem.new_quantity
            }).then((response) => {

            }).catch(error => {
                cartItem.new_quantity = cartItem.quantity;
            })
        },

        removeCartItem(cartItemId){

        },
        canReattemptPayment(order){
            let paymentStatus = order.payment.status;
            return ['pending', 'failed'].includes(paymentStatus)
        },
        canReauthorizePayment(order){
            let paymentStatus = order.payment.status;
            return ['requires_action'].includes(paymentStatus)

        },
        canChangePaymentMethod(order){
            let paymentStatus = order.payment.status
            return ['pending', 'failed'].includes(paymentStatus)
        },
        reconfirmPayment(order){
            order.disabled_buttons.reconfirm = true;
            axios.get(`/api/orders/${order.id}/payment/reconfirm`).then((response) => {
                if(response.data.payment_intent.status ==='requires_action' || response.data.payment_intent.status ==='requires_confirmation'){
                    stripe.handleCardAction(response.data.payment_intent.client_secret)
                        .then((result) => {
                            if(result.error){
                                order.disabled_buttons.reconfirm = false;
                                alert(result.error.message)
                            }
                            this.fetchOrders();

                        });
                } else if (response.data.payment_intent.status === 'succeeded'){
                    order.disabled_buttons.reconfirm = false;
                    this.fetchOrders()
                    alert('Payment Success')
                }
            }).catch((error) => {
                order.disabled_buttons.reconfirm = false;
                this.fetchOrders()
                alert(error.response.data.message);
            });
        },
        updatePaymentMethod(order, paymentMethodId){
            if(order.payment.payment_method_id === paymentMethodId){
                return;
            }
            axios.put(`/api/orders/${order.id}/payment/update` , {
                payment_method_id: paymentMethodId
            }).then((response) => {
                Object.assign(order, response.data.order)
            }).catch((error) => {
                console.log(error)
            })
        },
        refundPayment(order){
            order.disabled_buttons.refund = true;
            axios.post(`/api/orders/${order.id}/payment/refund`).then((response) => {
                // setback the new order data
                Object.assign(order, response.data.order);
                order.disabled_buttons.refund = false;

            }).catch((error) => {
                alert(error.response.data.error);
                order.disabled_buttons.refund = false;
            })
        },
        cancelOrder(order){
            order.disabled_buttons.cancel = true;
            axios.post(`/api/orders/${order.id}/payment/refund`).then((response) => {
                Object.assign(order, response.data.order);
                order.disabled_buttons.cancel = false;
            }).catch((error) => {
                alert(error.response.data.error);
                order.disabled_buttons.cancel = false;
            })
        }
    },
    created() {
        this.fetchProducts();
        if(window.auth){
            this.fetchOrders();
            this.fetchCartItems();
            this.fetchPaymentMethods();
        }
    },
    computed: {
        selectedCartItems(){
            let selectedCartItems =  this.cartItems.filter((product) => product.selected === true);
            let cartItemsID  = []
            selectedCartItems.forEach((cartItem) => {
                cartItemsID.push(cartItem.id);
            })
            return cartItemsID;
        }
    }
})
