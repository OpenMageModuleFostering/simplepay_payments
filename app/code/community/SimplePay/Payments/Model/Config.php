<?php

/**
 * Config Model
 *
 * @category Model
 * @package  SimplePay_Payments
 * @author   SimplePay
 */
class SimplePay_Payments_Model_Config extends Mage_Payment_Model_Config
{
    const SIMPLEPAY_PATH = 'payment_services/simplepay/';
    const SIMPLEPAY_CONTROLLER_ROUTE = 'simplepay/payment/';

    /**
     * Token generation URLs
     */
    const TOKEN_URL      = 'https://simplepays.com/frontend/GenerateToken';
    const TOKEN_TEST_URL = 'https://test.simplepays.com/frontend/GenerateToken';

    /**
     * Payment form URLs
     */
    const FORM_URL      = 'https://simplepays.com/frontend/widget/v3/widget.js?language=en&style=card';
    const FORM_TEST_URL = 'https://test.simplepays.com/frontend/widget/v3/widget.js?language=en&style=card';

    /**
     * Transaction status URLs
     */
    const STATUS_URL      = 'https://simplepays.com/frontend/GetStatus;jsessionid=';
    const STATUS_TEST_URL = 'https://test.simplepays.com/frontend/GetStatus;jsessionid=';

    /**
     * Return SimplePay config information
     *
     * @param string $path
     * @param int $storeId
     * @return Simple_Xml
     */
    public function getConfigData($path, $storeId = null)
    {
        if (!empty($path)) {
            return Mage::getStoreConfig(self::SIMPLEPAY_PATH . $path, $storeId);
        }
        return false;
    }

    /**
     * Return security sender from config
     *
     * @param int $storeId
     * @return string
     */
    public function getSecuritySender($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('security_sender', $storeId));
    }
    
    /**
     * Return transaction channel from config
     *
     * @param int $storeId
     * @return string
     */
    public function getTransactionChannel($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('transaction_channel', $storeId));
    }

    /**
     * Return transaction mode from config
     *
     * @param int $storeId
     * @return string
     */
    public function getTransactionMode($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('transaction_mode', $storeId));
    }

    /**
     * Return user login from config
     *
     * @param int $storeId
     * @return string
     */
    public function getUserLogin($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('user_login', $storeId));
    }

    /**
     * Return user pwd from config
     *
     * @param int $storeId
     * @return string
     */
    public function getUserPwd($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('user_pwd', $storeId));
    }

    /**
     * Return user pwd from config
     *
     * @param int $storeId
     * @return string
     */
    public function getPaymentType($storeId = null)
    {
        return Mage::helper('core')->decrypt($this->getConfigData('payment_type', $storeId));
    }
    
    /**
     * Check whether in test mode or not
     *
     * @return boolean
     */
    public function inTestMode($storeId = null)
    {
        return $this->getConfigData('test_mode_flag', $storeId);
    }

    public function getTokenUrl($storeId = 0)
    {
        if ($this->inTestMode($storeId)) {
            return self::TOKEN_TEST_URL;
        } else {
            return self::TOKEN_URL;
        }
    }

    public function getFormUrl($storeId = 0)
    {
        if ($this->inTestMode($storeId)) {
            return self::FORM_TEST_URL;
        } else {
            return self::FORM_URL;
        }
    }

    public function getStatusUrl($storeId = 0)
    {
        if ($this->inTestMode($storeId)) {
            return self::STATUS_TEST_URL;
        } else {
            return self::STATUS_URL;
        }
    }
    
    /**
     * Returns the accepted brands
     */
    public function getAcceptedBrands()
    {
        return Mage::getStoreConfig('payment/simplepay/brands');
    }

    /**
     * Return all accepted brands
     */
    public function getAllBrands()
    {
        return explode(',', Mage::getStoreConfig('payment/simplepay/availableBrands'));
    }

    public function getAcceptedBrandsForForm()
    {
        return implode(' ', explode(',', $this->getAcceptedBrands()));
    }

    /**
     * Return URL to redirect after placing an order
     *
     * @return string
     */
    public function getPaymentRedirectUrl()
    {
        return Mage::getUrl(
            self::SIMPLEPAY_CONTROLLER_ROUTE . 'pay', 
            array('_secure' => true, '_nosid' => true)
        );
    }

    /**
     * Return URL to return to after submitting the payment form
     *
     * @return string
     */
    public function getResponseUrl()
    {
        return Mage::getUrl(
            self::SIMPLEPAY_CONTROLLER_ROUTE . 'response', 
            array('_secure' => true, '_nosid' => true)
        );
    }

    /**
     * Return the cancel URL
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return Mage::getUrl(
            self::SIMPLEPAY_CONTROLLER_ROUTE . 'cancel',
            array('_secure' => true, '_nosid' => true)
        );
    }
}