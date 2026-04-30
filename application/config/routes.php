<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

//Authentication API Routes
$route['api/auth/register'] = 'auth/register';
$route['api/auth/login']    = 'auth/login';
$route['api/auth/verify'] = 'auth/verify';
$route['api/auth/logout']          = 'auth/logout';
$route['api/auth/forgot-password'] = 'auth/forgot_password';
$route['api/auth/reset-password']  = 'auth/reset_password';

//Profile routes (Protected)
$route['api/profile']        = 'profile/index';  //GET request
$route['api/profile/update'] = 'profile/update'; //POST/PUT request

$route['api/profile/upload-image'] = 'profile/upload_image';
$route['api/profile/degree']       = 'profile/add_degree'; //POST
$route['api/profile/degree/(:num)']= 'profile/delete_degree/$1'; //DELETE

//Qualifications routes
$route['api/profile/qualification']       = 'profile/add_qualification'; //POST
$route['api/profile/qualification/(:num)']= 'profile/delete_qualification/$1'; //DELETE

//Employment routes
$route['api/profile/employment']          = 'profile/add_employment'; //POST
$route['api/profile/employment/(:num)']   = 'profile/delete_employment/$1'; //DELETE

//Bidding System routes
$route['api/bids']       = 'bid/index'; //GET
$route['api/bids/place'] = 'bid/place'; //POST

//Public Featured Alumni routes
$route['api/featured']        = 'featured/index'; 
$route['api/featured/(:any)'] = 'featured/index/$1';

//Client Endpoints
$route['api/ar/alumni-of-day']    = 'ar_app/get_alumni_of_day';
$route['api/analytics/dashboard'] = 'analytics/dashboard_data';
$route['api/analytics/alumni']    = 'analytics/alumni_list';