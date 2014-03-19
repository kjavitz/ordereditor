<?php

require_once('Mage/Adminhtml/controllers/Sales/Order/CreateController.php');

class ITwebexperts_Ordereditor_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController
{
    /**
     * Saving quote and create order
     */
    public function saveAction()
    {
        try {
            $this->_processActionData('save');
            $paymentData = $this->getRequest()->getPost('payment');
            if ($paymentData) {
                $paymentData['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_INTERNAL
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }

            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'))
                ->createOrder();

            $this->_getSession()->clear();

            $orderPrev = Mage::getModel('sales/order')->load($order->getRelationParentId());
            if(!is_object($orderPrev) || is_null($order->getRelationParentId())){
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
                if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                    $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
                } else {
                    $this->_redirect('*/sales_order/index');
                }
                return ;
            }
            $hasInvoices = $orderPrev->hasInvoices();

            if ($order->canInvoice() && $hasInvoices) {
                try {
                    $invoiceId = Mage::getModel('sales/order_invoice_api')
                        ->create($order->getIncrementId(), array());
                    $invoice = Mage::getModel('sales/order_invoice')
                        ->loadByIncrementId($invoiceId)
                        ->setOrder($order);
                    $capture_case = 'offline';
                    $invoice->setRequestedCaptureCase($capture_case)->setCanVoidFlag(false)->pay(); //->save();

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();
                }
                catch (Mage_Core_Exception $e) {
                }
            }

            $orderPrev = Mage::getModel('sales/order')->load($order->getRelationParentId());
            $orderPrev->setIsHidden(1);
            $orderPrev->save();

            $order->setRealIncrement($orderPrev->getRealIncrement());
            if($orderPrev->getIsInvoice() == '1'){
                $order->setIsInvoice(1);
                $order->setIsHidden(1);
                //create auto invoice not sure needs testing with authorize and without
            }
            $order->save();

            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been edited.'));
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Mage_Core_Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Order saving error') . ': %s', $e->getMessage());
            $this->_redirect('*/*/');
        }
    }
}
