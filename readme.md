# Paycove Payment Gateway

## Working with this plugin

- make sure to bump versions on final commits in both the `readme.txt` file and `index.php` for the plugin
- when releasing to the wordpress.org plugin repository, remove the `class-github-updates.php` file and the call to it in `index.php`, then run `npm run plugin-zip and submit those files.

## Install

You know the drill:

```bash
# Install
npm install
composer install

# Build JS assets for plugin.
npm start
```

## Formatting and linting

See `composer.json` and `package.json` for available formatting and linting scripts.

## Plugin distribution

Run the following:

```
npm run build
npm run plugin-zip
```

This will build a `.zip` of the build version of the plugin for distribution.

## Resources

- https://docs.paycove.io/
- https://help.paycove.io/knowledge/using-the-payment-planner
- https://help.paycove.io/knowledge/scheduled-payments-split-payments-and-auto-billing
- https://rudrastyh.com/woocommerce/payment-gateway-plugin.html#gateway_processing
- https://rudrastyh.com/woocommerce/checkout-block-payment-method-integration.html
