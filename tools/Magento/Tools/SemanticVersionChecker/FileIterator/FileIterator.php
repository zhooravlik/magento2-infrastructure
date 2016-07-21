<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\FileIterator;

use File_Iterator_Facade;

class FileIterator
{
    /*
     * @var string $sourceDirectory
     */
    public function getFilesAsArray($sourceDirectory)
    {
        $fileIterator = new File_Iterator_Facade;

        $fileList = $fileIterator->getFilesAsArray($sourceDirectory, '.php');

        $apiFilterCallback = function($file) {
            $handle = fopen($file, 'r');
            $result = false;
            while (($buffer = fgets($handle)) !== false) {
                if (strpos($buffer, \Magento\Tools\SemanticVersionChecker\Console\Application::ANNOTATION_API) !== false) {
                    $result = true;
                    break;
                }
            }
            fclose($handle);

            return $result;
        };

        $fileList = array_filter($fileList, $apiFilterCallback);

        return $fileList;
    }


}
