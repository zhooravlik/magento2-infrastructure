<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Helper;

/**
 * Class for excluding folders while zipping
 */
class ExcludeFilter extends \RecursiveFilterIterator
{
    /**
     * Paths to be excluded (the path is full path not relative)
     *
     * @var array
     */
    protected $exclude;

    /**
     * ExcludeFilter Constructor
     *
     * @param  \RecursiveDirectoryIterator $iterator
     * @param array $exclude
     */
    public function __construct(\RecursiveDirectoryIterator $iterator, array $exclude)
    {
        parent::__construct($iterator);
        $this->exclude = $exclude;
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $path = str_replace('\\', '/', realpath($this->current()->getPathname()));
        return !in_array($path, $this->exclude);
    }

    /**
     * Getting the children of Inner Iterator
     *
     * @return \RecursiveDirectoryIterator
     */
    public function getChildren()
    {
        return new ExcludeFilter(
            $this->getInnerIterator()->getChildren(),
            $this->exclude
        );
    }
}
