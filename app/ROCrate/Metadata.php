<?php

namespace TLCMap\ROCrate;

class Metadata
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var DataEntity[]
     */
    protected $dataEntities;

    /**
     * Constructor.
     *
     * @param string $version
     *   The version of the RO-Crate. Default is 1.1.
     */
    public function __construct($version = '1.1')
    {
        $this->dataEntities = [];
        $this->data = [
            "@context" => "https://w3id.org/ro/crate/{$version}/context",
            "@graph" => [
                [
                    "@type" => "CreativeWork",
                    "@id" => "ro-crate-metadata.json",
                    "conformsTo" => ["@id" => "https://w3id.org/ro/crate/{$version}"],
                    "about" => ["@id" => "./"]
                ],
            ],
        ];
    }

    /**
     * Add a data entity to the metadata.
     *
     * @param DataEntity $entity
     *   The data entity object.
     * @return void
     */
    public function addDataEntity(DataEntity $entity)
    {
        $this->dataEntities[] = $entity;
    }

    /**
     * Get the RO-Crate metadata.
     *
     * @return array
     *   Returns the array containing the whole content of RO-Crate metadata.
     */
    public function getData()
    {
        $data = $this->data;
        foreach($this->dataEntities as $entity) {
            $entityData = $entity->getData();
            foreach ($entityData as $item) {
                $data['@graph'][] = $item;
            }
        }
        return $data;
    }
}
