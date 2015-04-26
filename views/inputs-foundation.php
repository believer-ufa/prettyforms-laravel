<?php

// Генерация полей для добавления/редактирования записи

if (!isset($prefix)) {
    $prefix = '';
}

if (!isset($values)) {
    $values = null;
}

// В некоторых исключительных ситуациях бывает необходимо вытащить номер текущего редактируемого объекта
echo Form::hidden('data_item_id', $item->id);
echo Form::token();

foreach ($fields as $name => $field) {
    if (!isset($field['attributes'])) {
        $field['attributes'] = [];
    }

    $input_name = $prefix . $name;
    $label = array_get($field, 'label');

    if (Lang::has($label)) {
        $label = trans($label);
    }

    if ($field['tag'] == 'hidden') {
        echo Form::hidden($input_name, pf_get_value($name, $item, $values), $field['attributes']);
    } else {
        
        $field_is_required = '';
        if (isset($field['attributes']['data-validation']) AND str_contains($field['attributes']['data-validation'], 'notempty')) {
            $field_is_required = '<span class="text-danger" title="'.trans('prettyforms::validation.field_is_required').'"> *</span>';
        }

        echo "<div class='row'>";
        echo "<div class='small-12 medium-3 columns'> <label for='field-{$input_name}' class='small-text-left medium-text-right inline'>{$label}{$field_is_required}";
        if (isset($field['desc'])) {
            echo "<br/><small class='text-muted' style='font-weight: normal'>{$field['desc']}</small>";
        }
        echo "</div>";


        echo '<div class="small-12 medium-9 columns">';
        echo view("prettyforms::tags.{$field['tag']}", compact('item','field','values','input_name', 'name', 'label'))->render();
        echo "<small style='display:none' id='validation-error-{$input_name}' class='error'></small>";
        echo '</div></div>';
        
    }
}