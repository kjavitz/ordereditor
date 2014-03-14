<?php
/**
 *
 * @author Enrique Piatti
 */
/** @var Mage_Catalog_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();


$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

try {

}catch(Exception $E) {

}

$setup = $this;

$installer->endSetup();

$installer2 = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$installer2->startSetup();

$installer2->endSetup();
?>