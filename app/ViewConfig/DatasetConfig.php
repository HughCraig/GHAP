<?php

namespace TLCMap\ViewConfig;

/**
 * Class of configuration applying to a dataset (layer) within a collection (multilayer).
 */
class DatasetConfig extends ViewConfig
{
    /**
     * Set the color of the dataset.
     *
     * @param string $color
     *   The name of the color or HEX code.
     * @return void
     */
    public function setColor($color)
    {
        $this->set('color', $color);
    }

    /**
     * Allow the list pane to show the color of the dataset.
     *
     * @return void
     */
    public function enableListPaneColor()
    {
        $this->set('showColor', true, 'listPane');
    }

    /**
     * Prevent the list pane to show the color of the dataset.
     *
     * @return void
     */
    public function disableListPaneColor()
    {
        $this->set('showColor', false, 'listPane');
    }

    /**
     * Set the content of the list pane.
     *
     * @param string $text
     *   The text of the content.
     * @return void
     */
    public function setListPaneContent($text)
    {
        $this->set('content', $text, 'listPane');
    }
}
