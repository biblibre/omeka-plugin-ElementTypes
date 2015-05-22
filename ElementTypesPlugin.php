<?php

/**
 * @file
 * Element Types plugin main file.
 */

/**
 * Element Types plugin main class.
 */
class ElementTypesPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
    );

    protected $_filters = array(
        'admin_navigation_main',
    );

    /**
     * Create database table.
     */
    public function hookInstall() {
        $this->_installOptions();

        $db = $this->_db;
        $sql = "
            CREATE TABLE IF NOT EXISTS {$db->ElementType} (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                element_id int(10) unsigned NOT NULL,
                element_type varchar(255) NOT NULL,
                element_type_options TEXT NULL DEFAULT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (element_id) REFERENCES elements (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ";
        $db->query($sql);
    }

    /**
     * Remove database table.
     */
    public function hookUninstall() {
        $this->_uninstallOptions();

        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS {$db->ElementType}";
        $db->query($sql);
    }

    /**
     * Set up plugins, translations, and filters
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');

        $db = get_db();

        $element_types_info = apply_filters('element_types_info', array());
        Zend_Registry::set('element_types_info', $element_types_info);

        // Add filters
        $filter_names = array(
            'Display',
            'ElementForm',
            'ElementInput',
            'Flatten',
            'Save',
            'Validate'
        );
        $element_types = $db->getTable('ElementType')->findAll();
        $element_types_by_id = array();
        foreach ($element_types as $element_type) {
            if (!isset($element_types_info[$element_type->element_type])) {
                continue;
            };

            $element = $db->getTable('Element')->find($element_type->element_id);
            $elementSet = $db->getTable('ElementSet')->find($element->element_set_id);

            foreach ($filter_names as $filter_name) {
                add_filter(
                    array($filter_name, 'Item', $elementSet->name, $element->name),
                    array($this, 'filter' . $filter_name)
                );
            }

            $element_type_info = $element_types_info[$element_type->element_type];
            if (isset($element_type_info['hooks'])) {
                foreach ($element_type_info['hooks'] as $key => $hook) {
                    add_plugin_hook(
                        "element_types_{$element_type->element_type}_{$key}",
                        $hook
                    );
                }
            }

            $element_types_by_id[$element_type->element_id] = $element_type;
        }
        Zend_Registry::set('element_types_by_id', $element_types_by_id);
    }

    /**
     * Add an entry in the admin navigation menu.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Element types'),
            'uri' => url('element-types'),
        );
        return $nav;
    }

    public function filterDisplay($text, $args) {
        $element_id = $args['element_text']->element_id;
        return $this->_applyFilters('Display', $element_id, $text, $args);
    }

    public function filterElementForm($components, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('ElementForm', $element_id, $components, $args);
    }

    public function filterElementInput($components, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('ElementInput', $element_id, $components, $args);
    }

    public function filterFlatten($flatText, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('Flatten', $element_id, $flatText, $args);
    }

    public function filterSave($text, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('Save', $element_id, $text, $args);
    }

    public function filterValidate($isValid, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('Validate', $element_id, $isValid, $args);
    }

    protected function _applyFilters($name, $element_id, $value, $args) {
        $element_types_info = Zend_Registry::get('element_types_info');
        $element_types_by_id = Zend_Registry::get('element_types_by_id');
        $element_type = $element_types_by_id[$element_id];

        $type = $element_type['element_type'];
        if (!isset($element_types_info[$type]['filters'][$name])) {
            return $value;
        }

        $filter = $element_types_info[$type]['filters'][$name];
        $args['element_type_options'] = json_decode(
            $element_type['element_type_options'], TRUE);

        return call_user_func($filter, $value, $args);
    }
}
