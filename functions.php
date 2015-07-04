<?php

if (!function_exists('pf_param'))
{
    /**
     * Возвращает содержимое указанного параметра из механизма роутинга
     * @param string $name Наименование параметра
     * @return string содержимое
     */
    function pf_param($name = 'one', $default = null) {
        $current_route = Route::getCurrentRoute();
        if (is_null($current_route)) {
            return $default;
        } else {
            return Route::getCurrentRoute()->getParameter($name, $default);
        }
    }
}

if (!function_exists('pf_controller'))
{
    /**
     * Возвращает ссылку на индексную функцию текущего контроллера
     * @return string
     */
    function pf_controller() {
        $namespace = PrettyFormsLaravel\Helper::getCurrentAppNamespace();
        $current_action = str_replace($namespace.'Http\Controllers\\','',Route::getCurrentRoute()->getActionName());
        $controller_name = mb_substr($current_action,0, mb_strpos($current_action, '@'));
        $url = action($controller_name . '@getIndex');
        return str_replace('/index', '', $url);
    }
}

if (!function_exists('pf_get_value'))
{
    /**
     * Функция сначала ищет значение в переданном массиве $values
     * Если не находит, возвращает текущее значение модели $item
     * @param string $key Название параметра
     * @param Model $item Объект модели
     * @param array $values Ассоциативный массив со значениями
     * @return type
     */
    function pf_get_value($key, $item, $values) {
        if (isset($values[$key])) {
            return $values[$key];
        } else {
            return $item->$key;
        }
    }
}

if (!function_exists('pf_get_item_value'))
{
    /**
     * Специальная функция для получения значения из объекта на основе указанного объекта информации
     */
    function pf_get_item_value($item,$key = NULL) {
        // Если для значений был передан массив, обрабатываем его особым образом
        // Если в значениях массива встретится массив, значит это функция..
        // Если в значениях встретится простая строка, то передаем её как строку
        if (is_array($key)) {
            $item_value = '';
            foreach ($key as $key_param) {
                if (is_array($key_param)) {
                    $item_method = $key_param[0];
                    if (isset($key_param[1])) {
                        $item_value .= $item->$item_method($key_param[1]);
                    } else {
                        $item_value .= $item->$item_method();
                    }
                } else {
                    $item_value .= $key_param;
                }
            }

            return $item_value;
        } elseif (empty($key)) {
            return (string)$item;
        } else {
            return method_exists($item, $key) ? $item->$key() : $item->$key;
        }
    }
}

if (!function_exists('pf_array_pluck_object'))
{
    function pf_array_pluck_object($mass, $key, $label = null) {
        $newarray = array();
        foreach ($mass as $item) {
            $newarray[pf_get_item_value($item, $key)] = pf_get_item_value($item, $label);
        }
        return $newarray;
    }
}

if (! function_exists('pf_display_messages'))
{
    /**
     * Отобразить сообщения, заготовленные в сессии для пользователя
     * @return string Строка с сообщениями
     */
    function pf_display_messages() {
        if (Session::has('message')) {
            list($type, $message) = explode('|', Session::get('message'));

            if ($message === null) {
                $message = $type;
            }
            $type = ($type === $message) ? 'info' : $type;

            return sprintf( config('prettyforms.messages-template'), $type, $message);
        }
        return '';
    }
}