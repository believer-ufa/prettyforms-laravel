<?php

if (isset($field['options'])) {
    $options = $field['options'];
} elseif (isset($field['model'])) {
    $model = new $field['model'];

    // Отфильтруем данные, если есть необходимость
    if (!empty($field['model_wheres'])) {
        foreach ($field['model_wheres'] as $where) {
            $model = $model->where($where[0], $where[1], $where[2]);
        }
    }

    // Отсортируем данные, если есть необходимость
    if (!empty($field['model_orders'])) {
        foreach((array)$field['model_orders'] as $order_field) {
            $model = $model->orderBy($order_field);
        }
    }

    $options = [];
    foreach($model->get() as $model_item) {
        $option = [
            'value' => $model_item->id,
            'text'  => pf_get_item_value($model_item,array_get($field,'model_name')),
        ];

        if (isset($field['model_desc'])) {
            $option['desc'] = $model_item->$field['model_desc'];
        }

        $options[] = $option;
    }
}

$selected = [];
if (!is_null($item->id)) {
    $selected = array_pluck($item->$name()->get(), 'id');
}

if (empty($options)) {
    $options = [
        [
            'text' => 'список пуст',
            'value' => ''
        ]
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
        ) . $option['text']; ?>
        <?php $desc = array_get($option,'desc') ?>
        <?php if ($desc) { ?>
            <br/>
            <small class="text-muted"><?=nl2br($desc)?></small>
        <?php } ?>
    </label>
</div>
<?php } ?>