# Paycove Payment Gateway

## Working with this plugin

- make sure to bump versions on final commits in both the `readme.txt` file and `paycove.php` for the plugin
- when releasing to the wordpress.org plugin repository, remove the `class-github-updates.php` file and the call to it in `paycove.php`, then run `npm run plugin-zip and submit those files.
- it's good to update the Changelog section in the `readme.txt` file for each version release.

## WordPress.org plugin releases

- Move the `class-github-updater.php` file to the root of the project.
- Run `npm run plugin-zip`, this will zip all the necessary files for WP.org.
- Move the `class-github-updater.php` file back to the `includes` folder.
- Submit that zip to the repo.

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
