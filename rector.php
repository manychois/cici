<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    /*
    ->withPhpSets()
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
    ])
    ->withTypeCoverageLevel(0);
    */
    ->withRules([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ]);
