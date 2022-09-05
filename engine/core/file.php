<?php
/** Операции с файлами */

// Максимальный размер загружаемого файла
//ini_set('upload_max_filesize', '5M');

class FILE
{


    /**
     * Сохраняем изображение в папку сайта
     * @param array $file           - массив $_FILES
     * @param string $subfolder     - пользовательская подпапка
     * @param string $custom_name   - пользовательское имя файла
     * @param string $resolution    - разрешение файла
     * @return int file_id
     */
    public static function saveOneImageSiteDir($file, $subfolder = '', $custom_name = '', $resolution = '40x40'){
        $res = [];

        // todo задействовать разные разрешения

        /*===== Проверка на ошибки при загрузки =====*/
        if (!empty($file['error']) || empty($file['tmp_name'])) {
			switch (@$file['error']) {
				case 1:
				case 2: $res['errors'][] = 'Превышен размер загружаемого файла.'; break;
				case 3: $res['errors'][] = 'Файл был получен только частично.'; break;
				case 4: $res['errors'][] = 'Файл не был загружен.'; break;
				case 6: $res['errors'][] = 'Файл не загружен - отсутствует временная директория.'; break;
				case 7: $res['errors'][] = 'Не удалось записать файл на диск.'; break;
				case 8: $res['errors'][] = 'PHP-расширение остановило загрузку файла.'; break;
				case 9: $res['errors'][] = 'Файл не был загружен - директория не существует.'; break;
				case 10: $res['errors'][] = 'Превышен максимально допустимый размер файла.'; break;
				case 11: $res['errors'][] = 'Данный тип файла запрещен.'; break;
				case 12: $res['errors'][] = 'Ошибка при копировании файла.'; break;
				default: $res['errors'][] = 'Файл не был загружен - неизвестная ошибка.'; break;
			}
		} elseif ($file['tmp_name'] == 'none' || !is_uploaded_file($file['tmp_name'])) {
			$res['errors'][] = 'Не удалось загрузить файл.';
        }


        /*===== Определяем пути =====*/
        $subfolder_way = $subfolder? '/'.$subfolder : '';       // Пользовательская поддиректория
        $base_dir = _ROOT_DIR_;
        $file_dir = _VIEWS_.'/sources/img' . $subfolder_way;

        if( !is_dir($base_dir.$file_dir) ){
            // создаем папку если такой нет
            mkdir($base_dir.$file_dir, 0777, true);
        }


        /*===== Определяем имена =====*/
        $file_info = pathinfo($file['name']);   // Разбираем файл на составны части

        $file_name = $custom_name? : $file_info['filename'];        // Если задано определенное имя, используем его
        if (preg_match("/[А-Яа-я]/", $file_name)) {
            // Кириллицу в названии преобразуем в латиницу
            $file_name = translit($file_name);
        }
        $file_name = strtolower($file_name);

        // Оставляем в имени файла только буквы, цифры и некоторые символы.
        $pattern = "[^a-z0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
        $file_name = mb_eregi_replace($pattern, '_', $file_name);

        $ext = $file_info['extension'];


        // Чтобы не затереть файл с таким же названием, добавим префикс.
        $i = 0;
        $prefix = '';
        while (is_file($base_dir.$file_dir.'/'.$file_name . $prefix .'.'.$ext)) {
            $prefix = '(' . ++$i . ')';
        }
        $file_name = $file_name . $prefix;

        // Итоговый путь
        $full_way = $base_dir.$file_dir.'/'.$file_name.'.'.$ext;



        /*===== Проверка на недопустимые расширения =====*/
        // Разрешенные расширения файлов.
        $allow = [];

        // Запрещенные расширения файлов.
        $deny = [
            'phtml', 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps', 'cgi', 'pl', 'asp',
            'aspx', 'shtml', 'shtm', 'htaccess', 'htpasswd', 'ini', 'log', 'sh', 'js', 'html',
            'htm', 'css', 'sql', 'spl', 'scgi', 'fcgi'
        ];

        if (empty($file_name) || empty($ext)) {
            $res['errors'][] = 'Недопустимое тип файла';
        } elseif (!empty($allow) && !in_array(strtolower($ext), $allow)) {
           $res['errors'][] = 'Недопустимое тип файла';
        } elseif (!empty($deny) && in_array(strtolower($ext), $deny)) {
            $res['errors'][] = 'Недопустимое тип файла';
        }



        // Перемещаем файл в финальный каталог
        if( !move_uploaded_file($file['tmp_name'], $full_way) ){
            $res['errors'][] = 'Ошибка при перемещении файла из временной директории';
        }

        if( !empty($res['errors']) ) return $res;  // Прерываем если есть ошибки


        // Сохраняем в БД
        $filesObj = new table('_files');
        $filesObj->byId(0);
        $filesObj->set('title', $file_name);
        $filesObj->set('url', $file_dir.'/'.$file_name.'.'.$ext);
        $filesObj->set('title_orig', mysql_escape_mimic($file['name']));
        $filesObj->set('type', $file['type']);
        $filesObj->set('ext', $ext);
        $filesObj->save();

        $res['file_id'] = $filesObj->id;

        return $res;
    }
}