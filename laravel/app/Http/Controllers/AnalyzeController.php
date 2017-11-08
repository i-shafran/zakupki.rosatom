<?php
namespace App\Http\Controllers;

use App\Http\Models\Analyze\analyze_get_company_win;
use App\Http\Models\Parsing\company;
use App\Http\Models\Parsing\purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;

class AnalyzeController extends Controller
{
	/**
	 * Лимит запросов, дабы не перегружать базу
	 * @var int
	 */
	public $limit = 50;
	
	/**
	 * Победы и суммы побед для компаний
	 */
	public function get_company_win()
	{
		// Методы закупки
		$method_of_purchase = array(
			"auction" => "Аукцион",
			"auction_electron_wins" => "Аукцион в электронной форме",
			"single_supplier" => "Закупка у единственного поставщика",
			"request_proposal" => "Запрос предложений",
			"request_electron_proposal" => "Запрос предложений в электронной форме",
			"request_price" => "Запрос цен",
			"request_price_electron" => "Запрос цен в электронной форме",
			"price_quotations" => "Запрос ценовых котировок",
			"price_quotations_electron" => "Запрос ценовых котировок в электронной форме",
			"competitive_negotiations" => "Конкурентные переговоры",
			"competition" => "Конкурс",
			"competition_electron" => "Конкурс в электронной форме",
			"small_purchase" => "Мелкая закупка",
			"reduction_auction" => "Понижающий аукцион",
			"reduction_auction_electron" => "Понижающий аукцион в электронной форме",
			"reduction_reduction" => "Понижающий редукцион",
			"reduction_reduction_electron" => "Понижающий редукцион в электронной форме",
			"reduction" => "Редукцион",
			"reduction_electron" => "Редукцион в электронной форме",
			"simple_purchase" => "Упрощенная закупка"
		);

		DB::statement('TRUNCATE TABLE analyze_get_company_win'); // Удалить все из analyze_get_company_win
		
		$i = 0;
		while($companies = company::limit($this->limit)->offset($this->limit*$i)->get() and count($companies) > 0)
		{
			foreach($companies as $company){
				$analyze = new analyze_get_company_win();

				// Начальные данные
				$analyze->name = $company->name;
				$analyze->inn = $company->inn;
				$analyze->href = $company->href;
				$analyze->contact_persons = $company->contact_persons;
				$analyze->site = $company->site;
				$analyze->number_wins = 0;
				$analyze->sum_wins = 0;
				$analyze->average_wins = 0;
				foreach($method_of_purchase as $key => $value){
					$analyze->$key = 0;
				}

				$purchase = purchase::whereRaw("contractor_id = ?", array($company->id))->get();

				$number_wins = count($purchase); // Кол-во всех побед
				$analyze->number_wins = $number_wins;
				
				foreach($purchase as $key => $value){
					// Победы по типам
					if($key = array_search($value->method_of_purchase, $method_of_purchase)){
						$analyze->$key++;
					} else {
						return "Способ закупки $value->method_of_purchase не найден";
					}
					// сумма всех побед
					$price = preg_replace('~[^0-9,]+~','',$value->initial_price);
					$price = str_replace(",", ".", $price);
					$price = floatval($price);
					$analyze->sum_wins = $analyze->sum_wins + $price;
				}

				// средняя стоимость
				if($analyze->number_wins > 0){
					$analyze->average_wins = round($analyze->sum_wins / $analyze->number_wins, 2);
				}

				$analyze->save();

			}
			

			$i++;
		}
		
		
		
		
		$content = view("AnalyzeController.get_company_win");
		return response($content)
			->header('Cache-Control', "no-cache, no-store, must-revalidate")
			->header('Pragma', 'no-cache')
			->header('Expires', '0');

	}

	/**
	 * Обновление компаний данными из закупок
	 * @return bool
	 */
	public function update_company_from_purchase()
	{
		$purchase = purchase::whereRaw('contractor_inn IS NOT NULL')->get();

		foreach($purchase as $value){
			$inn = $value->contractor_inn;
			$company = company::whereRaw("inn = $inn")->get()->last();
			if(count($company) == 0){
				// Добавление компании
				$company = new company();
				$company->name = $value->contractor_name;
				$company->inn = $value->contractor_inn;
				$company->actual_address = $value->contractor_adress;
				$company->postal_adress = $value->contractor_adress;
				$company->company_adress = $value->contractor_adress;

				$company->save();
				
				// Обновление закупки
				$value->contractor_id = $company->id;
				$value->save();
			}
		}
		
		return true;
	}

	/**
	 * Обновление contractor_id закупок по contractor_inn
	 */
	public function update_purchase_contractor_id_from_company()
	{
		$i = 0;
		while($purchase = purchase::whereRaw('contractor_id IS NULL AND contractor_inn IS NOT NULL LIMIT 50')->get() and count($purchase) > 0)
		{
			foreach($purchase as $key => $value){
				$company = company::whereRaw("inn = ?", array($value->contractor_inn))->get()->last();
				$value->contractor_id = $company->id;
				$value->save();
				
				$i++;
			}
		}
		
		return $i;
	}



}