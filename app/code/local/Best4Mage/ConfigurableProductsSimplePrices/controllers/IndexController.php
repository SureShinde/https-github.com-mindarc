<?php
class Best4Mage_ConfigurableProductsSimplePrices_IndexController extends Mage_Core_Controller_Front_Action
{ 
    public function simpleproducttirepriceAction()
	{
		if($id = $this->getRequest()->getParam('simpleproductid')){
			$html = '';
			$product = Mage::getModel('catalog/product')->load($id);
			$tirePrices = $product->getTierPrice();
			if(count($tirePrices)>0){
				foreach($tirePrices as $tirePrice){
					$product->getPrice(); 
					$price  = $tirePrice['price'];
					$qty  = round($tirePrice['price_qty']);
					$tirePriceTotal = ($price * $qty); 
					$finalPriceTotal = ($product->getPrice() * $qty); 
					$percent = 100 - ($tirePriceTotal * 100)/($finalPriceTotal);
					$html .= '<li class="tier-price">Buy  '.round($tirePrice['price_qty']).' for '.Mage::helper('core')->currency($tirePrice['price'], array('precision' => -2) ).' each and save <strong class="benefit">save&nbsp;<span class="percent">'.round($percent).'</span>%
                   </strong></li>';
				} 
			}			
		} 	
		$output['resp'] = $html;  
        $json = json_encode($output); 
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($json);
	}
}