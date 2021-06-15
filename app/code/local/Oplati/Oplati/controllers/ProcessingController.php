<?php

class Oplati_Oplati_ProcessingController extends Mage_Core_Controller_Front_Action
{

    protected $_resp
        = [
            'success' => true,
            'data'    => [],
            'message' => ''
        ];

    public function paymentAction()
    {
        try {

            $session = $this->_getCheckout();
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if ( ! $order->getId()) {
                Mage::throwException('No order for processing found');
            }
            $order->setState(Mage::getStoreConfig('payment/oplati/new_order_status'), true);
            $order->save();
            $this->_getHelper()->createOplatiTransaction($order);
            $session->setOplatiTransaction($this->_getHelper()->getOplatiTransaction());
            if (Mage::getStoreConfig('payment/oplati/sava_transaction') === '1') {
                /** @var Oplati_Oplati_Model_Method $oplati */
                $oplati = Mage::getModel('oplati/method');
                $oplati->saveTransaction($order, $this->_getHelper()->getOplatiTransaction());
            }
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    public function checkStatusAction()
    {
        try {
            if ( ! $this->_validateFormKey()) {
                throw new Exception('Empty form key');
            }
            $session           = $this->_getCheckout();
            $oplatiTransaction = $session->getOplatiTransaction();
            if (empty($oplatiTransaction['paymentId'])) {
                throw new Exception('Empty transaction paymentId');
            }
            $oldStatus                     = $oplatiTransaction['status'];
            $status                        = $this->_getHelper()->getOplatiTransactionStatus($oplatiTransaction['paymentId']);
            $this->_resp['data']['status'] = $status;
            $this->_resp['message']        = $this->_getHelper()->__('STATUS_'.$status);
            if (Mage::getStoreConfig('payment/oplati/sava_transaction') === '1' && $oldStatus !== $status) {
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    /** @var Oplati_Oplati_Model_Method $oplati */
                    $oplati = Mage::getModel('oplati/method');
                    $oplati->updateTransaction($order, $this->_getHelper()->getOplatiTransaction());
                } else {
                    Mage::throwException('No order for processing found');
                }
            }
        } catch (Exception $e) {
            $this->_resp['success'] = false;
            $this->_resp['message'] = $e->getMessage();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->_resp));
    }

    public function rePaymentAction()
    {
        try {
            if ( ! $this->_validateFormKey()) {
                throw new Exception('Empty form key');
            }
            $session = $this->_getCheckout();
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if ( ! $order->getId()) {
                Mage::throwException('No order for processing found');
            }
            $order->setState(Mage::getStoreConfig('payment/oplati/new_order_status'), true);
            $this->_getHelper()->createOplatiTransaction($order);
            $session->setOplatiTransaction($this->_getHelper()->getOplatiTransaction());
            if (Mage::getStoreConfig('payment/oplati/sava_transaction') === '1') {
                /** @var Oplati_Oplati_Model_Method $oplati */
                $oplati = Mage::getModel('oplati/method');
                $oplati->saveTransaction($order, $this->_getHelper()->getOplatiTransaction());
            }
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();
            $this->_resp['data'] = $this->getLayout()->createBlock('oplati/payment')->setTemplate('oplati/payment.phtml')->toHtml();
        } catch (Exception $e) {
            $this->_resp['success'] = false;
            $this->_resp['message'] = $e->getMessage();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->_resp));
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
    {
        try {
            $session = $this->_getCheckout();
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if ( ! $order->getId()) {
                Mage::throwException('No order for processing found');
            }
            $order->setState(Mage::getStoreConfig('payment/oplati/success_order_status'), true);
            $session->setLastSuccessQuoteId($order->getQuoteId());
            $order->save();
            $this->_redirect('checkout/onepage/success');

            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the customer will be returned if the payment process is
     * cancelled.
     * Cancel order and redirect user to the shopping cart.
     */
    public function cancelAction()
    {
        $session = $this->_getCheckout();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        if ( ! $order->getId()) {
            Mage::throwException('No order for processing found');
        }
        $order->cancel();
        $order->setState(Mage::getStoreConfig('payment/oplati/cancel_order_status'), true);
        $order->save();
        $this->_redirect('checkout/cart');
    }

    /**
     * Retrieve Oplati helper
     *
     * @return Oplati_Oplati_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('oplati');
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param   string  $path
     * @param   array   $arguments
     */
    protected function _redirect($path, $arguments = array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('moneybookers/redirect')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );

        return $this;
    }
}
