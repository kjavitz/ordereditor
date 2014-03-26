<?php
class ITwebexperts_Ordereditor_Adminhtml_DifferenceController extends Mage_Adminhtml_Controller_Action
{

    private $_storeId = '1';
    private $_groupId = '1';
    private $_sendConfirmation = '0';
    private $orderData = array();
    private $_product;
    private $_sourceCustomer;
    private $_sourceOrder;

    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }
    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }
    /**
     * Initialize order creation session data
     *
     * @param array $data
     * @return Mage_Adminhtml_Sales_Order_CreateController
     */
    protected function _initSession($data)
    {
        /* Get/identify customer */
        if (!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int) $data['customer_id']);
        }
        /* Get/identify store */
        if (!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int) $data['store_id']);
        }
        return $this;
    }

	public function getorderAction(){
        //it will create order and open the pay invoice for the order

        ITwebexperts_Payperrentals_Helper_Data::createDifferenceProduct();
        ITwebexperts_Payperrentals_Helper_Data::generateRule('freeShippingDifference',null,0,'1','difference_ppr');
        $productId = Mage::getModel('catalog/product')->loadByAttribute('sku','difference_ppr')->getId();

        //order_id
        $order_id = Mage::app()->getRequest()->getParam('order_id');
        $differenceSum = Mage::app()->getRequest()->getParam('difference');
        $sourceOrder = Mage::getModel('sales/order')->load($order_id);
        $sourceCustomer = Mage::getModel('customer/customer')->load($sourceOrder->getCustomerId());
        $this->_storeId = $sourceOrder->getStoreId();
        $this->_sourceOrder = $sourceOrder;
        $this->_sourceCustomer = $sourceCustomer;
        //You can extract/refactor this if you have more than one product, etc.
        $this->_product = Mage::getModel('catalog/product')
            //->setStoreId($this->_storeId)
            ->load($productId);

        $this->orderData = array(
            'session'       => array(
                'customer_id'   => $this->_sourceCustomer->getId(),
                'store_id'      => $this->_storeId,
            ),
            'payment'       => array(
                'method'    => 'checkmo',
            ),
            'add_products'  =>array(
                $this->_product->getId() => array('qty' => 1,'price' => $differenceSum,'original_price' => $differenceSum,'custom_price' => $differenceSum,'original_custom_price'=>$differenceSum),
            ),
            'order' => array(
                'currency' => 'USD',
                'account' => array(
                    'group_id' => $this->_groupId,
                    'email' => $this->_sourceCustomer->getEmail()
                ),
                'billing_address' => array(
                    //'customer_address_id' => $this->_sourceCustomer->getCustomerAddressId(),
                    'prefix' => '',
                    'firstname' => $this->_sourceOrder->getBillingAddress()->getFirstname(),
                    'middlename' => '',
                    'lastname' => $this->_sourceOrder->getBillingAddress()->getLastname(),
                    'suffix' => '',
                    'company' => '',
                    'street' => $this->_sourceOrder->getBillingAddress()->getStreet(),
                    'city' => $this->_sourceOrder->getBillingAddress()->getCity(),
                    'country_id' => $this->_sourceOrder->getBillingAddress()->getCountryId(),
                    'region' => '',
                    'region_id' => $this->_sourceOrder->getBillingAddress()->getRegionId(),
                    'postcode' => $this->_sourceOrder->getBillingAddress()->getPostcode(),
                    'telephone' => $this->_sourceOrder->getBillingAddress()->getTelephone(),
                    'fax' => '',
                ),
                'shipping_address' => array(
                    //'customer_address_id' => $this->_sourceCustomer->getCustomerAddressId(),
                    'prefix' => '',
                    'firstname' => $this->_sourceOrder->getBillingAddress()->getFirstname(),
                    'middlename' => '',
                    'lastname' => $this->_sourceOrder->getBillingAddress()->getLastname(),
                    'suffix' => '',
                    'company' => '',
                    'street' => $this->_sourceOrder->getBillingAddress()->getStreet(),
                    'city' => $this->_sourceOrder->getBillingAddress()->getCity(),
                    'country_id' => $this->_sourceOrder->getBillingAddress()->getCountryId(),
                    'region' => '',
                    'region_id' => $this->_sourceOrder->getBillingAddress()->getRegionId(),
                    'postcode' => $this->_sourceOrder->getBillingAddress()->getPostcode(),
                    'telephone' => $this->_sourceOrder->getBillingAddress()->getTelephone(),
                    'fax' => '',
                ),
                'shipping_method' => 'flatrate_flatrate',
                'comment' => array(
                    'customer_note' => 'Order adjustment.',
                ),
                'send_confirmation' => $this->_sendConfirmation
            ),
        );
        //print_r($this->orderData);
        //die();
        if (!empty($this->orderData)) {
            $this->_initSession($this->orderData['session']);
            try {
                $this->_processQuote($this->orderData);
                if (!empty($this->orderData['payment'])) {
                    $this->_getOrderCreateModel()->setPaymentData($this->orderData['payment']);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($this->orderData['payment']);
                }
                $item = $this->_getOrderCreateModel()->getQuote()->getItemByProduct($this->_product);
                //$item->setPrice($differenceSum);
                //$item->setOriginalPrice($differenceSum);
                $item->setQty(1);
                $item->setCustomPrice($differenceSum);
                $item->setOriginalCustomPrice($differenceSum);
                $item->setBaseCustomPrice($differenceSum);
                $item->getProduct()->setIsSuperMode(true);
                $this->_getOrderCreateModel()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();

                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
                $_order = $this->_getOrderCreateModel()
                    ->importPostData($this->orderData['order'])
                    ->createOrder();
                $_order->setRealIncrement($this->_sourceOrder->getRealIncrement());
                $_order->setOriginalIncrementId($this->_sourceOrder->getRealIncrement());
                $_order->setEditIncrement($this->_sourceOrder->getEditIncrement()+1);
                $_order->setRelationParentId($this->_sourceOrder->getId());
                $_order->setRelationParentRealId($this->_sourceOrder->getIncrementId());
                $_order->setIsInvoice(1);
                $_order->setIsHidden(1);
                $_order->save();
                $id = $_order->getId();
                $this->_getSession()->clear();
                Mage::unregister('rule_data');
                $this->_redirect('adminhtml/sales_order_edit/start/order_id/'.$id);
                return;
                //return $_order;
            }
            catch (Exception $e){
                Mage::log("Order save error...");
            }
        }
        $this->_redirect('adminhtml/sales_order_edit/start/order_id/'.$id);
        return null;
    }

    protected function _processQuote($data = array())
    {
        /* Saving order data */
        if (!empty($data['order'])) {
            $this->_getOrderCreateModel()->importPostData($data['order']);
        }
        $this->_getOrderCreateModel()->getBillingAddress();
        $this->_getOrderCreateModel()->setShippingAsBilling(true);
        /* Just like adding products from Magento admin grid */
        if (!empty($data['add_products'])) {
            $this->_getOrderCreateModel()->addProducts($data['add_products']);
        }
        /* Collect shipping rates */
        $this->_getOrderCreateModel()->collectShippingRates();
        /* Add payment data */
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        $this->_getOrderCreateModel()
            ->initRuleData()
            ->saveQuote();
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        return $this;
    }

    public function getcreditmemoAction(){
        //it will create creditmemo
        //search for the last invoice with a sum bigger than the credit memo difference->get the order_id for that
        //open create credit memo and fill the adjustment negative field with the sum. and put 0 for all the qtys and push update qtys.
        $order_id = Mage::app()->getRequest()->getParam('order_id');
        $differenceSum = Mage::app()->getRequest()->getParam('difference');

        $sourceOrder = Mage::getModel('sales/order')->load($order_id);

        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('sales/order_invoice_collection');

        $collection->getSelect()->joinLeft(array('sfo'=> $coreResource->getTableName('sales_flat_order')),
            'sfo.entity_id=main_table.order_id',array('sfo.base_grand_total as gd','sfo.order_id as order_id'));

        $collection->getSelect()->where('sfo.real_increment = ?', $sourceOrder->getRealIncrement());
        $collection->getSelect()->where('main_table.base_grand_total >= ?', $differenceSum);
        $collection->getSelect()->order('main_table.created_at DESC');//here I could order by grand_base_total
        foreach($collection as $iCol){
            $oID = $iCol->getOrderId();
            break;
        }
        if($oID){
            $this->_redirect('adminhtml/sales_order_creditmemo/new/order_id/'.$oID.'/justcredit/1/difference/'.$differenceSum);
        }


    }

    protected function createCreditMemo($dif, $info){
        $qtys = array();

        foreach ($dif as $item) {
            if (isset($item['qty'])) {
                $qtys[$item['order_item_id']] = array("qty"=> $item['qty']);
            }
            if (isset($item['back_to_stock'])) {
                $backToStock[$item['order_item_id']] = true;
            }
        }

        $data = array(
            "items" => $qtys,
            "do_offline" => "1",
            "comment_text" => "",
            "shipping_amount" => "0",
            "adjustment_positive" => "0",
            "adjustment_negative" => "0",
        );
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $creditmemo = $this->_initCreditmemo($data, $info);
            if ($creditmemo) {
                if (($creditmemo->getGrandTotal() <=0) && (!$creditmemo->getAllowZeroGrandTotal())) {
                    Mage::throwException(
                        $this->__('Credit memo\'s total must be positive.')
                    );
                }

                $comment = '';
                if (!empty($data['comment_text'])) {
                    $creditmemo->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );
                    if (isset($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                }

                if (isset($data['do_refund'])) {
                    $creditmemo->setRefundRequested(true);
                }
                if (isset($data['do_offline'])) {
                    $creditmemo->setOfflineRequested((bool)(int)$data['do_offline']);
                }

                $creditmemo->register();
                if (!empty($data['send_email'])) {
                    $creditmemo->setEmailSent(true);
                }

                $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $this->_saveCreditmemo($creditmemo);
                $creditmemo->sendEmail(!empty($data['send_email']), $comment);
                echo '<br>The credit memo has been created.';
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
                return;
            } else {
                //$this->_forward('noRoute');
                //return;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save the credit memo.'));
        }
    }

    /**
     *
     * @param type $data contains products info to refund
     * @param type $info array("order_increment_id" => $order->getIncrementId(), "invoice_id" => $invoiceId);
     * @param type $update
     * @return boolean
     */
    protected function _initCreditmemo($data, $info, $update = false)
    {
        $creditmemo = false;
        $invoice=false;
        $creditmemoId = null;//$this->getRequest()->getParam('creditmemo_id');
        $orderId = $info['order_increment_id'];//$this->getRequest()->getParam('order_id');
        $invoiceId = $data['invoice_id'];
        echo "<br>abans if. OrderId: ".$orderId;
        if ($creditmemoId) {
            $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
        } elseif ($orderId) {
            $order  = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($invoiceId) {
                $invoice = Mage::getModel('sales/order_invoice')
                    ->load($invoiceId)
                    ->setOrder($order);
                echo '<br>loaded_invoice_number: '.$invoice->getId();
            }

            if (!$order->canCreditmemo()) {
                echo '<br>cannot create credit memo';
                if(!$order->isPaymentReview())
                {
                    echo '<br>cannot credit memo Payment is in review';
                }
                if(!$order->canUnhold())
                {
                    echo '<br>cannot credit memo Order is on hold';
                }
                if(abs($order->getTotalPaid()-$order->getTotalRefunded())<.0001)
                {
                    echo '<br>cannot credit memo Amount Paid is equal or less than amount refunded';
                }
                if($order->getActionFlag('edit') === false)
                {
                    echo '<br>cannot credit memo Action Flag of Edit not set';
                }
                if ($order->hasForcedCanCreditmemo()) {
                    echo '<br>cannot credit memo Can Credit Memo has been forced set';
                }
                return false;
            }

            $savedData = array();
            if (isset($data['items'])) {
                $savedData = $data['items'];
            } else {
                $savedData = array();
            }

            $qtys = array();
            $backToStock = array();
            foreach ($savedData as $orderItemId =>$itemData) {
                if (isset($itemData['qty'])) {
                    $qtys[$orderItemId] = $itemData['qty'];
                }
                if (isset($itemData['back_to_stock'])) {
                    $backToStock[$orderItemId] = true;
                }
            }
            $data['qtys'] = $qtys;

            $service = Mage::getModel('sales/service_order', $order);
            if ($invoice) {
                $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
            } else {
                $creditmemo = $service->prepareCreditmemo($data);
            }

            /**
             * Process back to stock flags
             */
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $parentId = $orderItem->getParentItemId();
                if (isset($backToStock[$orderItem->getId()])) {
                    $creditmemoItem->setBackToStock(true);
                } elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                    $creditmemoItem->setBackToStock(true);
                } elseif (empty($savedData)) {
                    $creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
                } else {
                    $creditmemoItem->setBackToStock(false);
                }
            }
        }

        return $creditmemo;
    }

    /**
     * Save creditmemo and related order, invoice in one transaction
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     */
    protected function _saveCreditmemo($creditmemo)
    {
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();

        return $this;
    }
}
?>