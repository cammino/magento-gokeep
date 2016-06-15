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

}