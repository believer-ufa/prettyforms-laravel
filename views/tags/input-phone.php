<?php

if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}

if (!isset($field['mask'])) {
    $field['mask'] = '+7(999) 999 99 99';
    $field['placeholder'] = '+7(___) ___ __ __';
}

$field['attributes']['placeholder'] = $field['placeholder'];

echo Form::input('text', $input_name, pf_get_value($name, $item, $values), $field['attributes']);

if (config('prettyforms.maskedinput-included') === false) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.3.1/jquery.maskedinput.min.js"></script>
<?php } ?>

<script>
    <?=config('prettyforms.js-load-wrapper')?>(function() {
        $('input[name="<?=$input_name?>"]').mask('<?=$field['mask']?>');
    });
</script>