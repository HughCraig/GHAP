<?php

namespace TLCMap\ViewConfig;

/**
 * Class of configuration applying to a collection (multilayer).
 */
class CollectionConfig extends ViewConfig
{
    // Include info block configurations.
    use InfoBlockConfig;

    /**
     * Enable the legend of the collection.
     *
     * @return void
     */
    public function enableLegend()
    {
        $this->set('legend', true, 'info');
    }

    /**
     * Disable the legend of the collection.
     *
     * @return void
     */
    public function disableLegend()
    {
        $this->set('legend', false, 'info');
    }

    /**
     * Set the display mode for the list pane.
     *
     * @param string $mode
     *   The display mode. MUST be one of the view mode constants:
     *   - ViewConfig::VIEW_MODE_ENABLED
     *   - ViewConfig::VIEW_MODE_DISABLED
     *   - ViewConfig::VIEW_MODE_HIDDEN
     * @return void
     */
    public function setListPaneDisplay($mode = ViewConfig::VIEW_MODE_ENABLED)
    {
        $this->set('listPane', $mode);
    }
}
