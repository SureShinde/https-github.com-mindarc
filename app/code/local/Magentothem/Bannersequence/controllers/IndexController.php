<?php
class Magentothem_Bannersequence_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/bannersequence?id=15 
    	 *  or
    	 * http://site.com/bannersequence/id/15 	
    	 */
    	/* 
		$bannersequence_id = $this->getRequest()->getParam('id');

  		if($bannersequence_id != null && $bannersequence_id != '')	{
			$bannersequence = Mage::getModel('bannersequence/bannersequence')->load($bannersequence_id)->getData();
		} else {
			$bannersequence = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($bannersequence == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$bannersequenceTable = $resource->getTableName('bannersequence');
			
			$select = $read->select()
			   ->from($bannersequenceTable,array('bannersequence_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$bannersequence = $read->fetchRow($select);
		}
		Mage::register('bannersequence', $bannersequence);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}