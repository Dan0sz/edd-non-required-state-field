<?php
/**
 * Plugin Name: Easy Digital Downloads - Non Required State Field
 * Description: Dynamically detect if State should be a required field, based on the selected Country.
 * Version: 1.0.1
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * GitHub Plugin URI: Dan0sz/non-required-state-field
 * Primary Branch: master
 * License: MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

$edd_non_req_state_field = new \Daan\EDD\NonReqStateField\Plugin();