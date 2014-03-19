<?php
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$setup = $this;

$installer->endSetup();

$installer2 = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$installer2->startSetup();

$installer2->addAttribute('invoice', 'real_increment', array(
        'type' => 'varchar',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/invoice'), 'real_increment', 'VARCHAR');

$installer2->addAttribute('creditmemo', 'real_increment', array(
        'type' => 'varchar',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/creditmemo'), 'real_increment', 'VARCHAR');


$installer2->addAttribute('shipment', 'real_increment', array(
        'type' => 'varchar',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/shipment'), 'real_increment', 'VARCHAR');


$installer2->addAttribute('invoice', 'edit_order_id', array(
        'type' => 'int',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/invoice'), 'edit_order_id', 'INT');

$installer2->addAttribute('creditmemo', 'edit_order_id', array(
        'type' => 'int',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/creditmemo'), 'edit_order_id', 'INT');


$installer2->addAttribute('shipment', 'edit_order_id', array(
        'type' => 'int',
        'grid' => true,
        'unsigned'  => true
    ));

$installer2->getConnection()->addColumn($installer2->getTable('sales/shipment'), 'edit_order_id', 'INT');


$installer2->addAttribute('order', 'notes', array(
        'type' => 'text',
        'grid' => true,
        'unsigned'  => true
    ));
$installer2->getConnection()->addColumn($installer2->getTable('sales/order'), 'notes', 'TEXT');

$installer2->addAttribute('order', 'extra_data', array(
        'type' => 'text',
        'grid' => true,
        'unsigned'  => true
    ));
$installer2->getConnection()->addColumn($installer2->getTable('sales/order'), 'extra_data', 'TEXT');


$installer2->endSetup();
?>