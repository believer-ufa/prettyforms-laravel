<?php

use function PrettyFormsLaravel\get_value;

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}

echo Form::input('text', $input_name, get_value($name, $item, $values), $field['attributes']);
