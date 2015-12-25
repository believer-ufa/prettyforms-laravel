<?php

// Генерация полей для добавления/редактирования записи

if ( ! isset($prefix)) {
    $prefix = '';
}

if ( ! isset($values)) {
    $values = null;
}

// В некоторых исключительных ситуациях бывает необходимо вытащить номер текущего редактируемого объекта
echo Form::hidden('data_item_id', $item->id);
echo Form::token();

foreach ($fields as $name => $field) {
    if ( ! isset($field['attributes'])) {
        $field['attributes'] = [];
    }

    $input_name = $prefix.$name;
    $label      = array_get($field, 'label');

    if (Lang::has($label)) {
        $label = trans($label);
    }

    if ($field['tag'] == 'hidden') {
        echo Form::hidden($input_name, pf_get_value($name, $item, $values), $field['attributes']);
    } else {
        $field_is_required = '';
        if (isset($field['attributes']['data-validation']) and str_contains($field['attributes']['data-validation'], 'notempty')) {
            $field_is_required = '<span class="req" title="'.trans('prettyforms::validation.field_is_required').'"> *</span>';
        }

        echo '<section>';
        echo "<label><div>{$label}{$field_is_required}</div>";
        if (isset($field['desc'])) {
            echo "<div class='soft-muted small'>{$field['desc']}</div><br>";
        }

        echo view("prettyforms::tags.{$field['tag']}", compact('item', 'field', 'values', 'input_name', 'name', 'label'))->render();

        echo "<div style='display:none' id='validation-error-{$input_name}' class='alert alert-error'></div>";
        echo '</section>';
    }
}
