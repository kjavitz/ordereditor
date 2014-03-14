<?php

class ITwebexperts_Ordereditor_Model_Observer {

    protected $_session;

    public function __construct() {
        $this->_session = Mage::getSingleton('customer/session');
    }

    public function getSession() {
        return $this->_session;
    }


}
?>
