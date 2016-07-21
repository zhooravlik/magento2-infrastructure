<?php
/**
 * A tool for creating root composer.json files
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Package;

use Magento\Tools\Composer\Helper\ReplaceFilter;
use Magento\Tools\Composer\Helper\VersionCalculator;

require __DIR__ . '/../../../bootstrap_tools.php';

$baseOption = 'base';
$productOption = 'product';
$projectOption = 'project';

$ceCode = 'ce';
$eeCode = 'ee';
$b2bCode = 'b2b';

define(
'USAGE',
"Usage: php -f create-root.php --
    --source-dir=<path>
    [--type=<$baseOption|$productOption|$projectOption>] [--wildcard]
    [--target-file=<path>] [--set=<option:value>] [--edition=<$eeCode|$ceCode|$b2bCode>]
    [--repo=<repository>]
    [--package-repo-url=<url>]
    --source-dir=/path/to/magento/dir - path to a Magento root directory. By default will use current working copy
        this directory must contain a root composer.json which is going to be used as template.
    --type=$baseOption|$productOption|$projectOption - render the result as a product base or as a product or as a project.
        --type=$baseOption render the result as a product base
        --type=$productOption render the result as a product
        --type=$projectOption render the result as a project
    --wildcard - whether to set 'require' versions to wildcard
    --target-file=/path/to/composer.json - render output to the specified file. If not specified, render into STDOUT.
    --set='path->to->node:value' - set a value to the specified node. Use colon to separate node path and value.
        Overrides anything that was before in the template or in default values.
        May be used multiple times to specify multiple values. For example:
        --set=name:vendor/package --set=extra->branch-alias->dev-master:2.0-dev
    --edition=$eeCode|$ceCode|$b2bCode - which edition is the package being created for. \"ce\" by default
    --repo='packages.repository' - which additional custom repository to retrieve packages from: must be given if EE or B2B edition
        is used. packages.magento.com is already used for all editions
    \n"
);
$opt = getopt('', ['source-dir::', 'type::', 'wildcard', 'target-file::', 'set::', 'edition::', 'repo::', 'package-repo-url::']);

$cePackageRepo = isset($opt['package-repo-url']) ? $opt['package-repo-url'] : 'http://packages.magento.com/';

/**
 * Names of Magento editions
 */
const EE_EDITION_NAME = 'Enterprise Edition';
const CE_EDITION_NAME = 'Community Edition';
const B2B_EDITION_NAME = 'B2B Edition';


/**
 * Output composer package names for combinations of edition and package type
 */
const B2B_DEFAULT_NAME = 'magento/magento2b2b';
const B2B_PROJECT_NAME = 'magento/project-b2b-edition';
const B2B_PRODUCT_NAME = 'magento/product-b2b-edition';
const B2B_BASE_NAME = 'magento/magento2-b2b-base';

const EE_DEFAULT_NAME = 'magento/magento2ee';
const EE_PROJECT_NAME = 'magento/project-enterprise-edition';
const EE_PRODUCT_NAME = 'magento/product-enterprise-edition';
const EE_BASE_NAME = 'magento/magento2-ee-base';

const CE_DEFAULT_NAME = 'magento/magento2ce';
const CE_PROJECT_NAME = 'magento/project-community-edition';
const CE_PRODUCT_NAME = 'magento/product-community-edition';
const CE_BASE_NAME = 'magento/magento2-base';

/**
 * Mapping of edition name to package names
 */
$editionDefaults = [
    $b2bCode => [
        'type' => [
            'default' => B2B_DEFAULT_NAME,
            'project' => B2B_PROJECT_NAME,
            'product' => B2B_PRODUCT_NAME,
            'base' => B2B_BASE_NAME
        ],
        'editionName' => B2B_EDITION_NAME
    ],
    $eeCode => [
        'type' => [
            'default' => EE_DEFAULT_NAME,
            'project' => EE_PROJECT_NAME,
            'product' => EE_PRODUCT_NAME,
            'base' => EE_BASE_NAME
        ],
        'editionName' => EE_EDITION_NAME
    ],
    $ceCode => [
        'type' => [
            'default' => CE_DEFAULT_NAME,
            'project' => CE_PROJECT_NAME,
            'product' => CE_PRODUCT_NAME,
            'base' => CE_BASE_NAME
        ],
        'editionName' => CE_EDITION_NAME
    ],
];


