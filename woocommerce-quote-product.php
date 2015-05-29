<?php   
    /* 
    Plugin Name: WooCommerce Quote Product
    Plugin URI: https://github.com/fuyuko/woocommerce-quote-product
    Description: WooCommerce Extension Plugin - Add an option to product to be "call for quote" product, and disable online ordering 
    Author: Fuyuko Gratton 
    Version: 0.1
    Author URI: http://fuyuko.net/
    */ 

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}


//activation setup
function woocommerce_quote_product_activate(){
    //define the quote product text to display
    update_option('woocommerce_quote_product_text', 'Please Call 800-570-6890 To Order');
} 
register_activation_hook( __FILE__, 'woocommerce_quote_product_activate' );

//deactivation setup
function woocommerce_quote_product_deactivate(){
   delete_option('woocommerce_quote_product_text');
} 
register_deactivation_hook( __FILE__, 'woocommerce_quote_product_deactivate' );

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
   

    // Display Fields
    function woo_add_custom_general_fields() {
 
        global $woocommerce, $post;
          
        echo '<div class="options_group">';
      
        // Checkbox
        woocommerce_wp_checkbox( 
        array( 
            'id'            => 'quote_product_checkbox', 
            'wrapper_class' => 'show_if_simple', 
            'label'         => __('Quote Product', 'woocommerce' ), 
            'description'   => __( 'Display request quote message and disable add to cart button for this product', 'woocommerce' ) 
            )
        );
      
        echo '</div>';
            
    }
    add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );

    // Save Fields
    function woo_add_custom_general_fields_save( $post_id ){
        // Checkbox
        $woocommerce_checkbox = isset( $_POST['quote_product_checkbox'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, 'quote_product_checkbox', $woocommerce_checkbox );
    }
    add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );

    
    /*
        The normal WooCommerce template loader searches the following locations in order, until a match is found:

        your theme / template path / template name
        your theme / template name
        default path / template name

        We’re going to alter this slightly by injecting a search for the template within our own custom plugin (step 3 below), before finally defaulting to the WooCommerce core templates directory:

        your theme / template path / template name
        your theme / template name
        your plugin / woocommerce / template name
        default path / template name


    */
    function myplugin_plugin_path() { // gets the absolute path to this plugin directory
        return untrailingslashit( plugin_dir_path( __FILE__ ) );    
    }   
     
    function myplugin_woocommerce_locate_template( $template, $template_name, $template_path ) {
         
        global $woocommerce;
         
        $_template = $template;
         
        if ( ! $template_path ) $template_path = $woocommerce->template_url;
         
        $plugin_path  = myplugin_plugin_path() . '/woocommerce/';
         
        // Look within passed path within the theme - this is priority
        $template = locate_template( 
            array(
                $template_path . $template_name,
                $template_name
            )
        );
         
    

        // Modification: Get the template from this plugin, if it exists
        if ( ! $template && file_exists( $plugin_path . $template_name ) ){
            $template = $plugin_path . $template_name; 
        }
         
        // Use default template
        if ( ! $template ) $template = $_template;
         
        // Return what we found
        return $template;   
    }
    add_filter( 'woocommerce_locate_template', 'myplugin_woocommerce_locate_template', 10, 3 );

    /**
     * custom_woocommerce_template_loop_add_to_cart_text
    */
    function custom_woocommerce_product_add_to_cart_text() {
        global $product;

        //quote product
        $quote_product =  !(strcmp( get_post_meta( $product->id, 'quote_product_checkbox', true ), "yes")) ;

        if($quote_product){
            return __( 'Read more', 'woocommerce' );
        }

        if( !$product->is_purchasable() || !$product->is_in_stock()){
            return __( 'Read more', 'woocommerce' );
        }

        $product_type = $product->product_type;
                
        switch ( $product_type ) {
            case 'external':
                return __( 'Buy product', 'woocommerce' );
            break;
            case 'grouped':
                return __( 'View products', 'woocommerce' );
            break;
            case 'simple':
                return __( 'Add to cart', 'woocommerce' );
            break;
            case 'variable':
                return __( 'Select options', 'woocommerce' );
            break;
            default:
                return __( 'Read more', 'woocommerce' );
        }
    }
    add_filter( 'woocommerce_product_add_to_cart_text' , 'custom_woocommerce_product_add_to_cart_text' );

    /**
     * custom_woocommerce_template_loop_add_to_cart_url
    */
    function custom_woocommerce_product_add_to_cart_url() {
        global $product;

        //quote product
        $quote_product =  !(strcmp( get_post_meta( $product->id, 'quote_product_checkbox', true ), "yes")) ;

        $url = (($product->is_purchasable()) && ($product->is_in_stock()) && (!$quote_product)) ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $product->id ) ) : get_permalink( $product->id );

        return $url;

    }
    add_filter( 'woocommerce_product_add_to_cart_url', 'custom_woocommerce_product_add_to_cart_url');
        
}

