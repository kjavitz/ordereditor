<?php

class ITwebexperts_Ordereditor_Block_Adminhtml_Sales_Order_View_Tab_Infohistory
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('order_historyppr');
        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_collection';
    }

    protected function _prepareCollection()
    {
        /* $collection = Mage::getResourceModel($this->_getCollectionClass());
         $this->setCollection($collection);
         return parent::_prepareCollection();*/
        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        /*$collection = Mage::getResourceModel($this->_getCollectionClass())
                ->join(
                    'sales/order_item',
                    '`sales/order_item`.order_id=`main_table`.entity_id',
                    array(
                        'skus' => new Zend_Db_Expr('group_concat(`sales/order_item`.sku SEPARATOR ",")'),
                    )
                );

        $collection->getSelect()->group('main_table.entity_id');*/
        //if($this->getOrder()->getIsHidden() == '0'){
            $collection->getSelect()->joinLeft(array('sfog' => $coreResource->getTableName('sales_flat_order_grid')),
                'main_table.entity_id = sfog.entity_id',array('sfog.shipping_name','sfog.billing_name'));

            $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
                'sfo.entity_id=main_table.entity_id',array('sfo.customer_email','sfo.weight',
                    'sfo.discount_description','sfo.increment_id','sfo.store_id','sfo.created_at','sfo.status',
                    'sfo.base_grand_total','sfo.grand_total','sfo.start_datetime','sfo.end_datetime'));

            $collection->getSelect()->joinLeft(array('sfoa'=>$coreResource->getTableName('sales_flat_order_address')),
                'main_table.entity_id = sfoa.parent_id AND sfoa.address_type="shipping"',array('sfoa.street',
                    'sfoa.city','sfoa.region','sfoa.postcode','sfoa.telephone'));

            $collection->getSelect()->where('sfo.is_hidden = ?','1')
                ->where('sfo.real_increment = ?', $this->getOrder()->getRealIncrement());

            $collection->getSelect()->where('sfo.is_invoice = 0 OR sfo.is_invoice is null');
        //}
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'filter_index' => 'sfo.created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
            'filter_index' => 'sfog.billing_name',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
            'filter_index' => 'sfog.shipping_name',
        ));

        $this->addColumn('base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'filter_index' => 'sfo.base_grand_total',
            'type'  => 'currency',
            'currency' => 'base_currency_code',
        ));

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'filter_index' => 'sfo.grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'filter_index' => 'sfo.status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        $this->addColumnAfter('start_datetime', array(
            'header' => Mage::helper('payperrentals')->__('Start Date'),
            'index' => 'start_datetime',
            'filter_index' => 'sfo.start_datetime',
            'renderer'  => new ITwebexperts_Payperrentals_Block_Adminhtml_Html_Renderer_Datetime(),
            'type'  => 'datetime'
        ),'created_at');
        $this->addColumnAfter('end_datetime', array(
            'header' => Mage::helper('payperrentals')->__('End Date'),
            'index' => 'end_datetime',
            'filter_index' => 'sfo.end_datetime',
            'renderer'  => new ITwebexperts_Payperrentals_Block_Adminhtml_Html_Renderer_Datetime(),
            'type'  => 'datetime'
        ),'start_datetime');

        $this->addColumnAfter('send_datetime', array(
            'header' => Mage::helper('payperrentals')->__('Dropoff Date'),
            'index' => 'send_datetime',
            'filter_index' => 'sfo.send_datetime',
            'renderer'  => new ITwebexperts_Payperrentals_Block_Adminhtml_Html_Renderer_Datetime(),
            'type'  => 'datetime'
        ),'end_datetime');
        $this->addColumnAfter('return_datetime', array(
            'header' => Mage::helper('payperrentals')->__('Pickup Date'),
            'index' => 'return_datetime',
            'filter_index' => 'sfo.return_datetime',
            'renderer'  => new ITwebexperts_Payperrentals_Block_Adminhtml_Html_Renderer_Datetime(),
            'type'  => 'datetime'
        ),'send_datetime');

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('sales')->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'     => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('sales')->__('View'),
                            'url'     => array('base'=>'*/sales_order/view'),
                            'field'   => 'order_id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
                ));
        }
        $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));
        $this->addExportType('payperrentals_admin/adminhtml_salesgrid/exportIcal', Mage::helper('payperrentals')->__('iCal'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/cancel')) {
            $this->getMassactionBlock()->addItem('cancel_order', array(
                'label'=> Mage::helper('sales')->__('Cancel'),
                'url'  => $this->getUrl('*/sales_order/massCancel'),
            ));
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/hold')) {
            $this->getMassactionBlock()->addItem('hold_order', array(
                'label'=> Mage::helper('sales')->__('Hold'),
                'url'  => $this->getUrl('*/sales_order/massHold'),
            ));
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/unhold')) {
            $this->getMassactionBlock()->addItem('unhold_order', array(
                'label'=> Mage::helper('sales')->__('Unhold'),
                'url'  => $this->getUrl('*/sales_order/massUnhold'),
            ));
        }

        $this->getMassactionBlock()->addItem('pdfinvoices_order', array(
            'label'=> Mage::helper('sales')->__('Print Invoices'),
            'url'  => $this->getUrl('*/sales_order/pdfinvoices'),
        ));

        $this->getMassactionBlock()->addItem('pdfshipments_order', array(
            'label'=> Mage::helper('sales')->__('Print Packingslips'),
            'url'  => $this->getUrl('*/sales_order/pdfshipments'),
        ));

        $this->getMassactionBlock()->addItem('pdfcreditmemos_order', array(
            'label'=> Mage::helper('sales')->__('Print Credit Memos'),
            'url'  => $this->getUrl('*/sales_order/pdfcreditmemos'),
        ));

        $this->getMassactionBlock()->addItem('pdfdocs_order', array(
            'label'=> Mage::helper('sales')->__('Print All'),
            'url'  => $this->getUrl('*/sales_order/pdfdocs'),
        ));

        $this->getMassactionBlock()->addItem('print_shipping_label', array(
            'label'=> Mage::helper('sales')->__('Print Shipping Labels'),
            'url'  => $this->getUrl('*/sales_order_shipment/massPrintShippingLabel'),
        ));

        $this->getMassactionBlock()->addItem('delete_order_completely', array(
            'label'=> Mage::helper('sales')->__('Delete Order Completely'),
            'url'  => $this->getUrl('payperrentals/adminhtml_sales_order_create/massDelete'),//
        ));
//
        return $this;
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
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


    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Order History');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Order History');
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
