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

    public function salesOrderGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_grid_collection') : $collection;
        $coreResource = Mage::getSingleton('core/resource');
        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.entity_id', array('sfo.is_hidden', 'sfo.is_invoice'));
        $collection->getSelect()->where('sfo.is_hidden = 0 OR sfo.is_hidden is null');
        $collection->getSelect()->where('sfo.is_invoice = 0 OR sfo.is_invoice is null');
    }

    public function salesInvoiceGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderInvoiceGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_invoice_grid_collection') : $collection;

        $coreResource = Mage::getSingleton('core/resource');
        $collection->getSelect()->joinInner(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id', array('sfo.real_increment'));
        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->where('sfo.real_increment = ?', $order->getRealIncrement());
        }

    }


    public function salesCreditmemoGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderCreditmemoGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_creditmemo_grid_collection') : $collection;

        $coreResource = Mage::getSingleton('core/resource');
        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id', array('sfo.real_increment'));
        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->where('sfo.real_increment = ?', $order->getRealIncrement());
        }
    }


    public function salesShipmentGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderShipmentGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_shipment_grid_collection') : $collection;

        $coreResource = Mage::getSingleton('core/resource');
        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id', array('sfo.real_increment'));
        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->where('sfo.real_increment = ?', $order->getRealIncrement());
        }

    }


    /**
     * Apply some needed changes to grid blocks before their HTML output
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeBlockToHtml($observer)
    {
        if (($grid = $observer->getEvent()->getBlock())
        && ($grid instanceof Mage_Adminhtml_Block_Sales_Order_Grid) && $grid->getId() == 'sales_order_grid') {
            $grid->removeColumn('real_order_id');
            $grid->addColumnAfter('real_order_id', array(
                    'header'=> Mage::helper('ordereditor')->__('Order #'),
                    'width' => '80px',
                    'type'  => 'text',
                    'index' => 'real_increment'
                ), 'massaction');
            $grid->sortColumnsByOrder();
           // $grid->addColumnsOrder('real_order_id','created_at');
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
