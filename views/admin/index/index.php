<?php echo head(array('title' => __('Element Types'))); ?>
<?php echo flash(); ?>

<table>
  <thead>
    <tr>
      <th><?php echo __('Element'); ?></th>
      <th><?php echo __('Type'); ?></th>
      <th><?php echo __('Actions'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($elements as $group => $elts): ?>
      <tr><td colspan="3"><strong><?php echo $group ?></strong></td></tr>
      <?php foreach($elts as $element): ?>
        <tr>
          <td><?php echo __($element['element_name']); ?></td>
          <td>
            <?php
              if (isset($element_types_info[$element['element_type']])) {
                echo __($element_types_info[$element['element_type']]['label']);
              }
            ?>
          </td>
          <td>
              <a href="<?php echo url("element-types/index/edit/element_id/{$element['element_id']}"); ?>"><?php echo __('Modify'); ?></a>
              <?php if (isset($element_types_info[$element['element_type']]['hooks']['OptionsForm'])): ?>
                | <a href="<?php echo url("element-types/index/edit-options/element_id/{$element['element_id']}"); ?>"><?php echo __('Configure'); ?></a>
              <?php endif; ?>
            </ul>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </tbody>
</table>

<?php echo foot(); ?>
