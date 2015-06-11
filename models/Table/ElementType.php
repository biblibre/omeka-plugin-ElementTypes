<?php

class Table_ElementType extends Omeka_Db_Table
{
    public function findByElementId($elementId)
    {
        $select = $this->getSelect()->where('element_id = ?', $elementId);
        return $this->fetchObject($select);
    }

    public function findByElementType($elementType)
    {
        $select = $this->getSelect()->where('element_type = ?', $elementType);
        return $this->fetchObjects($select);
    }
}
