
<script type="text/javascript" src="/assets/development/bower/moment/min/moment-with-locales.min.js" ></script>
<script type="text/javascript" src="/assets/development/bower/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js" ></script>

<link rel="stylesheet" href="/assets/development/bower/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css" />

<?php
if (isset($field['attributes']['class'])) {
    $field['attributes']['class'] .= ' form-control';
} else {
    $field['attributes']['class'] = 'form-control';
}
?>

<?=Form::input('text', $input_name, pf_get_value($name, $item, $values), $field['attributes']);?>

<script>
    $(window).load(function() {
        $('input[name="<?=$input_name?>"]').datetimepicker({
            locale: 'ru',
            sideBySide: true,
            //minDate: new Date(),
            format: 'YYYY-MM-DD HH:mm:ss'
        });
    });
</script>