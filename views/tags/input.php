<?php

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}

echo Form::input('text', $input_name, pf_get_value($name, $item, $values), $field['attributes']);
