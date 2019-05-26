<?php

use Illuminate\Http\Request;

Route::post('api/user/register', 'RegisterController@register');
Route::post('api/user/login', 'LoginController@login');
Route::get('api/user/verify/{verification_code}', 'RegisterController@verifyUser');
Route::post('api/user/forgot_password', 'LoginController@forgotPassword');
Route::post('api/user/verify_token', 'LoginController@verifyPassword');
Route::post('api/user/reset_password', 'LoginController@resetPassword');

Route::post('api/find_restaurant','RestaurantController@search_restaturant');
Route::post('api/restaurant/owner_registration','RegisterController@RestaurantOwnerSignUp');

Route::get('api/get_today_special_old/{lat}/{lng}','RestaurantSpecialController@get_today_special_old');
Route::get('api/get_today_special/{lat}/{lng}/{page_no}','RestaurantSpecialController@get_today_special');
Route::get('api/search_today_special/{lat}/{lng}/{page_no}/{search}','RestaurantSpecialController@search_today_special');
Route::get('api/filter_today_special/{lat}/{lng}/{page_no}/{min_price}/{max_price}','RestaurantSpecialController@filter_today_special');
Route::get('api/today_special_combined_search/{lat}/{lng}/{page_no}/{search}/{min_price}/{max_price}','RestaurantSpecialController@filter_and_search_today_special');
Route::post('api/get_today_advanced_search','RestaurantSpecialController@get_today_advanced_search');
Route::get('api/search_food/{lat}/{lng}/{page_no}/{city_name}/{zip_code}/{food_name}','RestaurantSpecialController@search_food');
Route::get('api/find_special/{lat}/{lng}/{page_no}','RestaurantSpecialController@find_special_new');
Route::get('api/search_find_special/{lat}/{lng}/{page_no}/{search}','RestaurantSpecialController@search_find_special');
Route::get('api/filter_find_special/{lat}/{lng}/{page_no}/{min_price}/{max_price}','RestaurantSpecialController@filter_find_special');
Route::get('api/find_special_combined_search/{lat}/{lng}/{page_no}/{search}/{min_price}/{max_price}','RestaurantSpecialController@filter_and_search_find_special');
Route::post('api/get_find_special_advanced_search','RestaurantSpecialController@get_find_special_advanced_search');
Route::get('api/special_show/{rest_id}','RestaurantSpecialController@get_restaurant_special_list');
Route::get('api/get_special/{spe_id}','RestaurantSpecialController@get_special');
Route::get('api/special_show','RestaurantSpecialController@get_all_special');
Route::get('api/get_restaurant_special/{spe_id}/{rest_id}','RestaurantSpecialController@get_restaurant_special');
Route::get('api/get_restaurant_details/{spe_id}/{rest_id}','RestaurantSpecialController@get_restaurant_details');

Route::get('api/special/review_list/{rest_id}', 'RestaurantReviewsController@reviewList');
Route::get('api/special/review_image_list/{rest_id}', 'RestaurantReviewsController@reviewImageList');

Route::get('api/restaurant_show/{rest_id}','RestaurantController@show');
Route::get('api/restaurant_show','RestaurantController@show_all');
Route::get('api/resturant_info/{rest_id}','RestaurantController@show_restaurant_info');
Route::get('api/get_city/{city_name}','RestaurantController@get_city');
Route::get('api/show_logo_banner/{rest_id}','RestaurantController@show_logo_banner');

Route::get('api/get_category_list/{rest_id}','RestaurantMenuCategoryController@get_category_list');
Route::get('api/get_catagory_for_all_restaurant','RestaurantMenuCategoryController@show_all');

Route::get('api/restaurant_menu_list/{rest_id}','RestaurantMenuController@get_menu_list');
Route::get('api/get_menu_with_restaurant_details/{rest_id}','RestaurantMenuController@get_menu_with_restaurant_details');
Route::get('api/get_menu_for_all_restaurant','RestaurantMenuController@show_all');

Route::middleware('auth:api')->get('api/user', function (Request $request) {
    return $request->user();
});

Route::middleware('jwt.auth')->get('api/user_info', function(Request $request) {
    return auth()->user();
});

