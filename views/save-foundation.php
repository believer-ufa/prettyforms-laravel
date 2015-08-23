<?php
  $template = config('prettyforms.extend-template');
  $section = config('prettyforms.section');
?>

<?php if ($template) { $__env->startSection($section); } ?>

    <?php if (config('prettyforms.show-breadcrumbs') AND class_exists('Breadcrumbs') AND ! empty(Route::currentRouteName())): ?>
        <?= Breadcrumbs::render(Route::currentRouteName(), $item) ?>
    <?php endif; ?>

    <div id="save-form">

        <?php if (isset($strings[$mode]['caption'])) { ?>
            <h3><?= $strings[$mode]['caption'] ?></h3>
        <?php } ?>

        <fieldset>
            <?php if (isset($strings[$mode]['legend'])) { ?>
                <legend><?= $strings[$mode]['legend'] ?></legend>
            <?php } ?>
            <?php echo view('prettyforms::inputs-foundation', compact('item','fields', 'values'))->render() ?>
        </fieldset>

        <br/>

        <div class="senddata button success"
             data-link="<?= Request::getRequestUri() ?>"
             id="btn-save"
             data-input="#save-form">
                <?= $mode === 'add' ? 'Создать' : 'Применить' ?>
        </div>

        <a class="button tiny"
           href="<?= $home_link ?>"
           id="btn-save"
           data-input="#save-form">
            Отмена
        </a>

        <div class="clear"></div>
    </div>

<?php if ($template) { $__env->stopSection(); } ?>

<?php if ($template) { echo $__env->make($template, array_except(get_defined_vars(), array('__data', '__path')))->render(); }