<div class="form-control-static">
    <?php $field['attributes']['readonly'] = 'true'; ?>
    <?php echo Form::input('text', $input_name, pf_get_value($name, $item, $values), $field['attributes']); ?>
</div>