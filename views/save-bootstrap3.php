<?php
  $template = config('prettyforms.extend-template');
  $section  = config('prettyforms.section');
?>

<?php if ($template) {
    $__env->startSection($section);
} ?>

    <?php if (config('prettyforms.show-breadcrumbs') and class_exists('Breadcrumbs') and ! empty(Route::currentRouteName())): ?>
        <?=Breadcrumbs::render(Route::currentRouteName(), $item)?>
    <?php endif; ?>

    <div id="save-form">
        <div class="form-horizontal">
    		<?php if (isset($strings[$mode]['caption'])): ?>
    		  <h3><?=$strings[$mode]['caption']?></h3>
    		<?php endif; ?>
            <?php echo view('prettyforms::inputs-bootstrap3', compact('item', 'fields', 'values'))->render() ?>
        </div>

        <br/>

        <div class="senddata btn btn-primary"
             data-link="<?=Request::getRequestUri()?>"
             id="btn-save"
             data-input="#save-form">
            <?= $mode === 'add' ? 'Создать' : 'Применить' ?>
        </div>

        <a class="btn btn-default"
             href="<?=$home_link?>"
             id="btn-save"
             data-input="#save-form">
            Отмена
        </a>

        <div class="clear"></div>
    </div>

<?php if ($template) {
    $__env->stopSection();
} ?>

<?php if ($template) {
    echo $__env->make($template, array_except(get_defined_vars(), ['__data', '__path']))->render();
}
