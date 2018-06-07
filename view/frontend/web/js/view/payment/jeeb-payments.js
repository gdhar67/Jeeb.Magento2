/**
 * Jeeb payment method model
 *
 * @category    Jeeb
 * @package     Jeeb_Merchant
 * @author      Jeeb
 * @copyright   Jeeb (https://jeeb.com)
 * @license     https://github.com/jeeb/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'jeeb_merchant',
                component: 'Jeeb_Merchant/js/view/payment/method-renderer/jeeb-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
