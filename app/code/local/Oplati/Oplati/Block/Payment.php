<?php

class Oplati_Oplati_Block_Payment extends Mage_Core_Block_Template
{

    public function getDynamicQR()
    {
        $transaction = $this->_getCheckout()->getOplatiTransaction();

        return $transaction['dynamicQR'];
    }

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

}
