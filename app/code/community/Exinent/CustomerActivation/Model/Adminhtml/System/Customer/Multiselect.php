<?php

class Exinent_CustomerActivation_Model_Adminhtml_System_Customer_Multiselect {

    protected $_options;

    public function toOptionArray() {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('customer/group_collection')
                            ->setRealGroupsFilter()
                            ->addFieldToFilter('customer_group_id', array('neq' => 1))
                            ->loadData()->toOptionArray();
            array_unshift($this->_options, array('value' => '', 'label' => Mage::helper('adminhtml')->__('No Group Selected')));
        }
        return $this->_options;
    }

}
