<?php

/**
 * Payment Controller
 *
 * @category Controller
 * @package  SimplePay_Payments
 * @author   SimplePay
 */
class SimplePay_Payments_PaymentController extends Mage_Core_Controller_Front_Action {

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function getConfig()
    {
        return Mage::getSingleton('simplepay/config');
    }

    protected function getPaymentModel()
    {
        return Mage::getSingleton('simplepay/payment');
    }

    protected function generatePaymentToken($storeId = 0)
    {

        // Get the token generation URL
        $tokenUrl = $this->getConfig()->getTokenUrl($storeId);

        // Get the order ID from the session data
        $lastIncrementId = $this->_getCheckout()->getLastRealOrderId();

        if ($lastIncrementId) {

            // Load the order using the order ID
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            // Prepare the data for generating a payment token
            $data = array(
                'SECURITY.SENDER'          => $this->getConfig()->getSecuritySender($storeId),
                'TRANSACTION.CHANNEL'      => $this->getConfig()->getTransactionChannel($storeId),
                'TRANSACTION.MODE'         => $this->getConfig()->getTransactionMode($storeId),
                'USER.LOGIN'               => $this->getConfig()->getUserLogin($storeId),
                'USER.PWD'                 => $this->getConfig()->getUserPwd($storeId),
                'PAYMENT.TYPE'             => $this->getConfig()->getPaymentType($storeId),
                'PRESENTATION.AMOUNT'      => $order->getGrandTotal(),
                'PRESENTATION.CURRENCY'    => $order->getOrderCurrencyCode(),
                'IDENTIFICATION.INVOICEID' => $lastIncrementId,
            );

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                )
            );

            $context = stream_context_create($options);
            $response = file_get_contents($tokenUrl, false, $context);

            // Check if the response is valid, return null if no response was received
            if ($response === false) {
                return null;
            }

            $resultJson = json_decode($response, true);

            // Return the payment token
            if (isset($resultJson['transaction']['token'])) {
                return $resultJson['transaction']['token'];
            }
        }

        // Did not find an order ID in the session data, return null
        return null;

    }

    /**
     * Called when an order is submitted, generates the payment form
     */
    public function payAction()
    {

        $lastIncrementId = $this->_getCheckout()->getLastRealOrderId();

        if ($lastIncrementId) {

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            // Get a new payment token
            $paymentToken = $this->generatePaymentToken($order->getStoreId());

            // Check if a payment token was generated
            if (is_null($paymentToken)) {
                $this->_getCheckout()->addError('Could not connect to payment server, please try again later.');
                $this->_redirect('checkout/cart');
                return;
            } else {
                // Store the payment token in the payment model for later access
                $this->getPaymentModel()->setData('paymentToken', $paymentToken);
            }

            // Update the order status
            if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {

                if ($order->getId()) {
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
                    );
                    $order->save();
                }

            }

        } else {
            // No order ID was found, redirect to the home page
            $this->_redirect('/');
            return;
        }

        // Load the payment page
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Called when a user cancels an order
     */
    public function cancelAction() {

        $lastIncrementId = $this->_getCheckout()->getLastRealOrderId();

        if ($lastIncrementId) {

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            if ($order->getId()) {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    true,
                    'This order was cancelled before a payment was made.'
                );
                $order->cancel();
                $order->save();

            }

        }

        // Display a message
        Mage::getSingleton('core/session')->addNotice('Order cancelled.');
        // Redirect to the shopping cart
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Called after submitting the payment form
     * Check the returned token to see if the payment was successful
     */
    public function responseAction()
    {
        $token = Mage::app()->getRequest()->getParam('token');

        // If the token is not present, redirect to the home page
        if (is_null($token)) {
            $this->_redirect('/');
            return;
        }

        $lastIncrementId = $this->_getCheckout()->getLastRealOrderId();

        if ($lastIncrementId) {

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            $statusUrl = $this->getConfig()->getStatusUrl($order->getStoreId()) . $token;

            // Check the status of the payment
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $statusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $resultPayment = curl_exec($ch);
            curl_close($ch);

            // Check if the response is valid
            if ($resultPayment === false) {
                // Could not get a response from the payment server

                // Set the order as payment review
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                    true,
                    'Could not contact payment server to check payment status.'
                );
                $order->save();

                // Redirect to the checkout failure page
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                return;
            }

            $resultJson = json_decode($resultPayment, true);

            // Check if the payment was successful
            if (isset($resultJson['errorMessage']) ||
                (isset($resultJson['transaction']) && $resultJson['transaction']['processing']['result'] === 'NOK'))
            {

                // Payment was unsuccessful
                // Display an error message to the user
                // Also list the error in the order comments
                $errorMsg = 'Payment unsuccessful.';

                // Append an error message if set
                if (isset($resultJson['errorMessage'])) {

                    $errorMsg .= '<br>Error: ' . $resultJson['errorMessage'];

                } else if (isset($resultJson['transaction']['processing']['return']['message'])) {

                    $errorMsg .= '<br>Error: ' . $resultJson['transaction']['processing']['return']['message'];

                }

                Mage::getSingleton('core/session')->addError($errorMsg);

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    true,
                    $errorMsg
                );
                $order->save();

                // Redirect back to the payment page to retry payment
                $this->_redirect('*/*/pay', array('_secure' => true));
                return;

            } else if (isset($resultJson['transaction']) &&
                       isset($resultJson['transaction']['processing']['result']) &&
                       $resultJson['transaction']['processing']['result'] === 'ACK')
            {

                // Payment was successful

                // Prepare a success message
                $successMsg = 'Payment has been authorised.';

                // Append the transaction ID
                if (isset($resultJson['transaction']['identification']['uniqueId'])) {
                    $successMsg .=  '<br>Transaction ID: ' . $resultJson['transaction']['identification']['uniqueId'];
                }

                // Append the response message
                if (isset($resultJson['transaction']['processing']['return']['message'])) {
                    $successMsg .=  '<br>Response: ' . $resultJson['transaction']['processing']['return']['message'];
                }

                // Update the order
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    true,
                    $successMsg
                );
                $order->save();

                // Send an order email to the customer
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);

                // Set the last completed order
                $this->_getCheckout()->setLastSuccessQuoteId($order->getQuoteId());

                // Redirect to the checkout success page
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                return;

            } else {

                // Unknown transaction status

                // Set the order as payment review
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                    true,
                    'Could not verify payment.'
                );
                $order->save();

                // Redirect to the checkout failure page
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                return;

            }

        }

        // No order ID was found, redirect to home page
        $this->_redirect('/');
        return;
    }

}