Route::group(['middleware' => ['jwt.auth']], function() {
	Route::post('api/user/edit_user', 'UserController@edit_user_info');
	Route::post('api/user/update_user_status/{id}', 'UserController@update_user_status');
	Route::get('api/users','UserController@getUsers')->name('users');
	Route::get('api/logout','LoginController@logout');
	Route::post('api/user/edit_user','UserController@edit_user_info');
	Route::post('api/user/update_location','UserController@updateLocation');
	Route::post('api/user/update_location_from_background/{user_id}','UserController@updateLocationFromBackground');
	Route::post('api/user/change_password','RegisterController@changePassword');
	Route::post('api/user/get_user_search','UserController@get_user_search');
	//favorite
	Route::post('api/add_favourite', 'UserController@add_favourite');
	Route::post('api/remove_favourite', 'UserController@removeFavourite');
	Route::get('api/favourite_list/{user_id}/{page?}', 'UserController@favouriteList');
	Route::post('api/favourite_list_search', 'UserController@favouriteListSearch');
	Route::post('api/restaurant_activation', 'UserController@adminActivation');
	Route::post('api/user_activation', 'UserController@userActivation');
	//resturant special upload route
	Route::post('api/special','RestaurantSpecialController@store');
	Route::post('api/special_update/{rest_id}','RestaurantSpecialController@update');
	Route::get('api/special_delete/{rest_id}','RestaurantSpecialController@destroy');
	//special review
	Route::post('api/special/add_review/{user_id}/{rest_id}', 'RestaurantReviewsController@addReview');
	Route::post('api/special/add_review_image/{user_id}/{rest_id}', 'RestaurantReviewsController@addReviewImage');
	//reservation
	Route::post('api/special/reservation', 'RestaurantOrderController@reservation');
	Route::get('api/special/user_reservation_list/{user_id}', 'RestaurantOrderController@userReservationList');
	Route::get('api/special/restaurant_reservation_today/{rest_id}', 'RestaurantOrderController@restaurantReservationListToday');
	Route::get('api/special/restaurant_reservation_cancelled/{rest_id}/{page_no}', 'RestaurantOrderController@restaurantReservationListCancelled');
	Route::get('api/special/restaurant_reservation_pending/{rest_id}/{page_no}', 'RestaurantOrderController@restaurantReservationListPending');
	Route::get('api/special/restaurant_reservation_confirmed/{rest_id}/{page_no}', 'RestaurantOrderController@restaurantReservationListConfirmed');
	Route::get('api/special/admin_reservation/{page?}', 'RestaurantOrderController@adminReservations');
	Route::get('api/special/admin_reservation_restaurant/{rest_id}/{page?}', 'RestaurantOrderController@adminReservationsRestaurant');
	Route::post('api/special/admin_reservation_restaurant_search/', 'RestaurantOrderController@adminReservationsRestaurantSearch');
	Route::get('api/special/reservation_details/{order_id}', 'RestaurantOrderController@reservationDetails');
	Route::get('api/special/reservation_cancel/{order_id}', 'RestaurantOrderController@reservationCancel');
	Route::get('api/special/reservation_confirm/{order_id}', 'RestaurantOrderController@reservationConfirm');
	Route::get('api/get_client/{rest_id}/{client_id}', 'RestaurantOrderController@getClient');
	Route::post('api/get_restaurant_report', 'RestaurantOrderController@get_restaurant_report');
	//resturant info edit update or delete
	Route::post('api/restaurant_create','RestaurantController@store');
	Route::post('api/restaurant_update/{rest_id}','RestaurantController@update');
	Route::get('api/restaurant_delete/{rest_id}','RestaurantController@destroy');
	//Logo bahnner upload
	Route::post('api/logo_banner_upload','RestaurantController@logo_banner');
	//resturant catagory create
	Route::post('api/category_create/{rest_id}','RestaurantMenuCategoryController@store');
	Route::post('api/category_update/{category_id}','RestaurantMenuCategoryController@update');
	Route::get('api/category_delete/{category_id}','RestaurantMenuCategoryController@delete');
	//resturant food menu item crud
	Route::post('api/restaurant_menu_create','RestaurantMenuController@store');
	Route::post('api/restaurant_menu_update/{menu_id}','RestaurantMenuController@update');
	Route::get('api/restaurant_menu_delete/{menu_id}','RestaurantMenuController@delete');
});




