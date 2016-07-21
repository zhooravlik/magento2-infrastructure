<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Package;

/**
 * A model that represents composer package
 */
class Package extends \Magento\Framework\Config\Composer\Package
{
    /**
     * Path to the composer.json file
     *
     * @var string
     */
    private $file;

    /**
     * Constructor
     *
     * @param \StdClass $json
     * @param string $file
     */
    public function __construct(\StdClass $json, $file)
    {
        parent::__construct($json);
        $this->file = $file;
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * A setter for properties
     *
     * For example:
     *     $package->set('name', 'foo/bar');
     *     $package->set('require->foo/bar', '1.0.0');
     *     $package->set('extra->foo->bar', 'baz');
     *     $package->set('extra->foo', ['bar', 'baz']);
     *
     * @param string $propertyPath
     * @param mixed $value
     * @return void
     */
    public function set($propertyPath, $value)
    {
        $this->traverseSet($this->json, $value, explode('->', $propertyPath));
    }

    /**
     * Traverse an \StdClass object recursively and set the property by specified path (chain)
     *
     * @param \StdClass $target
     * @param mixed $value
     * @param array $chain
     * @param int $index
     * @return void
     */
    private function traverseSet(\StdClass $target, $value, array $chain, $index = 0)
    {
        $property = $chain[$index];
        if (isset($chain[$index + 1])) {
            if (!property_exists($target, $property)) {
                $target->{$property} = new \StdClass();
            }
            $this->traverseSet($target->{$property}, $value, $chain, $index + 1);
        } else {
            $target->{$property} = $value;
        }
    }

    /**
     * Unset a property by specified path
     *
     * @param string $path
     * @return void
     */
    public function unsetProperty($path)
    {
        $chain = explode('->', $path);
        $this->traverseUnset($this->json, $chain, count($chain) - 1);
    }

    /**
     * Traverse an \StdClass object recursively and unset the property by specified path (chain)
     *
     * @param \StdClass $json
     * @param array $chain
     * @param int $endIndex
     * @param int $index
     * @return void
     */
    private function traverseUnset(\StdClass $json, array $chain, $endIndex, $index = 0)
    {
        $key = $chain[$index];
        if ($index < $endIndex) {
            if (isset($json->{$key}) && isset($chain[$index + 1])) {
                $this->traverseUnset($json->{$key}, $chain, $endIndex, $index + 1);
            }
        } else {
            unset($json->{$key});
        }
    }
}
