<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Package;

/**
 * A collection of objects representing composer packages
 */
class Collection
{
    /**@#+
     * Propagate version across dependent components
     */
    const UPDATE_DEPENDENT_NONE = '';
    const UPDATE_DEPENDENT_EXACT = 'exact';
    const UPDATE_DEPENDENT_WILDCARD = 'wildcard';
    /**@#-*/

    /**
     * Map of component names to the original json objects
     *
     * @var Package[]
     */
    private $packages = [];

    /**
     * Which way to update dependent components
     *
     * @var string
     */
    private $updateDependent;

    /**
     * Constructor
     *
     * @param string $updateDependent
     */
    public function __construct($updateDependent = self::UPDATE_DEPENDENT_NONE)
    {
        $this->updateDependent = $updateDependent;
        $this->getDependentVersion();
    }

    /**
     * Add package definition to the collection
     *
     * @param Package $package
     * @return void
     * @throws \LogicException
     */
    public function add(Package $package)
    {
        $name = $package->get('name');
        if (false === $name) {
            throw new \LogicException("No package name found in the file: {$package->getFile()}");
        }
        if (isset($this->packages[$name])) {
            throw new \LogicException("The package '{$name}' already exists in collection");
        }
        $this->packages[$name] = $package;
    }

    /**
     * Get the collection of packages
     *
     * @return Package[]
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Set a version to the package and optionally propagate the version in any other packages that depend on it
     *
     * @param string $packageName
     * @param string $version
     * @return void
     */
    public function setVersion($packageName, $version)
    {
        Version::validate($version);
        $package = $this->getPackage($packageName);
        $package->set('version', $version);
        $dependentVersion = $this->getDependentVersion($version);
        if ($dependentVersion) {
            $this->massUpdate($packageName, $dependentVersion);
        }
    }

    /**
     * Get a package object
     *
     * @param string $name
     * @return Package
     * @throws \LogicException
     */
    public function getPackage($name)
    {
        if (!isset($this->packages[$name])) {
            throw new \LogicException("Package not found: {$name}");
        }
        return $this->packages[$name];
    }

    /**
     * Perform a mass-update of versions in "require" section in all packages
     *
     * @param string $subjectName
     * @param string $targetValue
     * @return void
     */
    private function massUpdate($subjectName, $targetValue)
    {
        foreach ($this->packages as $package) {
            if ($package->get("require->{$subjectName}")) {
                $package->set("require->{$subjectName}", $targetValue);
            }
        }
    }

    /**
     * Validate/filter a version and determine what version to specify to dependent components
     *
     * @param string $versionAgainst
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getDependentVersion($versionAgainst = '')
    {
        $value = $this->updateDependent;
        switch ($value) {
            case self::UPDATE_DEPENDENT_EXACT:
                return $versionAgainst;
            case self::UPDATE_DEPENDENT_WILDCARD:
                if ($versionAgainst) {
                    if (!preg_match('/^\d+\.\d+\.\d+$/', $versionAgainst)) {
                        throw new \InvalidArgumentException(
                            'Wildcard may be set only fo stable versions (format: x.y.z)'
                        );
                    }
                    return preg_replace('/\.\d+$/', '.*', $versionAgainst);
                }
                return '';
            case self::UPDATE_DEPENDENT_NONE:
                return '';
            default:
                throw new \InvalidArgumentException("Unexpected value for 'dependent' argument: '{$value}'");
        }
    }
}
