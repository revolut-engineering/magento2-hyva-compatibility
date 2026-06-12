## Revolut Payment — Hyvä Checkout compatibility for Adobe Commerce (Magento 2)

This module adds [Hyvä Checkout](https://www.hyva.io/) compatibility for the
[Revolut Payment plugin](https://commercemarketplace.adobe.com/revolut-module-payment.html).
Install it alongside `revolut/module-payment` when running a Hyvä-based checkout.

### Requirements

- PHP >= 7.4
- `revolut/module-payment` ^2.1
- `hyva-themes/magento2-hyva-checkout` ^1.1
- `magewirephp/magewire` ^1.13

### Installation

```bash
composer require revolut/module-payment-hyva-checkout
bin/magento module:enable Revolut_PaymentHyva
bin/magento setup:upgrade
```

Then recompile and deploy static content as required by your mode:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:flush
```

No additional configuration is needed — the module reuses the existing Revolut
payment settings (**Stores → Configuration → Sales → Payment Methods → Revolut**)
and automatically applies them to the Hyvä checkout.
