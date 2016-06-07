<?php
/**
 * MagePlace Gallery Extension
 *
 * @category    Mageplace_Gallery
 * @package     Mageplace_Gallery
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Block_Adminhtml_Widget_Form_Element_Dependence
 */
class Mageplace_Backup_Block_Adminhtml_Widget_Form_Element_Dependence extends Mage_Adminhtml_Block_Abstract
{
    private static $_isJsDisplayed = false;

    protected $_fields = array();
    protected $_depends = array();
    protected $_configOptions = array();

    /**
     * Add name => id mapping
     *
     * @param string $fieldId   - element ID in DOM
     * @param string $fieldName - element name in their fieldset/form namespace
     *
     * @return $this
     */
    public function addFieldMap($fieldId, $fieldName)
    {
        $this->_fields[$fieldName] = $fieldId;

        return $this;
    }

    /**
     * Register field name dependence one from each other by specified values
     *
     * @param string       $fieldName
     * @param string       $fieldNameFrom
     * @param string|array $refValues
     *
     * @return $this
     */
    public function addFieldDependence($fieldName, $fieldNameFrom, $refValues)
    {
        $this->_depends[$fieldName][$fieldNameFrom] = $refValues;

        return $this;
    }

    /**
     * Add misc configuration options to the javascript dependencies controller
     *
     * @param array $options
     *
     * @return $this
     */
    public function addConfigOptions(array $options)
    {
        $this->_configOptions = array_merge($this->_configOptions, $options);

        return $this;
    }

    /**
     * HTML output getter
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_depends) {
            return '';
        }

        $js = '';
        if(Mage::helper('mpbackup/version')->isNoMultiDependence() && !$this->_isJsDisplayed) {
            $js .= "
            FormElementDependenceController.addMethods({
                initialize : function (elementsMap, config)
                {
                    if (config) {
                        this._config = config;
                    }
                    for (var idTo in elementsMap) {
                        for (var idFrom in elementsMap[idTo]) {
                            if ($(idFrom)) {
                                Event.observe($(idFrom), 'change', this.trackChange.bindAsEventListener(this, idTo, elementsMap[idTo]));
                                this.trackChange(null, idTo, elementsMap[idTo]);
                            } else {
                                this.trackChange(null, idTo, elementsMap[idTo]);
                            }
                        }
                    }
                },

                trackChange : function(e, idTo, valuesFrom)
                {
                    // define whether the target should show up
                    var shouldShowUp = true;
                    for (var idFrom in valuesFrom) {
                        var from = $(idFrom);
                        if (valuesFrom[idFrom] instanceof Array) {
                            if (!from || valuesFrom[idFrom].indexOf(from.value) == -1) {
                                shouldShowUp = false;
                            }
                        } else {
                            if (!from || from.value != valuesFrom[idFrom]) {
                                shouldShowUp = false;
                            }
                        }
                    }

                    // toggle target row
                    if (shouldShowUp) {
                        var currentConfig = this._config;
                        $(idTo).up(this._config.levels_up).select('input', 'select', 'td').each(function (item) {
                            // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                            if ((!item.type || item.type != 'hidden') && !($(item.id+'_inherit') && $(item.id+'_inherit').checked)
                                && !(currentConfig.can_edit_price != undefined && !currentConfig.can_edit_price)) {
                                item.disabled = false;
                            }
                        });
                        $(idTo).up(this._config.levels_up).show();
                    } else {
                        $(idTo).up(this._config.levels_up).select('input', 'select', 'td').each(function (item){
                            // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                            if ((!item.type || item.type != 'hidden') && !($(item.id+'_inherit') && $(item.id+'_inherit').checked)) {
                                item.disabled = true;
                            }
                        });
                        $(idTo).up(this._config.levels_up).hide();
                    }
                }
            });\n";

            $this->_isJsDisplayed = true;
        }

        return '<script type="text/javascript">'
        . $js
        . ' new FormElementDependenceController('
        . $this->_getDependsJson()
        . ($this->_configOptions ? ', ' . Mage::helper('core')->jsonEncode($this->_configOptions) : '')
        . ');'
        . '</script>'
        . $this->getAdditionalHtml();

    }

    /**
     * Field dependences JSON map generator
     *
     * @return string
     */
    protected function _getDependsJson()
    {
        $result = array();
        foreach ($this->_depends as $to => $row) {
            foreach ($row as $from => $value) {
                $result[$this->_fields[$to]][$this->_fields[$from]] = $value;
            }
        }

        return Mage::helper('core')->jsonEncode($result);
    }
}
