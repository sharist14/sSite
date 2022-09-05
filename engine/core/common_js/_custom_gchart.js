var gchart = {
    core_js:  '/engine/core/common_js',
    money_js: '/view/sites/money/sources/js',


     /* ДЛЯ ГРАФИКА ПИРОГА */

    /**
     * Рисуем график Google Charts для пирога
     * @param chartdata (обязательные отмечены *)
             * chartdata['package'] = "corechart";
             * chartdata['data'] = [
                  ['Task', 'Hours per Day'],
                  ['Аренда',     24780],
                  ['Еда',      15227],
                  ['Кафе',  4200],
                  ['Развлечения', 6800],
                  ['Хозяйственные',    1300]
              ];
              chartdata['title'] = "Дата: 02.2022;
              chartdata['is3D'] = false;
              chartdata['div_id'] = "piechart02";
              chartdata['div_class'] = "";
              chartdata['div_style'] = "width: 900px; height: 500px;";

     */
    gchartPie: async function(chartdata){

        await $.getScript(gchart.core_js+'/google_charts_loader.js', async function() {
            google.charts.load("current", {packages:[chartdata.package]});

            google.charts.setOnLoadCallback(async function(){

                // Создаем архитектуру элементов
                var month_div = '<div id="'+chartdata.div_id+'" class="chart alert alert-light border border-light '+chartdata.div_class+'" style="width: 400px"></div>';
                $('#googleCharts').append(month_div);
                $('#'+chartdata.div_id).append('<div id="chart_'+chartdata.div_id+'" style="'+chartdata.div_style+'" align="center">');


                /***** ГРАФИК *****/

                // adapted from a previous example
                var colorPallette = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#3366cc","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];

                var data = google.visualization.arrayToDataTable(chartdata.data);

                var options = {
                    colors: colorPallette,
                    // title: chartdata.title,
                    is3D: chartdata.is3D,
                    legend: chartdata.legend,
                    pieHole: 0.0,
                    chartArea: {
                        left: 0,                   /* Как далеко рисовать диаграмму от левой границы. */
                        // top: 0,
                        height: "100%",
                        width: "100%"               /* ширина графика */
                    },
                };

                var chart = new google.visualization.PieChart(document.getElementById('chart_'+chartdata.div_id));

                /***** конец ГРАФИК *****/


                // Дожидаемся когда график будет ожидать (chart ready event)
                google.visualization.events.addListener(chart, 'ready', function () {

                    /***** ЛЕГЕНДА *****/

                    $('#'+chartdata.div_id).append('<div id="legend_'+chartdata.div_id+'" class="justify-content-center">');
                    $('#legend_'+chartdata.div_id).append('<div id="sum_'+chartdata.div_id+'" class="mt-4" style="text-align: center"></div>');

                    var legendTable = gchart.getPieLegendTpl();         // получаем шаблон
                    $('#legend_'+chartdata.div_id).append(legendTable);


                    // Заголовки таблицы легенды
                    for (var colIndex = data.getNumberOfColumns() - 1; colIndex >= 0; colIndex--) {
                      var markerProps = {};
                      markerProps.label = data.getColumnLabel(colIndex);
                      markerProps.id = chartdata.div_id;
                      if(markerProps.label == '') continue;     // Пропускаем заголовок с данными
                      gchart.addLegendHead(markerProps);
                    }


                    // Данные таблицы легенды
                    var sum_month = 0;
                    for (var colIndex = data.getNumberOfRows() - 1; colIndex >= 0; colIndex--) {
                      var markerProps = {};
                      markerProps.index = colIndex;
                      markerProps.color = colorPallette[colIndex];
                      markerProps.label = data.getValue(colIndex, 0);
                      markerProps.value = data.getValue(colIndex, 1) + ' руб';
                      markerProps.type_id = data.getValue(colIndex, 2);
                      markerProps.id = chartdata.div_id;

                      sum_month += parseInt(markerProps.value);

                      gchart.addLegendBody(markerProps);
                    }

                    $('#sum_'+chartdata.div_id).append("<span class='text-danger'>Сумма за "+chartdata.title+": <b>"+sum_month+" руб</b></span>");

                    /***** конец ЛЕГЕНДА *****/


                    /***** FLOW *****/
                    $('#'+chartdata.div_id).append('<div id="flow_'+chartdata.div_id+'" class="flow">');


                    // add click event to each legend marker
                    // var markers = legend.getElementsByTagName('DIV');
                    // Array.prototype.forEach.call(markers, function(marker) {
                    //   marker.addEventListener('click', function (e) {
                    //     var marker = e.target || e.srcElement;
                    //     if (marker.tagName.toUpperCase() !== 'DIV') {
                    //       marker = marker.parentNode;
                    //     }
                    //     var columnIndex = parseInt(marker.getAttribute('data-columnIndex'));
                    //     document.getElementById('message_div').innerHTML = 'legend marker clicked = ' + data.getValue(columnIndex, 0);
                    //   }, false);
                    // });

                    // var markers = legend.getElementsByTagName('TR');
                    // Array.prototype.forEach.call(markers, function(marker) {
                    //   marker.addEventListener('click', function (e) {
                    //     var marker = e.target || e.srcElement;
                    //     if (marker.tagName.toUpperCase() !== 'TR') {
                    //       marker = marker.parentNode;
                    //     }
                    //     var columnIndex = parseInt(marker.getAttribute('data-columnIndex'));
                    //     document.getElementById('message_div').innerHTML = 'legend marker clicked = ' + data.getValue(columnIndex, 0);
                    //   }, false);
                    // });

                });

                await chart.draw(data, options);
            });
        });
    },


    /* Получить шаблон легенды для пирога */
    getPieLegendTpl: function(){
        return '<div class="table_div" style="max-width: 400px;">\
                    <table class="table table-bordered">\
                        <thead>\
                            <tr></tr>\
                        </thead>\
                        <tbody>\
                        </tbody>\
                    </table>\
                </div>';
    },


    addLegendHead: function(markerProps){
        var thead_tr = document.getElementById("legend_"+markerProps.id).querySelector("table > thead > tr");
        thead_tr.insertCell(0).outerHTML = "<th>"+markerProps.label+"</th>";  // rather than innerHTML
    },

    addLegendBody: function(markerProps){
        var tbody = document.getElementById("legend_"+markerProps.id).querySelector("table > tbody");
        var row = tbody.insertRow(0);
        row.setAttribute("id", markerProps.id+'_'+markerProps.type_id, 0);
        var td = "<td><div style='display: inline-block; height: 12px; width: 12px; background-color: "+markerProps['color']+"'></div> "+markerProps.label+"</td><td style='text-align: right'>"+markerProps.value+"</td>";
        row.insertCell(0).outerHTML = td;
    },

    parseYears: async function (data) {
        var years = data;

        for (var year in years) {
            await gchart.parseMonth(year, data);
        }
    },

    parseMonth: async function (year, data) {
        var months = data[year];

        for (var month in months) {
            await gchart.parseDataPie(year, month, data);
        }
    },

    parseDataPie: async function (year, month, data) {
        // График3
        var header = ['Категория','Сумма', {'role': 'info'}];
        var chartdata = [];
        chartdata['data'] = [header].concat(data[year][month]);
        chartdata['package'] = "controls, corechart";
        chartdata['title'] = month+"."+year;
        chartdata['is3D'] = false;
        chartdata['div_id'] = year+'_'+month;
        chartdata['div_class'] = "col-12 col-xl-6";
        chartdata['div_style'] = "";
        chartdata['legend'] = "none";

        await gchart.gchartPie(chartdata);
    },


    // render html template
    // addLegendMarker: function(markerProps){
    //     var legendMarker = document.getElementById('template-legend-marker').innerHTML;
    //     for (var handle in markerProps) {
    //       if (markerProps.hasOwnProperty(handle)) {
    //         legendMarker = legendMarker.replace('~~' + handle + '~~', markerProps[handle]);
    //       }
    //     }
    //     document.getElementById('legend_div').insertAdjacentHTML('beforeEnd', legendMarker);
    // }


    /* ДЛЯ ЛИНЕЙНОГО ГРАФИКА */
    parseCats: async function (data) {

        for (let div_id in data) {
            await gchart.parseDataLinear(div_id, data[div_id]);
        }
    },

    parseDataLinear: async function (div_id, arr) {

        // График
        var chartdata = [];
        chartdata['package'] = "corechart";

        chartdata['data'] = arr['data'];
        chartdata['title'] = arr['title'];
        chartdata['div_id'] = div_id;
        // chartdata['div_class'] = "col-12 col-xl-6";
        // chartdata['div_style'] = "";
        chartdata['legend'] = "none";
        chartdata['color'] = arr['color'];

        await gchart.gchartLinear(chartdata);
    },

    gchartLinear: async function(chartdata){

        await $.getScript(gchart.core_js+'/google_charts_loader.js', async function() {
            google.charts.load("current", {packages:[chartdata.package]});

            google.charts.setOnLoadCallback(async function(){

                // Создаем архитектуру элементов
                var month_div = '<div id="'+chartdata.div_id+'" class="chart alert alert-light border border-light '+chartdata.div_class+'" style="width: 100%"></div>';
                $('#googleCharts').append(month_div);
                $('#'+chartdata.div_id).append('<div id="chart_'+chartdata.div_id+'" style="'+chartdata.div_style+'" align="center">');


                /***** ГРАФИК *****/

                var data = new google.visualization.DataTable();

                // Добавляем колонки
                data.addColumn('number', 'Месяцы');
                data.addColumn('number', chartdata.title);
                data.addColumn({type:'string', role:'annotation'}); // annotationText col
                data.addColumn({type:'string', role: 'info'}); // annotationText col

                var new_rows = [];
                var new_hAxis = [{'v': 0, 'f': ''}];
                for(let key in chartdata.data){
                    // console.log(chartdata.data);
                    let month = chartdata.data[key][0];
                    let year = chartdata.data[key][1];
                    let sum = chartdata.data[key][2];
                    let cat_id = chartdata.data[key][3];
                    
                    new_rows[key] = [Number(key)+1, sum, String(sum), String(month+'~'+year+'~'+cat_id)];

                    new_hAxis[Number(key)+1] = {'v': Number(key)+1, 'f': String(month+'.'+year)};
                    last_key = Number(key)+1;
                }
                new_hAxis[last_key+1] = {'v': last_key+1, 'f': ''};

                // Добавляем строки с данными
                data.addRows(new_rows);


                var options = {
                    hAxis: {
                        // Добавляем строки с датами
                        ticks: new_hAxis,
                        // title: '2022 год'
                    },
                    vAxis: {
                        textPosition: 'none'
                    },
                    displayAnnotations: true,

                    /* Аннотации (подписи к данным) */
                    annotations: {
                        alwaysOutside: false,          /* В столбчатых и столбчатых диаграммах, если установлено значение true, все аннотации отображаются за пределами столбца / столбца. */
                        textStyle: {                  /* стили текста */
                            fontSize: 25,
                            bold: true,
                            color: "#ffffff",
                            auraColor: "none"    /* цвет контура */
                        },
                            boxStyle: {
                                stroke: '#ffffff',
                                strokeWidth: 0.5,
                                rx: 5,
                                ry: 5,
                            gradient: {
                                // color1: '#ff5100',
                                // color2: '#ff5100',
                                color1: chartdata['color'],
                                color2: chartdata['color'],
                                x1: '0%', y1: '0%',
                                x2: '100%', y2: '100%',
                                useObjectBoundingBoxUnits: true
                            }
                        }
                    },
                    colors: [chartdata.color],
                    title: chartdata.title,
                    legend: chartdata.legend,
                    chartArea: {
                        left: 0,                   /* Как далеко рисовать диаграмму от левой границы. */
                        // top: 0,
                        height: "80%",
                        width: "100%"               /* ширина графика */
                    },
                  };


                var chart = new google.visualization.LineChart(document.getElementById('chart_'+chartdata.div_id));

                /***** конец ГРАФИК *****/


                /***** Клик на сумму в графике *****/
                // При нажатии на подсказку с суммой
                var handler = function(e) {
                    var parts = e.targetID.split('#');
                    
                    var header = data['If'][1]['label'];

                    if (parts[0] == 'annotationtext') {
                        var idx = Number(parts[2]);

                        // Находим данные по месяцу, году и категории
                        var find = data.getValue(idx,3);
                        var find_parts = find.split('~');
                        let month = find_parts[0];
                        let year = find_parts[1];
                        let type_id = find_parts[2];

                        // Находим направление
                        var flow_direct = $('.flow_type:checked').val();

                        $.ajax({
                            url: '/analytics?act=_ajax_getMonthTypeFlow',
                            method: 'post',
                            dataType: 'html',
                            data: {year: year, month: month, type_id: type_id, flow_direct: flow_direct},
                            success: function(data){

                                // Открываем модальное окно
                                utils.modal_custom(data, header);
                            }
                        });
                    }

                };
                google.visualization.events.addListener(chart, 'click', handler);

                /***** конец Клик на сумму в графике *****/

                await chart.draw(data, options);
            });
        });
    },


};