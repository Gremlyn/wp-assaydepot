<?php
/*
Plugin Name: Assay Depot API Plugin
Plugin URI: https://github.com/Gremlyn/wp-assaydepot
Description: A plugin that allows the user to access information from
             the Assay Depot system and display it on a WordPress
             site. it is built using assaydepot-php, the PHP SDK for
             Assay Depot's JSON API.
Version: 0.0
Author: Colin Burton
Author URI: https://plus.google.com/u/0/117991393503416337615
License: MIT LICENSE
*/

require_once('adphp/lib/assaydepot.php');

$page = (get_query_var('page')) ? get_query_var('page') : '';
if (!is_int($page) && $page != '') {
    die('Non-integer specified for page number!');
}

// Set config variables for class
$access_token = '';
$url = 'https://www.assaydepot.com/api';

// Instantiate the class
$ad_api = new assaydepot($access_token, $url);

/**
 * Results Processing and Display Functions
 */
function ad_search_results($args) {
    extract( shortcode_atts( array(
            'type' => '',
            'page' => $page,
            'per_page' => '',
            'sort_by' => '',
            'sort_order' => '',
            'query' => ''
    ), $args) );

    // Instantiate the class
    $ad_api = new assaydepot($access_token, $url);


    // Set options, if they exist
    ($args['page'] != '') ? $ad_api->option_set('page', $args['page']) : NULL;
    ($args['per_page'] != '') ? $ad_api->option_set('per_page', $args['per_page']) : NULL;
    ($args['sort_by'] != '') ? $ad_api->option_set('sort_by', $args['sort_by']) : NULL;
    ($args['sort_order'] != '') ? $ad_api->option_set('sort_order', $args['sort_order']) : NULL;

    // Pass required args to search (builds the search URL, doesn't perform it)
    $ad_api->search($args['type'], $args['query']);

    // Make API call and receive back associative array with results
    $search_output = $ad_api->json_output();

}
add_shortcode('ad_search_results', 'ad_search_results');

?>