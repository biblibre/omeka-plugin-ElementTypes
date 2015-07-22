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
        'upgrade',
        'initialize',
        'admin_head',
    );

    protected $_filters = array(
        'admin_navigation_main',
        'element_types_info',
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
                FOREIGN KEY (element_id) REFERENCES {$db->Element} (id)
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

    public function hookUpgrade($args)
    {
        $old_version = $args['old_version'];
        $db = $this->_db;

        if (version_compare($old_version, '0.3', '<=')) {
            $table = $db->getTable('ElementText');
            $select = $table->getSelect()
                ->join(array('et' => "{$db->ElementType}"),
                    'element_texts.element_id = et.element_id')
                ->where('et.element_type = ?', 'date');
            $elementTexts = $table->fetchObjects($select);
            foreach ($elementTexts as $elementText) {
                $text = $this->dateFilterFormat($elementText->text, array());
                if (isset($text)) {
                    $elementText->text = $text;
                    $elementText->save();
                }
            }

            $table = $db->getTable('ElementType');
            $elementTypes = $table->findByElementType('date');
            foreach ($elementTypes as $elementType) {
                $elementType->element_type_options = json_encode(array('format' => ''));
                $elementType->save();
            }
        }
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
            'Validate',
            'Format',
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
                add_filter(
                    array($filter_name, 'Collection', $elementSet->name, $element->name),
                    array($this, 'filter' . $filter_name)
                );
            }


            $element_types_by_id[$element_type->element_id] = $element_type;
        }
        Zend_Registry::set('element_types_by_id', $element_types_by_id);

        foreach ($element_types_info as $type => $element_type_info) {
            if (isset($element_type_info['hooks'])) {
                foreach ($element_type_info['hooks'] as $key => $hook) {
                    $hook_name = "element_types_{$type}_{$key}";
                    add_plugin_hook($hook_name, $hook);
                }
            }
        }
    }

    public function hookAdminHead($args) {
        queue_js_file('date');
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

    public function filterElementTypesInfo($types) {
        $types['date'] = array(
            'label' => __('Date'),
            'filters' => array(
                'ElementInput' => array($this, 'dateFilterElementInput'),
                'Save' => array($this, 'dateFilterSave'),
                'Validate' => array($this, 'dateFilterValidate'),
                'Display' => array($this, 'dateFilterDisplay'),
                'Format' => array($this, 'dateFilterFormat'),
            ),
            'hooks' => array(
                'OptionsForm' => array($this, 'dateHookOptionsForm'),
            ),
        );
        return $types;
    }

    public function filterDisplay($text, $args) {
        if (!$args['element_text']) {
            return $text;
        }

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

    public function filterFormat($value, $args) {
        $element_id = $args['element']->id;
        return $this->_applyFilters('Format', $element_id, $value, $args);
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


    // Date type callbacks //

    public function dateFilterElementInput($components, $args)
    {
        $view = get_view();
        $element = $args['element'];
        $element_id = $element->id;
        $index = $args['index'];
        $name = "Elements[$element_id][$index][text]";
        $format = $args['element_type_options']['format'];
        $value = $this->dateFilterDisplay($args['value'], $args);
        $components['input'] = $view->formText($name, $value, array(
            'data-type' => 'date',
            'data-format' => $this->_phpToDatepickerFormat($format),
        ));
        $components['html_checkbox'] = NULL;
        return $components;
    }

    public function dateFilterSave($text, $args)
    {
        $format = $args['element_type_options']['format'];
        $format = $format ? $format : 'Y-m-d';

        $dt = DateTime::createFromFormat($format, $text);
        if ($dt) {
            return $dt->format('Y-m-d');
        }

        return $text;
    }

    public function dateFilterValidate($isValid, $args)
    {
        $dt = DateTime::createFromFormat('Y-m-d', $args['text']);

        return $dt ? true : false;
    }

    public function dateFilterDisplay($text, $args)
    {
        $format = $args['element_type_options']['format'];
        $format = $format ? $format : 'Y-m-d';

        $dt = DateTime::createFromFormat('Y-m-d', $text);
        if ($dt) {
            return $dt->format($format);
        }

        return '';
    }

    public function dateFilterFormat($value, $args)
    {
        $format = $args['element_type_options']['format'];
        $format = $format ? $format : 'Y-m-d';

        $dt = DateTime::createFromFormat($format, $value);
        if ($dt) {
            return $dt->format('Y-m-d');
        }

        $time = strtotime($value);
        if ($time) {
            return date('Y-m-d', $time);
        }

        return null;
    }

    public function dateHookOptionsForm($args) {
        $view = get_view();
        $options = $args['element_type']['element_type_options'];
        print $view->formLabel('format', __('Format')) . ' ';
        print $view->formText(
            'format',
            isset($options) ? $options['format'] : ''
        );
        print ' <span>(' . __('Example:') . ' Y-m-d)</span>';
        print
            '<h2>' . __('Possible formats') . '</h2>'
            . '<table>'
            . '  <tbody>'
            . '    <tr><td>j</td><td>' . __('Day of month') . '</td></tr>'
            . '    <tr><td>d</td><td>' . __('Day of month (with padding zero)') . '</td></tr>'
            . '    <tr><td>z</td><td>' . __('Day of year') . '</td></tr>'
            . '    <tr><td>D</td><td>' . __('Day name (short)') . '</td></tr>'
            . '    <tr><td>l</td><td>' . __('Day name (long)') . '</td></tr>'
            . '    <tr><td>n</td><td>' . __('Month of year') . '</td></tr>'
            . '    <tr><td>m</td><td>' . __('Month of year (with padding zero)') . '</td></tr>'
            . '    <tr><td>M</td><td>' . __('Month name (short)') . '</td></tr>'
            . '    <tr><td>F</td><td>' . __('Month name (long)') . '</td></tr>'
            . '    <tr><td>y</td><td>' . __('Year (2 digits)') . '</td></tr>'
            . '    <tr><td>Y</td><td>' . __('Year (4 digits)') . '</td></tr>'
            . '  </tbody>'
            . '</table>';
    }

    protected function _phpToDatepickerFormat($format)
    {
        $chars = str_split($format);
        $escaped = false;
        $dpFormat = '';
        foreach ($chars as $c) {
            if ($escaped) {
                $dpFormat .= $c == "'" ? "''" : "'$c'";
                $escaped = false;
                continue;
            }

            switch ($c) {
                case 'j': $dpFormat .= 'd';  break;  // day of month
                case 'd': $dpFormat .= 'dd'; break;  // day of month with 0
                case 'z': $dpFormat .= 'o';  break;  // day of year
                case 'D': $dpFormat .= 'D';  break;  // day name short
                case 'l': $dpFormat .= 'DD'; break;  // day name long
                case 'n': $dpFormat .= 'm';  break;  // month of year
                case 'm': $dpFormat .= 'mm'; break;  // month of year with 0
                case 'M': $dpFormat .= 'M';  break;  // month name short
                case 'F': $dpFormat .= 'MM'; break;  // month name long
                case 'y': $dpFormat .= 'y';  break;  // year (2 digits)
                case 'Y': $dpFormat .= 'yy'; break;  // year (4 digits)

                case "'": $dpFormat .= "''"; break;  // single quote
                case "\\": $escaped = true;  break;

                default: $dpFormat .= "'$c'";
            }
        }
        return $dpFormat;
    }
}

function element_types_format($element_id, $value)
{
    $db = get_db();
    $element = $db->getTable('Element')->find($element_id);
    $elementSet = $db->getTable('ElementSet')->find($element->element_set_id);
    $filter = array('Format', 'Item', $elementSet->name, $element->name);
    return apply_filters($filter, $value, array('element' => $element));
}
