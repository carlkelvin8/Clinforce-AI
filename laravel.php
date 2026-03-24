<?php

/**
 * Laravel passthrough for Hostinger shared hosting.
 *
 * This file lives in public_html/ and forwards /api/* and /auth/*
 * requests to the Laravel app located at ../clinforce-ai/public/index.php
 *
 * Upload this file to: public_html/laravel.php
 */

// Point to the Laravel public directory (one level up from public_html)
$laravelPublic = dirname(__DIR__) . '/clinforce-ai/public';

// Override $_SERVER paths so Laravel sees the original request URI
$_SERVER['SCRIPT_FILENAME'] = $laravelPublic . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

// Change working directory so relative paths inside Laravel resolve correctly
chdir($laravelPublic);

// Bootstrap Laravel
require $laravelPublic . '/index.php';