try {

    // Process arguments
    if (empty($opt['source-dir'])) {
        throw new \InvalidArgumentException("'source-dir' argument is required and must not be empty");
    }

    $source = $opt['source-dir'];
    $source = realpath($source);
    assertLogical(is_dir($source), "The source directory doesn't exist: {$source}");

    $editionCode = isset($opt['edition']) ? $opt['edition'] : 'ce';
    assertLogical(in_array($editionCode, array_keys($editionDefaults)), "Edition code \"$editionCode\" is invalid.");

    $sourceComposer = $source . '/composer.json';
    assertLogical(is_file($sourceComposer), "The source composer.json file doesn't exist: {$sourceComposer}");

    $type = isset($opt['type']) ? $opt['type'] : 'default';
    $validTypes = [$baseOption, $productOption, $projectOption, 'default'];
    assertLogical(in_array($type, $validTypes), "Result type \"$type\" is invalid.");

    $useWildcard = isset($opt['wildcard']);


    // Get info from template package
    $templatePackage = new Package(json_decode(file_get_contents($sourceComposer)), $sourceComposer);
    $version = $templatePackage->get('version');

    // Determine default values which depend on edition
    $editionName = $editionDefaults[$editionCode]['editionName'];
    $longDescription = "eCommerce Platform for Growth ($editionName)";
    $repositoryUrl = isset($opt['repo']) ? $opt['repo'] : null;
    if ($editionCode === $eeCode && $type === $projectOption) {
        assertArgument($repositoryUrl, "The EE Project requires that a custom repository is given");
    }
    if ($editionCode === $b2bCode && $type === $projectOption) {
        assertArgument($repositoryUrl, "The B2B Project requires that a custom repository is given");
    }

    $defaultPackageName = getResultPackageName($editionCode, 'default', $editionDefaults);
    $productPackageName = getResultPackageName($editionCode, 'product', $editionDefaults);
    $projectPackageName = getResultPackageName($editionCode, 'project', $editionDefaults);
    $basePackageName = getResultPackageName($editionCode, 'base', $editionDefaults);

    // Product package must require base package
    $productRequireSection = [$basePackageName => $version];

    // EE product must require CE product
    if ($editionCode === $eeCode) {
        $ceProductName = getResultPackageName($ceCode, 'product', $editionDefaults);
        $productRequireSection[$ceProductName] = $version;
    }

    // B2B product must require CE and EE product
    if ($editionCode === $b2bCode) {
        $ceProductName = getResultPackageName($ceCode, 'product', $editionDefaults);
        $productRequireSection[$ceProductName] = $version;

        $eeProductName = getResultPackageName($eeCode, 'product', $editionDefaults);
        $productRequireSection[$eeProductName] = $version;
    }

    // Set default node values for each package type
    $defaults = [
        'name' => $defaultPackageName,
        'description' => "Magento 2 ($editionName)",
    ];

    $baseDefaults = [
        'name' => $basePackageName,
        'description' => "Magento 2 Base ($editionName)",
        'type' => 'magento2-component',
        'version' => $version ,
    ];

    $productDefaults = [
        'name' => $productPackageName,
        'description' => $longDescription,
        'version' => $version,
        'type' => 'metapackage',
        'require' => $productRequireSection
    ];

    $projectDefaults = [
        'name' => $projectPackageName,
        'description' => $longDescription,
        'type' => 'project',
        'repositories' => [
            [
                'type' => 'composer',
                'url' => $cePackageRepo
            ],
        ],
        'require' => [
            $productPackageName => $version,
            'composer/composer' => '@alpha'
        ],
    ];

    if ($repositoryUrl) {
        $projectDefaults['repositories'][] = [
            'type' => 'composer',
            'url' => $repositoryUrl
        ];
    }

    // Generate the result package
    switch ($type) {
        case $baseOption:
            $targetPackage = createBase($templatePackage, $baseDefaults, $source, $editionCode);
            break;
        case $productOption:
            $targetPackage = createProduct(
                $templatePackage,
                $productDefaults,
                $source,
                $useWildcard
            );
            break;
        case $projectOption:
            $targetPackage = createProject($templatePackage, $projectDefaults);
            break;
        default:
            $targetPackage = createDefault($templatePackage, $defaults, $source);
    }

    // Override default node values with "set" option
    if (isset($opt['set'])) {
        foreach ((array)$opt['set'] as $row) {
            assertLogical(preg_match('/^(.*?):(.+)$/', $row, $matches), "Unable to parse 'set' value: {$row}");
            list(, $key, $value) = $matches;
            $targetPackage->set($key, $value);
        }
    }

    // Write result file
    $output = $targetPackage->getJson();
    $output = str_replace('_empty_', '', $output);
    if (isset($opt['target-file'])) {
        $file = $opt['target-file'];
        assertArgument(!empty($file), "Target file name must not be empty.");
        assertLogical(file_put_contents($file, $output), "Unable to record output to the file: {$file}");
        echo "Output has been recorded to: {$file}\n";
    } else {
        echo $output;
    }
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
    echo USAGE;
    exit(1);
} catch (\Exception $e) {
    echo $e;
    exit(1);
}
exit(0);

/**
 * Create default package
 *
 * @param Package $package
 * @param array $defaults
 * @param string $source
 * @return Package
 */
function createDefault($package, $defaults, $source)
{
    // Remove missing replace components
    $replaceFilter = new ReplaceFilter($source);
    $replaceFilter->removeMissing($package);
    $package->unsetProperty('suggest');

    // defaults
    foreach ($defaults as $key => $value) {
        $package->set($key, $value);
    }

    return $package;
}

