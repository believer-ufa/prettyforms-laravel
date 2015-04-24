<div style="width:130px;float:left;">
    <div id="<?= $input_name ?>-photography"
         style="background-image:url(<?php if ($item->$field['file_relationship']->exists) {
             echo $item->$field['file_relationship']->link();
         } else {
             echo '/themes/images/profile_avatar_default.png';
         } ?>)"></div>
    <div id="<?= $input_name ?>-uploadphoto"></div>
    <input name="<?= $input_name ?>" id="<?= $input_name ?>-avatar_id" type="hidden"
           value="<?= get_value($name, $item, $values) ?>"/>
</div>
<p class="font-small">В качестве изображения<br/> используйте<br/> файл в формате JPG, GIF, PNG</p>
<div class="btn btn-mini"
     onclick="$('#<?= $input_name ?>-photography').css('backgroundImage','url()'); $('#<?= $input_name ?>-avatar_id').val('');">
    Удалить изображение
</div>
<script type="text/javascript">
    Upload.init({
        elementId: '<?=$input_name?>-uploadphoto',
        successFunc: function (data) {
            $('#<?=$input_name?>-photography')
                .css('backgroundImage', 'url()')
                .css('backgroundImage', 'url(' + data.link + ')');
            $('#<?=$input_name?>-avatar_id').val(data.id);
        },
        mask: '*.png;*.jpg;*.jpeg;*.gif;*.PNG;*.JPG;*.JPEG;*.GIF',
        maskText: 'Изображение',
        button: 'normal',
        uploadType: Upload.types.SYSTEM,
        fileQueueLimit: null
    });
</script>
<style>
    #<?=$input_name?>-photography {
        width: 120px;
        height: 120px;
        margin-bottom: 5px;
        background-position: center center;
        background-repeat: no-repeat;
        border: 1px solid #aeaeae;
        border-radius: 3px;
        background-color: rgba(0, 0, 0, 0.5);
    }
</style>
