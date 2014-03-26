<?php
class ITwebexperts_Ordereditor_Adminhtml_DifferenceController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Method use to transform in order for invoicing the difference sum and redirect to the created order
     * It will use the convert order to quote and quote to order, removing from quote all the items and
     * adding a product with custom price. Also it will generate a rule to not add shipping for this product
     *
     *@return null
     */

    public function getOrderAction(){
        //it will create order and open the pay invoice for the order

        ITwebexperts_Ordereditor_Helper_Data::createDifferenceProduct();
        ITwebexperts_Ordereditor_Helper_Data::generateRule('freeShippingDifference',null,0,'1','difference_ppr');

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', 'difference_ppr');
        $productId = $product->getId();

        $order_id = Mage::app()->getRequest()->getParam('order_id');
        $differenceSum = Mage::app()->getRequest()->getParam('difference');

        /** @var $sourceOrder Mage_Sales_Model_Order */
        $sourceOrder = Mage::getModel('sales/order')->load($order_id);

        /** @var $sourceCustomer Mage_Customer_Model_Customer */
        $sourceCustomer = Mage::getModel('customer/customer')->load($sourceOrder->getCustomerId());

        /** @var $converterOrder Mage_Sales_Model_Convert_Order */
        $converterOrder = Mage::getModel('sales/convert_order');

        /** @var $converterQuote Mage_Sales_Model_Convert_Quote */
        $converterQuote = Mage::getModel('sales/convert_quote');


        /** @var $quote Mage_Sales_Model_Quote */
        $quote = $converterOrder->toQuote($sourceOrder);
        $orderShippingAddress = $converterOrder->addressToQuoteAddress($sourceOrder->getShippingAddress());
        $orderBillingAddress = $converterOrder->addressToQuoteAddress($sourceOrder->getBillingAddress());
        $orderPayment = $converterOrder->paymentToQuotePayment($sourceOrder->getPayment());
        $quote->setShippingAddress($orderShippingAddress);
        $quote->setBillingAddress($orderBillingAddress);
        $quote->setPayment($orderPayment);

        $quote->removeAllItems();
        $buyRequestArray = array('qty' => 1, 'custom_price' => $differenceSum, 'original_custom_price'=> $differenceSum);
        $buyRequest = new Varien_Object();
        $buyRequest->setData($buyRequestArray);
        $product->setIsSuperMode(true);
        $quote->setIsSuperMode(true);
        $quote->addProduct($product, $buyRequest);



        $data = array(
            'method' => 'checkmo',
//            'cc_type' => 'VI',
//            'cc_number' => '4111111111111111',
//            'cc_exp_month' => '1',
//            'cc_exp_year' => (date('Y') + 6),
//            'cc_cid' => '444'
        );

        $quote->getShippingAddress()->setPaymentMethod('checkmo');
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $payment = $quote->getPayment();
        $payment->importData($data);
        $quote->save();

        try {
            /** @var $item Mage_Sales_Model_Quote_Item */
            foreach ($quote->getItemsCollection() as $item) {
                $item->setQty(1);
                $item->setCustomPrice($differenceSum);
                $item->setOriginalCustomPrice($differenceSum);
                $item->getProduct()->setIsSuperMode(true);
            }
            $quote->setTotalsCollectedFlag(false)->collectTotals();

            $emailEnabled = Mage::app()->getStore()->getConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED);
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, '0');

            /** @var $order Mage_Sales_Model_Order */
            $order = $converterQuote->toOrder($quote);
            //$order->addressToOrder($quote->getAddress(),$order);
            $quoteBillingAddress = $converterQuote->addressToOrderAddress($quote->getBillingAddress());
            $quoteShippingAddress = $converterQuote->addressToOrderAddress($quote->getShippingAddress());
            $order->setBillingAddress($quoteBillingAddress);
            $order->setShippingAddress($quoteShippingAddress);

            foreach ($quote->getAllItems() as $item) {
                $orderItem = $converterQuote->itemToOrderItem($item);
                if ($item->getParentItem()) {
                    $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                }
                $order->addItem($orderItem);
            }

            $payment = $converterQuote->paymentToOrderPayment($quote->getPayment());

            $order->setPayment($payment);

            $message = '[Notice] - Order converted from quote manually';
            $order->addStatusToHistory($order->getStatus(), $message);
            $order->place();

            $order->save();
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, $emailEnabled);

            $order->setRealIncrement($sourceOrder->getRealIncrement());
            $order->setOriginalIncrementId($sourceOrder->getRealIncrement());
            $order->setEditIncrement($sourceOrder->getEditIncrement() + 1);
            $order->setRelationParentId($sourceOrder->getId());
            $order->setRelationParentRealId($sourceOrder->getIncrementId());
            $order->setIsInvoice(1);
            $order->setIsHidden(1);
            $order->save();
            $id = $order->getId();

            Mage::unregister('rule_data');
            $this->_redirect('adminhtml/sales_order_edit/start/order_id/' . $id);

            return;
            //return $_order;
        } catch (Exception $e) {
            Mage::log("Order save error...");
        }

        return null;
    }


    /**
     * Method used to redirect to credit memo creator
     */
    public function getCreditmemoAction(){
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
}
?>