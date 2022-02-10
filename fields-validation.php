<?php
/*
Plugin Name: Wp All import fields validation
Plugin URI: https://petoasisksa.com
Description: This is a simple addon for WP all import plugin is used to validate the fields before the import and skip the wrong fields.
Author: Saleem Summour
Version: 1.0.0
Author URI: https://lvendr.com/
*/




/*
 * Import log function
 */
function custom_import_log( $entry, $mode = 'a', $file = 'plugin' ) {
    // Get WordPress uploads directory.
    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['basedir'];

    // If the entry is array, json_encode.
    if ( is_array( $entry ) ) {
        $entry = json_encode( $entry );
    }

    // Write the log file.
    $file  = $upload_dir . '/import-log/' . $file . '.txt';
    $file  = fopen( $file, $mode );
    $bytes = fwrite( $file, "Skipped Products::" . $entry . "\n" );
    fclose( $file );

    return $bytes;
}
/*
 * array of categories names
 */
function array_of_products_categories_names(){
    $terms = get_terms( array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ) );
    $cat_names=array();
    foreach($terms as $term){
        array_push($cat_names,$term->name);
    }
    return $cat_names;
}

/**
 * Validate price in the import
 */
function validate_price_in_the_import( $continue_import, $data, $import_id ) {
    if ( !empty($data['regularprice']) ) {
        return true;
    }
    else {
        custom_import_log('SKU: '.$data['baracode']." - Title:".$data['titleen'].':: Check Prices','a',$import_id);
        return false;
    }


}
add_filter('wp_all_import_is_post_to_create', 'validate_price_in_the_import', 10, 3);

/**
 * Validate Category in the import
 */
function validate_category_in_the_import( $continue_import, $data, $import_id ) {
    $ar_cat=explode(" > ",$data['catagoryar']);
    $en_cat=explode(" > ",$data['catagoryen']);

    if ( count(array_diff($ar_cat, array_of_products_categories_names()))==0) {
        return true;
    }
    else {
        custom_import_log('SKU: '.$data['baracode']." - Title:".$data['titleen'].':: Check Categories','a',$import_id);
        return false;
    }


}
add_filter('wp_all_import_is_post_to_create', 'validate_category_in_the_import', 10, 3);


/**
 * Skip repeated import
 */
function skip_repeated_import( $continue_import, $data, $import_id ) {

    if ( get_page_by_title($data['titleen'], OBJECT, 'product')) {
        return false;
        custom_import_log('SKU: '.$data['baracode']." - Title:".$data['titleen'].':: Repeated Product','a',$import_id);

    }
    else {
        return true;

    }


}
add_filter('wp_all_import_is_post_to_create', 'skip_repeated_import', 10, 3);

/**
 * Restrict import to products
 */
add_action( 'pmxi_before_xml_import', 'restrict_import_to_products', 10, 1 );
function restrict_import_to_products( $importID ) {
    $import = new PMXI_Import_Record();
    $import->getById($importID);
    $post_type = $import->options['custom_type'];
    if ( $post_type!="product") {
        // reset import data
        $import->set( array(
            'queue_chunk_number' => 0,
            'processing' => 0,
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'triggered' => 0,
            'executing' => 0
        ))->update();
        echo 'Import skipped because you did not select the wocoomerce product as a post type';
        // stop import processing
        die();
    }


}


function change_import_log_url() {
    if ($_GET['page'] == 'pmxi-admin-manage' || $_GET['page'] == 'pmxi-admin-import') {
        ?>
        <script>

            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;

                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');

                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                    }
                }
                return false;
            }

            jQuery(document).ready(function(){
                var href = jQuery("#download_log").attr('href');
                var log_id = href.substr(href.indexOf("id=") + 3);
                jQuery("#download_log").prop("href", "<?php echo get_site_url();?>/wp-content/uploads/import-log/"+log_id+".txt")
            });


        </script>
        <?php
    }
}

add_filter('admin_head', 'change_import_log_url');
