<?php

namespace TLCMap\ViewConfig;

/**
 * Abstract class of the configuration object used for TLCMap views.
 */
abstract class ViewConfig
{
    /**
     * The constants of view mode.
     */
    const VIEW_MODE_ENABLED = 'enabled';
    const VIEW_MODE_DISABLED = 'disabled';
    const VIEW_MODE_HIDDEN = 'hidden';

    /**
     * @var array $config
     *   The full configuration object.
     */
    protected $config;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->config = ["display" => []];
    }

    /**
     * Set a configuration.
     *
     * @param string $name
     *   The name of the configuration.
     * @param mixed $value
     *   The value of the configuration.
     * @param string|null $group
     *   The optional group name of the configuration.
     * @return void
     */
    public function set($name, $value, $group = null)
    {
        if (isset($group)) {
            if (isset($this->config['display'][$group]) && is_array($this->config['display'][$group])) {
                $this->config['display'][$group][$name] = $value;
            } else {
                $this->config['display'][$group] = [];
                $this->config['display'][$group][$name] = $value;
            }
        } else {
            $this->config['display'][$name] = $value;
        }
    }

    /**
     * Get the value of a configuration.
     *
     * @param string $name
     *   The name of the configuration.
     * @param string|null $group
     *   The optional group name of the configuration.
     * @return mixed|null
     *   The value of the configuration.
     */
    public function get($name, $group = null)
    {
        if (isset($group)) {
            return $this->config['display'][$group][$name] ?? null;
        } else {
            return $this->config['display'][$name] ?? null;
        }
    }

    /**
     * Convert the whole config object to array.
     *
     * @return array
     *   The configuration data excluding the top-level `display` property.
     */
    public function toArray()
    {
        return $this->config['display'];
    }
}
