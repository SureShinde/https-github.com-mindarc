<?php
    header('Content-type: text/css; charset: UTF-8');
    header('Cache-Control: must-revalidate');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    $url = $_REQUEST['url'];
?>


.pt_custommenu {
    border-radius: 5px;
}

.item-inner .actions button.btn-cart span, .item-inner .add-to-links li a {
    border-radius: 4px;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
}
button.button span, .wrapper_box #continue_shopping, .wrapper_box #shopping_cart {
    border-radius: 3px;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
}
.item-inner .actions, .banner-static-2 .f-col h2 {
    transition: all 0.2s ease-out;
    -webkit-transition: all 0.2s ease-out;
    -moz-transition: all 0.2s ease-out;
}

.block-sequence  .block-inner:hover img, .banner-static .banner-box:hover img { 
	opacity: 0.7;
	-webkit-opacity: 0.7;
	-moz-opacity: 0.7;
}
.block-sequence  .block-inner:hover img, .banner-static .banner-box:hover img {
    transition: all 0.3s ease-out;
    -webkit-transition: all 0.3s ease-out;
    -moz-transition: all 0.3s ease-out;
}