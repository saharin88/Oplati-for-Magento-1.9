<?php

class Oplati_Oplati_Model_Method extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'oplati';

    public function canUseForCurrency($currencyCode)
    {
        return parent::canUseForCountry($currencyCode);
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return url for redirection after order placed
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('oplati/processing/payment');
    }

    /**
     * @param   Mage_Sales_Model_Order  $order
     * @param   array                   $oplatiTransaction
     *
     * @throws Mage_Core_Exception
     */
    public function saveTransaction($order, $oplatiTransaction)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($oplatiTransaction['paymentId']); // Make it unique.
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $oplatiTransaction);
        $transaction->setIsClosed(1);
        $transaction->save();
        $order->save();
    }


    /**
     * @param   Mage_Sales_Model_Order  $order
     * @param   array                   $oplatiTransaction
     *
     * @throws Mage_Core_Exception
     */
    public function updateTransaction($order, $oplatiTransaction)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment     = $order->getPayment();
        $transaction = $payment->getTransaction($oplatiTransaction['paymentId']);
        $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $oplatiTransaction);
        $transaction->save();
        $order->save();
    }

}