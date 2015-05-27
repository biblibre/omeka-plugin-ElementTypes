<?php

class ElementTypes_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Configuration form.
     */
    public function indexAction()
    {
        $this->view->elements = $this->_getElements();
        $this->view->element_types_info = $this->_getElementTypesInfo();
    }

    public function editAction()
    {
        $element_id = $this->_getParam('element_id');
        $this->view->element = $this->_getElement($element_id);
        $this->view->element_type = $this->_getElementType($element_id);
        $this->view->element_types_info_options = $this->_getElementTypesInfoOptions();
    }

    /**
     * Save configuration and redirect to configuration form.
     */
    public function saveAction()
    {
        $element_id = $this->_getParam('element_id');
        $type = $this->_getParam('type');
        $element_type = $this->_getElementType($element_id);

        if ($type) {
            if (!isset($element_type)) {
                $element_type = new ElementType;
                $element_type->element_id = $element_id;
            }
            $element_type->element_type = $type;
            $element_type->save();
        } elseif (isset($element_type)) {
            $element_type->delete();
        }

        $this->_helper->flashMessenger(__('Successfully saved configuration'),
            'success');

        $this->_helper->redirector('index', 'index');
    }

    public function editOptionsAction() {
        $element_id = $this->_getParam('element_id');
        $element_type = $this->_getElementType($element_id);
        $element_type['element_type_options'] =
            json_decode($element_type['element_type_options'], TRUE);

        $hook = "element_types_{$element_type['element_type']}_OptionsForm";
        $this->view->options_form = get_plugin_hook_output($hook, array(
            'element_type' => $element_type,
        ));
        $this->view->element = $this->_getElement($element_id);
        $this->view->element_type = $element_type;
        $this->view->element_types = $this->_getElementTypesInfoOptions();
    }

    public function saveOptionsAction()
    {
        $params = $this->_getAllParams();
        $element_id = $params['element_id'];

        unset($params['element_id']);
        unset($params['admin']);
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['save']);

        $element_type = $this->_getElementType($element_id);
        if (isset($element_type)) {
            $element_type->element_type_options = json_encode($params);
            $element_type->save();
        }

        $this->_helper->flashMessenger(__('Successfully saved configuration'),
            'success');

        $this->_helper->redirector('index', 'index');
    }

    /**
     * Returns all elements grouped by element sets
     */
    protected function _getElements()
    {
        $db = get_db();
        $sql = "
            SELECT
                es.name AS element_set_name,
                e.id AS element_id,
                e.name AS element_name,
                it.name AS item_type_name,
                et.id AS element_type_id,
                et.element_type AS element_type,
                et.element_type_options AS element_type_options
            FROM {$db->ElementSet} es
                JOIN {$db->Element} e ON es.id = e.element_set_id
                LEFT JOIN {$db->ItemTypesElements} ite ON e.id = ite.element_id
                LEFT JOIN {$db->ItemType} it ON ite.item_type_id = it.id
                LEFT JOIN {$db->ElementType} et ON e.id = et.element_id
            WHERE es.record_type IS NULL OR es.record_type = 'Item'
            ORDER BY es.name, it.name, e.name
        ";
        $elements = $db->fetchAll($sql);
        $result = array();
        foreach ($elements as $element) {
            $group = $element['item_type_name']
                ? __('Item Type') . ': ' . __($element['item_type_name'])
                : __($element['element_set_name']);
            $result[$group][] = $element;
        }
        return $result;
    }

    protected function _getElementTypesInfo() {
        return Zend_Registry::get('element_types_info');
    }

    protected function _getElementTypesInfoOptions() {
        $element_types = $this->_getElementTypesInfo();
        $options = array('' => '');
        foreach ($element_types as $key => $type) {
            $options[$key] = __($type['label']);
        }
        return $options;
    }

    protected function _getElementType($element_id) {
        return get_db()
            ->getTable('ElementType')
            ->findByElementId($element_id);
    }

    protected function _getElement($element_id) {
        return get_db()
            ->getTable('Element')
            ->find($element_id);
    }
}
