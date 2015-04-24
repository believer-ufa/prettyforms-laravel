<?php

/**
 * Данный файл генерирует сразу несколько разных элементов интерфейса,
 * позволяющих выбрать нужный объект в форме. Вариантов может быть несколько.
 *
 * В данный файл должны быть отправлены следующие переменные:
 *
 * $input_name - название инпута, который будет сгенерирован для объекта
 * $item - текущий объект, в котором мы будем смотреть текущие связи.
 * $name - наименование связи текущей модели, которую мы редактируем, с теми объектами, которые мы выбираем
 * $field - массив со всеми основными параметрами:
 * [
 *    'tag' => 'Название тэга, который надо сгенерировать. Может иметь четыре разных варианта:
 *              select        - обычный селект с возможностью выбрать 1 элемент
 *              select-multi  - селект, позволяющий выбрать несколько элементов
 *              search        - поисковая строка, позволяющая найти нужный объект через ajax-поиск
 *              search-multi  - поисковая строка, через которую можно указать несколько объектов'
 *    [необ.] 'attributes' => [ массив атрибутов select'а ]
 *    'options' => [ ассоциативный массив значений, если он указан, то он будет использоваться для генерации пунктов select'а ]
 *    'model'   => 'наименование модели, из которой будут взяты все значения для select'а.
 *                  Модель должна иметь метод __toString(), который будет вызван для
 *                  получения наименований объектов'
 *    [только для атр. типа select] [необ.] 'model_wheres' => [ ['field_name', 'condition', 'value'], ... ] -- массив условий для выборки данных из модели
 *    [только для атр. типа select] [необ.] 'model_orders' => 'массив свойст, по которым надо отсортировать данные'
 *    [необ.] 'placeholder' => 'Текст, отображающийся в select'е тогда, когда в нём ничего не выбрано'
 *
 *    // Следующие свойства используются для тэгов search и search-multi:
 *    [только для атр. типа search] 'search_method' => 'название экшена в Search-контроллере, по которому будут искаться объекты'
 *    [необ.] 'display_as' => 'Свойство в объекте, которое будет выступать как наименование объекта. Можно также использовать название метода в модели'
 * ]
 *
 * [необ.] $values['имя объекта'] - массив выбранных значений, который будет перекрывать выбранные элементы
 */

if (!isset($name)) {
    $name = $input_name;
}

if (!isset($javascript)) {
    $javascript = '';
    $execute_js = true;
}

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}

// Если это простой селект, то вытащим из БД его пункты выбора
if (in_array($field['tag'],['select-multi','select'],true)) {
	if (isset($field['options'])) {
		$options = $field['options'];
	} elseif (isset($field['model'])) {
		$model = new $field['model'];

		// Отфильтруем данные, если есть необходимость
		if (!empty($field['model_wheres'])) {
			foreach ($field['model_wheres'] as $where) {
				$model = $model->where($where[0], $where[1], $where[2]);
			}
		}

		// Отсортируем данные, если есть необходимость
		if (!empty($field['model_orders'])) {
			$model = $model->orderBy($field['model_orders']);
		}

		$options = array_pluck_object($model->get(), 'id', array_get($field,'display_as'));
		if (!empty($field['placeholder'])) {
			$options = ['' => __($field['placeholder']) ] + $options;
		}
	}
}

if (in_array($field['tag'],['select-multi','search-multi'],true)) {
    $field['attributes']['multiple'] = '.';
    if (isset($item) AND $item->exists AND empty($values[$name])) {
        $selected = array_pluck($item->$name()->get(), 'id');
    }
} else {
    if (isset($item)) {
        $selected = get_value($name, $item, $values);
    } else {
        $selected = array_get($values, $name);
    }
}

if (in_array($field['tag'],['select-multi','select'],true)) {

	$field['attributes']['data-placeholder'] = $label . '..';
	if (empty($options)) { $options = ['' => 'список пуст']; }
	$javascript .= "$('select[name=\"$input_name\"]').chosen({disable_search_threshold: 15});";
	echo Form::select($input_name, $options, $selected, $field['attributes']);

} elseif(in_array($field['tag'],['search-multi','search'],true)) {

    $field['attributes']['data-placeholder'] = !empty($field['placeholder'])
        ? __($field['placeholder'])
        : __('search.default_placeholder');

	// Изначально селект имеет в своём списке только те значения, которые уже присвоены к этому объекту
	$options = [];

	if ($selected) {
		foreach($field['model']::whereIn('id',(array)$selected)->get() as $selected_item) {
			$options[$selected_item->id] = get_item_value($selected_item,array_get($field,'display_as'));
		}
	}

	$javascript .= "App.ajaxSelect('select[name=\"$input_name\"]','/search/{$field['search_method']}');";
	echo Form::select($input_name, $options, $selected, $field['attributes']);
}

if (isset($execute_js)) {
    echo '<script>App.onFullLoad(function(){' . $javascript . '});</script>';
}