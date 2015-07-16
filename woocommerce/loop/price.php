<?php
/**
 * Loop Price
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

//quote product
$quote_product =  !(strcmp( get_post_meta( $product->id, 'quote_product_checkbox', true ), "yes"));
	if($quote_product){
?>
		<span class="price"><?php echo get_option('woocommerce_quote_product_text'); ?></span>
<?php	
	}elseif ( $price_html = $product->get_price_html() ){ ?>
	<span class="price"><?php echo $price_html; ?></span>
<?php } ?>
