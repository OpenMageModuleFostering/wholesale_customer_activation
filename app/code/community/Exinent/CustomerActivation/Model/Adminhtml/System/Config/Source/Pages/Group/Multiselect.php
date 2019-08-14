<?php

class Exinent_CustomerActivation_Model_Adminhtml_System_Config_Source_Pages_Group_Multiselect {

    protected $_options;

    public function toOptionArray() {
        if (!$this->_options) {
            $this->_options = Mage::getModel('cms/page')->getCollection()->toOptionArray();
            array_unshift($this->_options, array('value' => '', 'label' => Mage::helper('adminhtml')->__('No page Selected')));
        }
        return $this->_options;
    }

}
