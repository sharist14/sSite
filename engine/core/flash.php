<?php
/** Всплывающие подсказки */

session_start();
if($_GET['del_noty']) flash::del_noty($_GET['del_noty']);
if($_GET['del_toast']) flash::del_toast($_GET['del_toast']);


class flash {

    // Информационные сообщения в div
    public static function add_noty($title, $text, $delay = 0, $type = 'secondary', $datetime = 0){
        if( !$datetime ) $datetime = time();

        $_SESSION['flash']['noty'][] = [
            'title' => $title,
            'text' => $text,
            'type' => $type,
            'datetime' => $datetime,
            'delay' => $delay
        ];
    }

    // Всплывающие информационные сообщения
    public static function add_toast($title, $text, $delay = 0, $type = 'secondary', $datetime = 0){
        if( !$datetime ) $datetime = time();

        $_SESSION['flash']['toast'][] = [
            'title' => $title,
            'text' => $text,
            'type' => $type,
            'datetime' => $datetime,
            'delay' => $delay
        ];
    }

    static function del_noty($id){
//        unset($_SESSION['flash']['noty'][intval($id)]);
    }

     static function del_toast($toast){
        $id = preg_replace('/[^0-9]/', '', $toast);

        unset($_SESSION['flash']['toast'][intval($id)]);
    }

    static function view(){
        if( !empty($_SESSION['flash']['toast']) ){
            $cur_time = time();


            // Формируем подсказки
            echo '<div class="toast-container" style="position: fixed; top: 80px; right: 10px; z-index: 9999; width: 95%; max-width: 400px;">';
            foreach($_SESSION['flash']['toast'] as $key => $toast){

                // Когда была добавлена подсказка
                $diff = diff_time($toast["datetime"], $cur_time);

                // Автозакрытие подсказки
                $delay = $toast['delay']? 'data-bs-delay="'.($toast["delay"] * 1000).'"' : 'data-bs-autohide="false"';

                echo '<div id="toast'.$key.'" class="toast bg-'.$toast['type'].' text-white" '.$delay.' style="display: none; width:100%">
                    <div class="toast-header bg-'.$toast['type'].' text-white">
                        <strong class="me-auto"><i class="bi-globe"></i>'.$toast["title"].'</strong>
                        <small>'.$diff.'</small>
                        <button onclick="close_toast('.$key.')" type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">'.$toast["text"].'</div>
                </div>';
            }
            echo '</div>';


            echo '<script>              
                /* Скрываем подсказку */
                function close_toast(id){
                    $("#toast"+id).toast("hide");      
                }
            </script>';
        }
    }

}