<?php
class Gokeep_Tracking_Model_Observer 
{
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
	            "id"    => (int)    $product->getId(),
	            "name"  => (string) $product->getName(),
	            "price" => (float)  $product->getPrice(),
	            "sku"   => (string) $product->getSku(),
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
		$return = array(
			"name" 	=> $post["billing"]["firstname"] . " " . $post["billing"]["lastname"],
			"email" => $post["billing"]["email"]
		);

		Mage::getModel('core/session')->setGokeepLeadBilling($return);
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