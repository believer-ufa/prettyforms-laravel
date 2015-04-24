<?php /* Подключим к странице файлы CKEditor'а */ ?>
<script type="text/javascript" src="/assets/development/javascripts/ckeditor/params.js" ></script>
<script type="text/javascript" src="/assets/development/bower/ckeditor/ckeditor.js" ></script>

<?php

$field['attributes']['id'] = $input_name;

$javascript .= "App.editor('{$input_name}');";

echo Form::textarea($input_name, get_value($name, $item, $values), $field['attributes']);
