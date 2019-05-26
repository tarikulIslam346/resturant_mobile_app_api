<?php



Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::resource('restaurant','RestaurantController');

Route::post('/restaurant', 'RestaurantController@store')->name('restaurant.store');


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::post('/mail','EmailController@send_mail');
Route::get('/restaurant_data/{id}','RestaurantController@destroy');










