<?php

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}
if (! isset($field['attributes']['rows'])) {
    $field['attributes']['rows'] = '3';
}

$field['attributes']['id'] = $input_name;

echo Form::textarea($input_name, get_value($name, $item, $values), $field['attributes']);
