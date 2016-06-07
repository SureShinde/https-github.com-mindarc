<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Form_Element_Crontime extends Varien_Data_Form_Element_Abstract
{
    const SUFFIX_TYPE = '_type';
    const SUFFIX_HOURS = '_hour';
    const SUFFIX_MINUTES = '_minutes';
    const SUFFIX_FREQUENCY = '_frequency';
    const SUFFIX_EXPRESSION = '_expression';
	
	const TYPE_DEFAULT = 'D';
	const TYPE_CUSTOM = 'C';
	
	
	public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('crontime');
    }

    public function getElementHtml()
    {
        $this->addClass('select');

  		$frequency = 'D';
		$value_hrs = 0;
        $value_min = 0;
       
		$expr = '';		
			
		if(!$type = $this->getCronExpressionType()) {
			$type = 'D';
		}
		
        if($value = $this->getValue()) {
            if($type == 'D') {
				@list($time, $frequency) = explode(' ', $value);
				if($time) {
					$values = explode(',', $time);
					if(is_array($values) && (count($values) == 2 || count($values) == 3)) {
						$value_hrs = $values[0];
						$value_min = $values[1];
					}
				}
			} else {
				$expr = $this->getCronExpression();
			}
        }
		
		if(!$frequency) {
			$frequency = 'D';
		}

        $html = '<input type="hidden" id="' . $this->getHtmlId() . '" />';
		
		$html .= '<input type="radio" name="'. $this->getName() . self::SUFFIX_TYPE . '" id="'. $this->getName() . self::SUFFIX_TYPE . '" value="D" '. ( ($type == 'D') ? 'checked="checked"' : '' ) .' onclick="changeCronExpType(this)" />&nbsp;' . $this->getLabelDefault();
		$html .= '&nbsp;&nbsp;&nbsp;<input type="radio" name="'. $this->getName() . self::SUFFIX_TYPE . '" id="'. $this->getName() . self::SUFFIX_TYPE . '" value="C" '. ( ($type == 'C') ? 'checked="checked"' : '' ) .' onclick="changeCronExpType(this)"/>&nbsp;' . $this->getLabelCustom();

        $html .= '<p style="line-height:5px">&nbsp;</p>';
		
		$html .= '<div id="default_expr_area" style="display:' . ($type == 'D' ? 'block;' : 'none;') . '">';
		$html .= '<select name="'. $this->getName() . self::SUFFIX_FREQUENCY . '" id="'. $this->getName() . self::SUFFIX_FREQUENCY . '" style="width:100px">'."\n";
        $freqValues = Mage::getModel('adminhtml/system_config_source_cron_frequency')->toOptionArray();
		foreach($freqValues as $freqValue) {
			$html.= '<option value="'.$freqValue['value'].'" '. ( ($frequency == $freqValue['value']) ? 'selected="selected"' : '' ) .'>' . $freqValue['label'] . '</option>';
		}
		$html .= '</select>'."\n";

        $html .= '&nbsp;&nbsp;&nbsp;<select name="'. $this->getName() . self::SUFFIX_HOURS . '" id="'. $this->getName() . self::SUFFIX_HOURS . '" style="width:40px">'."\n";
        for( $i=0;$i<24;$i++ ) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= '<option value="'.$hour.'" '. ( ($value_hrs == $i) ? 'selected="selected"' : '' ) .'>' . $hour . '</option>';
        }
        $html .= '</select>'."\n";

        $html.= '&nbsp;:&nbsp;<select name="'. $this->getName() . self::SUFFIX_MINUTES . '" id="'. $this->getName() . self::SUFFIX_MINUTES . '" style="width:40px">'."\n";
        for( $i=0;$i<60;$i++ ) {
            $minute = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= '<option value="'.$minute.'" '. ( ($value_min == $i) ? 'selected="selected"' : '' ) .'>' . $minute . '</option>';
        }
        $html .= '</select>'."\n";
		$html .= '<p class="note" id="note_accessKey"><span>' . $this->getFrequencyTimeNote() . '</span></p>';
		$html .= '</div>';
		
		$html .= '<div id="custom_expr_area" style="display:' . ($type == 'D' ? 'none;' : 'block;') . '">';
		$html .= '<input type="text" name="'. $this->getName() . self::SUFFIX_EXPRESSION . '" id="'. $this->getName() . self::SUFFIX_EXPRESSION . '" value="' . $expr . '" />';
		$html .= '<p class="note" id="note_accessKey"><span>' . $this->getCronExprNote() . '</span></p>';
		$html .= '</div>';
		
        
		$html.= '<script>'."\n";
		$html.= 'function changeCronExpType(el) {'."\n";
		$html.= '	if(el.value == "D") {';
		$html.= '	document.getElementById("default_expr_area").style.display = "block";'."\n";
		$html.= '	document.getElementById("custom_expr_area").style.display = "none";'."\n";
		$html.= '	} else {'."\n";
		$html.= '	document.getElementById("default_expr_area").style.display = "none";'."\n";
		$html.= '	document.getElementById("custom_expr_area").style.display = "block";'."\n";
		$html.= '	}'."\n";
		$html.= '}'."\n";
		$html.= '</script>'."\n";
		
        $html.= $this->getAfterElementHtml();
        return $html;
    }
}