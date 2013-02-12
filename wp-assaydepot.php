<?php
/*
Plugin Name: Assay Depot API Plugin
Plugin URI: https://github.com/Gremlyn/wp-assaydepot
Description: A plugin that allows the user to access information from
             the Assay Depot system and display it on a WordPress
             site. it is built using assaydepot-php, the PHP SDK for
             Assay Depot's JSON API.
Version: 0.1
Author: Colin Burton
Author URI: https://plus.google.com/u/0/117991393503416337615
License: MIT LICENSE
*/

require_once('ad-config.php');
require_once('adphp/lib/assaydepot.php');

require_once('PHP-OAuth2/Client.php');
require_once('PHP-OAuth2/GrantType/IGrantType.php');
require_once('PHP-OAuth2/GrantType/ClientCredentials.php');

/**
 * Make the connection to the API.
 */
$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET, 1);
$params = array();
$response = $client->getAccessToken(TOKEN_ENDPOINT, 'client_credentials', $params);
$access_token = $response['result']['access_token'];

/**
 * Set variables from query string, and sanitise while we're at it.
 */
$page = (get_query_var('page')) ? get_query_var('page') : '';
if (!is_int($page) && $page != '') {
    $page = '';
}

$queryad = (get_query_var('queryad')) ? get_query_var('queryad') : '';
$queryad = sanitize_text_field($queryad);

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
            'query' => $queryad
    ), $args) );

    // Instantiate the class
    $assaydepot = new assaydepot($access_token, AD_URL);

    // Set options, if they exist
    ($args['page'] != '') ? $assaydepot->option_set('page', $args['page']) : NULL;
    ($args['per_page'] != '') ? $assaydepot->option_set('per_page', $args['per_page']) : NULL;
    ($args['sort_by'] != '') ? $assaydepot->option_set('sort_by', $args['sort_by']) : NULL;
    ($args['sort_order'] != '') ? $assaydepot->option_set('sort_order', $args['sort_order']) : NULL;

    // Pass required args to search (builds the search URL, doesn't perform it)
    $assaydepot->search($args['type'], $args['query']);

    // Make API call and receive back associative array with results
    $search_output = $assaydepot->json_output();

}
add_shortcode('ad_search_results', 'ad_search_results');

?>