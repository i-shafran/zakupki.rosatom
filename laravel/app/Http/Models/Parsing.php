<?
namespace App\Http\Models;

use App\Http\Models\Parsing\company;
use App\Http\Models\Parsing\contract;
use App\Http\Models\Parsing\documents;
use App\Http\Models\Parsing\errors;
use App\Http\Models\Parsing\gpz;
use App\Http\Models\Parsing\lots;
use App\Http\Models\Parsing\process;
use App\Http\Models\Parsing\purchase;
use Carbon\Carbon;

require_once(__DIR__ . "/../../Library/phpQuery.php");

class Parsing
{
	/**
	 * Статусы закупки
	 * @var array
	 */
	public static $status = array(
		1 => "Опубликована",
		2 => "Отменена",
		3 => "Проведена"
	);

	/**
	 * Статусы лота
	 * @var array
	 */
	public static $lot_status = array(
		1 => "Опубликован",
		2 => "Отменен",
		3 => "Проведен"
	);

	/**
	 * Парсинг детальной
	 * @param $url - URL детальной
	 * @param $process_id - ID Процесса
	 * @return array|bool
	 */
	public static function purchase_parsing($url, $process_id)
	{
		// Таблица закупки детальная
		$columns_detail = array(
			"name" => "Наименование закупки",
			"date_published" => "Опубликована",
			"number" => "Номер закупки",
			"status" => "Статус",
			"method_of_purchase" => "Способ закупки",
			"form_of_trading" => "Форма торгов",
			"number_of_stages" => "Количество этапов закупки",
			"name_funding_source" => "Наименование источника финансирования",
			"initial_price" => "Начальная (максимальная) цена договора в рублях РФ",
			"time_zone" => "Часовой пояс закупки",
			"electronic_trading" => "Электронная торговая площадка",
			"reference_ETP" => "Ссылка на закупку на ЭТП",
			"company_name" => "Наименование организации",
			"company_actual_adress" => "Место нахождения",
			"company_postal_adress" => "Почтовый адрес",
			"company_phone" => "Телефон",
			"company_email" => "Адрес электронной почты",
			"company_fax" => "Факс",
			"company_contact_person" => "Контактное лицо",
			"company_href" => "Организатор закупки ссылка на оригинал",
			"contractor_name" => "Наименование поставщика",
			"contractor_okopf" => "ОКОПФ",
			"contractor_inn" => "ИНН",
			"contractor_ogrn" => "ОГРН",
			"contractor_adress" => "Юридический адрес",
			"href" => "Ссылка на оригинал",
			"lots" => "Лоты",
			"original_id" => "Оригинальный ID"

		);

		// Таблица лота
		$columns_lot = array(
			"starting_price" => "Начальная цена (руб.)",
			"currency_lot" => "Валюта лота",
			"initial_price" => "Начальная цена лота в валюте лота",
			"status" => "Статус лота",
			"without_contract" => "Без заключения договора",
			"company_name" => "Официальное наименование",
			"company_adress" => "Место нахождения",
			"company_postal_adress" => "Почтовый адрес",
			"company_phone" => "Телефон",
			"company_email" => "Адрес электронной почты",
			"company_fax" => "Факс",
			"date_end" => "Дата и время окончания подачи заявок",
			"end_date_stage" => "Дата окончания отборочной стадии",
			"end_date_stage_update" => "Измененная дата окончания отборочной стадии",
			"date_summarizing" => "Дата подведения итогов",
			"date_summarizing_update" => "Измененная дата подведения итогов",
			"place_consideration" => "Место рассмотрения заявок",			
			"place summarizing" => "Место подведения итогов",
			"place_envelopes" => "Место вскрытия конвертов",
			"place_proposals" => "Место рассмотрения предложений",
			"end_date_proposals" => "Дата и время окончания подачи предложений",
			"extension_deadline" => "Дата и время продления срока подачи заявок",
			"end_date_1_parts" => "Дата рассмотрения 1 частей заявок",
			"end_date_2_parts" => "Дата окончания рассмотрения 2 частей заявок",
			"date_electronic_auction" => "Дата и время проведения электронного аукциона",
			"date_envelopes" => "Дата и время вскрытия  конвертов",
			"date_proposals" => "Дата рассмотрения предложений",
			"date_sails" => "Дата рассмотрения заявок",
			"result_lot" => "Результат лота",
			"closed" => "Закрыт"
		);

		// Таблица компании
		$columns_company = array(
			"name" => "Официальное наименование",
			"short_name" => "Короткое наименование",
			"actual_address" => "Фактический адрес",
			"postal_adress" => "Почтовый адрес",
			"company_adress" => "Юридический адрес",
			"phone" => "Телефон",
			"email" => "Адрес электронной почты",
			"fax" => "Факс",
			"site" => "Домашняя страница",
			"contact_person" => "Контактное лицо",
			"name_director" => "Фамилия руководителя",
			"firstname_director" => "Имя руководителя",
			"secondname_director" => "Отчество руководителя",
			"position_director" => "Должность руководителя",
			"contact_persons" => "Контактные лица",
			"inn" => "ИНН",
			"kpp" => "КПП",
			"okpo" => "ОКПО",
			"bank_account" => "Расчетный счет",
			"correspondent_account" => "Корреспондентский счет",
			"bik" => "БИК",
			"bank_name" => "Название банка",
			"bank_adress" => "Почтовый адрес банка"
		);
		
		$res = array();
		$res["href"] = $url;
		$res["original_id"] = trim(substr(strrchr($res["href"], "/"), 1));

		// Парсинг детальной страницы
		$detail_content = static::get_html($res["href"]);

		if(!$detail_content){
			// Запись об ошибке получения HTML
			$error = new errors();
			$error->href = $res["href"];
			$error->process_id = $process_id;
			$error->page_type = "Закупка";
			$error->save();

			return false;
		}

		$document_detail = \phpQuery::newDocumentHTML($detail_content, "UTF8");

		// Дата публикации в формате sql
		$date_text = $document_detail->find('div.printed')->text();
		$pattern = "#Опубликована ([0-9]+\.[0-9]+\.[0-9]+)#i";
		preg_match($pattern, $date_text, $matches);
		if (isset($matches[1])) {
			$res["date_published"] = Carbon::parse($matches[1])->format('Y-m-d');
		}

		$tr = $document_detail->find('div#tab1 tr');

		foreach ($tr as $value) {
			$td_name = pq($value)->find("td")->eq(0)->text();
			$td_value = pq($value)->find("td")->eq(1)->text();

			if ($key = array_search($td_name, $columns_detail) and !empty($td_value)) {
				if($key == "status"){
					// Статус
					if($status_id = array_search($td_value, static::$status)){
						$res["status_id"] = $status_id;
					} else {
						$res["status_id"] = NULL; // Статус не определен
					}
				} else {
					$res[$key] = trim($td_value);
				}

				// company_href => "Организатор закупки ссылка на оригинал",
				if($key == "company_name"){
					$company_href = pq($value)->find("td")->eq(1)->find("a")->attr("href");
					if($company_href){
						$res["company_href"] = "http://zakupki.rosatom.ru" . $company_href;
					} else{
						$res["company_href"] = NULL;
					}
				}

				// contractor_href => "Поставщик ссылка на оригинал",
				if($key == "contractor_name"){
					$contractor_href = pq($value)->find("td")->eq(1)->find("a")->attr("href");
					if($contractor_href){
						$res["contractor_href"] = "http://zakupki.rosatom.ru" . $contractor_href;
					} else{
						$res["contractor_href"] = NULL;
					}
				}
			}
		}

		// insert to DB if not duplicate
		$duplicate = purchase::whereRaw('original_id = ?', array($res["original_id"]))->get();
		$duplicate = count($duplicate);
		if ($duplicate == 0) {

			// insert to purchase if process != stop
			$process = process::find($process_id);
			if ($process != NULL and $process->stop == 1) {
				return array("status" => "Парсинг остановлен");
			}
			$purchase = new purchase();
			foreach ($res as $key => $value) {
				$purchase->$key = $value;
			}
			$purchase->save();
			$purchase_id = $purchase->id;

			// update process
			if($process_id){
				$process = process::find($process_id);
				$process->complete_count++;
				$process->href = $res["href"];
				$process->save();
			}

			/* Лоты */
			$lots_html = $document_detail->find('a:contains("Лот №")');
			$ii = 0;
			foreach ($lots_html as $value) {
				$href = pq($value)->attr("href");
				$href = "http://zakupki.rosatom.ru" . $href;

				$lot_content = static::get_html($href);

				if(!$lot_content){
					// Запись об ошибке получения HTML
					$error = new errors();
					$error->href = "http://zakupki.rosatom.ru" . $href;
					$error->process_id = $process_id;
					$error->page_type = "Лот";
					$error->save();

					continue;
				}

				$document_lot = \phpQuery::newDocumentHTML($lot_content, "UTF8");

				$tr = $document_lot->find('div.body-column table tr');

				foreach ($tr as $value) {
					$td_name = pq($value)->find("td")->eq(0)->text();
					$td_value = pq($value)->find("td")->eq(1)->text();

					if ($key = array_search($td_name, $columns_lot) and !empty($td_value)) {
						if($key == "status"){
							// Статус лота
							if($status_id = array_search($td_value, static::$lot_status)){
								$res["lots"][$ii]["status_id"] = $status_id;
							} else {
								$res["lots"][$ii]["status_id"] = NULL; // Статус не определен
							}
						} else {
							$res["lots"][$ii][$key] = trim($td_value);
						}
					}
					
					// Преобразование дат лота
					$arLotDate = array(
						"end_date_stage", "end_date_stage_update", "date_summarizing",
						"date_summarizing_update", "date_end", "end_date_proposals",
						"extension_deadline", "end_date_1_parts", "end_date_2_parts",
						"date_electronic_auction", "date_envelopes", "date_proposals",
						"date_sails"
					);
					if(in_array($key, $arLotDate)){
						$pattern = "#[0-9\.: ]+#";
						preg_match($pattern, $res["lots"][$ii][$key], $matches);
						if(!empty($matches[0])){
							$res["lots"][$ii][$key] = Carbon::parse($matches[0])->format('Y-m-d H:i:s'); // дата и время sql
						} else {
							$res["lots"][$ii][$key] = NULL;
						}
					}

					// company_href => "Ссылка на заказчика"
					if($key == "company_name"){
						$company_href = pq($value)->find("td")->eq(1)->find("a")->attr("href");
						if($company_href){
							$res["lots"][$ii]["company_href"] = "http://zakupki.rosatom.ru/" . $company_href;
						} else{
							$res["lots"][$ii]["company_href"] = NULL;
						}
					}
				}

				// Ссылка на лот
				$res["lots"][$ii]["href"] = $href;

				// insert to lots
				$lots[$ii] = new lots();
				foreach ($res["lots"][$ii] as $key => $value) {
					$lots[$ii]->$key = $value;
				}
				$lots[$ii]->purchase_id = $purchase_id;
				$lots[$ii]->save();

				/* ГПЗ */
				$gpz_document = $document_lot->find("tbody#custitemsbody_1");
				if(count($gpz_document) != 0){
					$tr = $gpz_document->find("tr");
					$res_gpz = array();
					foreach($tr as $value_gpz){
						$td = pq($value_gpz)->find("td");
						$res_gpz["number"] = $td->eq(0)->text();
						$res_gpz["name"] = $td->eq(1)->text();
						$res_gpz["count"] = $td->eq(2)->text();
						$res_gpz["lot_id"] = $lots[$ii]->id;

						// insert to gpz
						$gpz = new gpz();
						foreach ($res_gpz as $key => $value) {
							$gpz->$key = $value;
						}
						$gpz->save();
					}
				}
				

				$ii++;
			}


			/* Заказчики лота */
			$ii = 0;
			if(isset($res["lots"])){
				foreach($res["lots"] as $value){
					if(!empty($value["company_href"])){
						// Загрузка кампании
						$company_content = static::get_html($value["company_href"]);
	
						if(!$company_content){
							// Запись об ошибке получения HTML
							$error = new errors();
							$error->href = $value["company_href"];
							$error->process_id = $process_id;
							$error->page_type = "Заказчик лота";
							$error->save();
	
							continue;
						}
	
						$document_company = \phpQuery::newDocumentHTML($company_content, "UTF8");
	
						if(is_object($document_company)){
							$tr = $document_company->find('div.body-column table tr');
	
							$lot_company = array();
							foreach ($tr as $value2) {
								$td_name = pq($value2)->find("td")->eq(0)->text();
								$td_value = pq($value2)->find("td")->eq(1)->text();
	
								if ($key = array_search($td_name, $columns_company) and !empty($td_value)) {
									$lot_company[$key] = trim($td_value);
								}
							}
	
							$lot_company["href"] = $value["company_href"];
	
							// insert to company if not duplicate
							if(!empty($lot_company["inn"])){
								$duplicate_company = company::whereRaw('inn = ?', array($lot_company["inn"]))->get();
	
								// Если не дубликат
								if(count($duplicate_company) == 0){
									$company = new company();
									foreach ($lot_company as $key => $value) {
										$company->$key = $value;
									}
									$company->save();
	
									// update lot
									$lots[$ii]->company_id = $company->id;
									$lots[$ii]->save();
								} else {
									$old_company_id = $duplicate_company->last()->toArray();
									$old_company_id = $old_company_id["id"];
	
									// update lot
									$lots[$ii]->company_id = $old_company_id;
									$lots[$ii]->save();
								}
							}
						}
					}
					
					$ii++;
				}
			}


			/* Документы */
			$files = $document_detail->find('div#table_04 table tr');
			$ii = 0;
			foreach ($files as $value) {
				// Пропуск первого tr
				if ($ii == 0) {
					$ii++;
					continue;
				}
				$res["documents"][$ii]["name"] = trim(pq($value)->find("td a")->text());
				$res["documents"][$ii]["original_file_src"] = "http://zakupki.rosatom.ru" . pq($value)->find("td a")->attr("href");
				$res["documents"][$ii]["file_id"] = substr(strrchr($res["documents"][$ii]["original_file_src"], "="), 1);
				$documents_date = pq($value)->find("td")->eq(1)->text();
				$res["documents"][$ii]["date"] = Carbon::parse($documents_date)->format('Y-m-d'); // дата sql
				$res["documents"][$ii]["type"] = pq($value)->find("td")->eq(2)->text();
				$res["documents"][$ii]["step_purchases"] = pq($value)->find("td")->eq(3)->text();
				$res["documents"][$ii]["lots"] = pq($value)->find("td")->eq(4)->text();

				// Загрузка файла
				// TODO $file_src = $this->file_upload($res["documents"][$ii]["original_file_src"], $res["original_id"])
				$file_src = NULL;
				if (true) {
					$res["documents"][$ii]["file_src"] = $file_src;
				} else {
					$res["documents"][$ii]["file_src"] = "error";
				}

				// insert to documents
				$documents = new documents();
				foreach ($res["documents"][$ii] as $key => $value) {
					$documents->$key = $value;
				}
				$documents->purchase_id = $purchase_id;
				$documents->save();

				$ii++;
			}

			/* Кампании */
			if(!empty($res["company_href"])){
				$company_content = static::get_html($res["company_href"]);

				if(!$company_content){
					// Запись об ошибке получения HTML
					$error = new errors();
					$error->href = $res["company_href"];
					$error->process_id = $process_id;
					$error->page_type = "Компания";
					$error->save();

					return false;
				}

				$document_company = \phpQuery::newDocumentHTML($company_content, "UTF8");

				if(is_object($document_company)){
					$tr = $document_company->find('div.body-column table tr');

					foreach ($tr as $value) {
						$td_name = pq($value)->find("td")->eq(0)->text();
						$td_value = pq($value)->find("td")->eq(1)->text();

						if ($key = array_search($td_name, $columns_company) and !empty($td_value)) {
							$res["company"][$key] = trim($td_value);
						}
					}

					$res["company"]["href"] = $res["company_href"];

					// insert to company if not duplicate
					if(!empty($res["company"]["inn"])){
						$duplicate_company = company::whereRaw('inn = ?', array($res["company"]["inn"]))->get();

						// Если не дубликат
						if(count($duplicate_company) == 0){
							$company = new company();
							foreach ($res["company"] as $key => $value) {
								$company->$key = $value;
							}
							$company->save();

							// update purchase
							$purchase->company_id = $company->id;
							$purchase->save();
						} else {
							$old_company_id = $duplicate_company->last()->toArray();
							$old_company_id = $old_company_id["id"];

							// update purchase
							$purchase->company_id = $old_company_id;
							$purchase->save();
						}
					}
				}
			}

			/* Поставщик */
			if(!empty($res["contractor_href"])){
				$contractor_content = static::get_html($res["contractor_href"]);

				if(!$contractor_content){
					// Запись об ошибке получения HTML
					$error = new errors();
					$error->href = $res["contractor_href"];
					$error->process_id = $process_id;
					$error->page_type = "Компания";
					$error->save();

					return false;
				}

				$document_contractor = \phpQuery::newDocumentHTML($contractor_content, "UTF8");

				if(is_object($document_contractor)){
					$tr = $document_contractor->find('div.body-column table tr');

					foreach ($tr as $value) {
						$td_name = pq($value)->find("td")->eq(0)->text();
						$td_value = pq($value)->find("td")->eq(1)->text();

						if ($key = array_search($td_name, $columns_company) and !empty($td_value)) {
							$res["contractor"][$key] = trim($td_value);
						}
					}

					$res["contractor"]["href"] = $res["contractor_href"];

					// insert to company if not duplicate
					if(!empty($res["contractor"]["inn"])){
						$duplicate_company = company::whereRaw('inn = ?', array($res["contractor"]["inn"]))->get();

						if(count($duplicate_company) == 0){
							$company = new company();
							foreach ($res["contractor"] as $key => $value) {
								$company->$key = $value;
							}
							$company->save();

							// update purchase
							$purchase->contractor_id = $company->id;
							$purchase->save();
						} else {
							$old_company_id = $duplicate_company->last()->toArray();
							$old_company_id = $old_company_id["id"];

							// update purchase
							$purchase->contractor_id = $old_company_id;
							$purchase->save();
						}
					}
				}
			}
			
			/* Договора */
			$tr = $document_detail->find("div#table_tp2_1 table tr");
			if(count($tr) != 0){
				$res["contract"]["customer"] = $tr->eq(0)->find("td")->eq(1)->text();
				$res["contract"]["contractor"] = $tr->eq(1)->find("td")->eq(1)->text();
				$res["contract"]["document"] = $tr->eq(3)->find("td")->eq(0)->text();				
				$res["contract"]["number"] = $tr->eq(3)->find("td")->eq(1)->text();
				$date = $tr->eq(3)->find("td")->eq(2)->text();
				$res["contract"]["date"] = Carbon::parse($date)->format('Y-m-d'); // дата sql;
				$res["contract"]["price"] = $tr->eq(3)->find("td")->eq(3)->text();
				$res["contract"]["volume_change"] = $tr->eq(3)->find("td")->eq(4)->text();
				$res["contract"]["change_price"] = $tr->eq(3)->find("td")->eq(5)->text();
				$res["contract"]["term_transformation"] = $tr->eq(3)->find("td")->eq(5)->text();
				$res["contract"]["point_ext_agreement"] = $tr->eq(3)->find("td")->eq(5)->text();

				// insert to contract
				$contract = new contract();
				foreach ($res["contract"] as $key => $value) {
					$contract->$key = $value;
				}
				$contract->save();

				// update purchase
				$purchase->contract_id = $contract->id;
				$purchase->save();
			}			

		} else {
			// Запись в процесс о дубликате
			// update process
			if($process_id){
				$process = process::find($process_id);
				$process->duplicate_count++;
				$process->href = $res["href"];
				$process->save();
			}
		}
		
		return true;
	}


