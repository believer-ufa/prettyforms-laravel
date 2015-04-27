<?php

use Illuminate\Validation\Validator;
use PrettyFormsLaravel\Validation\ValidatorException;
use PrettyFormsLaravel\Http\Commands;

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

/**
 * Произвести валидацию
 * @param array $rules ассоциативный массив, где ключи - названия полей, а значения - это правила
 */
function pf_validate_inputs($rules) {
    $validator = Validator::make(Input::only(array_keys($rules)),$rules);
    if ($validator->fails()) {
        pf_validation_error($validator->errors()->getMessages());
    }
}

/**
 * Выкинуть ошибку валидации
 * @param array $messages
 * @throws ValidatorException
 */
function pf_validation_error($messages) {
    if ($messages instanceof Validator) {
        $messages = $messages->errors()->getMessages();
    }
    throw (new ValidatorException)->setValidationErrors($messages);
}

/**
 * Сгенерировать ответ с информацией об ошибках валидации для библиотеки PrettyForms
 * @param Exception $e
 * @return \Symfony\Component\HttpFoundation\Response
 */
function pf_validation_errors_answer($e) {
    return response()->json(Commands::generate([
        'validation_errors' => Commands::generateValidationErrors($e->getValidationErrors())
    ]));
}

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

include('functions_for_arrays.php');
