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

class Receivelanguage
{
    /**
     * @return array
     */
     public function toOptionArray()
     {
         return array(
            array('value' => 'none', 'label' => 'Auo-Select'),
            array('value' => 'en', 'label' => 'English'),
            array('value' => 'fa', 'label' => 'Persian')
         );
     }}
