<?php

namespace TLCMap\ViewConfig;

/**
 * Class of configurations set on the top level `FeatureCollection` object in the GeoJSON feed.
 *
 * Note that this class is extending the `FeatureConfig` class, which allows feature level configurations to be set
 * globally.
 */
class FeatureCollectionConfig extends FeatureConfig
{
    // Include info block configurations.
    use InfoBlockConfig;

    /**
     * Enable the basemap gallery.
     *
     * @return void
     */
    public function enableBasemapGallery()
    {
        $this->set('basemapGallery', true);
    }

    /**
     * Disable the basemap gallery.
     *
     * @return void
     */
    public function disableBasemapGallery()
    {
        $this->set('basemapGallery', false);
    }

    /**
     * Set the default basemap.
     *
     * @param string $name
     *   The name of the basemap.
     * @return void
     */
    public function setBasemap($name)
    {
        $this->set('basemap', $name);
    }

    /**
     * Set the general cluster color.
     *
     * @param string $color
     *   The color name or HEX code.
     * @return void
     */
    public function setClusterColor($color)
    {
        $this->set('clusterColor', $color);
    }
}
