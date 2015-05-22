<?php echo head(array('title' => __('Element Types'))); ?>
<?php echo flash(); ?>

<h1><?php echo __('Change type of element %s', __($element['name'])); ?></h1>

<form method="post" action="<?php echo url('element-types/index/save'); ?>">
  <div>
    <?php
      echo $this->formHidden('element_id', $element['id']);
      $name = 'type';
      echo $this->formLabel($name, __('Type'));
      echo ' ';
      echo $this->formSelect(
        $name,
        $element_type['element_type'],
        null,
        $element_types_info_options
      );
    ?>
  </div>
  <?php
    echo $this->formSubmit('save', __('Save'));
  ?>
</form>

<?php echo foot(); ?>
