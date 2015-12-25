<div class="limited-width">
    <?php
      $template = config('prettyforms.extend-template');
      $section  = config('prettyforms.section');
    ?>

    <?php if ($template) {
    $__env->startSection($section);
} ?>

        <?php if (isset($strings[$mode]['caption'])) {
    ?>
            <h1 class="page-title"><?= $strings[$mode]['caption'] ?></h1>
        <?php 
} ?>

        <?php if (config('prettyforms.show-breadcrumbs') and class_exists('Breadcrumbs') and ! empty(Route::currentRouteName())): ?>
            <?= Breadcrumbs::render(Route::currentRouteName(), $item) ?>
        <?php endif; ?>

        <div id="save-form" class="forms">

            <fieldset>
                @if (isset($strings[$mode]['legend']))
                    <legend><?= $strings[$mode]['legend'] ?></legend>
                @endif
                <?php echo view('prettyforms::inputs-kube', compact('item', 'fields', 'values'))->render() ?>

                @if (isset($lang_inputs))
                    <?= $lang_inputs ?>
                @endif

                @if (isset($lang_buttons))
                    <h4>Языковые данные</h4>
                    <?= $lang_buttons ?>
                @endif
            </fieldset>

            <br/>

            <div class="senddata btn" type=primary
                 data-link="<?= Request::getRequestUri() ?>"
                 id="btn-save"
                 data-input="#save-form">
                    <?= $mode === 'add' ? 'Создать' : 'Применить' ?>
            </div>

            <a class="btn" outline
               href="<?= $home_link ?>"
               id="btn-save"
               data-input="#save-form">
                Отмена
            </a>
        </div>

    <?php if ($template) {
    $__env->stopSection();
} ?>

    <?php if ($template) {
    echo $__env->make($template, array_except(get_defined_vars(), ['__data', '__path']))->render();
} ?>
</div>