<?php

/**
 * Master module routes — top-level lookup CRUDs (CI3 `master` module).
 * The master/* group carries adminAuth + fyContext + rbac (Config\Filters).
 * URLs preserved from CI3.
 */

use App\Modules\Master\Controllers\City;
use App\Modules\Master\Controllers\State;
use App\Modules\Master\Controllers\Tax;

$routes->get('master/city/listing', [City::class, 'listing']);
$routes->get('master/city', [City::class, 'listing']);
$routes->post('master/city/listing_data', [City::class, 'listingData']);
$routes->post('master/city/save', [City::class, 'save']);
$routes->post('master/city/delete', [City::class, 'delete']);
$routes->get('master/city/row/(:num)', [City::class, 'row']);

$routes->get('master/state/listing', [State::class, 'listing']);
$routes->get('master/state', [State::class, 'listing']);
$routes->post('master/state/listing_data', [State::class, 'listingData']);
$routes->post('master/state/save', [State::class, 'save']);
$routes->post('master/state/delete', [State::class, 'delete']);
$routes->get('master/state/row/(:num)', [State::class, 'row']);

$routes->get('master/tax/listing', [Tax::class, 'listing']);
$routes->get('master/tax', [Tax::class, 'listing']);
$routes->post('master/tax/listing_data', [Tax::class, 'listingData']);
$routes->post('master/tax/save', [Tax::class, 'save']);
$routes->post('master/tax/delete', [Tax::class, 'delete']);
$routes->get('master/tax/row/(:num)', [Tax::class, 'row']);
