<?php

// Генерация полей для добавления/редактирования записи
$javascript = '';

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

        echo "<div class='form-group'>";
        echo "<label for='field-{$input_name}' class='col-sm-2 control-label'>{$label}{$field_is_required}";
        if (isset($field['desc'])) {
            echo "<br/><small class='text-muted' style='font-weight: normal'>{$field['desc']}</small>";
        }
        echo "</label>";


        echo '<div class="col-sm-10">';
        echo view("prettyforms::tags.{$field['tag']}", compact('item','field','values','input_name', 'name', 'label'))->render();
        echo "<div style='display:none;margin-top:10px' id='validation-error-{$input_name}' class='alert alert-danger' role='alert'></div>";
        echo '</div></div>';
    }
}

if (!empty($javascript)) {
    echo "<script type='text/javascript'>$(document.ready(function() { $javascript });</script>";
}