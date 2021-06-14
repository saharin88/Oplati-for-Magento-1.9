<?php

class Oplati_Oplati_Model_System_Config_Source_Order_Status extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{

    protected $_stateStatuses
        = array(
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_CANCELED,
            Mage_Sales_Model_Order::STATE_HOLDED,
            Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
        );

}