/**
 * Create base package
 *
 * @param Package $package
 * @param array $defaults
 * @param string $source
 * @return Package
 */
function createBase($package, $defaults, $source, $editionCode = 'ce')
{
    $targetPackage = new Package(new \StdClass(), null);

    // defaults
    foreach ($defaults as $key => $value) {
        $targetPackage->set($key, $value);
    }

    $requiredPackages = (array) $package->get('require');
    $targetPackage->set('require', (object) $requiredPackages);
    //removing white list
    $targetPackage = removeWhiteList($targetPackage, $editionCode);

    //adding magento-composer-installer back
    $requiredPackages = (array) $targetPackage->get('require');
    $requiredPackages = ['magento/magento-composer-installer' => '*'] + $requiredPackages;
    $targetPackage->set('require', (object) $requiredPackages);


    $targetPackage->set('license', $package->get('license'));

    // Remove the "replace" elements that are magento components
    $replaceFilter = new ReplaceFilter($source);
    $replaceFilter->removeMissing($package);
    $replaceFilter->removeMagentoComponentsFromReplace($package);
    $replaces = (array)$package->get('replace');
    if (count($replaces) > 0) {
        $targetPackage->set('replace', $replaces);
    }

    $componentPaths = (array)$package->get('extra->component_paths');
    if (count($componentPaths) > 0) {
        $targetPackage->set('extra->component_paths', $componentPaths);
    }

    // marshaling mapping (for base)
    $reader = new Reader($source);
    $targetPackage->set('extra->map', $reader->getRootMappingPatterns());

    return $targetPackage;
}

/**
 * Create product package
 *
 * @param Package $package
 * @param array $defaults
 * @param string $source
 * @param bool $useWildcard
 * @param array $baseRequirement
 * @return Package
 */
function createProduct($package, $defaults, $source, $useWildcard)
{
    // Convert the "replace" elements that are magento components into "require" components
    $replaceFilter = new ReplaceFilter($source);
    $replaceFilter->moveMagentoComponentsToRequire($package, $useWildcard);

    $targetPackage = new Package(new \StdClass(), null);

    // Set defaults
    foreach ($defaults as $key => $value) {
        $targetPackage->set($key, $value);
    }

    // Get select values from the template package
    $targetPackage->set('license', $package->get('license'));

    // Include require section of template package and also the base package
    $require = $defaults['require'];
    foreach($package->get('require') as $key => $val) {
        $require[$key] = $val;
    }
    $targetPackage->set('require', $require);

    return $targetPackage;
}

/**
 * Create project package
 *
 * @param Package $package
 * @param array $defaults
 * @return Package
 */
function createProject($package, $defaults)
{
    // defaults
    foreach ($defaults as $key => $value) {
        $package->set($key, $value);
    }

    $package->unsetProperty('suggest');
    $package->unsetProperty('replace');
    $package->unsetProperty('extra');
    $package->set('extra->magento-force', 'override');

    return $package;
}

/**
 * Assert a condition and throw an \InvalidArgumentException if false
 *
 * @param bool $condition
 * @param string $error
 * @return void
 * @throws \InvalidArgumentException
 */
function assertArgument($condition, $error)
{
    if (!$condition) {
        throw new \InvalidArgumentException($error);
    }
}

/**
 * Assert a condition and throw an \Logic if false
 *
 * @param bool $condition
 * @param string $error
 * @return void
 * @throws \LogicException
 */
function assertLogical($condition, $error)
{
    if (!$condition) {
        throw new \LogicException($error);
    }
}

/**
 * Given an edition and output type, returns the name of the output package
 *
 * @param string $editionCode I.e. "ce"
 * @param string $resultType I.e. "product", "project", "base"
 * @param array $map Mapping of edition/type combination to package name
 * @return string
 */
function getResultPackageName($editionCode, $resultType, $map)
{
    assertLogical(isset($map[$editionCode]), "Edition code $editionCode is not supported by this tool");
    assertLogical(isset($map[$editionCode]['type'][$resultType]), "Output type $resultType is not supported by this tool");
    return $map[$editionCode]['type'][$resultType];
}

/**
 * Removes white listed library and extensions from the require section
 *
 * @param Package $package
 * @return Package
 */
function removeWhiteList($package, $editionCode)
{
    $pathOfWhiteLists =  __DIR__
        . '/../../../../tests/static/testsuite/Magento/Test/Integrity/_files/composer_require';

    $whiteList = include $pathOfWhiteLists . '/whitelist.php';
    $whiteListEE = include $pathOfWhiteLists . '/whitelist_ee.php';
    if ($editionCode === 'ee') {
        $whiteList = array_merge($whiteList, $whiteListEE);
    }
    $requiredPackages = (array) $package->get('require');
    foreach (array_keys($requiredPackages) as $requiredPackage) {
        if (!in_array($requiredPackage, $whiteList)) {
            $package->unsetProperty('require->' . $requiredPackage);
        }
    }
    return $package;
}
