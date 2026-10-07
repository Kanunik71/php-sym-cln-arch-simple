<?php

declare(strict_types=1);

namespace App\Tests\Utils\Asserts;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InputAssertUtilsTest extends TestCase
{
    public function testRequiredStringTrimsValue(): void
    {
        $this->assertSame('john@example.com', InputAssertUtils::requiredString('  john@example.com  ', 'email'));
    }

    public function testRequiredStringRejectsBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "email" is required.');

        InputAssertUtils::requiredString('   ', 'email');
    }

    public function testOptionalStringNormalizesBlankAndNull(): void
    {
        $this->assertNull(InputAssertUtils::optionalString(null, 'city'));
        $this->assertNull(InputAssertUtils::optionalString('   ', 'city'));
        $this->assertSame('Berlin', InputAssertUtils::optionalString('  Berlin  ', 'city'));
    }

    public function testOptionalStringRejectsNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "city" must be a string.');

        InputAssertUtils::optionalString(1, 'city');
    }

    public function testOptionalIntCastsNumericValue(): void
    {
        $this->assertNull(InputAssertUtils::optionalInt(null, 'estimateTime'));
        $this->assertSame(120, InputAssertUtils::optionalInt('120', 'estimateTime'));
    }

    public function testStringOrEmptyKeepsStringAndFallsBack(): void
    {
        $this->assertSame('user-1', InputAssertUtils::stringOrEmpty('user-1'));
        $this->assertSame('', InputAssertUtils::stringOrEmpty(null));
        $this->assertSame('', InputAssertUtils::stringOrEmpty(10));
    }

    public function testOptionalUuidRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "parentId" must be a valid UUID.');

        InputAssertUtils::optionalUuid('not-a-uuid', 'parentId');
    }

    public function testOptionalFloatCastsNumericValue(): void
    {
        $this->assertNull(InputAssertUtils::optionalFloat(null, 'price'));
        $this->assertSame(9.99, InputAssertUtils::optionalFloat('9.99', 'price'));
    }

    public function testOptionalFloatRejectsNonNumericValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "price" must be a number.');

        InputAssertUtils::optionalFloat('abc', 'price');
    }

    public function testOptionalArrayAcceptsNullAndArray(): void
    {
        $this->assertNull(InputAssertUtils::optionalArray(null, 'location'));
        $this->assertSame(['city' => 'Berlin'], InputAssertUtils::optionalArray(['city' => 'Berlin'], 'location'));
    }

    public function testOptionalArrayRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" must be an object.');

        InputAssertUtils::optionalArray('Berlin', 'location');
    }

    public function testOptionalNestedModelDelegatesToFromArray(): void
    {
        $this->assertNull(InputAssertUtils::optionalNestedModel(
            null,
            'location',
            static fn (array $data): string => $data['city'],
        ));
        $this->assertSame('Berlin', InputAssertUtils::optionalNestedModel(
            ['city' => 'Berlin'],
            'location',
            static fn (array $data): string => $data['city'],
        ));
    }

    public function testOptionalNestedModelRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" must be an object.');

        InputAssertUtils::optionalNestedModel(
            1,
            'location',
            static fn (array $data): string => $data['city'],
        );
    }

    public function testRequiredArrayAcceptsArray(): void
    {
        $this->assertSame(['city' => 'Berlin'], InputAssertUtils::requiredArray(['city' => 'Berlin'], 'location'));
    }

    public function testRequiredArrayRejectsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" is required.');

        InputAssertUtils::requiredArray(null, 'location');
    }

    public function testRequiredArrayRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" must be an object.');

        InputAssertUtils::requiredArray('Berlin', 'location');
    }

    public function testRequiredNestedModelDelegatesToFromArray(): void
    {
        $this->assertSame('Berlin', InputAssertUtils::requiredNestedModel(
            ['city' => 'Berlin'],
            'location',
            static fn (array $data): string => $data['city'],
        ));
    }

    public function testRequiredNestedModelRejectsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" is required.');

        InputAssertUtils::requiredNestedModel(
            null,
            'location',
            static fn (array $data): string => $data['city'],
        );
    }

    public function testRequiredNestedModelRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "location" must be an object.');

        InputAssertUtils::requiredNestedModel(
            1,
            'location',
            static fn (array $data): string => $data['city'],
        );
    }

    public function testNestedModelListDelegatesToFromArray(): void
    {
        $this->assertSame([], InputAssertUtils::nestedModelList(
            null,
            'items',
            static fn (array $data): string => $data['city'],
        ));
        $this->assertSame(['Berlin', 'Paris'], InputAssertUtils::nestedModelList(
            [['city' => 'Berlin'], ['city' => 'Paris']],
            'items',
            static fn (array $data): string => $data['city'],
        ));
    }

    public function testNestedModelListRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "items" must be an array.');

        InputAssertUtils::nestedModelList(
            'Berlin',
            'items',
            static fn (array $data): string => $data['city'],
        );
    }

    public function testNestedModelListRejectsNonObjectItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "items[]" must be an object.');

        InputAssertUtils::nestedModelList(
            ['Berlin'],
            'items',
            static fn (array $data): string => $data['city'],
        );
    }

    public function testStringListAcceptsStrings(): void
    {
        $this->assertSame([], InputAssertUtils::stringList(null, 'sortFields'));
        $this->assertSame(['createdAt', 'email'], InputAssertUtils::stringList(
            ['createdAt', '  email  '],
            'sortFields',
        ));
    }

    public function testStringListRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "sortFields" must be an array.');

        InputAssertUtils::stringList('createdAt', 'sortFields');
    }

    public function testStringListRejectsNonStringItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "sortFields[]" is required.');

        InputAssertUtils::stringList([1], 'sortFields');
    }

    public function testEnumListAcceptsEnumValues(): void
    {
        $this->assertSame([], InputAssertUtils::enumList(null, 'channels', TaskStatusEnum::class));
        $this->assertSame(
            [TaskStatusEnum::Active, TaskStatusEnum::Finished],
            InputAssertUtils::enumList(['Active', 'Finished'], 'channels', TaskStatusEnum::class),
        );
    }

    public function testEnumListRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "channels" must be an array.');

        InputAssertUtils::enumList('Active', 'channels', TaskStatusEnum::class);
    }

    public function testEnumListRejectsInvalidItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "channels[]" has invalid value. Allowed: Initial, Active, Canceled, Finished.');

        InputAssertUtils::enumList(['Unknown'], 'channels', TaskStatusEnum::class);
    }

    public function testRequiredEnumParsesBackedEnum(): void
    {
        $this->assertSame(
            TaskStatusEnum::Active,
            InputAssertUtils::requiredEnum('Active', 'status', TaskStatusEnum::class),
        );
    }

    public function testRequiredEnumRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "status" has invalid value. Allowed: Initial, Active, Canceled, Finished.');

        InputAssertUtils::requiredEnum('Unknown', 'status', TaskStatusEnum::class);
    }

    public function testOptionalEnumNormalizesBlankAndNull(): void
    {
        $this->assertNull(InputAssertUtils::optionalEnum(null, 'status', TaskStatusEnum::class));
        $this->assertNull(InputAssertUtils::optionalEnum('   ', 'status', TaskStatusEnum::class));
        $this->assertSame(
            TaskStatusEnum::Finished,
            InputAssertUtils::optionalEnum('Finished', 'status', TaskStatusEnum::class),
        );
    }

    public function testOptionalEnumRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "status" has invalid value. Allowed: Initial, Active, Canceled, Finished.');

        InputAssertUtils::optionalEnum('Unknown', 'status', TaskStatusEnum::class);
    }
}
