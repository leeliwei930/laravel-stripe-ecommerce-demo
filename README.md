# Laravel Stripe Integration Demo

## Setup Instruction

### Software Needed
- Composer
- Yarn / NPM
- nodeJS

### Run composer install
```bash
$ composer install
```

### Copy env file
```bash
$ cp .env.example .env
```
> Don't forget to place those Stripe keys inside, as well as the webhooks secret

### Run npm install for frontend packages Vue and Tailwind
```bash
$ npm install
```

### Run NPX mix to start the frontend bundling
```bash
$ npx mix
```

### Webhook Setup

#### Software Needed
- Homebrew

## Debug Webhook Event using StripeCLI

To install StripeCLI , Homebrew need to be installed on your Mac

In your terminal insert the command below.
```json
brew install stripe/stripe-cli/stripe
```


For the first time we need to login Stripe Account in order to use the StripeCLI
```bash
stripe login
```

You might redirect to the Stripe authentication page, follow the instruction on the browser.

### Forward Stripe Webhook Event to local development machine

Debugging webhook on local machine is hard, in order to proxy the network from internet to local machine. We need to use the tools like ngrok or expose. For Stripe, which is quite simple we can tell StripeCLI to listen on any Webhook Event and forward it to our local machine web server.

```bash
stripe listen --forward-to http://checkoutwithstripe.test/api/webhooks/stripe

# listen on any webhooks events, if there is an incoming event forward to the host in this case 
http://checkoutwithstripe.test/api/webhooks/stripe
```

Keep the terminal session opened, and copy the webhook secret into your `.env` file, we will be using this to validate the webhook event on our side to make sure the webhook events is come from a legit source.

```bash
▶ stripe listen --forward-to http://checkoutwithstripe.test/api/webhooks/stripe
> Ready! Your webhook signing secret is whsec_qqB8bMzi5o3pHNQoVSFYoFdkdpOUf4ru (^C to quit)
```
