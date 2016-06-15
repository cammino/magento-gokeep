<?php
class Gokeep_Tracking_Model_Observer 
{
	public function addToCart()
	{
		$id  = Mage::app()->getRequest()->getParam('product', 0);
		$qtd = Mage::app()->getRequest()->getParam('qty', 1);
		
		Mage::getModel('core/session')->setGokeepAddProductToCart(
			new Varien_Object(array(
				'id' => $id,
				'qtd' => $qtd
			))
		);
	}

	public function deleteFromCart() {
		die;
		// $cart = Mage::getSingleton('checkout/cart');
		// $itemId = Mage::app()->getRequest()->getParam('id', 0);
		// $item = $cart->getQuote()->getItemById($itemId);
		// $product = $item->getProduct();

		// Mage::getModel('core/session')->setGaDeleteProductFromCart(
		// 	new Varien_Object(array(
		// 		'id' => $product->getId(),
		// 		'qty' => $item->getQty()
		// 	))
		// );
	}

}