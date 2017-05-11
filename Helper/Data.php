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
        } else if ($productType == "bundle") {
            return $this->getBundleProductPrice($product);
        } else if ($productType == "configurable") {
            return $this->getConfigurableProductPrice($product);
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
        return $product->getFinalPrice();
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
            if ($item->getFinalPrice() > 0) {
                array_push($prices, $item->getFinalPrice());
            }
        }

        rsort($prices, SORT_NUMERIC);

        if (count($prices) > 0) {
            $minimal = end($prices);    
        }

        return $minimal;
    }

    /**
    * Get price for bundle product
    *
    * @return string
    */
    public function getBundleProductPrice($product){
        $optionCollection = $product->getTypeInstance(true)->getOptionsIds($product);
        $selectionsCollection = Mage::getModel('bundle/selection')->getCollection();
        $selectionsCollection->getSelect()->where('option_id in (?)', $optionCollection)->where('is_default = ?', 1);
        $defaultPrice = 0;

        foreach ($selectionsCollection as $_selection) {
            $_selectionProduct = Mage::getModel('catalog/product')->load($_selection->getProductId());
            $_selectionPrice = $product->getPriceModel()->getSelectionFinalTotalPrice(
                $product,
                $_selectionProduct,
                0,
                $_selection->getSelectionQty(),
                false,
                true
            );
            $defaultPrice += ($_selectionPrice * $_selection->getSelectionQty());
        }
        return $defaultPrice;
    }

    /**
    * Get price for configurable product
    *
    * @return string
    */
    public function getConfigurableProductPrice($product) {
        $childProducts = Mage::getSingleton('catalog/product_type_configurable')->getUsedProducts( null, $product );
        $childPriceLowest = '';

        if ( $childProducts ) {
            foreach ( $childProducts as $child ) {
                $_child = Mage::getSingleton('catalog/product')->load( $child->getId() );
                $_inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_child)->getIsInStock();

                if (($_child->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_DISABLED) && $_inStock) {
                    if ( $childPriceLowest == '' || $childPriceLowest > $_child->getFinalPrice() ) {
                        $childPriceLowest =  $_child->getFinalPrice();
                    }
                }
            }
        } else {
            $childPriceLowest = $product->getFinalPrice();
        }

        return $childPriceLowest;
    }

    /**
    * Get price of last product changed in quote
    *
    * @return float
    */
    public function getPriceProductQuote($productId){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $lastItem = null;
        $price = 0;
        foreach ($quote->getAllItems() as $item) {
            if($item->getProductId() == $productId){
                $lastItem = $lastItem == null ? $item : $lastItem;
                $lastItem = $lastItem->getCreatedAt() < $item->getCreatedAt() ? $item : $lastItem;
                $price = $lastItem->getPrice();
            }
        }
        return $price;
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