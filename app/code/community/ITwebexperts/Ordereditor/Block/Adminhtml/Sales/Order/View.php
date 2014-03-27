<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order view
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ITwebexperts_Ordereditor_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{

    public function __construct()
    {

        parent::__construct();
        $order = $this->getOrder();

        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_invoice_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd'));

        $collection->getSelect()->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

        $sumInvoice = 0;
        foreach($collection as $iCol){
            $sumInvoice += $iCol->getBaseGrandTotal();
        }

        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_creditmemo_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd' ));

        $collection->getSelect()->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

        $sumCredit = 0;
        foreach($collection as $iCol){
            $sumCredit += $iCol->getBaseGrandTotal();
        }
        $sum = $sumInvoice - $sumCredit;

        if($order->getIsInvoice() && $order->getIsInvoice() == 1 || $order->getIsHidden() && $order->getIsHidden() == 1){
            $this->removeButton('order_edit');
            $this->removeButton('order_cancel');
            $this->removeButton('send_notification');
            $this->removeButton('order_creditmemo');
            $this->removeButton('void_payment');
            $this->removeButton('order_hold');
            $this->removeButton('order_unhold');
            $this->removeButton('accept_payment');
            $this->removeButton('deny_payment');

            $this->removeButton('order_ship');
            $this->removeButton('order_reorder');

        }else{
            $onclickJs = 'deleteConfirm(\''
                . Mage::helper('sales')->__('Are you sure you want to edit this order?')
                . '\', \'' . $this->getEditUrl() . '\');';
            $this->_updateButton('order_edit', 'onclick',
                $onclickJs
            );
        }

        if(!($sum  == 0 || ($order->getIsInvoice() && $order->getIsInvoice() == 1))){
            $this->removeButton('order_invoice');
        }


        //here I add a new button to pay the difference between orders

        //a different solution could be to see the products which
        //weren't invoiced and invoice them, also the removed products should be added to a credit memo,
        //and only the difference resulted from changing dates on a specific product which was already invoiced
        //should be added to invoice as a different product

        //for now I will use a product for the whole difference without taking into account the invoiced products
        //it will create an order and the button will open it for invoice or credit memo.
        //the problem with a credit memo is there has to exist an order already invoiced, and for the credit memo to
        //make a real transaction needs to be done on the invoiced order with products from there...
        //so the solution is to make the credit memos always be offline



        if($sum - $order->getBaseGrandTotal() < 0){
            $label = Mage::helper('sales')->__('Invoice Difference: '). Mage::helper('core')->currency($order->getBaseGrandTotal() - $sum);
        }else{
            $label = Mage::helper('sales')->__('Credit Memo Difference: '). Mage::helper('core')->currency($sum - $order->getBaseGrandTotal());
        }
        //show hide button base on getisinvoice or not
        if((!$order->getIsInvoice() || $order->getIsInvoice() == 0) && $sum > 0 && $sum - $order->getBaseGrandTotal() != 0){
            $this->_addButton('order_difference', array(
                'label'     => $label,
                'onclick'   => 'setLocation(\'' . $this->getDifferenceUrl() . '\')',
            ));
        }

    }

    public function getHeaderText()
    {
        if ($_extOrderId = $this->getOrder()->getExtOrderId()) {
            $_extOrderId = '[' . $_extOrderId . '] ';
        } else {
            $_extOrderId = '';
        }
        if((!$this->getOrder()->getIsInvoice() || $this->getOrder()->getIsInvoice() == 0)){
            return Mage::helper('sales')->__('Order # %s %s | %s', $this->getOrder()->getRealIncrement(), $_extOrderId, $this->formatDate($this->getOrder()->getCreatedAtDate(), 'medium', true));
        }else{
            //get original order
            //$id = $this->getOrder()->getRelationParentId();
            //or <a href="%s">Go to original order</a>
            //for getting the original order i need to get the collection.
            //Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $id)
            return Mage::helper('sales')->__('Adjustment For Order # %s %s | %s.You can only Invoice', $this->getOrder()->getRealIncrement(), $_extOrderId, $this->formatDate($this->getOrder()->getCreatedAtDate(), 'medium', true));
        }

    }


    public function getDifferenceUrl()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_invoice_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd'));

        $collection->getSelect()->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

        $sumInvoice = 0;
        foreach($collection as $iCol){
            $sumInvoice += $iCol->getBaseGrandTotal();
        }

        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_creditmemo_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd'));

        $collection->getSelect()->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

        $sumCredit = 0;
        foreach($collection as $iCol){
            $sumCredit += $iCol->getBaseGrandTotal();
        }
        $sum = $sumInvoice - $sumCredit;

        if($sum > 0){
            if($sum - $this->getOrder()->getBaseGrandTotal() < 0){
                return $this->getUrl('ordereditor_admin/adminhtml_difference/getorder/difference/'.(abs($sum - $this->getOrder()->getBaseGrandTotal())));
            }else{
                return $this->getUrl('ordereditor_admin/adminhtml_difference/getcreditmemo/difference/'.(abs($sum - $this->getOrder()->getBaseGrandTotal())));
            }
        }else{
            return $this->getInvoiceUrl();
        }
    }
}
