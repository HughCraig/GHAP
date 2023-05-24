<?php

namespace TLCMap\ROCrate;

use TLCMap\Http\Helpers\UUID;

class DataEntity
{
    protected $data;

    /**
     * Constructor.
     *
     * @param string $type
     *   The type of the data entity.
     * @param string|null $id
     *   The ID of the data entity. If omitted, it will assign an automatically generated UUID.
     * @throws \Exception
     */
    public function __construct($type, $id = null)
    {
        $this->data = [];
        // Generate an UUID if ID is empty.
        if (empty($id)) {
            $id = '#' . UUID::create();
        }
        $this->set('@id', $id);
        $this->set('@type', $type);
    }

    /**
     * Get the ID of the data entity.
     *
     * @return string
     */
    public function id()
    {
        return $this->get('@id');
    }

    /**
     * Get the type of the data entity.
     *
     * @return string
     */
    public function type()
    {
        return $this->get('@type');
    }

    /**
     * Get the entity data.
     *
     * @return array
     *   Returns the flat data entities including referenced entities.
     */
    public function getData()
    {
        $data = [];
        $entityData = [];
        $refEntities = [];
        // Unpack properties with DataEntity reference.
        // Note that this will not check the duplication if an entity is referenced in multiple places.
        foreach ($this->data as $name => $value) {
            if (is_array($value)) {
                $entityData[$name] = [];
                foreach ($value as $item) {
                    if ($item instanceof DataEntity) {
                        $refEntities[] = $item;
                        $entityData[$name][] = ["@id" => $item->id()];
                    } else {
                        $entityData[$name][] = $item;
                    }
                }
            } elseif ($value instanceof DataEntity) {
                $refEntities[] = $value;
                $entityData[$name] = ["@id" => $value->id()];
            } else {
                $entityData[$name] = $value;
            }
        }
        $data[] = $entityData;
        foreach ($refEntities as $refEntity) {
            $data = array_merge($data, $refEntity->getData());
        }
        return $data;
    }

    /**
     * Add a part to this data entity.
     *
     * @param DataEntity $part
     *   The part entity.
     * @return void
     */
    public function addPart(DataEntity $part)
    {
        $this->append('hasPart', $part);
    }

    /**
     * Get the parts of the current data entity.
     *
     * @return DataEntity|DataEntity[]
     */
    public function getParts()
    {
        return $this->data['hasPart'];
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *   The name of the property.
     * @return mixed|DataEntity|array|null
     */
    public function get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    /**
     * Set the value of a property.
     *
     * @param string $name
     *   The name of the property.
     * @param mixed|DataEntity|array $value
     *   The value of the property
     * @return void
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Append a value to a property.
     *
     * This won't overwrite the property value if the property already has one or many values. It will append the value
     * to the existing value as an array.
     *
     * @param string $name
     *   The name of the property.
     * @param mixed|DataEntity|array $value
     *   The value of the property.
     * @return void
     */
    public function append($name, $value)
    {
        if (isset($this->data[$name])) {
            if (is_array($this->data[$name])) {
                // Add the value to array if it's an array already.
                $this->data[$name][] = $value;
            } else {
                // Make the property as an array if it has a single value.
                $this->data[$name] = [$this->data[$name], $value];
            }
        } else {
            $this->set($name, $value);
        }
    }

    /**
     * Unset a property.
     *
     * @param string $name
     *   The name of the property.
     * @return void
     */
    public function unset($name)
    {
        unset($this->data[$name]);
    }
}
