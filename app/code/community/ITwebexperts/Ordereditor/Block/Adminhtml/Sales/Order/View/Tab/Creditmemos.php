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
 * Order Credit Memos grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Sales_Order_View_Tab_Creditmemos
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('order_creditmemos');
        $this->setUseAjax(true);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/order_creditmemo_collection';
    }

    protected function _prepareCollection()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel($this->_getCollectionClass());

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd'));

        $collection->getSelect()->joinLeft(array('sfog' => $coreResource->getTableName('sales_flat_creditmemo_grid')),
            'main_table.entity_id = sfog.entity_id',array('sfog.*'));

        $collection->getSelect()->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }


    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header' => Mage::helper('sales')->__('Credit Memo #'),
            'width' => '120px',
            'index' => 'increment_id',
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Created At'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('state', array(
            'header'    => Mage::helper('sales')->__('Status'),
            'index'     => 'state',
            'type'      => 'options',
            'options'   => Mage::getModel('sales/order_creditmemo')->getStates(),
        ));

        $this->addColumn('base_grand_total', array(
            'header'    => Mage::helper('customer')->__('Refunded'),
            'index'     => 'base_grand_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/sales_order_creditmemo/view',
            array(
                'creditmemo_id'=> $row->getId(),
                'order_id'  => $row->getOrderId()
             ));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/creditmemos', array('_current' => true));
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Credit Memos');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Order Credit Memos');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
