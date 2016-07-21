<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Helper;

use Magento\Tools\Composer\Package\Package;

/**
 * A helper for filtering root composer.json files
 */
class ReplaceFilter
{
    /**
     * Path to root directory
     *
     * @var string
     */
    private $source;

    /**
     * Package reader
     *
     * @var \Magento\Tools\Composer\Package\Reader
     */
    private $reader;

    /**
     * Set the root directory
     *
     * @param string $source
     */
    public function __construct($source)
    {
        $this->source = $source;
        $this->reader = new \Magento\Tools\Composer\Package\Reader($source);
    }

    /**
     * Go through the "replace" section and remove items that are missing in the working copy
     *
     * @param Package $package
     * @return void
     */
    public function removeMissing(Package $package)
    {
        $replace = (array)$package->get('replace');
        foreach (array_keys($replace) as $key) {
            $locations = $this->getExpectedComponentLocations($key, $package);
            $newLocations = [];
            foreach ($locations as $location) {
                if (file_exists("{$this->source}/{$location}")) {
                    $newLocations[] = $location;
                }
            }
            if (empty($newLocations)) {
                $package->unsetProperty("replace->{$key}");
                $package->unsetProperty("extra->component_paths->{$key}");
            } elseif ($package->get("extra->component_paths->{$key}")) {
                $locationValue = count($newLocations) == 1 ? $newLocations[0] : $newLocations;
                $package->set("extra->component_paths->{$key}", $locationValue);
            }
        }
    }

    /**
     * Go through the "replace section" and move Magento components under "require" section.
     *
     * Only do this if the component isn't also listed under the suggest section.
     *
     * @param Package $package
     * @param bool $useWildcard
     * @return void
     */
    public function moveMagentoComponentsToRequire(Package $package, $useWildcard)
    {
        $rootVersion = $package->get('version');
        $packages = $this->reader->getPackages();
        foreach ($package->get('replace') as $key => $value) {
            if (array_key_exists($key, $packages) && $package->get("replace->{$key}")
                && !$package->get("suggest->{$key}")
            ) {
                $package->unsetProperty("replace->{$key}");
                $newValue = VersionCalculator::calculateVersionValue($rootVersion, $value, $useWildcard);
                $package->set("require->{$key}", $newValue);
            }
        }
    }

    /**
     * Go through the "replace section" and remove Magento components under "replace" section
     *
     * @param Package $package
     * @return void
     */
    public function removeMagentoComponentsFromReplace(Package $package)
    {
        $keys = array_keys((array)$package->get('replace'));
        $packages = $this->reader->getPackages();
        foreach ($keys as $key) {
            if (array_key_exists($key, $packages) && $package->get("replace->{$key}")) {
                $package->unsetProperty("replace->{$key}");
            }
        }
    }

    /**
     * Whether the specified component name is a component of Magento system
     *
     * @param string $name
     * @return bool
     */
    public static function isMagentoComponent($name)
    {
        return (bool)self::matchMagentoComponent($name);
    }

    /**
     * Obtains a set of possible component locations for a component
     *
     * Normally a component is supposed to reside in a directory - that's how Composer is designed
     * However, some of components currently don't comply with Composer and they are scattered across the board and/or
     * mixed together. Once this situation is resolved, this method could be refactored to return a directory path.
     *
     * @param string $key
     * @param Package $package
     * @return string[]
     */
    private function getExpectedComponentLocations($key, Package $package)
    {
        $packages = $this->reader->getPackages();
        switch ($this->matchMagentoComponent($key, $matches)) {
            case 'module':
                $result = array_key_exists($key, $packages) ?
                    'app/code/Magento/' . $this->toCamelCase($matches[1]) : [];
                break;
            case 'theme':
                $result = array_key_exists($key, $packages) ?
                    'app/design/' . $matches[1] . '/Magento/' . $matches[2] : [];
                break;
            case 'language':
                $result = array_key_exists($key, $packages) ?
                    'app/i18n/magento/' . $matches[1] : [];
                break;
            case 'framework':
                $result = array_key_exists($key, $packages) ?
                    'lib/internal/Magento/' . $this->toCamelCase($matches[1]) : [];
                break;
            default:
                $result = $package->get("extra->component_paths->{$key}");
        }

        if (!$result) {
            return [];
        }

        if (!is_array($result)) {
            $result = [$result];
        }
        return $result;
    }

    /**
     * Determines if the specified value is a Magento component name
     *
     * If not, returns false.
     * If yes, returns the determined type. Also the name is tokenized into elements into &$matches array,
     * where first element is the type and the rest are other tokens in the original order
     *
     * @param string $key
     * @param array &$matches
     * @return bool|string
     */
    private static function matchMagentoComponent($key, &$matches = [])
    {
        $regex = '/^magento\/(module|theme|language|framework)(?:-(\w+))?([a-z_-]+)?$/';
        if (!preg_match($regex, $key, $pregMatch)) {
            return false;
        }
        if (func_num_args() === 1) {
            return $pregMatch[1];
        }
        $matches[] = $pregMatch[1];
        switch($pregMatch[1]) {
            case 'theme':
                $matches[] = $pregMatch[2];
                $matches[] = ltrim($pregMatch[3], '-');
                break;
            case 'framework':
                $matches[] = $pregMatch[1];
                break;
            default:
                $matches[] = isset($pregMatch[3]) ? $pregMatch[2] . $pregMatch[3] : $pregMatch[2];
        }
        return $matches[0];
    }

    /**
     * A supplementary converter of a name token to CamelCase
     *
     * @param string $name
     * @return string
     */
    private function toCamelCase($name)
    {
        $parts = explode('-', $name);
        $result = [];
        foreach ($parts as $token) {
            $result[] = ucfirst($token);
        }
        return implode($result);
    }
}
