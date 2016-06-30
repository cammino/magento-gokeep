<?php
/**
 * Observer
 *
 * This file is responsible for execute functions from observers
 *
 * @category   Gokeep
 * @package    Tracking
 * @author     Cammino Digital <contato@cammino.com.br>
 */

class Gokeep_Tracking_Model_Observer 
{
	protected $gokeepHelper;

    function __construct()
    {
        $this->gokeepHelper = Mage::helper('gokeep');
    }

	/**
    * Observer responsible for get the product added to cart
    *
    * @return null
    */
	public function addToCart()
	{
		$id  = Mage::app()->getRequest()->getParam('product', 0);
		$qty = Mage::app()->getRequest()->getParam('qty', 1);
		
		Mage::getModel('core/session')->setGokeepAddProductToCart(
			new Varien_Object(array(
				'id'  => (int) $id,
				'qty' => (int) $qty
			))
		);
	}

	/**
    * Observer responsible for get the product removed to cart
    *
    * @return null
    */
	public function deleteFromCart()
	{
		$cart = Mage::getSingleton('checkout/cart');
		$id = Mage::app()->getRequest()->getParam('id', 0);
		$item = $cart->getQuote()->getItemById($id);
		$product = $item->getProduct();

		Mage::getModel('core/session')->setGokeepDeleteProductFromCart(
			new Varien_Object(array(
				'id'  => (int) $product->getId(),
				'qty' => (int) $item->getQty()
			))
		);
	}

	/**
    * Observer responsible for get the products updated to cart
    *
    * @return null
    */
	public function updateCart()
	{
		$cart = Mage::getSingleton('checkout/cart')->getQuote();		
			
		foreach ($cart->getAllItems() as $cartItem)
		{
			$product = $cartItem->getProduct();

			$items[] = array(
	            "id"    => (int)    $gokeepHelper->getProductId($product),
	            "name"  => (string) $gokeepHelper->getProductName($product),
	            "price" => (float)  $gokeepHelper->getProductPrice($product),
	            "sku"   => (string) $gokeepHelper->getProductSku($product),
	            "qty"   => (int)    $cartItem->getQty()
            );
		}

		Mage::getModel('core/session')->setGokeepUpdateProductCart($items);
	}

	/**
    * Observer responsible for get order when it finished
    *
    * @return null
    */
	public function orderSuccess()
	{
		$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($incrementId);

		$orderId = (int) $order->getId();
		Mage::getModel('core/session')->setGokeepOrder($orderId);
	}

	/**
    * Observer responsible for get lead when the customer enter your email
    *
    * This observers works in:
    * - Login default (login in menu store) 
    * - Login billing (login during the buying process)
	* - Account Register default (Create account in menu store)
    * 
    * @return null
    */
	public function setLeadLoginRegister(Varien_Event_Observer $observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$return = array(
			"name" 	=> $customer->getFirstname() . " " . $customer->getLastname(),
			"email" => $customer->getEmail()
		);

		Mage::getModel('core/session')->setGokeepLead($return);
	}

	/**
    * Observer responsible for get lead when the customer enter your email
    *
    * This observers works in:
    * - Account Register billing (Account registration during the buying process)
    * 
    * @return null
    */
	public function setLeadRegisterBilling()
	{
		$post = Mage::app()->getRequest()->getPost();

		if(isset($post["billing"])){
			$name  = isset($post["billing"]["firstname"]) ? $post["billing"]["firstname"] : "";
			$name .= isset($post["billing"]["lastname"]) ? " " . $post["billing"]["lastname"] : "";
			$email = isset($post["billing"]["email"]) ? $post["billing"]["email"] : "";

			$return = array(
				"name" 	=> $name,
				"email" => $email
			);
			
			Mage::getModel('core/session')->setGokeepLeadBilling($return);
		}
	}

	/**
    * Observer responsible for get customer email when he subscribes
    *
    * @return null
    */
	public function setSubscriber(Varien_Event_Observer $observer)
	{
	    $data = $observer->getEvent()->getDataObject()->getData();
	    $return = array(
			"name" 	=> "",
			"email" => $data["subscriber_email"]
		);
		
	    Mage::getModel('core/session')->setGokeepLeadSubscriber($return);
	}
}