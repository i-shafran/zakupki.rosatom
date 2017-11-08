<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="/js/main.js"></script>
	<title>Парсинг</title>
	<style type="text/css">
		div#content {
			text-align: center;
			margin: 50px;
		}
		div#status {
			text-align: left;
			padding: 5px;
			width: 500px;
			border: solid 1px #009926;
			color: #009926;
			margin: auto;
		}
		div#status .data {
			font-weight: bold;
			color: #000000;
		}
	</style>
</head>
<body>
<div id="content">
	<div id="status">
		<p>Время старта: <span id="time_start" class="data"></span></p>
		<p>Прошло времени с начала старта: <span id="time_passed" class="data"></span></p>
		<p>Оценка времени завершения: <span id="time_complete" class="data"></span></p>
		<p>Закупок спарсено: <span id="complete_count" class="data"></span></p>
		<p>Всего закупок: <span id="max_count" class="data"></span></p>
		<p>Найдено дубликатов: <span id="duplicate_count" class="data"></span></p>
		<p>Текущая страница: <span id="page_count" class="data"></span></p>
	</div>
	
	<p>
		<input type="radio" name="type" value="currentorders" /> Парсинг текущих закупок http://zakupki.rosatom.ru/Web.aspx?node=currentorders <br>
		<input type="radio" name="type" value="archiveorders" /> Парсинг архива http://zakupki.rosatom.ru/Web.aspx?node=archiveorders<br>
		<input type="text" name="url" placeholder = "URL" size="50" /> Парсинг конкретного URL закупки
	</p>
	<button id="parsing">Начать парсинг</button>
	<button id="stop">Остановить</button>
</div>
</body>
</html>