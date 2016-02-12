<?php /* Подключим к странице файлы CKEditor'а */

use function PrettyFormsLaravel\get_value;

$ckeditor_paths = config('prettyforms.ckeditor-paths');

if ($ckeditor_paths === 'cdn') {
    ?>
    <script src="//cdn.ckeditor.com/4.4.7/standard/ckeditor.js"></script>
<?php
} elseif (is_array($ckeditor_paths)) {
    foreach ($ckeditor_paths as $path) {
        ?>
        <script type="text/javascript" src="<?=$path?>" ></script>
    <?php
    }
} ?>

<?php

$field['attributes']['id'] = $input_name;

echo Form::textarea($input_name, get_value($name, $item, $values), $field['attributes']);

?>
<br>
<script>
    <?=config('prettyforms.js-load-wrapper')?>(function() {
        <?php if (isset($field['ckeditor_function'])) { ?>
            <?=$field['ckeditor_function']?>('<?=$input_name?>');
        <?php } else { ?>
            var element = '<?=$input_name?>';

            if (CKEDITOR.instances[element]) {
                delete CKEDITOR.instances[element];
            }

            CKEDITOR.replace(element,{
                allowedContent: true,
                toolbar: 'basic',
                toolbar_basic : [
                    { name: 'document',     groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'NewPage', '-', 'Templates' ] },
                    { name: 'editing',      groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
                    { name: 'basicstyles',  groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
                    { name: 'paragraph',    groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
                    { name: 'links',    items: [ 'Link', 'Unlink', 'Anchor' ] },
                    { name: 'insert',   items: [ 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak'] },
                    { name: 'image',    items: [ 'Image', 'FlashUpload', 'base64image' ] },
                    { name: 'styles',   items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
                    { name: 'colors',   items: [ 'TextColor', 'BGColor' ] },
                    { name: 'tools',    items: [ 'Maximize', 'ShowBlocks' ] },
                    { name: 'others',   items: [ '-' ] }
                ]
            });

            CKEDITOR.config.height = 300;
        <?php } ?>
    });
</script>