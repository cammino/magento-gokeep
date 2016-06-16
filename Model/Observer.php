<?php
class Gokeep_Tracking_Model_Observer 
{
	public function addToCart()
	{
		$id  = Mage::app()->getRequest()->getParam('product', 0);
		$qty = Mage::app()->getRequest()->getParam('qty', 1);
		
		Mage::getModel('core/session')->setGokeepAddProductToCart(
			new Varien_Object(array(
				'id' => $id,
				'qty' => $qty
			))
		);
	}

	public function deleteFromCart()
	{
		$cart = Mage::getSingleton('checkout/cart');
		$id = Mage::app()->getRequest()->getParam('id', 0);
		$item = $cart->getQuote()->getItemById($id);
		$product = $item->getProduct();

		Mage::getModel('core/session')->setGokeepDeleteProductFromCart(
			new Varien_Object(array(
				'id' => $product->getId(),
				'qty' => $item->getQty()
			))
		);
	}

	public function updateCart()
	{
		$cart = Mage::getSingleton('checkout/cart')->getQuote();		
			
		foreach ($cart->getAllItems() as $product)
		{
			$items[] = array(
	            "id"    => (int) $product->getProduct()->getId(),
	            "name"  => $product->getProduct()->getName(),
	            "price" => (float) $product->getProduct()->getPrice(),
	            "qty"   => $product->getQty()
            );
		}

		Mage::getModel('core/session')->setGokeepUpdateProductCart($items);
	}

	public function orderSuccess()
	{
		$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($incrementId);

		$orderId = (int) $order->getId();
		Mage::getModel('core/session')->setGokeepOrder($orderId);
	}
}