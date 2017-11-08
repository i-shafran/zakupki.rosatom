<?php
namespace App\Http\Controllers;

use App\Http\Requests;
use Carbon\Carbon;
use Illuminate\View\View;

class IndexController extends Controller {
	
	public function index()
	{
		$content = view("index.index");
		return response($content)
			->header('Cache-Control', "no-cache, no-store, must-revalidate")
			->header('Pragma', 'no-cache')
			->header('Expires', '0');
	}

}