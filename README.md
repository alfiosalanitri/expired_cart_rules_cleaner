# Expired Cart Rules Cleaner

There is an unresolved bug in PrestaShop (at least up to version 8.1.2. [https://github.com/PrestaShop/PrestaShop/issues/19393](https://github.com/PrestaShop/PrestaShop/issues/19393)) that allows users who have entered a discount code in the cart before it expires to still make purchases with the discount even after the code has expired or been deactivated, as long as the cart is still active.

This module provides a URL that can be manually accessed or scheduled via a periodic cronjob to remove discount codes from abandoned carts that have expired or been deactivated.

Verified with PrestaShop versions 1.7.8.7 and 8.1.2
