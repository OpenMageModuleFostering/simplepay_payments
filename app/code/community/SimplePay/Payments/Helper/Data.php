<?php

/**
 * Helper
 *
 * @category Helper
 * @package  SimplePay_Payments
 * @author   SimplePay
 */
class SimplePay_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{

    // Brand name to values array
    protected $brandNames = array(
        'American Express' => 'AMEX',
        'Diners'           => 'DINERS',
        'JCB'              => 'JCB',
        'MasterCard'       => 'MASTER',
        'Visa'             => 'VISA',
    );

    /**
     * Returns config model
     * 
     * @return SimplePay_Payments_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('simplepay/config');
    }

    public function brandToValue($brand)
    {
        return $this->brandNames[$brand];
    }

}