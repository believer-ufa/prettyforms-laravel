<div class="checkbox">
    <label>
        <?= Form::checkbox(
            $input_name,
            1,
            (bool)get_value($name, $item, $values),
            $field['attributes']
        ) . array_get($field, 'text'); ?>
    </label>
</div>