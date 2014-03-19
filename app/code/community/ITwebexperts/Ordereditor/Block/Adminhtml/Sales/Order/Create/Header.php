<?php

class ITwebexperts_Ordereditor_Block_Adminhtml_Sales_Order_Create_Header extends Mage_Adminhtml_Block_Sales_Order_Create_Header
{
    protected function _toHtml()
    {
        if ($this->_getSession()->getOrder()->getId()) {
            return '<h3 class="icon-head head-sales-order">'.Mage::helper('sales')->__('Edit Order #%s', $this->_getSession()->getOrder()->getRealIncrement()).'</h3>';
        }

        $customerId = $this->getCustomerId();
        $storeId    = $this->getStoreId();
        $out = '';
        if ($customerId && $storeId) {
            $out.= Mage::helper('sales')->__('Create New Order for %s in %s', $this->getCustomer()->getName(), $this->getStore()->getName());
        }
        elseif (!is_null($customerId) && $storeId){
            $out.= Mage::helper('sales')->__('Create New Order for New Customer in %s', $this->getStore()->getName());
        }
        elseif ($customerId) {
            $out.= Mage::helper('sales')->__('Create New Order for %s', $this->getCustomer()->getName());
        }
        elseif (!is_null($customerId)){
            $out.= Mage::helper('sales')->__('Create New Order for New Customer');
        }
        else {
            $out.= Mage::helper('sales')->__('Create New Order');
        }
        $out = $this->htmlEscape($out);
        $out = '<h3 class="icon-head head-sales-order">' . $out . '</h3>';
        return $out;
    }
}
