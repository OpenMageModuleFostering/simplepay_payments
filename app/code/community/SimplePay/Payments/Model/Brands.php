<?php

/**
 * Brands
 */
class SimplePay_Payments_Model_Brands
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        
        $brands = Mage::getModel('simplepay/config')->getAllBrands();

        $translatedBrands = array();

        foreach ($brands as $brand) {
            $translatedBrands[$brand] = Mage::helper('simplepay')->brandToValue($brand);
        }

        foreach ($translatedBrands as $label => $value) {
            $options[] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        return $options;
    }
}
