<?php
/**
 * Next Gen equipment create: legacy EquipmentData::create does not return the new row id.
 * Uses AbstractData::create + createCustomFieldData without modifying BNote classes.
 */

require_once BNOTE_ROOT . "/src/data/modules/equipmentdata.php";

final class NextGenEquipmentData extends EquipmentData
{
  public function create($values)
  {
    $m = new ReflectionMethod(AbstractData::class, "create");
    $id = $m->invoke($this, $values);
    $this->createCustomFieldData(EquipmentData::$CUSTOM_DATA_OTYPE, $id, $values);
    return $id;
  }
}
