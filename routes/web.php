<?php

use Illuminate\Support\Facades\App;

Auth::routes();

Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

Route::group(['middleware' => ['auth']], function() {
	Route::get('logs', 'LogController@index');
	Route::post('logs/purge', 'LogController@purge');
	Route::get('stats', 'AdminController@stats');
});

Route::group(['middleware' => ['private']], function() {
	Route::get('/home', function() {
		return redirect('/');
	});
	Route::get('/', 'TweetController@index');

	Route::get('/tweet/{id}', [
		'as' => 'tweet.single',
		'uses' => 'TweetController@show'
	]);

	Route::get('/tweet', function() { return redirect('/random'); });
	Route::get('/random', 'TweetController@random');

	Route::get('{year}/{month?}/{date?}', 'TweetController@index')->where([
		'year' => '[0-9]+',
		'month' => '[0-9]+',
		'date' => '[0-9]+'
	]);

	Route::get('/search', function() { return redirect('/'); });
	Route::post('/search', 'TweetController@search');

	Route::get('/search/{search?}/{year?}/{month?}/{day?}', [
		'as' => 'search',
		'uses' => 'TweetController@searchResults'
	]);
});

App::bind(\App\Breadcrumbs\BreadcrumbInterface::class, \App\Breadcrumbs\CreitiveBreadcrumb::class);