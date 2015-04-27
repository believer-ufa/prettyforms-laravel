<?php if (config('prettyforms.show-breadcrumbs') AND class_exists('Breadcrumbs') AND ! empty(Route::currentRouteName())): ?>
    <?=Breadcrumbs::render(Route::currentRouteName(),$item)?>
<?php endif; ?>

<div id="save-form">
    <div class="form-horizontal">
		<?php if (isset($strings[$mode]['caption'])): ?>
		  <h3><?=$strings[$mode]['caption']?></h3>
		<?php endif; ?>
        <?php echo view('prettyforms::inputs-bootstrap3', compact('item','fields', 'values'))->render() ?>
    </div>

    <br/>

    <div class="senddata-token btn btn-primary"
         data-link="<?=Request::getUri()?>"
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