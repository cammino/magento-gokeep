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
	public function getTrackingCode($code)
	{
		return $this->setPage();
	}

	/**
	* Identifies the page and call the function responsible for generating the tag
	*
	* @return string
	*/
	private function setPage()
	{
		
		// Category Page
		if (Mage::registry('current_category')){
			return $this->getTagProductImpression();
		}else{
			return "";
		}
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
}