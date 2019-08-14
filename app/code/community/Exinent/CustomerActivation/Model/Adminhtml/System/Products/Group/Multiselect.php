<?php

class Exinent_CustomerActivation_Model_Adminhtml_System_Products_Group_Multiselect {

    protected $_options;

    public function toOptionArray() {
        if (!$this->_options) {
            $categoryCollection = Mage::getModel('catalog/product')
                    ->getCollection()
                     ->addAttributeToFilter('status', array('eq'=>'1'))
                    ->addAttributeToSelect('*');
        }
        $optionArrays = array();
        foreach ($categoryCollection as $category) {
            $optionArray = array();
            $optionArray['value'] = $category->getUrlKey();
            $optionArray['label'] = $category->getName();
            array_push($optionArrays, $optionArray);
        }
        $this->_options = $optionArrays;
        array_unshift($this->_options, array('value' => '', 'label' => Mage::helper('adminhtml')->__('No Product Selected')));
        return $this->_options;
    }

}