<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$setup = $this;

$installer->endSetup();

$installer2 = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$installer2->startSetup();

$installer2->addAttribute('order', 'is_hidden', array(
        'type' => 'int',
        'grid' => false,
        'unsigned'  => true
    ));

$installer2->addAttribute('order', 'real_increment', array(
        'type' => 'varchar',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->addAttribute('order', 'is_invoice', array(
        'type' => 'int',
        'grid' => false,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/order'), 'is_hidden', 'INTEGER');
$installer2->getConnection()->addColumn($installer2->getTable('sales/order'), 'is_invoice', 'INTEGER');
$installer2->getConnection()->addColumn($installer2->getTable('sales/order'), 'real_increment', 'VARCHAR');




$installer2->endSetup();
?>