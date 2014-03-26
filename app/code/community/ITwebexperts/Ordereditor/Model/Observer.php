<?php

class ITwebexperts_Ordereditor_Model_Observer {

    /**
     * Set real increment for order
     * @param Varien_Event_Observer $observer
     */
    public function saveOrderBefore(Varien_Event_Observer $observer){
            if(!$observer->getEvent()->getOrder()->getRealIncrement()){
                $observer->getEvent()->getOrder()->setRealIncrement($observer->getEvent()->getOrder()->getIncrementId());
            }
    }

    /**
     * Set real increment for invoice
     * @param Varien_Event_Observer $observer
     */
    public function saveOrderInvoiceBefore(Varien_Event_Observer $observer){
        if(!$observer->getEvent()->getInvoice()->getRealIncrement() && $observer->getEvent()->getInvoice()->getOrder()->getRealIncrement()){
            $observer->getEvent()->getInvoice()->setRealIncrement($observer->getEvent()->getInvoice()->getOrder()->getRealIncrement());
        }
        if(!is_null($observer->getEvent()->getInvoice()->getOrder()->getRelationParentId())){
            $observer->getEvent()->getInvoice()->setEditOrderId($observer->getEvent()->getInvoice()->getOrder()->getRelationParentId());
        }
    }

    /**
     * Set real increment for creditmemo
     * @param Varien_Event_Observer $observer
     */
    public function saveOrderCreditmemoBefore(Varien_Event_Observer $observer){
        if(!$observer->getEvent()->getOrderCreditmemo()->getRealIncrement() && $observer->getEvent()->getOrder()->getRealIncrement()){
            $observer->getEvent()->getOrderCreditmemo()->setRealIncrement($observer->getEvent()->getOrder()->getRealIncrement());
        }
        if(!is_null($observer->getEvent()->getOrder()->getRelationParentId())){
            $observer->getEvent()->getOrderCreditmemo()->setEditOrderId($observer->getEvent()->getOrder()->getRelationParentId());
        }
    }

    /**
     * Set real increment for shipment
     * @param Varien_Event_Observer $observer
     */
    public function saveOrderShipmentBefore(Varien_Event_Observer $observer){
        if(!$observer->getEvent()->getOrderShipment()->getRealIncrement() && $observer->getEvent()->getOrder()->getRealIncrement()){
            $observer->getEvent()->getOrderShipment()->setRealIncrement($observer->getEvent()->getOrder()->getRealIncrement());
        }
        if(!is_null($observer->getEvent()->getOrder()->getRelationParentId())){
            $observer->getEvent()->getOrderShipment()->setEditOrderId($observer->getEvent()->getOrder()->getRelationParentId());
        }
    }

    /**
     * Before Loading order grid don't show is_hidden and is_invoice orders
     * @param Varien_Event_Observer $_observer
     */
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

    /**
     *
     * @param Varien_Event_Observer $_observer
     */
    public function salesInvoiceGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderInvoiceGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_invoice_grid_collection') : $collection;

        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->orWhere('main_table.order_id is not null');
            $collection->getSelect()->where('main_table.real_increment = ?', $order->getRealIncrement());
        }

    }


    public function salesCreditmemoGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderCreditmemoGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_creditmemo_grid_collection') : $collection;

        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->orWhere('main_table.order_id is not null');
            $collection->getSelect()->where('main_table.real_increment = ?', $order->getRealIncrement());
        }
    }


    public function salesShipmentGridCollectionLoadBefore(Varien_Event_Observer $_observer)
    {
        $collection = $_observer->getEvent()->getOrderShipmentGridCollection();
        $collection = (!$collection) ? Mage::getResourceModel('sales/order_shipment_grid_collection') : $collection;

        if(Mage::app()->getRequest()->getParam('order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            $collection->getSelect()->orWhere('main_table.order_id is not null');
            $collection->getSelect()->where('main_table.real_increment = ?', $order->getRealIncrement());
        }

    }

    /**
     * Apply some needed changes to grid blocks before their HTML output
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeBlockToHtml(Varien_Event_Observer $observer)
    {
        $grid = $observer->getEvent()->getBlock();
        if(Mage::app()->getRequest()->getParam('order_id') && (Mage::app()->getRequest()->getActionName() == 'view') && Mage::app()->getRequest()->getControllerName() == 'sales_order'){
            $realIncrement = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'))->getRealIncrement();
            Mage::app()->getLayout()->getBlock('head')->setTitle('Order #'.$realIncrement);
        }
        if ($grid && $grid->getId() == 'sales_order_grid') {
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
        if ($grid && $grid->getId() == 'sales_invoice_grid') {
            $grid->removeColumn('order_increment_id');
            $grid->addColumnAfter('order_increment_id', array(
                    'header'=> Mage::helper('ordereditor')->__('Order #'),
                    'width' => '80px',
                    'type'  => 'text',
                    'index' => 'real_increment'
                ), 'massaction');
            $grid->sortColumnsByOrder();
            // $grid->addColumnsOrder('real_order_id','created_at');
        }
        if ($grid && $grid->getId() == 'sales_creditmemo_grid') {
            $grid->removeColumn('order_increment_id');
            $grid->addColumnAfter('order_increment_id', array(
                    'header'=> Mage::helper('ordereditor')->__('Order #'),
                    'width' => '80px',
                    'type'  => 'text',
                    'index' => 'real_increment'
                ), 'massaction');
            $grid->sortColumnsByOrder();
            // $grid->addColumnsOrder('real_order_id','created_at');
        }
        if ($grid && $grid->getId() == 'sales_shipment_grid') {
            $grid->removeColumn('order_increment_id');
            $grid->addColumnAfter('order_increment_id', array(
                    'header'=> Mage::helper('ordereditor')->__('Order #'),
                    'width' => '80px',
                    'type'  => 'text',
                    'index' => 'real_increment'
                ), 'massaction');
            $grid->sortColumnsByOrder();
            // $grid->addColumnsOrder('real_order_id','created_at');
        }
    }

    /**
     * Create creditmemo for difference
     * @param Varien_Event_Observer $observer
     */
    public function creditMemoBefore(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getCreditmemo();
        $request = $observer->getRequest();
        if($request->getParam('justcredit') && $request->getParam('justcredit') == 1){
            $difference = $request->getParam('difference');
            $total = 0;
            foreach ($creditMemo->getAllItems() as $creditmemoItem) {
                $creditmemoItem->setQty(0);
                $total +=$creditmemoItem->getBaseRowTotal();
            }
            $total += $creditMemo->getBaseShippingAmount();
            $creditMemo->setBaseShippingAmount(0);
            $creditMemo->setBaseAdjustmentPositive($difference);
            $creditMemo->setShippingAmount(0);
            $creditMemo->setAdjustmentPositive($difference);
            $creditMemo->setBaseAdjustmentNegative($total);
            $creditMemo->setAdjustmentNegative($total);
            $creditMemo->collectTotals();
        }
    }

    /**
     * Fixes custom price subtotal for admin
     * @param Varien_Event_Observer $observer
     */
    public function checkoutCartProductAddAfter(Varien_Event_Observer $observer)
    {
        $buyInfo = $observer->getQuoteItem()->getBuyRequest();
        if ($customPrice = $buyInfo->getCustomPrice())
        {
            $observer->getQuoteItem()->setCustomPrice($customPrice)
                ->setOriginalCustomPrice($customPrice);
        }

    }


}
?>
