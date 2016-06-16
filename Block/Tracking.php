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
    * Check if module is active
    *
    * @return bool
    */
    public function isGokeepActive()
    {
        return (bool) Mage::getStoreConfig('gokeep/gokeep_group/gokeep_active');
    }

    /**
    * Get the Gokeep Store ID
    *
    * @return string
    */
    public function getGokeepStoreId()
    {
        return (string) Mage::getStoreConfig('gokeep/gokeep_group/gokeep_store_id');
    }

    /**
    * Function responsible for delegating which tag will be rendered based on page
    *
    * @return string
    */
    public function getPageTrackingCode()
    {
        return $this->setPage();
    }

    /**
    * Function responsible for delegating which tag will be rendered based on observers
    *
    * @return string
    */
    public function getObserverTrackingCode()
    {
        return $this->setObserver();
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
    * Function responsible for identifying which observer was triggered
    *
    * @return string
    */
    private function setObserver()
    {
        if (Mage::app()->getFrontController()->getRequest()->getControllerName() == "cart")
        {
            if (Mage::getModel('core/session')->getGokeepAddProductToCart() != null)
            {
                return $this->getTagCartAdd();
            }

            if (Mage::getModel('core/session')->getGokeepDeleteProductFromCart() != null)
            {
                return $this->getTagCartRemove();
            }

            if (Mage::getModel('core/session')->getGokeepUpdateProductCart() != null)
            {
                return $this->getTagCartUpdate();
            }
        }
        
        if (Mage::getModel('core/session')->getGokeepOrder() != null)
        {
            return $this->getTagOrder();
        }

        return "";
    }

    /**
    * Get order tag
    *
    * @return string
    */
    public function getTagOrder()
    {
        $orderId = Mage::getModel('core/session')->getGokeepOrder();
        $order = Mage::getModel('sales/order')->load($orderId);

        $products = $order->getAllVisibleItems();
        $items = array();

        foreach ($products as $product) {
            $items[] = array (
                "id"    => (int) $product->getProduct()->getId(),
                "price" => (float) $product->getProduct()->getPrice(),
                "qty"   => (int) $product->getData('qty_ordered')
            );
        }

        $json = array(
            "id"        => $orderId,
            "total"     => (float) $order->getGrandTotal(),
            "shipping"  => (float) $order->getShippingAmount(),
            "tax"       => (float) $order->getData('tax_amount'),
            "coupon"    => (string)$order->getCouponCode(),
            "items"     => $items
        );

        Mage::getModel('core/session')->unsGokeepOrder();

        $tag = "gokeep('send', 'order', " . json_encode($json) . "); ";
        return $tag;
    }

    /**
     * Get tag when user update products in cart
     *
     * @return string
     */
    private function getTagCartUpdate()
    {
        $itemSession = Mage::getModel('core/session')->getGokeepUpdateProductCart();
        $items = array();

        foreach ($itemSession as $product) {
            $items[] = array(
                "id"    => $product["id"],
                "name"  => $product["name"],
                "price" => $product["price"],
                "qty"   => $product["qty"]
            );
        }
        
        Mage::getModel('core/session')->unsGokeepUpdateProductCart();

        $tag = "gokeep('send', 'cartupdate', " . json_encode($items) . ");";
        return $tag;
    }

    /**
    * Get tag when user remove a product in cart
    *
    * @return string
    */
    private function getTagCartRemove()
    {
        $result = array();
        $itemSession = Mage::getModel('core/session')->getGokeepDeleteProductFromCart();

        $product = Mage::getModel('catalog/product')->load($itemSession->getId());

        Mage::getModel('core/session')->unsGokeepDeleteProductFromCart();
        
        $items[] = array(
            "id"    => (int)$this->getProductId($product),
            "name"  => $this->getProductName($product),
            "price" => (float)$this->getProductPrice($product),
            "sku"   => $this->getProductSku($product),
            "qty"   => $itemSession->getQty()
        );
        
        $tag = "gokeep('send', 'cartremove', " . json_encode($items) . ");";

        return $tag;
    }

    /**
    * Get tag when user add a product in cart
    *
    * @return string
    */
    private function getTagCartAdd()
    {
        $itemSession = Mage::getModel('core/session')->getGokeepAddProductToCart();
        $product = Mage::getModel('catalog/product')->load($itemSession->getId());

        Mage::getModel('core/session')->unsGokeepAddProductToCart();

        $items[] = array(
            "id"    => (int)$this->getProductId($product),
            "name"  => $this->getProductName($product),
            "price" => (float)$this->getProductPrice($product),
            "sku"   => $this->getProductSku($product),
            "qty"   => $itemSession->getQty()
        );

        $tag = "gokeep('send', 'cartadd', " . json_encode($items) . ");";

        return $tag;
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
            "id"        => (int)$this->getProductid($product),
            "name"      => $this->getProductName($product),
            "price"     => (float) $this->getProductPrice($product),
            "sku"       => $this->getProductSku($product)
            /**
            * TODO: Get product variatios
            * "variant"   => $this->getProductVariants($product) **/
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

        $json = array(
            "list"  => Mage::registry('current_category')->getName(),
            "items" => $items
        );

        $tag = "gokeep('send', 'productimpression', " . json_encode($json) ."); ";
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