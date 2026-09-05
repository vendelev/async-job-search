<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\PHPat;

final readonly class CleanArchitectureTest
{
    /**
     * Проверим зависимость между Domain и другими слоями
     */
    #[TestRule]
    public function testDomainLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App.*\\\\Domain(?:\\\\|$)/', true))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Application(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Presentation(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Infrastructure(?:\\\\|$)/', true),
            )
            ->because('Domain может использовать только Domain (и свой и чужой)');
    }

    /**
     * Проверим зависимость между Application и другими слоями
     */
    #[TestRule]
    public function testApplicationLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App.*\\\\Application(?:\\\\|$)/', true))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Presentation(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Infrastructure(?:\\\\|$)/', true),
            )
            ->because('Application не может использовать Presentation и Infrastructure');
    }

    /**
     * Проверим зависимость между Infrastructure и другими слоями
     */
    #[TestRule]
    public function testInfrastructureLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App.*\\\\Infrastructure(?:\\\\|$)/', true))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Presentation(?:\\\\|$)/', true),
            )
            ->because('Infrastructure может использовать только Application и Domain (и свой и чужой)');
    }

    /**
     * Проверим, что Presentation не зависит от внутренних слоёв другого модуля.
     */
    #[TestRule]
    public function testMigrationPresentationLayer(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\Migration\\Presentation'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\JobSearch\\Application'),
                Selector::inNamespace('App\\JobSearch\\Infrastructure'),
                Selector::inNamespace('App\\JobSearch\\Presentation'),
            )
            ->because('Presentation обращается к другому модулю только через его Domain-контракт');
    }

    /**
     * Проверим, что Migration не зависит от внутренних слоёв JobSearch.
     */
    #[TestRule]
    public function testMigrationModuleBoundary(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\Migration'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\JobSearch\\Application'),
                Selector::inNamespace('App\\JobSearch\\Infrastructure'),
                Selector::inNamespace('App\\JobSearch\\Presentation'),
            )
            ->because('Migration обращается к JobSearch только через его Domain-контракт');
    }

    /**
     * Проверим, что JobSearch не зависит от внутренних слоёв Migration.
     */
    #[TestRule]
    public function testJobSearchModuleBoundary(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\JobSearch'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\Migration\\Application'),
                Selector::inNamespace('App\\Migration\\Infrastructure'),
                Selector::inNamespace('App\\Migration\\Presentation'),
            )
            ->because('JobSearch обращается к Migration только через его Domain-контракт');
    }
}
