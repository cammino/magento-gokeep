<?php
/**
 * Tracking
 *
 * This file is responsible for managing tags of some specific pages 
 *
 * @category   Gokeep
 * @package    Tracking
 * @author     Cammino Digital <contato@cammino.com.br>
 */

class Gokeep_Tracking_Block_Tracking extends Mage_Core_Block_Template
{
    
    /**
    * Main function responsible for delegating which tag will be rendered
    *
    * @return string
    */
    public function getTrackingCode()
    {
        return $this->setPage();
    }

    /**
    * Identifies the page and call the function responsible for generating the tag
    *
    * Check the current_product page must come before the current_category verification
    *
    * @return string
    */
    private function setPage()
    {

        /* Product View */
        if(Mage::registry('current_product')){
            return $this->getTagProductView();
        }

        /* Category Page */
        if (Mage::registry('current_category')){
            return $this->getTagProductImpression();
        }
    }

    /**
    * Generates the tag to the page catalog-product-view
    *
    * @return string
    */
    private function getTagProductView()
    {
        $product = $this->getProduct();

        $items[] = array(
            "id"        => (int) $this->getProductid($product),
            "name"      => $this->getProductName($product),
            "price"     => (float) $this->getProductPrice($product),
            "sku"       => $this->getProductSku($product),
            "variant"   => $this->getProductVariants($product)
        );

        $tag = "gokeep('send', 'productview', " . json_encode($items) . ");";
        return $tag;
    }

    /**
    * Generates the tag to the page catalog-category-view
    *
    * @return string
    */
    private function getTagProductImpression()
    {
        $tag   = "";
        $items = array();
        $products = $this->getProducts();

        foreach ($products as $product){
            $items[] = array(
                 "id"    => (int)$this->getProductId($product),
                 "name"  => $this->getProductName($product),
                 "price" => (float)$this->getProductPrice($product),
                 "sku"   => $this->getProductSku($product)
            );
        }

        $tag = "gokeep('send', 'productimpression', " . json_encode($items) . ", '". Mage::registry('current_category')->getName() ."') ";
        return $tag;
    }

    /**
    * Get Products
    *
    * @return array
    */
    public function getProducts()
    {
        $productList_block  = Mage::app()->getLayout()->createBlock('catalog/product_list');        
        $collection = $productList_block->getLoadedProductCollection();
        $collection->clear();
        $collection->getSelect()->reset(Zend_Db_Select::ORDER);
        $collection->setOrder('entity_id','asc');
        $collection->getSelect()->limit(100, 0);
        $collection->load();
        return $collection;
    }

    /**
    * Get actual Product
    *
    * @return object
    */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

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
        return $product->getPrice();
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
    * Get the variants of the product
    *
    * @return array
    */
    public function getProductVariants($product)
    {
        return "vermelho";
        // $sku = $product->getData('sku');
        // $product = $product->load($sku);
        // if ($product->isConfigurable())
        // {
        //     $_attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        //     foreach($_attributes as $_attribute)
        //     {
        //         // you can now iterate through all the attributes
             
        //         // get the attribute label
        //         $attr_txt = $_attribute["store_label"];
 
        //         // get the attribute values
        //         foreach ($_attribute["values"] as $value)
        //         {
        //             // get the attribute value..
        //             // .. as for instance the color
        //             $value_label = $value["label"];
 
        //             // get the price for the option
        //             $option_price = $value["pricing_value"];
        //         }
        //     }
        // }
    }

}