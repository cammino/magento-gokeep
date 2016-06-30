<?php
/**
 * Data
 *
 * This file is contains some functions to help other methods in module
 *
 * @category   Gokeep
 * @package    Tracking
 * @author     Cammino Digital <contato@cammino.com.br>
 */

class Gokeep_Tracking_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
    * Get the Id of the product
    *
    * @return int
    */
    public function getProductId($product)
    {
        return $product->getId();
    }

    /**
    * Get the name of the product
    *
    * @return string
    */
    public function getProductName($product)
    {
        return $product->getName();
    }

    /**
    * Get the price of the product
    *
    * @return float
    */
    public function getProductPrice($product)
    {
        $productType = $product->getTypeId() != NULL ? $product->getTypeId() : $product->product_type;

        if ($productType == "simple") {
            return $this->getSimpleProductPrice($product);
        } else if ($productType == "grouped") {
            return $this->getGroupedProductPrice($product);
        } else{
            return "";
        }
    }

    /**
    * Get price for simple product
    *
    * @return string
    */
    public function getSimpleProductPrice($product) {
        if ($product->getSpecialPrice() > 0) {          
            return $product->getSpecialPrice();
        }else{
            if($product->getFinalPrice() > 0){
                return $product->getFinalPrice();
            }else{
                return $product->getPrice();
            }
        }
    }

    /**
    * Get price for grouped product
    *
    * @return string
    */
    public function getGroupedProductPrice($product) {
        $associated = $this->getAssociatedProducts($product);
        $prices = array();
        $minimal = 0;

        foreach($associated as $item) {
            if ($item->getPrice() > 0) {
                array_push($prices, $item->getPrice());
            }
        }

        rsort($prices, SORT_NUMERIC);

        if (count($prices) > 0) {
            $minimal = end($prices);    
        }

        return $minimal;
    }

    /**
    * Get associated products of one product
    *
    * @return collection
    */
    public function getAssociatedProducts($product) {
        $collection = $product->getTypeInstance(true)->getAssociatedProductCollection($product)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1);
        return $collection;
    }

    /**
    * Get the sku of the product
    *
    * @return int
    */
    public function getProductSku($product)
    {
        return $product->getSku();
    }

    /**
    * Get the image of the product
    *
    * @return string
    */
    public function getProductImage($product)
    {
        return $product->getImageUrl();
    }

    /**
    * Get the url of the product
    *
    * @return string
    */
    public function getProductUrl($product)
    {
		return Mage::helper('catalog/product')->getProductUrl($product->getId());
    }

    /**
    * Convert string in json
    *
    * @return json
    */
    public function getJson($string)
    {
        return json_encode($string);
    }

    /**
    * Calc item price from order
    *
    * @return string
    */
    function getOrderItemPrice($orderItem) {
        return (($orderItem->getRowTotal()-$orderItem->getDiscount())/$orderItem->getQtyOrdered());
    }
}