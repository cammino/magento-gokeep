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
    protected $gokeepHelper;

    function __construct()
    {
        $this->gokeepHelper = Mage::helper('gokeep');
    }

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
        if ($this->getRegistryCategory() || $this->isSearchPage())
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

        return "gokeep('send', 'lead', ". $this->gokeepHelper->getJson($json) .");";
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

        $orderItems = $order->getAllVisibleItems();
        $items = array();

        foreach ($orderItems as $orderItem) {

            $product = $orderItem->getProduct() != NULL ? $orderItem->getProduct() : $orderItem;
            $product = Mage::getModel('catalog/product')->load($product->getProductId());

            $items[] = array (
                "id"    => (int)    $product->getId(),
                "name"  => (string) $this->gokeepHelper->getProductName($product),
                "price" => (float)  $this->gokeepHelper->getOrderItemPrice($orderItem),
                "sku"   => (string) $this->gokeepHelper->getProductSku($product),
                "image" => (string) $product->getImageUrl(),
                "qty"   => (int)    $orderItem->getData('qty_ordered'),
                "url"   => (string) $this->gokeepHelper->getProductUrl($product)
            );
        }

        $json = array(
            "id"        => (int)    $orderId,
            "total"     => (float)  $order->getGrandTotal(),
            "shipping"  => (float)  $order->getShippingAmount(),
            "tax"       => (float)  $order->getData('tax_amount'),
            "coupon"    => (string) $order->getCouponCode(),
            "items"     => $items
        );

        Mage::getModel('core/session')->unsGokeepOrder();

        $tag = "gokeep('send', 'order', " . $this->gokeepHelper->getJson($json) . "); ";
        return $tag;
    }

    /**
    * Get cart update tag
    *
    * @return string
    */
    private function getTagCartUpdate()
    {
        $sessionItems = Mage::getModel('core/session')->getGokeepUpdateProductCart();
        $items = array();

        foreach ($sessionItems as $sessionItem) {

            $product = Mage::getModel('catalog/product')->load($sessionItem["id"]);

            $items[] = array(
                "id"    => (int)    $sessionItem["id"],
                "name"  => (string) $sessionItem["name"],
                "price" => (float)  $sessionItem["price"],
                "sku"   => (string) $sessionItem["sku"],
                "image" => (string) $product->getImageUrl(),
                "qty"   => (int)    $sessionItem["qty"],
                "url"   => (string) $this->gokeepHelper->getProductUrl($product)
            );
        }
        
        Mage::getModel('core/session')->unsGokeepUpdateProductCart();

        $tag = "gokeep('send', 'cartupdate', " . $this->gokeepHelper->getJson($items) . ");";
        return $tag;
    }

    /**
    * Get cart remove tag
    *
    * @return string
    */
    private function getTagCartRemove()
    {
        $sessionItem = Mage::getModel('core/session')->getGokeepDeleteProductFromCart();
        $product = Mage::getModel('catalog/product')->load($sessionItem->getId());

        Mage::getModel('core/session')->unsGokeepDeleteProductFromCart();
        
        $items[] = array(
            "id"    => (int)    $this->gokeepHelper->getProductId($product),
            "name"  => (string) $this->gokeepHelper->getProductName($product),
            "price" => (float)  $this->gokeepHelper->getProductPrice($product),
            "sku"   => (string) $this->gokeepHelper->getProductSku($product),
            "image" => (string) $this->gokeepHelper->getProductImage($product),
            "qty"   => (int)    $sessionItem->getQty(),
            "url"   => (string) $this->gokeepHelper->getProductUrl($product)
        );
        
        $tag = "gokeep('send', 'cartremove', " . $this->gokeepHelper->getJson($items) . ");";

        return $tag;
    }

    /**
    * Get cart add tag
    *
    * @return string
    */
    private function getTagCartAdd()
    {
        $sessionItem = Mage::getModel('core/session')->getGokeepAddProductToCart();
        $product = Mage::getModel('catalog/product')->load($sessionItem->getId());

        Mage::getModel('core/session')->unsGokeepAddProductToCart();

        $items[] = array(
            "id"    => (int)    $this->gokeepHelper->getProductId($product),
            "name"  => (string) $this->gokeepHelper->getProductName($product),
            "price" => (float)  $this->gokeepHelper->getProductPrice($product),
            "sku"   => (string) $this->gokeepHelper->getProductSku($product),
            "image" => (string) $this->gokeepHelper->getProductImage($product),
            "qty"   => (int)    $sessionItem->getQty(),
            "url"   => (string) $this->gokeepHelper->getProductUrl($product)
        );

        $tag = "gokeep('send', 'cartadd', " . $this->gokeepHelper->getJson($items) . ");";
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
            "id"        => (int)    $this->gokeepHelper->getProductid($product),
            "name"      => (string) $this->gokeepHelper->getProductName($product),
            "price"     => (float)  $this->gokeepHelper->getProductPrice($product),
            "sku"       => (string) $this->gokeepHelper->getProductSku($product),
            "image"     => (string) $this->gokeepHelper->getProductImage($product),
            "url"       => (string) $this->gokeepHelper->getProductUrl($product),
            "category"  => (string) $this->getRegistryCategory()
        );

        $tag = "gokeep('send', 'productview', " . $this->gokeepHelper->getJson($items) . ");";
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
        $isSearchPage = $this->isSearchPage();

        foreach ($products as $product){
            $items[] = array(
                 "id"       => (int)    $this->gokeepHelper->getProductId($product),
                 "name"     => (string) $this->gokeepHelper->getProductName($product),
                 "price"    => (float)  $this->gokeepHelper->getProductPrice($product),
                 "sku"      => (string) $this->gokeepHelper->getProductSku($product),
                 "image"    => (string) $this->gokeepHelper->getProductImage($product),
                 "url"      => (string) $this->gokeepHelper->getProductUrl($product),
                 "category" => $isSearchPage == false ? (string) $this->getRegistryCategory() : ""
            );
        }

        if ($isSearchPage) {
            $term = Mage::helper('catalogsearch')->getQueryText();
            $list = "Busca por '{$term}'";
        } else {
            $list = $this->getRegistryCategory();
        }

        $json = array(
            "list"  => $list,
            "items" => $items
        );

        $tag = "gokeep('send', 'productimpression', " . $this->gokeepHelper->getJson($json) ."); ";
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
        $quoteItems = $quote->getAllVisibleItems();
        
        foreach ($quoteItems as $quoteItem) {
            $product = Mage::getModel("catalog/product")->load($quoteItem->getProduct()->getId());

            $items[] = array (
                "id"    => (int)    $product->getId(),
                "name"  => (string) $this->gokeepHelper->getProductName($product),
                "price" => (float)  $this->gokeepHelper->getProductPrice($product),
                "sku"   => (string) $product->getSku(),
                "image" => (string) $product->getImageUrl(),
                "qty"   => (int)    $quoteItem->getQty(),
                "url"   => (string) $product->getProductUrl()
            );
        }
        return $this->gokeepHelper->getJson($items);
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
        $collection->addAttributeToSelect('image');
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
    * Get the registry category in product detail and product list
    *
    * @return string
    */
    public function getRegistryCategory()
    {
        $category = Mage::registry('current_category');

        return $category ? $category->getName() : "";
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
    * Check if is page catalogsearch/result
    *
    * @return bool
    */
    public function isSearchPage()
    {
        return (Mage::app()->getFrontController()->getRequest()->getControllerName() == "result");
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