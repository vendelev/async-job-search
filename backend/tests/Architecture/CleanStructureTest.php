<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\PHPat;

final readonly class CleanStructureTest
{
    /**
     * Проверим структуру папок в Application
     */
    #[TestRule]
    public function testApplicationFolder(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Command(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Factory(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Responder(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Query(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\UseCase(?:\\\\|$)/', true),
            )
            ->should()->beNamed('/.+Application.+/', true)
            ->because('Этот класс должен находится в слое Application');
    }

    /**
     * Проверим обязательность Final
     */
    #[TestRule]
    public function testIsFinal(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Application(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Domain\\\\Entity(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Domain\\\\ValueObject(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Infrastructure(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Presentation(?:\\\\|$)/', true),
            )
            ->should()->beFinal()
            ->because('Класс должен быть Final');
    }

    /**
     * Проверим обязательность Readonly для Application
     */
    #[TestRule]
    public function testIsReadonly(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Application(?:\\\\|$)/', true),
            )
            ->excluding(Selector::isEnum())
            ->should()->beReadonly()
            ->because('Класс должен быть Readonly');
    }

    /**
     * Проверим структуру папок в Domain
     */
    #[TestRule]
    public function testDomainFolder(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace(
                    '/^App.*\\\\(?:Event|Exception|Entity|Request|Response|Validation|ValueObject)(?:\\\\|$)/',
                    true,
                ),
            )
            ->should()->beNamed('/.+Domain.+/', true)
            ->because('Этот класс должен находится в слое Domain');
    }

    /**
     * Проверим интерфейсы созданы в Domain
     */
    #[TestRule]
    public function testInterfaceInDomain(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::isInterface(),
                    Selector::inNamespace('/^App\\\\(?:Migration|Platform\\\\(?:EventStore|Postgres))\\\\/', true),
                ),
            )
            ->should()->beNamed('/.+Domain.+/', true)
            ->because('Публичные интерфейсы модулей должны находиться в слое Domain');
    }

    /**
     * Проверим структуру папок в Presentation
     */
    #[TestRule]
    public function testPresentationFolder(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Config(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Console(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Http(?:\\\\|$)/', true),
                Selector::inNamespace('/^App.*\\\\Listener(?:\\\\|$)/', true),
            )
            ->should()->beNamed('/.+Presentation.+/', true)
            ->because('Этот класс должен находится в слое Presentation');
    }

    /**
     * Проверим структуру папок в Infrastructure
     */
    #[TestRule]
    public function testInfrastructureFolder(): BuildStep
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^App.*\\\\Adapter(?:\\\\|$)/', true),
            )
            ->should()->beNamed('/.+Infrastructure.+/', true)
            ->because('Этот класс должен находится в слое Infrastructure');
    }
}
