<?php
class Extendware_EWReviewReminder_Model_Email_Template_Filter extends Mage_Core_Model_Email_Template_Filter
{
	const CONSTRUCTION_FOREACH_PATTERN = '/{{foreach\s*(.*?)\s*as\s*(.*?)}}(.*?){{\\/foreach\s*}}/si';
	
	public function filter($value)
    {
        // "depend" and "if" operands should be first
        foreach (array(
            self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective',
            self::CONSTRUCTION_IF_PATTERN     => 'ifDirective',
            self::CONSTRUCTION_FOREACH_PATTERN => 'foreachDirective',
            ) as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach($constructions as $index => $construction) {
                    $replacedValue = '';
                    $callback = array($this, $directive);
                    if(!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }

        if(preg_match_all(self::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach($constructions as $index=>$construction) {
                $replacedValue = '';
                $callback = array($this, $construction[1].'Directive');
                if(!is_callable($callback)) {
                    continue;
                }
                try {
					$replacedValue = call_user_func($callback, $construction);
                } catch (Exception $e) {
                	throw $e;
                }
                $value = str_replace($construction[0], $replacedValue, $value);
            }
        }
        return $value;
    }
    
	public function foreachDirective($construction) {
		if (count($this->_templateVars) == 0) {
			return $construction[0];
		}
	
		$result = '';
		$variable = $this->_getVariable($construction[1]);
		if(!is_array($variable) and !$variable instanceof Varien_Data_Collection) {
			return $result;
		} else {
			if (isset($this->_templateVars[$construction[2]])) {
				$originValue = $this->_templateVars[$construction[2]];
			}
			
			foreach ($variable as $item) {
				$this->_templateVars[$construction[2]] = $item;
				$result .= $this->filter($construction[3]);
			}
			
			if (isset($originValue)) {
				$this->_templateVars[$construction[2]] = $originValue;
			} else {
				unset($this->_templateVars[$construction[2]]);
			}

			return $result;
		}
	}
}