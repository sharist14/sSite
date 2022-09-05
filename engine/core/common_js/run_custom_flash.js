$(function(){

    // Всплывающие подсказки
    $(".toast").show();
    $(".toast").toast("show");

    $('.toast').each(function(){
        $(this).on('hide.bs.toast', function (){
           $.get('/engine/core/flash.php?del_toast='+$(this).attr('id'))
        });
    });
});