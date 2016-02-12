<?php

if (!isset($field['options'])) {
    throw new Exception('Пожалуйста, добавьте параметр options в ваше поле типа checkbox-multi.');
}

// Получим массив опций, либо путём вызова коллбека, либо просто получением данных
$options = (is_callable($field['options'])) ? $field['options']($item) : $field['options'];

$selected = [];

// Если объект существует, значит у него могут быть уже выбранные значения.
// Получим их из базы
if ($item->exists) {
    $selected = array_pluck($item->$name()->select('id')->get(), 'id');
}

if (empty($options)) {
    $options = [
        [
            'text'  => 'список пуст',
            'value' => '',
        ],
    ];
}

?>

<?php foreach ($options as $option) { ?>
<div class="checkbox">
    <label>
        <?= Form::checkbox(
            $input_name.'[]',
            $option['value'],
            in_array($option['value'], $selected),
            $field['attributes']
        ).$option['text']; ?>
        <?php if ($desc = array_get($option, 'desc')) { ?>
            <br/>
            <small class="text-muted"><?=nl2br($desc)?></small>
        <?php } ?>
    </label>
</div>
<?php } ?>