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
require_once('assaydepot-php/lib/assaydepot.php');

require_once('PHP-OAuth2/Client.php');
require_once('PHP-OAuth2/GrantType/IGrantType.php');
require_once('PHP-OAuth2/GrantType/ClientCredentials.php');

/**
 * Make the connection to the API.
 */
$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET, 1);
$params = array();
$response = $client->getAccessToken(TOKEN_ENDPOINT, 'client_credentials', $params);

define('ACCESS_TOKEN', $response['result']['access_token']);

/**
 * Results Processing and Display Functions
 */
function ad_search_results($args) {
    // Set variables from query string, and sanitise while we're at it.
    $page = (get_query_var('page')) ? get_query_var('page') : '';
    if (!is_int($page) && $page != '') {
        $page = '';
    }

    $query = (isset($_GET['queryad'])) ? $_GET['queryad'] : '';
    $query = sanitize_text_field($query);

    extract( shortcode_atts( array(
            'type' => '',
            'page' => $page,
            'per_page' => 10,
            'sort_by' => '',
            'sort_order' => '',
            'query' => $query,
            'facets' => '',
            'search' => TRUE
    ), $args) );

    // Instantiate the class
    $assaydepot = new assaydepot(ACCESS_TOKEN, AD_URL);

    // Set options, if they exist
    ($page != '') ? $assaydepot->option_set('page', $page) : NULL;
    ($per_page != '') ? $assaydepot->option_set('per_page', $per_page) : NULL;
    ($sort_by != '') ? $assaydepot->option_set('sort_by', $sort_by) : NULL;
    ($sort_order != '') ? $assaydepot->option_set('sort_order', $sort_order) : NULL;

    // Handle the facets, if any, and set them
    $facets = ($facets != '') ? explode(';', $facets) : '';
    if (is_array($facets)) {
        foreach ($facets as $facet) {
            $facet = explode('=', $facet);
            $assaydepot->facet_set($facet[0], $facet[1]);
        }
    }

    // Pass required args to search (builds the search URL, doesn't perform it)
    $assaydepot->search($type, $query);

    // Make API call and receive back associative array with results
    $json = $assaydepot->json_output();

    switch($args['type']) {
        case 'providers':
            $type_ref = 'provider_refs';
            break;
        case 'wares':
            $type_ref = 'ware_refs';
            break;
    }

    // Do we want the search form? Build it if so
    if ($search) {
        $search_output = '<div style="max-width: 75%; text-align: right;">';
        if ($query != '') {
            $search_output .= '<div style="max-width: 50%; float: left;">';
            $search_output .= '<p style="font-style: italic; text-align: left;">';
            $search_output .= 'Your search for "'.$query.'" returned '.$json['total'].' results.</p>';
            $search_output .= '</div>';
        }
        $search_output .= '<form method="get" action="'.get_permalink().'" style="max-width: 50%; float:right">';
        $search_output .= '<input type="text" placeholder="Enter Search Term(s)..." name="queryad"/><br />';
        $search_output .= '<input type="submit" value="submit" name="submit" />';
        $search_output .= '</form>';
        $search_output .= '</div>';
        $search_output .= '<div style="clear:both;"></div>';
    } else {
        $search_output = '';
    }

    /*echo "<pre>";
    print_r($json);
    echo "</pre>";*/

    // Create the Output
    $output = $search_output;
    $output .= '<ul style="list-style-type: none; margin: 0; max-width: 75%">';
    foreach ($json[$type_ref] as $arr) {
        if (isset($arr['providers'])) {
            $provider_count = count($arr['providers']);

            if ($provider_count > 1) {
                $provider_output = '<p style="font-size: 85%; font-weight: bold;">'.$provider_count.' Providers</p>';
            } else {
                $provider_output = '<p style="font-size: 85%; font-weight: bold;">Provider: '.$arr['providers'][0]['name'].'</p>';
            }
        } else {
            $provider_output = '';
        }

        $output .= '<li style="padding: 5px;">';
        $output .= '<h3>'.$arr['name'].'</h3>';
        $output .= '<p>'.$arr['snippet'].'</p>';
        $output .= $provider_output;
        $output .= '</li>';
    }
    $output .= '</ul>';

    // How many pages do we have? Do we need to paginate?
    $total_pages = ceil($json['total']/$json['per_page']);
    if ($total_pages > 1) {
        $output .= '<div style="max-width: 75%;">';
        if ($json['page'] != 1) {
            $output .= '<div style="max-width: 25%; float: left;">';
            $output .= '<a href="'.get_permalink().'?queryad='.$query.'&page='.($json['page']-1).'">';
            $output .= '&laquo; Previous';
            $output .= '</a>';
            $output .= '</div>';
        }
        if ($json['page'] != $total_pages) {
            $output .= '<div style="max-width: 25%; float: right;">';
            $output .= '<a href="'.get_permalink().'?queryad='.$query.'&page='.($json['page']+1).'">';
            $output .= 'Next &raquo;';
            $output .= '</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
    }
    if ($total_pages > 0) {
        $output .= '<div style="max-width: 75%; text-align: center; clear: both;">';
        $output .= 'Page '.$json['page'].' of '.$total_pages;
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('assaydepot', 'ad_search_results');

?>