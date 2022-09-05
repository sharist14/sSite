<?php


class entity extends parentTemplate
{

    var $entObj;

    public function __construct($params = []){
        $this->entObj = new table('entity');
        parent::__construct($params);
    }


    function _act_(){
        $tpl = get_template('', 'entity', 'body');
        $tplr = get_template('', 'entity', 'row');

        $db = $this->entObj->getSSelect();
        while($row = $db->fetch_assoc()){
            $tt = $tplr;
            $tt = set($tt, 'id', $row['id']);

            // Логотипы
            if($row['file_id']){
                $fileObj = new table('_files');
                $fileObj->byId(intval($row['file_id']));

                $logo = '<img style="width: 45px;" src="'.$fileObj->url.'" alt="">';
                $tt = set($tt, 'logo', $logo);

            } else{
                $tt = set($tt, 'display_img', 'bg-secondary');
            }

            if($row['title_short']){
                $tt = set($tt, 'title_short', $row['title_short']);
            } else{
                $tt = set($tt, 'display_title_short', 'bg-secondary');
            }

            $tt = set($tt, 'title', $row['title']);
            $tt = set($tt, 'inn', $row['inn']);

            $tpl = setm($tpl, 'rows', $tt);
        }

        $this->render($tpl);
    }


    /**
     * Форма добавления/редактирования записи
     */
    function _act_edit(){
        $tpl = get_template('', 'entity', 'edit_form');

        $entTable = new table('entity');

        // При редактировании записи
        if($_GET['id']) {
            $entityID = intval($_GET['id']);
            $this->entObj->byId($entityID);
            $fileObj = new table('_files');

            $data = $this->entObj->get_vars_values();

            foreach($data as $k => $v){
                // Отдельно обрабатываем логотипы
                if($k == 'file_id' && !empty($v)){
                    $fileObj->byId(intval($v));

                    $logo = '<img style="width: 45px;" src="'.$fileObj->url.'" alt="">';
                    $tpl = set($tpl, 'logo', $logo);
                    continue;
                }


                // Добавляем остальные поля
                $tpl = set($tpl, $k, $v);
            }
        } else{
            $tpl = set($tpl, 'hide_del_btn', 'hide');
        }


        $this->render($tpl);
    }
    
    
    /**
     * Сохраняем юрика в БД
     */
    function _act_save($data = [], $return_result = false){
        if (empty($data) && !empty($_POST)) $data = $_POST;
        if (!$data) die('Нет данных для сохранения');


        // Проверяем загруженные файлы
        $file_id = '';
        if($_FILES['logo']['tmp_name']){
            if(!$data['title_short']) die('Задайте короткое имя, чтобы обозвать лого им же');

            // Отправляем на сохранение
            $answer = file::saveOneImageSiteDir($_FILES['logo'], 'entity', $data['title_short']);

            if( !$answer['errors'] && $answer['file_id'] ){
                $file_id = $answer['file_id'];
            } else{
                echo 'ошибки при загрузке файла';
                pr($answer['errors']);
                die();
            }
        }

        // Сохраняем основную запись
        $entityID = intval($data['id']);

        $this->entObj->byId($entityID);
        $this->entObj->set('title_short', htmlentities($data['title_short']));
        $this->entObj->set('title', htmlentities($data['title']));
        $this->entObj->set('inn', mysql_escape_mimic($data['inn']));
        if($file_id) $this->entObj->set('file_id', $file_id);
        $this->entObj->save();


        flash::add_toast('Entity', 'Запись успешно создана/обновлена', 2, 'success');
        _redirect('/entity');
    }

     /**
     * Удалить юрика из БД
     */
    function _act_delete_entity($id = '', $redirect = ''){

        // Находим id
        $entityID = '';
        if ($id) {
            $entityID = intval($id);
        } elseif ($_GET['id']) {
            $entityID = intval($_GET['id']);
        }
        if (!$entityID) die('Нет $entID, который необходим для удаления записи');


        // Удаляем запись
        $this->entObj->byId($entityID);
        $this->entObj->del();

        flash::add_toast('Entity', 'Запись успешно удалена', 2, 'info');
        _redirect('/entity');
    }
}