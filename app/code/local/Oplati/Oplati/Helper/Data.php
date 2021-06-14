<?php

class Oplati_Oplati_Helper_Data extends Mage_Core_Helper_Abstract
{


    protected $transaction;

    /**
     * @param   Mage_Sales_Model_Order  $order
     *
     * @throws Exception
     */
    public function createOplatiTransaction($order)
    {
        $this->transaction = $this->request('pos/webPayments', $this->prepareData($order));
        if (empty($this->transaction['paymentId'])) {
            throw new Exception((empty($this->transaction['devMessage']) ? 'Error create transaction' : $this->transaction['devMessage']));
        }
    }

    /**
     * @param   int  $paymentId
     *
     * @return string
     * @throws Exception
     */
    public function getOplatiTransactionStatus($paymentId)
    {
        $this->transaction = $this->request('pos/payments/'.$paymentId);
        if (isset($this->transaction['status']) === false) {
            throw new Exception((empty($this->transaction['devMessage']) ? 'Error get payment status' : $this->transaction['devMessage']));
        }

        return $this->transaction['status'];
    }


    /**
     * @return array
     */
    public function getOplatiTransaction()
    {
        return $this->transaction;
    }


    /**
     * @param   Mage_Sales_Model_Order  $order
     *
     * @return array
     */
    protected function prepareData($order)
    {
        $data = [
            'sum'         => $order->getGrandTotal(),
            'shift'       => 'smena 1',
            'orderNumber' => $order->getIncrementId(),
            'regNum'      => Mage::getStoreConfig('payment/oplati/regnum'),
            'details'     => [
                'amountTotal' => $order->getGrandTotal(),
                'items'       => []
            ],
            'successUrl'  => '',
            'failureUrl'  => ''
        ];

        $items = $order->getAllVisibleItems();

        foreach ($items as $i => $item) {
            $data['details']['items'][] = [
                'type'     => 1,
                'name'     => $item->getName(),
                'price'    => $item->getPrice(),
                'quantity' => $item->getQtyOrdered(),
                'cost'     => $item->getRowTotal(),
            ];
        }

        if ($order->getShippingAmount() > 0) {
            $data['details']['items'][] = [
                'type' => 2,
                'name' => $order->getShippingDescription(),
                'cost' => $order->getShippingAmount(),
            ];
        }

        return $data;
    }

    protected function request($url, $data = array(), $method = Zend_Http_Client::GET)
    {
        $client = new Varien_Http_Client();
        $client->setUri($this->getServer().$url)
            ->setHeaders('regNum', Mage::getStoreConfig('payment/oplati/regnum'))
            ->setHeaders('password', Mage::getStoreConfig('payment/oplati/password'));
        if ( ! empty($data)) {
            $client->setRawData(json_encode($data), 'application/json');
            $method = Zend_Http_Client::POST;
        }
        $request = $client->request($method);

        return json_decode($request->getRawBody(), true);
    }

    protected function getServer()
    {
        if (Mage::getStoreConfig('payment/oplati/test') === '1') {
            return 'https://bpay-testcashdesk.lwo.by/ms-pay/';
        } else {
            return 'https://cashboxapi.o-plati.by/ms-pay/';
        }
    }

}