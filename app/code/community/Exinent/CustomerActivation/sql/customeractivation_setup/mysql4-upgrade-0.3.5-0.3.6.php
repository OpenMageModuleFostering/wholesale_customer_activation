<?php
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->updateAttribute('customer', 'customer_activated', 'used_in_forms', 'adminhtml_customer');
