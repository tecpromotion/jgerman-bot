<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
	->in([__DIR__ . '/src', __DIR__ . '/cli', __DIR__ . '/includes', __DIR__ . '/tests'])
	->exclude(['vendor']);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRules([
		'@PSR12'                  => true,
		'declare_strict_types'    => true,
		'no_unused_imports'       => true,
		'ordered_imports'         => ['sort_algorithm' => 'alpha'],
		'single_quote'            => true,
		'trailing_comma_in_multiline' => ['elements' => ['arrays']],
	])
	->setFinder($finder);
