<?php

class ITwebexperts_Ordereditor_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Function which creates a deposit product
     * ** This Function Is Not Used Anymore **
     */
    public static function createDifferenceProduct(){
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku','difference_ppr');
        if(!$product){
            $product = new Mage_Catalog_Model_Product();

            $product->setSku('difference_ppr');
            $entityTypeId = Mage::getModel('eav/entity')
                ->setType('catalog_product')
                ->getTypeId();
            $attributeSetName   = 'Default';
            $attributeSetId     = Mage::getModel('eav/entity_attribute_set')
                ->getCollection()
                ->setEntityTypeFilter($entityTypeId)
                ->addFieldToFilter('attribute_set_name', $attributeSetName)
                ->getFirstItem()
                ->getAttributeSetId();
            $product->setAttributeSetId($attributeSetId);
            $product->setTypeId('simple');
            $product->setName('Difference PPR');
            //$product->setCategoryIds(array(42)); # some cat id's,
            $webArr = array();
            foreach (Mage::app()->getWebsites() as $website) {
                $webArr[] = $website->getId();
            }
            $product->setWebsiteIDs($webArr);
            $product->setDescription('Difference PPR');
            $product->setShortDescription('Difference PPR');
            $product->setPrice(0); # Set some price
            $product->setWeight(0.0000);
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setStatus(1);
            $product->setTaxClassId(0); # default tax class
            $stockData = array();
            $stockData['qty'] = 1;
            $stockData['is_in_stock'] = 1;
            $stockData['manage_stock'] = 0;
            $stockData['use_config_manage_stock'] = 0;
            $product->setStockData($stockData);
            $product->setCreatedAt(strtotime('now'));

            try {
                $product->save();
            }
            catch (Exception $ex) {

            }
        }

    }

    public static function generateRule($name = null, $coupon_code = null, $discount = 0, $free_shipping = '0', $sku)
    {
        $rulesList = Mage::getModel('salesrule/rule')
            ->getCollection()
            ->addFieldToFilter('name', array('like' => $name));
        foreach ($rulesList as $rule) {
            return false;
        }

        $rule = Mage::getModel('salesrule/rule');
        $customer_groups = array(0, 1, 2, 3);
        $rule->setName($name)
            ->setDescription($name)
            ->setFromDate('')
            ->setCouponType(1)
            ->setCouponCode($coupon_code)
            ->setUsesPerCustomer(1000)
            ->setCustomerGroupIds($customer_groups) //an array of customer grou pids
            ->setIsActive(1)
            ->setConditionsSerialized('')
            ->setActionsSerialized('')
            ->setStopRulesProcessing(0)
            ->setIsAdvanced(1)
            ->setProductIds('')
            ->setSortOrder(0)
            ->setSimpleAction('cart_fixed')
            ->setDiscountAmount($discount)
            ->setDiscountQty(null)
            ->setDiscountStep(0)
            ->setSimpleFreeShipping($free_shipping)
            ->setApplyToShipping('0')
            ->setIsRss(0)
            ->setWebsiteIds(array(1));

        $item_found = Mage::getModel('salesrule/rule_condition_product_found')
            ->setType('salesrule/rule_condition_product_found')
            ->setValue(1) // 1 == FOUND
            ->setAggregator('all'); // match ALL conditions
        $rule->getConditions()->addCondition($item_found);
        $conditions = Mage::getModel('salesrule/rule_condition_product')
            ->setType('salesrule/rule_condition_product')
            ->setAttribute('sku')
            ->setOperator('==')
            ->setValue($sku);
        $item_found->addCondition($conditions);

        $actions = Mage::getModel('salesrule/rule_condition_product')
            ->setType('salesrule/rule_condition_product')
            ->setAttribute('sku')
            ->setOperator('==')
            ->setValue($sku);
        $rule->getActions()->addCondition($actions);
        $rule->save();

    }
}