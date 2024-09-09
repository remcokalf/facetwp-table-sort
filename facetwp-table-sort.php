<?php
/*
Plugin Name: FacetWP - Table Sort
Description: Table Sort facet type
Version: 0.1
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-table-sort
*/

defined( 'ABSPATH' ) or exit;

define( 'FACETWP_TABLE_SORT_VERSION', '0.1' );
define( 'FACETWP_TABLE_SORT_URL', plugins_url( '', __FILE__ ) );


/**
 * FacetWP registration hook
 */
add_filter( 'facetwp_facet_types', function( $types ) {
    include( dirname( __FILE__ ) . '/class-table-sort.php' );
    $types['table_sort'] = new FacetWP_Facet_Table_Sort_Addon();
    return $types;
} );
