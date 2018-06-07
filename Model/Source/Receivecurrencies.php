<?php
/**
 * Receive currencies Source Model
 *
 * @category    Jeeb
 * @package     Jeeb_Merchant
 * @author      Jeeb
 * @copyright   Jeeb (https://jeeb.com)
 * @license     https://github.com/jeeb/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace Jeeb\Merchant\Model\Source;

class Receivecurrencies
{
    /**
     * @return array
     */
     public function toOptionArray()
     {
         return array(
            array('value' => 'btc', 'label' => 'Bitcoin (฿)'),
            array('value' => 'eur', 'label' => 'Euros (€)'),
            array('value' => 'usd', 'label' => 'US Dollars ($)') 
         );
     }}
