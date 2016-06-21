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
    public function getObserverTrackingCode($page)
    {
        if($page == "cart") { return $this->setObserverCart();  }
        if($page == "order"){ return $this->setObserverOrder(); }
        if($page == "lead") { return $this->setObserverLead();  }
        return "";
    }

    /**
    * Identifies the page and call the function responsible for generating the tag
    *
    * current_product validation must come before the current_category verification
    *
    * @return string
    */
    private function setPage()
    {
        // Product View
        if(Mage::registry('current_product')) 
        {
            return $this->getTagProductView();
        }

        // Category Page
        if (Mage::registry('current_category'))
        {
            return $this->getTagProductImpression();
        }

        return "";
    }

    /**
    * Identify if there is a variable in the session to render the tag and checks which tag will be rendered based on the action cart (add, update, delete)
    *
    * @return string
    */
    private function setObserverCart()
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

        return "";
    }

    /**
    * Identify if there is a variable in the session to render the tag for order success
    *
    * @return string
    */
    private function setObserverOrder()
    {
        if (Mage::getModel('core/session')->getGokeepOrder() != null)
        {
            return $this->getTagOrder();
        }
        return "";
    }

    /**
    * Identify if there is a variable in the session to render the tag and checks which tag will be rendered based on customer action
    *
    * Possible actions for get the email customer and identity the lead:
    * - Default (Login/Account register in menu store or Login in billing)
    * - Billing (Account register in billing)
    * - Subscriber (Opt-in/newsletter in footer store)
    *
    * @return string
    */
    private function setObserverLead()
    {

        if (Mage::getModel('core/session')->getGokeepLead() != null)
        {
            return $this->getTagLead("default");
        }

        if (Mage::getModel('core/session')->getGokeepLeadBilling() != null)
        {
            return $this->getTagLead("billing");
        }

        if (Mage::getModel('core/session')->getGokeepLeadSubscriber() != null && !$this->getRequest()->isXmlHttpRequest())
        {
            return $this->getTagLead("subscriber");
        }

        return "";
    }

    /**
    * Get lead tag
    *
    * @return string
    */
    public function getTagLead($page)
    {
        if ($page == "default"){
            $customer = Mage::getModel('core/session')->getGokeepLead();
            Mage::getModel('core/session')->unsGokeepLead();
        }
        elseif ($page == "billing"){
            $customer = Mage::getModel('core/session')->getGokeepLeadBilling();
            Mage::getModel('core/session')->unsGokeepLeadBilling();
        }
        elseif ($page == "subscriber"){
            $customer = Mage::getModel('core/session')->getGokeepLeadSubscriber();
            Mage::getModel('core/session')->unsGokeepLeadSubscriber();
        }
        else{
            return "";
        }

        $json = array(
            "name"  => $customer["name"],
            "email" => $customer["email"]
        );

        return "gokeep('send', 'lead', ". json_encode($json) .");";
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
    * Get cart update tag
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
    * Get cart remove tag
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
    * Get cart add tag
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
    * Get product view tag
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
            "sku"       => $this->getProductSku($product),
            "image"     => $this->getProductImage($product)
        );

        $tag = "gokeep('send', 'productview', " . json_encode($items) . ");";
        return $tag;
    }

    /**
    * Get product impressions tag
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
    * Get items for checkout tag
    *
    * @return json
    */
    public function getItemsTagCheckout()
    {
        $items = array();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $products = $quote->getAllVisibleItems();
        
        foreach ($products as $product) {
            $items[] = array (
                "id"    => (int)    $product->getProduct()->getId(),
                "price" => (float)  $product->getProduct()->getPrice(),
                "qty"   => (int)    $product->getQty()
            );
        }
        return json_encode($items);
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
    * Get actual product
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
    * Get the image of the product
    *
    * @return string
    */
    public function getProductImage($product)
    {
        return $product->getImageUrl();
    }

    /**
    * Check if is page checkout/onepage
    *
    * @return bool
    */
    public function isCheckoutPage()
    {
        return (Mage::app()->getFrontController()->getRequest()->getControllerName() == "onepage");
    }

    /**
    * Check if user is logged
    *
    * @return bool
    */
    public function isUserLogged()
    {
        return (Mage::getSingleton('customer/session')->isLoggedIn());
    }
}