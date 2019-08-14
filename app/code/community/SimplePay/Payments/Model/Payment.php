<?php

/**
 * Payment Model
 *
 * @category Model
 * @package  SimplePay_Payments
 * @author   SimplePay
 */
class SimplePay_Payments_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Unique internal payment method identifier
     */
    protected $_code = 'simplepay';

    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_canFetchTransactionInfo = false;

    protected function getConfig()
    {
        return Mage::getModel('simplepay/config');
    }

    public function getTokenUrl($storeId = 0)
    {
        return $this->getConfig()->getTokenUrl($storeId);
    }

    public function getFormUrl($storeId = 0)
    {
        return $this->getConfig()->getFormUrl($storeId);
    }

    public function getStatusUrl($storeId = 0)
    {
        return $this->getConfig()->getStatusUrl($storeId);
    }

    public function getAcceptedBrandsForForm()
    {
        return $this->getConfig()->getAcceptedBrandsForForm();
    }

    /**
     * Redirect URL to payment form
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
          return $this->getConfig()->getPaymentRedirectUrl();
    }

}