<?php

namespace TLCMap\ViewConfig;

/**
 * Class of configurations set on the individual `Feature` object in the GeoJSON feed.
 */
class FeatureConfig extends ViewConfig
{
    /**
     * Set the color of the feature.
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
     * Set the title of the popup.
     *
     * @param string $text
     *   The text of the popup title.
     * @return void
     */
    public function setPopupTitle($text)
    {
        $this->set('title', $text, 'popup');
    }

    /**
     * Set the content of the popup.
     *
     * @param string $text
     *   The text of the popup content.
     * @return void
     */
    public function setPopupContent($text)
    {
        $this->set('content', $text, 'popup');
    }

    /**
     * Set the fields allowed to be displayed in the property table.
     *
     * @param string[] $fields
     *   The names of allowed fields.
     * @return void
     */
    public function setAllowedFields(array $fields)
    {
        $this->set('allowedFields', $fields, 'popup');
    }

    /**
     * Set the fields blocked from displaying in the property table.
     *
     * @param string[] $fields
     *   The names of blocked fields.
     * @return void
     */
    public function setBlockedFields(array $fields)
    {
        $this->set('blockedFields', $fields, 'popup');
    }

    /**
     * Set the field lables displayed in the property table.
     *
     * @param array $lables
     *   The array of field lables which are keyed by the property name.
     * @return void
     */
    public function setFieldLabels(array $lables)
    {
        $this->set('fieldLabels', $lables, 'popup');
    }

    /**
     * Add a link to the popup link list.
     *
     * @param string $text
     *   The link text.
     * @param string $url
     *   The URL of the link.
     * @param string|null $target
     *   The optional target of the link.
     * @return void
     */
    public function addLink($text, $url, $target = null)
    {
        $links = $this->get('links', 'popup');
        if (!isset($links) || !is_array($links)) {
            $links = [];
        }
        $link = [
            'text' => $text,
            'link' => $url,
        ];
        if (isset($target)) {
            $link['target'] = $target;
        }
        $links[] = $link;
        $this->set('links', $links, 'popup');
    }

    /**
     * Set the line width for the line feature.
     *
     * @param int $width
     *   The line width.
     * @return void
     */
    public function setLineWidth($width)
    {
        $this->set('lineWidth', $width);
    }
}
