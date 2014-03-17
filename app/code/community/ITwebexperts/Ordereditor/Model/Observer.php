<?php

class ITwebexperts_Ordereditor_Model_Observer {

    protected $_session;

    public function __construct() {
        $this->_session = Mage::getSingleton('customer/session');
    }

    public function getSession() {
        return $this->_session;
    }
    public function saveOrderBefore($observer){
            if(!$observer->getEvent()->getOrder()->getRealIncrement()){
                $observer->getEvent()->getOrder()->setRealIncrement($observer->getEvent()->getOrder()->getIncrementId());
            }
    }

    public function creditMemoBefore($observer)
    {
        $creditMemo = $observer->getCreditmemo();
        $request = $observer->getRequest();
        if($request->getParam('justcredit') && $request->getParam('justcredit') == 1){
            $difference = $request->getParam('difference');
            $total = 0;
            foreach ($creditMemo->getAllItems() as $creditmemoItem) {
                //print_r($creditmemoItem->debug());
                $creditmemoItem->setQty(0);
                $total +=$creditmemoItem->getBaseRowTotal();
            }
            $total += $creditMemo->getBaseShippingAmount();
            //echo $total;
            //die();
            $creditMemo->setBaseShippingAmount(0);
            $creditMemo->setBaseAdjustmentPositive($difference);
            $creditMemo->setShippingAmount(0);
            $creditMemo->setAdjustmentPositive($difference);
            $creditMemo->setBaseAdjustmentNegative($total);
            $creditMemo->setAdjustmentNegative($total);
            //$creditMemo->setBaseGrandTotal($to)

            //$request->setParam('justcredit',0);

            //$this->_initCreditmemo($request, true);
            $creditMemo->collectTotals();
            //$creditMemo->register();
        }
    }

}
?>
