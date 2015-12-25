<?php
$column_value = pf_get_value($name, $item, $values);

$star = function ($rate_step) use ($column_value) {
    if ($rate_step <= $column_value) {
        return '';
    } else {
        return '-empty';
    }
};

?>

<div class="block-rating--bar block-rating-input">
    <?php foreach ([1, 2, 3, 4, 5] as $rate_step) {
    ?><span data-level="<?=$rate_step?>" class="glyphicon glyphicon-star<?=$star($rate_step)?>" aria-hidden="true"></span><?php

} ?>

    <?=Form::hidden($input_name, $column_value);?>
    <div class="clearfix"></div>
</div>

<script>
    <?=config('prettyforms.js-load-wrapper')?>(function() {
        $('.block-rating-input .glyphicon').on('mouseenter',function() {
            var current_level = $(this).attr('data-level');
            var items = $(this).parent().find('.glyphicon');
            var selected_items = items.filter(function(){
                return $(this).attr('data-level') <= current_level;
            });

            items.removeClass('active');
            selected_items.addClass('active');
        }).on('mouseleave',function() {
            var items = $(this).parent().find('.glyphicon');
            items.removeClass('active');
        }).on('click',function() {
            var current_level = $(this).attr('data-level');
            var items = $(this).parent().find('.glyphicon');
            var selected_items = items.filter(function(){
                return $(this).attr('data-level') <= current_level;
            });

            // Выкрасим звёзды в нужные цвета
            selected_items.addClass('glyphicon-star').removeClass('glyphicon-star-empty');
            items.not(selected_items).addClass('glyphicon-star-empty').removeClass('glyphicon-star');

            // Установим нужное значение в скрытом инпуте
            $(this).parent().find('input[type=hidden]').val(current_level);
        });
    });
</script>