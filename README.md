# Magento 2 Jeeb Plugin

Sign up for Jeeb account at <https://jeeb.io> for getting the signature for Live/Test transactions.

Please note, that for "Test" mode you **must** get separate API credentials. Normal API credentials will **not** work for "Test" mode.

## Installation via Composer

You can install Magento 2 Jeeb plugin via [Composer](http://getcomposer.org/). Run the following command in your terminal:

1. Go to your Magento 2 root folder.

2. Enter following commands to install plugin:

    ```bash
    composer require jeeb/magento2-plugin
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable plugin:

    ```bash
    php bin/magento module:enable Jeeb_Merchant --clear-static-content
    php bin/magento setup:upgrade
    ```

## Plugin Configuration

Enable and configure Jeeb plugin in Magento Admin under `Stores / Configuration / Sales / Payment Methods / Bitcoin and Altcoins via Jeeb`.
