<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mpaf\Tool\Lib;

use Mpaf\Tool\Lib\Config\Fragment\Builder as FragmentsBuilder;

class Builder
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var FragmentsBuilder
     */
    protected $fragmentsBuilder;

    /**
     * @var string
     */
    protected $outputDirectory;


    /**
     * @param Config $config
     * @param string $outputDirectory
     */
    public function __construct(Config $config, $outputDirectory)
    {
        $this->config = $config;
        $this->outputDirectory = $outputDirectory;
        $this->fragmentsBuilder = new FragmentsBuilder();
    }

    public function build($scenarioName)
    {
        $configData = $this->config->getScenario($scenarioName);

        $dom = new \DOMDocument();
        $dom->formatOutput = true;

        $jmeterTestPlan = $dom->createElement('jmeterTestPlan');
        $dom->appendChild($jmeterTestPlan);

        $this->createAttribute('version', '1.2', $jmeterTestPlan, $dom);
        $this->createAttribute('properties', '2.8', $jmeterTestPlan, $dom);
        $this->createAttribute('jmeter', '2.13 r1665067', $jmeterTestPlan, $dom);

        $jMeterTestPlanHashTree = $this->createElement('hashTree', $jmeterTestPlan, $dom);

        $this->createTestPlan($jMeterTestPlanHashTree, $dom, $configData);

        $hasTree = $this->createElement('hashTree', $jMeterTestPlanHashTree, $dom);
        foreach ($configData['items'] as $item) {
            $this->fragmentsBuilder->build($item, $hasTree, $dom);
        }

        $workBench = $this->createElement('WorkBench', $jMeterTestPlanHashTree, $dom);
        $this->createAttribute('guiclass', 'WorkBenchGui', $workBench, $dom);
        $this->createAttribute('testclass', 'WorkBench', $workBench, $dom);
        $this->createAttribute('testname', 'WorkBench', $workBench, $dom);
        $this->createAttribute('enabled', 'true', $workBench, $dom);

        $this->createAttribute(
            'name',
            'WorkBench.save',
            $this->createElement('boolProp', $workBench, $dom, 'true'),
            $dom
        );

        $path = $this->outputDirectory . $scenarioName . '.jmx';
        $dom->save($path);
        return $path;
    }

    /**
     * @param $name
     * @param $value
     * @param \DOMElement $parent
     * @param \DOMDocument $factory
     * @return \DOMAttr
     */
    protected function createAttribute($name, $value, \DOMElement $parent, \DOMDocument $factory)
    {
        $attribute = $factory->createAttribute($name);
        $attribute->value = $value;
        $parent->appendChild($attribute);
        return $attribute;
    }

    /**
     * @param $nodeName
     * @param \DOMElement $parent
     * @param \DOMDocument $factory
     * @return \DOMElement
     */
    protected function createElement ($nodeName, \DOMElement $parent, \DOMDocument $factory, $nodeValue = null)
    {
        $node = $factory->createElement($nodeName);
        if (null !== $nodeValue) {
            $node->nodeValue = $nodeValue;
        }
        $parent->appendChild($node);
        return $node;
    }

    /**
     * @param \DOMElement $jMeterTestPlanHashTree
     * @param \DOMDocument $dom
     * @param array $configData
     */
    protected function createTestPlan(\DOMElement $jMeterTestPlanHashTree, \DOMDocument $dom, $configData)
    {
        $testPlan = $this->createElement('TestPlan', $jMeterTestPlanHashTree, $dom);

        $this->createAttribute(
            'name',
            'TestPlan.comments',
            $this->createElement('stringProp', $testPlan, $dom, ''),
            $dom
        );
        $this->createAttribute(
            'name',
            'TestPlan.functional_mode',
            $this->createElement('boolProp', $testPlan, $dom, 'false'),
            $dom
        );
        $this->createAttribute(
            'name',
            'TestPlan.serialize_threadgroups',
            $this->createElement('boolProp', $testPlan, $dom, 'false'),
            $dom
        );
        $this->createAttribute(
            'name',
            'TestPlan.user_define_classpath',
            $this->createElement('stringProp', $testPlan, $dom, ''),
            $dom
        );

        $this->createAttribute('guiclass', 'TestPlanGui', $testPlan, $dom);
        $this->createAttribute('testclass', 'TestPlan', $testPlan, $dom);
        $this->createAttribute('testname', $configData['title'], $testPlan, $dom);
        $this->createAttribute('enabled', 'true', $testPlan, $dom);

        $this->createUserDefinedVariables($testPlan, $dom);
    }

    /**
     * @param \DOMElement $testPlan
     * @param \DOMDocument $dom
     */
    protected function createUserDefinedVariables(\DOMElement $testPlan, \DOMDocument $dom)
    {
        $testPlanVariables = $this->createElement('elementProp', $testPlan, $dom);
        $this->createAttribute('name', 'TestPlan.user_defined_variables', $testPlanVariables, $dom);
        $this->createAttribute('elementType', 'Arguments', $testPlanVariables, $dom);
        $this->createAttribute('guiclass', 'ArgumentsPanel', $testPlanVariables, $dom);
        $this->createAttribute('testclass', 'Arguments', $testPlanVariables, $dom);
        $this->createAttribute('testname', 'User Defined Variables', $testPlanVariables, $dom);
        $this->createAttribute('enabled', 'true', $testPlanVariables, $dom);


        $propCollection = $this->createElement('collectionProp', $testPlanVariables, $dom);
        $this->createAttribute('name', 'Arguments.arguments', $propCollection, $dom);

        foreach ($this->config->getVariables() as $variable) {
            $prop = $this->createElement('elementProp', $propCollection, $dom);
            $this->createAttribute('name', $variable['name'], $prop, $dom);
            $this->createAttribute('elementType', 'Argument', $prop, $dom);

            $this->createAttribute(
                'name',
                'Argument.name',
                $this->createElement('stringProp', $prop, $dom, $variable['name']),
                $dom
            );
            $this->createAttribute(
                'name',
                'Argument.value',
                $this->createElement('stringProp', $prop, $dom, $variable['value']),
                $dom
            );
            $this->createAttribute(
                'name',
                'Argument.metadata',
                $this->createElement('stringProp', $prop, $dom, $variable['metadata']),
                $dom
            );

        }
    }
}
