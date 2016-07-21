<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Helper;

/**
 * Class for Zipping Components
 */
class Zipper
{
    /**
     * Zip Components
     *
     * @param string $source
     * @param string $destination
     * @param array $excludes
     * @throws \Exception
     * @return int
     */
    public static function zip($source, $destination, array $excludes)
    {
        $noOfZips = 0;

        Zipper::checkSourceExtension($source);
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            throw new \Exception("Error while zipping: could not create the destination folder", "1");
        }
        if (is_dir($source) === true) {
            $files = Zipper::getFiles($source, $excludes);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));
                if (in_array(substr($file, strrpos($file, '/')+1), ['.', '..'])) {
                    continue;
                }
                $relativePath = str_replace($source . '/', '', $file);
                if (is_dir($file) === true) {
                    $relativePath .= '/';
                    $zip->addEmptyDir($relativePath);
                } elseif (is_file($file) === true) {
                    $zip->addFromString($relativePath, file_get_contents($file));
                } else {
                    throw new \Exception("The path $file is not a directory or file!", "1");
                }
            }
            $noOfZips += sizeof($files);
        } elseif (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
            $noOfZips++;
        }

        $zip->close();
        return $noOfZips;
    }

    /**
     * Checking existense of source and zip extension
     *
     * @param string $source
     * @return void
     * @throws \Exception
     */
    protected static function checkSourceExtension($source)
    {
        if (!file_exists($source)) {
            throw new \Exception("Error while zipping: source $source does not exist", "1");
        }
        if (!extension_loaded('zip')) {
            throw new \Exception("Error while zipping: extension loading problem", "1");
        }
    }

    /**
     * Creating the iterator for zipping
     *
     * @param string $source
     * @param string $excludes
     * @return \RecursiveIteratorIterator
     */
    protected static function getFiles($source, $excludes)
    {
        $directory = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        if (sizeof($excludes) > 0) {
            $directory = new ExcludeFilter($directory, $excludes);
        }
        $files = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        return $files;
    }
}
