<?php

declare(strict_types=1);

namespace Typhoon\Exporter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Exporter::class)]
final class ExporterTest extends TestCase
{
    public function testListAreExportedWithoutKeys(): void
    {
        $list = [1, 2, 3];

        $code = Exporter::export($list);

        self::assertSame('[1,2,3]', $code);
    }

    public function testHydratorIsInitializedOnlyOnce(): void
    {
        $objects = [new \ArrayObject(), new \ArrayObject()];

        $code = Exporter::export($objects);

        self::assertSame(1, substr_count($code, 'new \\' . Hydrator::class));
    }

    /**
     * @param positive-int $index
     */
    #[TestWith([0, '$o0'])]
    #[TestWith([10, '$oA'])]
    #[TestWith([11, '$oB'])]
    #[TestWith([62, '$o_'])]
    #[TestWith([63, '$o10'])]
    #[TestWith([64, '$o11'])]
    #[TestWith([250046, '$o___'])]
    public function testObjectVariable(int $index, string $variable): void
    {
        $actual = Exporter::objectVariable($index);

        self::assertSame($variable, $actual);
    }

    public function testItDeclaresVariableWhenObjectIsReused(): void
    {
        $object = new \stdClass();

        $code = Exporter::export([$object, $object]);

        self::assertStringContainsString('$', $code);
    }

    public function testItDoesNotRemoveStringThatLooksLikeObjectVariable(): void
    {
        $object = new \stdClass();
        $codeWithVariable = Exporter::export([$object, $object]);
        preg_match('/\$\w+=/', $codeWithVariable, $matches);
        $variableDeclaration = $matches[0];
        $object->property = $variableDeclaration;

        $code = Exporter::export($object);

        self::assertStringContainsString($variableDeclaration, $code);
    }

    public function testItRemovesNeedlessObjectVariable(): void
    {
        $object = new \stdClass();

        $code = Exporter::export($object);

        self::assertStringNotContainsString('$', $code);
    }

    public function testItRemovesAllNeedlessObjectVariables(): void
    {
        $value = [];
        for ($i = 0; $i < 1000; ++$i) {
            $value[] = new \stdClass();
        }

        $code = Exporter::export($value);

        self::assertStringNotContainsString('$', $code);
    }
}
