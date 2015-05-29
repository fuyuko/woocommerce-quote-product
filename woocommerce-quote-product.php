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


/**
 * Activation Setup
 **/
register_activation_hook( __FILE__, 'woocommerce_quote_product_activate' );
function woocommerce_quote_product_activate(){
    //define the quote product text to display
    update_option('woocommerce_quote_product_text', 'Please Contact Us To Order This Product');
} 


/**
 * Deactivation Setup
 **/
register_deactivation_hook( __FILE__, 'woocommerce_quote_product_deactivate' );
function woocommerce_quote_product_deactivate(){
   delete_option('woocommerce_quote_product_text');
} 


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {


    /**
     * BACKEND INSTALLED PLUGIN PAGE - additional links below the plugin title
     **/
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'quote_product_plugin_action_links' );
    function quote_product_plugin_action_links( $links ) {
       $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=products&section=quote_product') ) .'">Settings</a>';
       return $links;
    }

    /**
     * BACKEND WOOCOMMERCE SETTING PAGE - Create the section beneath the products tab
     **/
    add_filter( 'woocommerce_get_sections_products', 'quote_product_add_section' );
    function quote_product_add_section( $sections ) {
        
        $sections['quote_product'] = __( 'Quote Product', 'text-domain' );
        return $sections;
        
    }

    /**
     * BACKEND WOOCOMMERCE SETTING PAGE - List of Settings for Quote Product
     **/
    add_filter( 'woocommerce_get_settings_products', 'quote_product_all_settings', 10, 2 );
    function quote_product_all_settings( $settings, $current_section ) {

        /**
         * Check the current section is what we want
         **/

        if ( $current_section == 'quote_product' ) {

            $settings_quote_product = array();

            // Add Title to the Settings
            $settings_quote_product[] = array( 'name' => __( 'Quote Product Settings', 'text-domain' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure default quote product settings', 'text-domain' ), 'id' => 'quote_product' );

            // Add first checkbox option
            $settings_quote_product[] = array(

                'name'     => __( 'Defaut Quote Product Message', 'text-domain' ),
                'id'       => 'woocommerce_quote_product_text',
                'type'     => 'text',
                'css'      => 'min-width:300px;',
                'desc'     => __( 'This text will replace "add to cart" button in single product page.', 'text-domain' ),

            );
          
            $settings_quote_product[] = array( 'type' => 'sectionend', 'id' => 'quote_product' );

            return $settings_quote_product;

        } else { //If not, return the standard settings

            return $settings;

        }

    }
   
    /**
     * BACKEND WOOCOMMERCE EDIT PRODUCT PAGE - Put quote product activation checkbox in Product -> General Tab
     **/
    add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );
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
    

    /**
     * BACKEND WOOCOMMERCE EDIT PRODUCT PAGE - Save Quote Product Checkbox in the product edit page
     */
    add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );
    function woo_add_custom_general_fields_save( $post_id ){
        // Checkbox
        $woocommerce_checkbox = isset( $_POST['quote_product_checkbox'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, 'quote_product_checkbox', $woocommerce_checkbox );
    }
    

    
    /**
    * The normal WooCommerce template loader searches the following locations in order, until a match is found:
    *  1. your theme / template path / template name
    *  2. your theme / template name
    *  3. default path / template name
    *
    * The new order of search will be:
    *  1. your theme / template path / template name
    *  2. your theme / template name
    *  3. your plugin / woocommerce / template name
    *  4. default path / template name
    **/
    add_filter( 'woocommerce_locate_template', 'myplugin_woocommerce_locate_template', 10, 3 );
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
    

    /**
     * FUNCTION OVERWRITE - custom_woocommerce_template_loop_add_to_cart_text
    */
    add_filter( 'woocommerce_product_add_to_cart_text' , 'custom_woocommerce_product_add_to_cart_text' );
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
    

    /**
     * FUNCTION OVERWRITE - custom_woocommerce_template_loop_add_to_cart_url
    */
    add_filter( 'woocommerce_product_add_to_cart_url', 'custom_woocommerce_product_add_to_cart_url');
    function custom_woocommerce_product_add_to_cart_url() {
        global $product;

        //quote product
        $quote_product =  !(strcmp( get_post_meta( $product->id, 'quote_product_checkbox', true ), "yes")) ;

        $url = (($product->is_purchasable()) && ($product->is_in_stock()) && (!$quote_product)) ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $product->id ) ) : get_permalink( $product->id );

        return $url;

    }
            
}

