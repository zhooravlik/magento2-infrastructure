<?php
/**
 * Service routines for sanity check command line script
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Tools\Sanity;

/**
 * Extend words finder class, which is designed for sanity tests. The added functionality is method to search through
 * directories and method to return words list for logging.
 */
class SanityWordsFinder extends \Magento\TestFramework\Inspection\WordsFinder
{
    /**
     * {@inheritdoc}
     *
     * @var array
     */
    protected $copyrightSkipList = [
        'app/etc/vendor_path.php',
        'app/code/Magento/Doc/view/doc/web/jumly',
        'app/code/Magento/Fedex/etc/wsdl',
        'app/code/Magento/Solr/conf/schema.xml',
        'app/code/Magento/Solr/conf/solrconfig.xml',
        'app/code/Magento/Swagger/view/frontend/templates/swagger-ui',
        'app/code/Magento/Swagger/view/frontend/web',
        'dev/tests/integration/testsuite/Magento/Framework/Css/PreProcessor/_files',
        'dev/tests/integration/testsuite/Magento/Framework/Less/_files/design/frontend/test_pre_process',
        'dev/tests/integration/testsuite/Magento/Framework/Less/_files/lib/web/magento_import.less',
        'dev/tests/integration/testsuite/Magento/Framework/Less/_files/lib/web/some_dir',
        'dev/tests/integration/testsuite/Magento/Core/Model/_files/design/frontend/test_default/web/result_source.css',
        'dev/tests/integration/testsuite/Magento/Core/Model/_files/design/frontend/test_default/web/result_source_dev.css',
        'dev/tests/integration/testsuite/Magento/Core/Model/_files/design/frontend/test_default/web/source.less',
        'dev/tests/integration/tmp',
        'dev/tests/js/JsTestDriver/framework/qunit',
        'dev/tests/static/report',
        'dev/tests/static/framework/Magento/Sniffs/Annotations',
        'dev/tools/Magento/Tools/View/Test/Unit/Generator/_files/ThemeDeployment/run/source',
        'dev/tools/PHP-Parser',
        'Gruntfile.js',
        'lib/internal/CardinalCommerce',
        'lib/internal/Credis',
        'lib/internal/Cm',
        'lib/internal/JSMin',
        'lib/internal/Less',
        'lib/web/extjs',
        'lib/web/fotorama/fotorama.js',
        'lib/web/fotorama/fotorama.min.js',
        'lib/web/jquery',
        'lib/web/knockoutjs',
        'lib/web/moment.js',
        'lib/web/legacy-build.min.js',
        'lib/web/lib',
        'lib/web/less/less.min.js',
        'lib/web/mage/adminhtml/hash.js',
        'lib/web/matchMedia.js',
        'lib/web/modernizr',
        'lib/web/prototype',
        'lib/web/requirejs',
        'lib/web/scriptaculous',
        'lib/web/tiny_mce',
        'lib/web/underscore.js',
        'lib/web/es6-collections.js',
        'lib/web/MutationObserver.js',
        'pub/media',
        'var',
        'setup/pub/bootstrap',
        'setup/pub/angular',
        'setup/pub/angular-ui-bootstrap',
        'setup/pub/angular-ui-router',
        'setup/pub/angular-ng-storage',
        'setup/vendor',
        'dev/tools/Magento/Tools/StaticReview',
        'dev/tools/Magento/Tools/psr',
        'dev/tests/integration/framework/tests/unit/testsuite/Magento/Test/Bootstrap/_files/0',
        'lib/internal/Magento/Framework/ObjectManager/Test/Unit/_files/empty_definition_file',
        'lib/internal/Magento/Framework/ObjectManager/Test/Unit/_files/test_definition_file',
        'nginx.conf.sample',
        'update/pub/js/lib/jquery.js',
    ];

    /**
     * @param string|array $configFiles
     * @param string $baseDir
     * @param bool $isCopyrightChecked
     * @throws \Magento\TestFramework\Inspection\Exception
     */
    public function __construct($configFiles, $baseDir, $isCopyrightChecked = true)
    {
        parent::__construct($configFiles, $baseDir, $isCopyrightChecked);
    }

    /**
     * Get list of words, configured to be searched
     *
     * @return array
     */
    public function getSearchedWords()
    {
        return $this->_words;
    }

    /**
     * Search words in files content recursively within base directory tree
     *
     * @return array
     */
    public function findWordsRecursively()
    {
        return $this->_findWordsRecursively($this->_baseDir);
    }

    /**
     * Search words in files content recursively within base directory tree
     *
     * @param  string $currentDir Current dir to look in
     * @return array
     */
    protected function _findWordsRecursively($currentDir)
    {
        $result = [];

        $entries = glob($currentDir . '/*');
        $initialLength = strlen($this->_baseDir);
        foreach ($entries as $entry) {
            if (is_file($entry)) {
                $foundWords = $this->findWords($entry);
                if (!$foundWords) {
                    continue;
                }
                $relPath = substr($entry, $initialLength + 1);
                $result[] = ['words' => $foundWords, 'file' => $relPath];
            } elseif (is_dir($entry)) {
                $more = $this->_findWordsRecursively($entry);
                $result = array_merge($result, $more);
            }
        }

        return $result;
    }
}