	/**
	 * Получить HTML страницы
	 * @param $url - URL
	 * @return bool|mixed
	 */
	public static function get_html($url)
	{
		$ch = curl_init (); // инициализация
		$arParams = array(
			CURLOPT_URL => $url, // адрес страницы для скачивания
			CURLOPT_RETURNTRANSFER => 1, // нам нужно вывести загруженную страницу в переменную
			CURLOPT_TIMEOUT => 30, //TIMEOUT
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7.12) Gecko/20050919 Firefox/1.0.7", // каким браузером будем прикидываться
			CURLOPT_FOLLOWLOCATION => 1 //Переходим по редиректам
		);
		curl_setopt_array($ch, $arParams);
		$result = curl_exec($ch);
		//$header  = curl_getinfo($ch);
		//$error = curl_error($ch);
		curl_close($ch);

		if(!empty($result)){
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Загрузка файлов
	 * @param $url - URL
	 * @param $folderID - ID папки (= ID заявки)
	 * @return bool|string
	 */
	public function file_upload($url, $folderID)
	{
		$file =fopen($_SERVER["DOCUMENT_ROOT"]."/uploads/file.ext","w+b");
		if(!$file){
			return false;
		}

		// Получение заголовков
		$ch = curl_init (); // инициализация
		$arParams = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1, // нам нужно вывести загруженную страницу в переменную
			CURLOPT_TIMEOUT => 30, //TIMEOUT
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7.12) Gecko/20050919 Firefox/1.0.7", // каким браузером будем прикидываться
			CURLOPT_FOLLOWLOCATION => 1, //Переходим по редиректам
			CURLOPT_HEADER => 1, // TRUE для включения заголовков в вывод
			CURLOPT_NOBODY => 1
		);
		curl_setopt_array($ch, $arParams);
		$headers = curl_exec($ch);
		curl_close($ch);

