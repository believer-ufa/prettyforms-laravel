<?php

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

function pf_array_pluck_object($mass, $key, $label = null) {
	$newarray = array();
	foreach ($mass as $item) {
		$newarray[pf_get_item_value($item, $key)] = pf_get_item_value($item, $label);
	}
	return $newarray;
}

