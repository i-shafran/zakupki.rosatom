<?php
namespace App\Http\Controllers;

use App\Http\Models\Parsing\errors;
use App\Http\Models\Parsing\process;
use App\Http\Models\Parsing;
use App\Http\Requests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;

class ParsingController extends Controller {

	/**
	 * Сделок на одной странице
	 */
	const count_on_page = 30;

	/**
	 * Парсинг
	 * @param int|string $page - Страница старта (all - с последней страницы)
	 * @param string $orders - Парсить архив или текущих
	 * @param int $run - Номер потока для многопоточного режима
	 * @return array
	 */
	public function parsing($page = 1, $orders = "currentorders", $run = 1)
	{
		require __DIR__."/../../Library/phpQuery.php";
		if($orders == "currentorders"){
			$preview_content = file_get_contents("http://zakupki.rosatom.ru/Web.aspx?node=currentorders");
		}elseif($orders == "archiveorders"){
			$preview_content = file_get_contents("http://zakupki.rosatom.ru/Web.aspx?node=archiveorders"); // Архив
		}else {
			return false;
		}
		$document = \phpQuery::newDocumentHTML($preview_content, "UTF8");
		
		// start process
		$process = new process();
		$process->time_start = Carbon::now()->format('Y-m-d H:i:s');
		$process->run = $run;
		$max_count_text = $document->find('div.pages-list div:contains("всего найдено ")')->eq(0)->text();
		$pattern = "#всего найдено ([0-9]+)#i";
		if(preg_match($pattern, $max_count_text, $matches))
		{
			$process->max_count = $matches[1];
		} else {
			return array("status" => "Не найдено количество записей");
		}		

		// Количество страниц
		$pages = ceil($process->max_count / static::count_on_page);
		
		// Если новый старт с последней страницы
		if($page == "all"){
			$old_process = process::whereRaw('run = ?', array($run))->get();
			$old_process = $old_process->last();
			if(!empty($old_process) and $old_process->stop != 1){
				$page = $old_process->page_count;
			} else {
				$page = 1;
			}
		}

		$process->page_count = $page;
		$process->save();
		$process_id = $process->id;

		/* Цикл страниц */
		while ($page <= $pages) {
			if($orders == "currentorders"){
				$url = "http://zakupki.rosatom.ru/Web.aspx?node=currentorders&page=$page";
			} elseif($orders == "archiveorders") {
				$url = "http://zakupki.rosatom.ru/Web.aspx?node=archiveorders&page=$page";
			} else {
				return false;
			}

			$preview_content = Parsing::get_html($url);

			if(!$preview_content){
				// Запись об ошибке получения HTML
				$error = new errors();
				$error->href = $url;
				$error->process_id = $process_id;
				$error->page_type = "Список страниц";
				$error->page = $page;
				$error->save();
				
				continue;
			}

			$document = \phpQuery::newDocumentHTML($preview_content, "UTF8");

			$a = $document->find('div#table-lots-list td.description a'); // Ссылка на детальную

			/* Цикл детальной */
			$i = 0;
			foreach ($a as $value) {
				$url = "http://zakupki.rosatom.ru" . pq($value)->attr("href");
				
				// Парсинг детальной
				$res = Parsing::purchase_parsing($url, $process_id);
				if(is_array($res)){
					return $res;
				}
				
				$i++;
			}

			$page++;
			$process->page_count = $page;
			$process->save();
		}

		// Метка остановки процесса
		$process->stop = 1;
		$process->save();
		
		
		return array("status" => "Парсинг завершен");
	}

	/**
	 * Парсинг одной заявки
	 * @param Requests\Parsing $request
	 * @return string
	 */
	public function purchase_parsing(Requests\Parsing $request)
	{
		$post = $request->all();
		$res = Parsing::purchase_parsing($post["url"], NULL);
		
		if($res){
			return "parsing done";
		} else {
			return "Error";
		}
	}

	/**
	 * Данные о процессе парсинга
	 * @return array
	 */
	public function check_parsing_process()
	{
		sleep(10);
		
		$arResult = array();
		$process = process::all();
		$process = $process->last();
		
		$time_passed = Carbon::now()->timestamp - Carbon::parse($process->time_start)->timestamp; // Прошло времени
		if(!$process->complete_count or !$time_passed){
			$time_complete = "Вычисляется..."; // Осталось времени
		} else {
			$time_complete = $time_passed / ($process->complete_count + $process->duplicate_count) * $process->max_count - $time_passed;
			$time_complete = (int) round($time_complete);
			$time_complete = Parsing::time($time_complete);
		}		

		$arResult["id"] = $process->id;
		$arResult["time_start"] = $process->time_start;		
		$arResult["time_passed"] = Parsing::time($time_passed);
		$arResult["time_complete"] = $time_complete;
		$arResult["max_count"] = $process->max_count;		
		$arResult["page_count"] = $process->page_count;		
		$arResult["complete_count"] = $process->complete_count;
		$arResult["duplicate_count"] = $process->duplicate_count;
		
		if($process->stop == 1){
			$arResult["status"] = "stop";
		} else {
			$arResult["status"] = "running";
		}
		
				
		return $arResult;
	}

	/**
	 * Остановка парсинга
	 * @param int $run - Номер потока для многопоточного режима
	 * @return array
	 */
	public function stop_parsing($run = 1)
	{
		$process = process::whereRaw('run = ?', array($run))->get();
		$process = $process->last();
		$process->stop = 1;
		$process->save();
		
		return array("status" => "Парсинг остановлен");
	}

	/**
	 * Перезагрузка парсинга
	 * @param int $run - Номер потока для многопоточного режима
	 * @return array
	 */
	public function reload_parsing($run = 1)
	{
		$process = process::whereRaw('run = ?', array($run))->get();
		$process = $process->last();
		$process->stop = 1;
		$process->save();
		
		sleep(60);

		$process->stop = NULL;
		$process->save();
		
		return array("status" => "Процесс парсинга перезагружен. Необходимо сделать запуск парсинга с параметром all снова.");
	}

}