<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mpaf\Tool\Lib\Config\Fragment;

class Builder
{
    const STRATEGY_BEFORE_ALL = 'before_all';
    const STRATEGY_BEFORE_ITEM = 'before_item';
    const STRATEGY_AFTER_ALL = 'after_all';
    const STRATEGY_AFTER_ITEM = 'after_item';

    public function build(array $itemConfig, \DOMElement $parent, \DOMDocument $factory)
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        $path = BASE_PATH . 'mpaf/tool/fragments/' . $itemConfig['name'] .'.jmx';
        $dom->load($path);

        $jMeterTestPlan = $dom->getElementsByTagName('jmeterTestPlan')->item(0);

        $output = null;
        /** @var \DOMElement $child */
        foreach ($jMeterTestPlan->childNodes as $child) {
            if (!in_array($child->nodeName, ['hashTree'])|| $child->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $output = $child;
            break;
        }

        if (isset($itemConfig['items'])) {
            $childHashTree = null;
            foreach ($output->childNodes as $child) {
                if (!in_array($child->nodeName, ['hashTree'])|| $child->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $childHashTree = $child;
                break;
            }
            foreach ($itemConfig['items'] as $item) {
                $this->build($item, $childHashTree, $dom);
            }
        }

        $fragment = $factory->createDocumentFragment();
        /** @var \DOMNode $child */
        foreach ($output->childNodes as $child) {
            $fragment->appendXML($child->ownerDocument->saveXML($child));
        }

        $this->resolveStrategy($itemConfig);
        if ($itemConfig['reference']) {
            $reference = $this->findReference($itemConfig['reference'], $parent);
            if ($reference == null) {
                foreach($parent->childNodes as $child){
                    $reference = $this->findReference($itemConfig['reference'], $child);
                    if ($reference != null) {
                        break;
                    }
                }
            }
            $this->insertItem($itemConfig, $fragment, $reference);
        } else {
            $this->insertItem($itemConfig, $fragment, $parent);
        }
    }

    protected function resolveStrategy($itemConfig)
    {
        if (isset($itemConfig['before'])) {
            if ($itemConfig['before'] == '-') {
                return self::STRATEGY_BEFORE_ALL;
            } else {
                return self::STRATEGY_BEFORE_ITEM;
            }
        }

        if (isset($itemConfig['after'])) {
            if ($itemConfig['after'] == '-') {
                return self::STRATEGY_AFTER_ALL;
            } else {
                return self::STRATEGY_AFTER_ITEM;
            }
        }

        return self::STRATEGY_AFTER_ALL;
    }

    protected function insertItem($itemConfig, \DOMNode $newItem, \DOMNode $parent)
    {
        $strategy = $this->resolveStrategy($itemConfig);

        switch ($strategy) {
            case self::STRATEGY_AFTER_ALL:
                $parent->appendChild($newItem);
                break;

            case self::STRATEGY_AFTER_ITEM:
                $after = $this->findAfterReference($itemConfig['after'], $parent);
                $parent->insertBefore($newItem, $after);
                break;

            case self::STRATEGY_BEFORE_ALL:
                $parent->insertBefore($newItem, $parent->firstChild);
                break;

            case self::STRATEGY_BEFORE_ITEM:
                $parent->insertBefore($newItem, $this->findBeforeReference($itemConfig['before'], $parent));
                break;
        }
    }

    protected function findReference($name, \DOMNode $root)
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        $result = $xpath->query("*[@testname='" . $name . "']/following-sibling::hashTree[1]", $root);

        if (!$result) {
            throw new \InvalidArgumentException('Reference ' . $name . ' not found');
        }
        return $result->item(0);
    }

    protected function findBeforeReference($name, \DOMNode $root)
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        $result = $xpath->query("*[@testname='" . $name . "']", $root);

        if (!$result) {
            throw new \InvalidArgumentException('Reference ' . $name . ' not found');
        }
        return $result->item(0);
    }

    protected function findAfterReference($name, \DOMNode $root)
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        $result = $xpath->query("*[@testname='" . $name . "']/following-sibling::hashTree[1]/following-sibling::*[1]", $root);

        if (!$result) {
            throw new \InvalidArgumentException('Reference ' . $name . ' not found');
        }
        return $result->item(0);
    }
}
