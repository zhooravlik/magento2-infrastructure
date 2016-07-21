<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Scanner;

use PhpParser\Error;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PHPSemVerChecker\Registry\Registry;
use Magento\Tools\SemanticVersionChecker\Visitor\ClassVisitor;
use PHPSemVerChecker\Visitor\FunctionVisitor;
use Magento\Tools\SemanticVersionChecker\Visitor\InterfaceVisitor;
use PHPSemVerChecker\Visitor\TraitVisitor;
use RuntimeException;

class Scanner
{
    /**
     * @var \PHPSemVerChecker\Registry\Registry
     */
    protected $registry;
    /**
     * @var \PhpParser\Parser
     */
    protected $parser;
    /**
     * @var \PhpParser\NodeTraverser
     */
    protected $traverser;

    public function __construct()
    {
        $this->registry = new Registry();
        $this->parser = new Parser(new Emulative());
        $this->traverser = new NodeTraverser();

        $visitors = [
            new NameResolver(),
            new ClassVisitor($this->registry),
            new InterfaceVisitor($this->registry),
            new FunctionVisitor($this->registry),
            new TraitVisitor($this->registry),
        ];

        foreach ($visitors as $visitor) {
            $this->traverser->addVisitor($visitor);
        }
    }

    /**
     * @param string $file
     */
    public function scan($file)
    {
        // Set the current file used by the registry so that we can tell where the change was scanned.
        $this->registry->setCurrentFile($file);

        $code = file_get_contents($file);

        try {
            $statements = $this->parser->parse($code);
            $this->traverser->traverse($statements);
        } catch (Error $e) {
            throw new RuntimeException('Parse Error: ' . $e->getMessage() . ' in ' . $file);
        }
    }

    /**
     * @return \PHPSemVerChecker\Registry\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }
}
