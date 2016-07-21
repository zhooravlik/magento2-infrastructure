<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mpaf\Tool\Lib\Config;

class Converter
{
    public function convert(\DOMDocument $source)
    {
        $output = [];
        /** @var \DOMNodeList $scenario */
        $scenario = $source->getElementsByTagName('scenario');
        /** @var \DOMNode $scenarioConfig */
        foreach ($scenario as $scenarioConfig) {
            $scenarioName = $scenarioConfig->attributes->getNamedItem('name')->nodeValue;
            $items = [];
            /** @var \DOMNode $child */
            foreach ($scenarioConfig->childNodes as $child) {
                $config = [];
                if (!in_array($child->nodeName, ['import'])|| $child->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                $itemName = $child->attributes->getNamedItem('name');
                if (!$itemName) {
                    throw new \InvalidArgumentException('Attribute name is missed');
                }
                $config['import'] = $itemName->nodeValue;
                if ($child->childNodes->length > 0) {
                    foreach ($child->childNodes as $importNode) {
                        if ($importNode->nodeName != 'import' || $child->nodeType != XML_ELEMENT_NODE) {
                            continue;
                        }
                        $config['items'][] = [
                            'import' => $importNode->attributes->getNamedItem('name')->nodeValue,
                            'name' => $importNode->attributes->getNamedItem('name')->nodeValue,
                            'reference' => $importNode->attributes->getNamedItem('reference')
                                ? $importNode->attributes->getNamedItem('reference')->nodeValue
                                : null,
                            'before' => $importNode->attributes->getNamedItem('before')
                                ? $importNode->attributes->getNamedItem('before')->nodeValue
                                : null,
                            'after' => $importNode->attributes->getNamedItem('after')
                                ? $importNode->attributes->getNamedItem('after')->nodeValue
                                : null,
                        ];
                    }
                }

                $config['name'] = $itemName->nodeValue;
                $config['reference'] = null;
                $config['before'] = $child->attributes->getNamedItem('before')
                    ? $child->attributes->getNamedItem('before')->nodeValue
                    : null;
                $config['after'] = $child->attributes->getNamedItem('after')
                    ? $child->attributes->getNamedItem('after')->nodeValue
                    : null;
                $items[$itemName->nodeValue] = $config;
            }
            $output['scenario'][$scenarioName]['items'] = $items;
            $output['scenario'][$scenarioName]['name'] = $scenarioName;
            $output['scenario'][$scenarioName]['title'] = $scenarioConfig->attributes->getNamedItem('title')->nodeValue;
        }


        $variables = $source->getElementsByTagName('variable');

        $outputVars = [];
        /** @var \DOMNode $variable */
        foreach ($variables as $variable) {
            if ($variable->nodeType != XML_ELEMENT_NODE) {
                continue;
            }

            $varName = $variable->attributes->getNamedItem('name')->nodeValue;
            $varMetadata = $variable->attributes->getNamedItem('metadata')
                ? $variable->attributes->getNamedItem('metadata')->nodeValue
                : '=';
            $varValue = $variable->nodeValue;

            $outputVars[$varName] = [
                'name' => $varName,
                'metadata' => $varMetadata,
                'value' => $varValue
            ];
        }
        $output['variables'] = $outputVars;
        return $output;
    }
}
