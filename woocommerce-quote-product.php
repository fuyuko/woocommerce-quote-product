<?php   
    /* 
    Plugin Name: WooCommerce Quote Product
    Plugin URI: https://github.com/fuyuko/woocommerce-quote-product
    Description: WooCommerce Extension Plugin - Add an option to product to be "call for quote" product, and disable online ordering 
    Author: Fuyuko Gratton 
    Version: 0.5.2
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
    wp_register_style( 'quote_product_stylesheet', plugins_url('assets/stylesheet.css', __FILE__) );
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
            'wrapper_class' => 'show_if_simple show_if_variable', 
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
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - make the quote product column
     **/
    add_filter('manage_edit-product_columns', 'quote_product_into_product_list');
    function quote_product_into_product_list($defaults) {
        $defaults['quote_product_checkbox'] = 'Quote Product';
        return $defaults;
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - fill the quote product data in each row
     **/
    add_action( 'manage_product_posts_custom_column' , 'quote_product_data_into_product_list', 10, 2 );
    function quote_product_data_into_product_list($column, $post_id ){
        switch ( $column ) {
        case 'quote_product_checkbox':
            echo '<span id="quote_product_' . $post_id . '">';
            echo get_post_meta( $post_id , 'quote_product_checkbox' , true );
            echo '</span>';
        break;
        }
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - make the quote product column sortable
     **/
    add_filter( "manage_edit-product_sortable_columns", "sortable_columns" );
    function sortable_columns() {
        return array(
                    'quote_product_checkbox' => 'quote_product_checkbox'
                );
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - define the quote product column sort order
     **/
    add_action( 'pre_get_posts', 'event_column_orderby' );
    function event_column_orderby( $query ) {
        if( ! is_admin() )
            return;
        $orderby = $query->get( 'orderby');
        if( 'quote_product_checkbox' == $orderby ) {
            $query->set('meta_key','quote_product_checkbox');
            $query->set('orderby','meta_value');
        }
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - add the quote product to quick and bulk edit
     **/
    add_action( 'bulk_edit_custom_box', 'add_to_bulk_quick_edit_custom_box', 10, 2 );
    add_action( 'quick_edit_custom_box', 'add_to_bulk_quick_edit_custom_box', 10, 2 );
    function add_to_bulk_quick_edit_custom_box( $column_name, $post_type ) {
        switch ( $post_type ) {
            case 'product':
                switch( $column_name ) {
                    case 'quote_product_checkbox': 
                        echo '<fieldset class="inline-edit-col-left">&nbsp;';
                        echo '</fieldset>';
                        echo '<fieldset class="inline-edit-col-center">&nbsp;';
                        echo '</fieldset>';
                        echo '<fieldset class="inline-edit-col-right">';
                        echo '<div class="inline-edit-column">';
                        echo '<label>';       
                        echo '<input type="checkbox" name="quote_product_checkbox" id="quote_product_checkbox" value="" />';         
                        echo '<span class="checkbox-title">Quote Product</span>';
                        echo '</label>';
                        echo '</div>';
                        echo '</fieldset>';
                        break;
                }
            break;
        }
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - Populate Quote Product “Quick Edit” Data
     **/
    add_action( 'admin_print_scripts-edit.php', 'quote_product_enqueue_edit_scripts' );
    function quote_product_enqueue_edit_scripts() {
       wp_enqueue_script( 'quote-product-admin-edit', plugins_url( 'assets/quick-and-bulk-edit.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), '', true );
    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - Save Quote Product “Quick Edit” Data
     **/
    add_action( 'save_post','quote_product_quick_save_post', 10, 2 );
    function quote_product_quick_save_post( $post_id, $post ) {

       // don't save for autosave
       if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
          return $post_id;

       // dont save for revisions
       if ( isset( $post->post_type ) && $post->post_type == 'revision' )
          return $post_id;

       switch( $post->post_type ) {

            case 'product':

                if ( array_key_exists( 'quote_product_checkbox', $_POST ) ){
                     $woocommerce_checkbox = isset( $_POST['quote_product_checkbox'] ) ? 'yes' : 'no';
                    update_post_meta( $post_id, 'quote_product_checkbox', $woocommerce_checkbox );
                }

                break;
        }

    }

    /**
     * BACKEND WOOCOMMERCE LIST PRODUCTS PAGE - Save Quote Product “Bulk Edit” Data
     **/
    add_action( 'wp_ajax_quote_product_save_bulk_edit', 'quote_product_save_bulk_edit' );
    function quote_product_save_bulk_edit() {
       // get our variables
       $post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
       $woocommerce_checkbox = ( isset( $_POST[ 'woocommerce_checkbox' ] ) && !empty( $_POST[ 'woocommerce_checkbox' ] ) ) ? $_POST[ 'woocommerce_checkbox' ] : 'no';
   // if everything is in order
       // if everything is in order
       if ( !empty( $post_ids ) && is_array( $post_ids ) && !empty( $woocommerce_checkbox ) ) {
          foreach( $post_ids as $post_id ) {
             update_post_meta( $post_id, 'quote_product_checkbox', $woocommerce_checkbox);
          }
       }
       exit;
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
        $addon_product =  !(strcmp( $product->product_type, "addons")) ;
        $variable_product =  !(strcmp( $product->product_type, "variable")) ;

        //if product is purchasable, and is in stock, and not an addon product, and not a quote product then the url of the button is "add to cart" not the single product page
        $url = (($product->is_purchasable()) && ($product->is_in_stock()) && (!$variable_product) && (!$addon_product) && (!$quote_product)) ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $product->id ) ) : get_permalink( $product->id );

        return $url;

    }
            
}

