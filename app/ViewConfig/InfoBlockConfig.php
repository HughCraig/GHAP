<?php

namespace TLCMap\ViewConfig;

/**
 * Trait of configuration related to the info block.
 */
trait InfoBlockConfig
{
    /**
     * Set the info block display mode.
     *
     * @param string $mode
     *   The display mode. MUST be one of the view mode constants:
     *   - ViewConfig::VIEW_MODE_ENABLED
     *   - ViewConfig::VIEW_MODE_DISABLED
     *   - ViewConfig::VIEW_MODE_HIDDEN
     * @return void
     */
    public function setInfoDisplay($mode = ViewConfig::VIEW_MODE_ENABLED)
    {
        $this->set('display', $mode, 'info');
    }

    /**
     * Set the logo of the info block.
     *
     * @param string $url
     *   The URL of the logo image.
     * @return void
     */
    public function setLogo($url)
    {
        $this->set('logo', $url, 'info');
    }

    /**
     * Set the title of the info block.
     *
     * @param string $text
     *   The text of the title.
     * @param string|null $url
     *   The optional URL linked to the title.
     * @return void
     */
    public function setInfoTitle($text, $url = null)
    {
        if (isset($url)) {
            $this->set('title', ['text' => $text, 'link' => $url], 'info');
        } else {
            $this->set('title', $text, 'info');
        }
    }

    /**
     * Set the content of the info block.
     *
     * @param string $content
     *   The text of the content.
     * @return void
     */
    public function setInfoContent($content)
    {
        $this->set('content', $content, 'info');
    }
}
