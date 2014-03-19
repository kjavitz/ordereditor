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
 * Adminhtml order totals block
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ITwebexperts_Ordereditor_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals//Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_invoice_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id', array('sfo.base_grand_total as gd'));

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
        $sum = $this->getSource()->getGrandTotal() - ($sumInvoice - $sumCredit);
        $this->_totals['paid'] = new Varien_Object(array(
            'code'      => 'paid',
            'strong'    => true,
            'value'     => $sumInvoice/*$this->getSource()->getTotalPaid()*/,
            'base_value'=> $sumInvoice/*$this->getSource()->getBaseTotalPaid()*/,
            'label'     => $this->helper('sales')->__('Total Paid'),
            'area'      => 'footer'
        ));
        $this->_totals['refunded'] = new Varien_Object(array(
            'code'      => 'refunded',
            'strong'    => true,
            'value'     => $sumCredit/*$this->getSource()->getTotalRefunded()*/,
            'base_value'=> $sumCredit/*$this->getSource()->getBaseTotalRefunded()*/,
            'label'     => $this->helper('sales')->__('Total Refunded'),
            'area'      => 'footer'
        ));
        $this->_totals['due'] = new Varien_Object(array(
            'code'      => 'due',
            'strong'    => true,
            'value'     => $sum/*$this->getSource()->getTotalDue()*/,
            'base_value'=> $sum/*$this->getSource()->getBaseTotalDue()*/,
            'label'     => $this->helper('sales')->__('Total Due'),
            'area'      => 'footer'
        ));
        return $this;
    }
}
