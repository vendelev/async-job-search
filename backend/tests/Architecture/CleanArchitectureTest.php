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
     * Проверим, что Core не зависит от прикладных модулей.
     */
    #[TestRule]
    public function testCoreIndependence(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\Core'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\Platform'),
                Selector::inNamespace('App\\VacancyCatalog'),
                Selector::inNamespace('App\\VacancyDiscovery'),
            )
            ->because('Core содержит общие контракты и не может зависеть от модулей, которые его используют');
    }

    /**
     * Проверим, что внутренние слои Migration не используются другими модулями.
     */
    #[TestRule]
    public function testMigrationModuleBoundary(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App'),
                    Selector::Not(Selector::inNamespace('App\\Platform\\Migration')),
                ),
            )
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\Platform\\Migration\\Application'),
                Selector::inNamespace('App\\Platform\\Migration\\Infrastructure'),
            )
            ->because('Другие модули обращаются к Migration только через Domain-контракты и DI-модуль');
    }

    /**
     * Проверим, что внутренние слои VacancyCatalog не используются другими модулями.
     */
    #[TestRule]
    public function testVacancyCatalogModuleBoundary(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App'),
                    Selector::Not(Selector::inNamespace('App\\VacancyCatalog')),
                ),
            )
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\VacancyCatalog\\Application'),
                Selector::inNamespace('App\\VacancyCatalog\\Infrastructure'),
            )
            ->because('Другие модули обращаются к VacancyCatalog только через Domain-контракты и DI-модуль');
    }

    /**
     * Проверим, что внутренние слои VacancyDiscovery не используются другими модулями.
     */
    #[TestRule]
    public function testVacancyDiscoveryModuleBoundary(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App'),
                    Selector::Not(Selector::inNamespace('App\\VacancyDiscovery')),
                ),
            )
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\\VacancyDiscovery\\Application'),
                Selector::inNamespace('App\\VacancyDiscovery\\Infrastructure'),
            )
            ->because('Другие модули обращаются к VacancyDiscovery только через Domain-контракты и DI-модуль');
    }
}
