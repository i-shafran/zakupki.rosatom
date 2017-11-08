$(document).ready(function() {

	$("button#parsing").click(function(){
		$(this).attr("disabled", "disabled");
		$(this).text("Идет процесс...");
		
		var url = "";
		var radio = $("input:radio:checked").val();
		var purchase = $("input[name=url]").val();
		
		if(purchase.length != 0){
			url = "/purchase_parsing/";
		}
		else if(radio == "currentorders")
		{
			url = "/parsing/1/";
		} 
		else if(radio == "archiveorders")
		{
			url = "/parsing/1/true";
		}
		
		if(url == "/purchase_parsing/"){
			$.ajax({
				type: "POST",
				url: url,
				data:
				{
					url: purchase 
				},
				dataType: "JSON",
				success: function(data){
					console.log(data.status);
				},
				error: function(data){
					console.log("Ajax запрос выполнен неудачно");
					//console.log(data);
				}
			});
		} else {
			$.ajax({
				type: "GET",
				url: url,
				dataType: "JSON",
				success: function(data){
					console.log(data.status);
				},
				error: function(data){
					console.log("Ajax запрос выполнен неудачно");
					//console.log(data);
				}
			});
		}		
		
		// Запуск проверки процесса
		check_parsing_process();
	});

	$("button#stop").click(function(){
		$(this).attr("disabled", "disabled");
		$(this).text("Идет процесс...");
		
		$.ajax({
			type: "GET",
			url: "/stop_parsing/",
			dataType: "JSON",
			success: function(data){
				console.log(data.status);
			},
			error: function(data){
				console.log("Ajax запрос выполнен неудачно");
				//console.log(data);
			}
		});
	});

}); // end ready()

// Запуск проверки процесса
function check_parsing_process(){
    $.ajax({
        type: "GET",
        url: "/check_parsing_process/",
        dataType: "JSON",
        success: function(data){
			// Данные
			$("span#time_start").text(data.time_start);
			$("span#time_passed").text(data.time_passed);
			$("span#time_complete").text(data.time_complete);
			$("span#complete_count").text(data.complete_count);
			$("span#page_count").text(data.page_count);
			$("span#max_count").text(data.max_count);
			$("span#duplicate_count").text(data.duplicate_count);
			if(data.status != "stop"){
				check_parsing_process();
			} else {
				$("button#parsing").removeAttr("disabled");
				$("button#parsing").text("Начать парсинг");
				$("button#stop").removeAttr("disabled");
				$("button#stop").text("Остановить")
			}
        },
        error: function(data){
            console.log("Ajax запрос выполнен неудачно");
            //console.log(data);
        }
    });
}