		if(!$headers)
			return false;

		$headers = explode("\r\n", $headers);
		$pattern = '#^content-disposition: Attachment; FileName="(.+)"$#i';
		preg_match($pattern, $headers[5], $matches);

		if(isset($matches[1])){
			$file_name = iconv('UTF-8', 'CP1251', $matches[1]);
		} else {
			return false;
		}

		// Получение файла
		$ch = curl_init (); // инициализация
		$arParams = array(
			CURLOPT_URL => $url, // адрес страницы для скачивания
			CURLOPT_RETURNTRANSFER => 1, // нам нужно вывести загруженную страницу в переменную
			CURLOPT_TIMEOUT => 30, //TIMEOUT
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7.12) Gecko/20050919 Firefox/1.0.7", // каким браузером будем прикидываться
			CURLOPT_FOLLOWLOCATION => 1, //Переходим по редиректам
			CURLOPT_FILE => $file // Файл, в который будет записан результат передачи
		);
		curl_setopt_array($ch, $arParams);
		$result = curl_exec($ch);
		//$header  = curl_getinfo($ch);
		//$error = curl_error($ch);
		curl_close($ch);
		fclose($file);

		if(!$result)
			return false;

		// Переместим файл куда надо
		if(!is_dir($_SERVER["DOCUMENT_ROOT"]."/uploads/$folderID")){
			mkdir($_SERVER["DOCUMENT_ROOT"]."/uploads/$folderID");
		}
		rename($_SERVER["DOCUMENT_ROOT"]."/uploads/file.ext", $_SERVER["DOCUMENT_ROOT"]."/uploads/$folderID/$file_name");

		return "/uploads/$folderID/$matches[1]";
	}

	/**
	 * Время из секунд
	 * @param $value - секунды
	 * @return string
	 */
	public static function time($value)    {
		$hh = floor($value/3600);
		$min = floor(($value-$hh*3600)/60);
		$sec = $value-$hh*3600-$min*60;
		$l = sprintf('%02d',$hh).':'.sprintf('%02d',$min).':'.sprintf('%02d',$sec);
		return $l;
	}




}