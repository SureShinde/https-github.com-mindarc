<?php
class Ipragmatech_Checkoutstep_Adminhtml_ExportdataController extends Mage_Adminhtml_Controller_action
{
 
	public function indexAction()
    {
        $this->loadLayout()
        		->_setActiveMenu('checkoutstep/adminhtml_exportdata')
                ->_addContent(
                $this->getLayout()
                ->createBlock('checkoutstep/adminhtml_exportdata')
                ->setTemplate('checkoutstep/exportdata.phtml'))
                ->renderLayout();
       
    }
    
    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $customerdata = $read->fetchAll("select * from checkoutstep_customer_data");
            $columns = $read->fetchAll("SHOW COLUMNS FROM checkoutstep_customer_data ");
            $output = "";
            
            Mage::log("The column is".json_encode($columns),null,'mylog.log');
            
            //get the Table Header
            foreach ($columns as $column){
            	$output .='"'.$column['Field'].'",';
            }
            $output .="\n";
            
            // Get Records from the table
            if (!empty($customerdata)) {
            	foreach ($customerdata as $item){
	            	$output .='"'.$item['id'].'",';
	            	$output .='"'.$item['entrant_name'].'",';
	            	$output .='"'.$item['entrant_phone'].'",';
	            	$output .='"'.$item['entrant_email'].'",';
	            	$output .='"'.$item['permanent_address'].'",';
	            	$output .='"'.$item['address'].'",';
	            	$output .='"'.$item['order_id'].'",';
	            	$output .="\n";
	            	
	            }
            }
            // Download the file
			$filename = "Customerdata.csv";
			header('Content-type: application/csv');
			header('Content-Disposition: attachment; filename='.$filename);
			
			echo $output;
			exit;
            
            $message = $this->__('Your form has been submitted successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
}
