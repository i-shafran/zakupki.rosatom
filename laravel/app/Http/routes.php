<?php

/*Route::get('/', 'PagesController@index');
Route::controllers([
	'accounts' => 'AccountsController'
]);
Route::get("/accounts/{id}/edit/", "AccountsController@edit");
Route::get('/about/', 'PagesController@about');
Route::resource("/documents/", "DocumentsController");*/

// Главная
Route::get("/", "IndexController@index");

// Анализ
Route::get("/get_company_win/", "AnalyzeController@get_company_win");

// Рефакторинг базы
Route::get("/update_company_from_purchase/", "AnalyzeController@update_company_from_purchase");
Route::get("/update_purchase_contractor_id_from_company/", "AnalyzeController@update_purchase_contractor_id_from_company");

// Парсинг
Route::get("/parsing/{page?}/{orders?}/{run?}", "ParsingController@parsing")
	->where(array(
		'page' => '[0-9]+|all',
		'orders' => 'currentorders|archiveorders',
		'run' => '[0-9]+'
	));
Route::get("/reload_parsing/{run?}", "ParsingController@reload_parsing")
	->where(array(
		'run' => '[0-9]+'
	));
Route::get("/stop_parsing/{run?}", "ParsingController@stop_parsing")
	->where(array(
		'run' => '[0-9]+'
	));
Route::get("/check_parsing_process/", "ParsingController@check_parsing_process");
Route::post("/purchase_parsing/", "ParsingController@purchase_parsing");