<?php echo head(array('title' => __('Element Types'))); ?>
<?php echo flash(); ?>

<h1><?php echo __('Configure type %1$s for element %2$s', __($element_types[$element_type['element_type']]), __($element['name'])); ?></h1>

<form method="post" action="<?php echo url('element-types/index/save-options'); ?>">
    <div>
        <?php echo $options_form; ?>
    </div>
    <?php echo $this->formHidden('element_id', $element['id']); ?>
    <?php echo $this->formSubmit('save', __('Save')); ?>
</form>

<?php echo foot(); ?>
