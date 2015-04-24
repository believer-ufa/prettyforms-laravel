<?php

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}

$field['attributes']['placeholder'] = '+7(___) ___ __ __';
$javascript .= "$('input[name=\"$input_name\"]').mask('+7(999) 999 99 99');";

echo Form::input('text', $input_name, get_value($name, $item, $values), $field['attributes']);