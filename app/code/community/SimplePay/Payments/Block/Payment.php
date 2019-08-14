<?php

/**
 * Payment Block
 *
 * @category Block
 * @package  SimplePay_Payments
 * @author   SimplePay
 */
class SimplePay_Payments_Block_Payment extends Mage_Core_Block_Template
{

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get SimplePay config
     *
     * @return SimplePay_Payments_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('simplepay/config');
    }

    protected function getPaymentModel()
    {
        return Mage::getSingleton('simplepay/payment');
    }

    public function getFormUrl($storeId = 0)
    {
        return $this->getConfig()->getFormUrl($storeId);
    }

    public function getResponseUrl()
    {
        return $this->getConfig()->getResponseUrl();
    }

    public function getCancelUrl()
    {
        return $this->getConfig()->getCancelUrl();
    }

    public function getAcceptedBrandsForForm($storeId = 0)
    {
        return $this->getConfig()->getAcceptedBrandsForForm();
    }

}
