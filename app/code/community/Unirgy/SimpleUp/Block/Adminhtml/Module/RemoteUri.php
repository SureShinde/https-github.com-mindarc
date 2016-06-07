<?php

class Unirgy_SimpleUp_Block_Adminhtml_Module_RemoteUri extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $uri = isset($row['download_uri']) ? (string)$row['download_uri'] : null;
        $uri .= (strpos($uri, '?') === false ? '?' : '&') . 'php=' . PHP_VERSION;
        if (function_exists('ioncube_loader_version')) {
            $uri .= '&ioncube=' . ioncube_loader_version();
        }
        return $uri ? '<a href="'.$uri.'" title="'.$uri.'">Hover/Click</a>' : '';
    }
}