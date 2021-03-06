<?php

// Prevent direct requests to this file due to security reasons
defined('APP_INIT') or die('Access denied!');

// Return the config array
return Array(
    'general' => Array(
        'site_url'          => 'http://localhost/',
        'site_path'         => ''
    ),

    'database' => Array(
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'barapp',
        'user' => 'barapp',
        'password' => 'hoihoi',
        'table_prefix' => 'bar_'
    ),

    'cookie' => Array(
        'domain' => '',
        'path' => '/',
        'prefix' => 'carbon_'
    ),

    'hash' => Array(
        'algorithm' => 'sha256',
        'salt'  => '7cc8b7833dba1a03dd6d1401aa25262fab029862b494aff2168f1a6b35f1f406'
    ),

    'app' => Array(
        // TODO: Disable debug mode on release!
        'debug' => true,
        'language.directory' => '/language'
    ),

    'mail' => Array(
        'sender' => 'noreply@local.timvisee.com'
    )
);
