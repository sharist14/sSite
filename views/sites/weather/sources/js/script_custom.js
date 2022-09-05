var weather = {
    core_js: '/engine/core/common_js',
    weather_js: '/view/sites/weather/sources/js',

    /* Действие при зарузке сайта */
    global_onload: function () {

    },

    /* Часы в реальном времени */
    clock: function () {
        var d = new Date();
        var day = d.getDate();
        var hrs = d.getHours();
        var min = d.getMinutes();
        var sec = d.getSeconds();

        var mnt = new Array("января", "февраля", "марта", "апреля", "мая",
            "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");

        if (day <= 9) day = "0" + day;
        if (hrs <= 9) hrs = "0" + hrs;
        if (min <= 9) min = "0" + min;
        if (sec <= 9) sec = "0" + sec;

        $("#time").html("<span>" + hrs + ":" + min + ":" + sec + "</span><br><span>" + day + ' ' + mnt[d.getMonth()] + " " + d.getFullYear() + "<span>");
    },

    // Получаем свежие данные по погоде
    updateData: function(){
        $.post('/?act=_ajax_getData', function(data){
            if(data){
                var res = JSON.parse(data);

                if( !res['error'] ){
                    console.log(res);

                    // Проходимся по категориям
                    $.each(res, function(cat_name, cat_arr){

                        // Блок текущей погоды
                        if(cat_name == 'current'){
                            $('#curr_description').html(cat_arr["curr_description"]).text();
                            $('#curr_pressure').html(cat_arr["curr_pressure"]).text();
                            $('#curr_visibility').html(cat_arr["curr_visibility"]).text();
                            $('#curr_clouds').html(cat_arr["curr_clouds"]).text();
                            $('#curr_humidity').html(cat_arr["curr_humidity"]).text();
                            $('#curr_wind_arrow').html(cat_arr["curr_wind_arrow"]).text();
                            $('#curr_wind_speed').html(cat_arr["curr_wind_speed"]).text();
                            $('#curr_wind_char').html(cat_arr["curr_wind_char"]).text();
                            $('#curr_uvi').html(cat_arr["curr_uvi"]).text();
                            $('#intensity_rows').html(cat_arr["intensity_rows"]).text();

                            $('#curr_feels_like').html(cat_arr["curr_feels_like"][0]).text();
                            $('#curr_sunrise').html(cat_arr["curr_sunrise"][0]).text();
                            $('#curr_sunset').html(cat_arr["curr_sunset"][0]).text();
                            $('#curr_temp').html(cat_arr["curr_temp"][0]+"&#8451;").text();

                            $('#curr_wind_deg').addClass('towards-'+cat_arr["curr_wind_deg"]+'-deg');   // Направление для ветра
                            $('#color_temp').addClass(cat_arr["color_temp"]);       // Цвет значения температуры

                            // Цвет значения температуры
                            if( cat_arr["main_icon"]  == 'video'){
                                $('#weather_img_icon').addClass('hidden');       // Скрываем иконку картинку

                                $('#weather_video_icon source').attr('src', cat_arr["video_icon_url"]);
                                $("#weather_video_icon")[0].load()
                            }

                            else if( cat_arr["main_icon"]  == 'image'){
                                $('#weather_video_icon').addClass('hidden');       // Скрываем иконку картинку
                                $('#weather_img_icon').empty();
                                $('#weather_img_icon').append(cat_arr["image_icon_url"]);
                            }
                        }
                        // конец Блок текущей погоды

                        // Блок почасовой погоды на 6 часов
                        if(cat_name == 'hours_6'){
                            $('#hours_block').empty();
                            $('#hours_block').append(cat_arr["rows"]);
                        }

                        // Блок почасовой погоды c 6-23 часов (график Google Charts)
                        if(cat_name == 'hours_gchart'){
                            $('#gchart_hour_min').val(cat_arr["curr_hour_min"]);
                            $('#gchart_hour_width').val(cat_arr["curr_hour_width"]);
                            $('#gchart_data').val(cat_arr["hour_chart_data"]);

                            google.charts.load("current", {packages: ["corechart", "bar"]});    /!* Загружаем API визуализации (пакет corechart)*!/
                            google.charts.setOnLoadCallback(weather.drawAnnotations);                   /!* Вызов колбэк функции, после которого api будет загружено *!/
                        }

                        // Блок посуточной погоды
                        if(cat_name == 'days'){
                            $('#days_block').empty();
                            $('#days_block').append(cat_arr["rows"]);
                        }
                    });
                } else{
                    alert(res['error']);
                    return false;
                }
            }
        });


        // let datetime = new Date($.now());
        var d = new Date();
        var strDate = d.getDate() + "." +(d.getMonth()+1) + "." +  d.getFullYear() + ' ' + d.getHours() + ':' + d.getMinutes();
        $('#time_sync').text(strDate);
    },


    // Создаём график и заполняем его данными
    drawAnnotations: function(){
        var curr_hour_min = $('#gchart_hour_min').val();
        var curr_hour_width = $('#gchart_hour_width').val();
        var hour_chart_data = $('#gchart_data').val();


        // можно задавать опции таким образом
        // myLine.setOption('hAxis.viewWindow.min', dateRange.min);
        // myLine.setOption('hAxis.viewWindow.max', dateRange.max);

        // Создаем таблицу с данными
        var data = new google.visualization.DataTable();        /* используем график вида DataTable (таблица со структурированной коллекцией) */
        data.addColumn("timeofday", "");                        /* колонка со временем суток */
        data.addColumn("number", "");                           /* колонка со значением температуры */
        data.addColumn({type: "string", role: "annotation"});   /* колонка с подсказками (при наведении) */

        hour_chart_data = $.parseJSON(hour_chart_data);

        data.addRows(hour_chart_data);

        // Задаём опции
        var options = {
            // title: "Температура в течении дня",         /*Название графика*/
            height: 200,                        /* высота графика в px */
            backgroundColor: "#000000",         /* Фоновый цвет */
            legend: {
                position: "none"
            },

            /* Область диаграммы (где рисуется сама диаграмма, за исключением оси и легенд) */
            chartArea: {
                // left: 10,                   /* Как далеко рисовать диаграмму от левой границы. */
                width: "100%",               /* ширина графика */
                // top: 40,
                //width: curr_hour_width+"%",
                height: "70%"
            },

            /* Аннотации (подписи к данным) */
            annotations: {
                alwaysOutside: false,          /* В столбчатых и столбчатых диаграммах, если установлено значение true, все аннотации отображаются за пределами столбца / столбца. */
                textStyle: {                  /* стили текста */
                    fontSize: 25,
                    bold: true,
                    color: "#ffffff",
                    auraColor: "none"    /* цвет контура */
                },
                //     boxStyle: {
                //         stroke: '#ffffff',
                //         strokeWidth: 0.5,
                //         rx: 5,
                //         ry: 5,
                //     gradient: {
                //         color1: '#ff5100',
                //         color2: '#ff5100',
                //         x1: '0%', y1: '0%',
                //         x2: '100%', y2: '100%',
                //         useObjectBoundingBoxUnits: true
                //     }
                // }
            },

            /* Настройка элементов горизонтальной оси */
            hAxis: {
                // title: "Time of Day",                /* Текст подписи */
                format: "H:mm",                         /* Формат выводимых данных */
                viewWindow: {                           /* Задает диапазон обрезки по оси. */
                    min: [curr_hour_min, 30],
                    max: [23, 30]
                },

                gridlines: {                /*настройки линий сетки на горизонтальной оси*/
                    color: "transparent"    // цвет прозрачный
                },

                viewWindowMode: "explicit"
            },


            /* Настройка элементов вертикальной оси */
            vAxis: {
                // title: "Rating (scale of 1-10)",     /* Текст подписи */

                /*настройки линий сетки на вертикальной оси*/
                gridlines: {
                    color: "transparent"    // цвет прозрачный
                },
                textPosition: "none"        // Положение текста горизонтальной оси относительно области диаграммы. Поддерживаемые значения: out, in, none.
            },
        };

        // Выбираем формат нашей диаграммы (в данном случае ColumnChart - в виде колонок)
        var chart = new google.visualization.ColumnChart(document.getElementById("chart_div"));

        /* Отрисовывем диаграмму используя данные и опции */
        chart.draw(data, options);
    },


}