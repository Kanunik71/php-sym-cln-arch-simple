<?php

declare(strict_types=1);

namespace App\Tests;

use App\Tests\Support\TestBed;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application / handler tests against real Doctrine repositories + test DB.
 * Domain unit tests must not extend this class.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    /** @var TestBed Assigned in setUp() before use. */
    protected $bed;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->bed = TestBed::create(static::getContainer());
        $this->bed->resetSchema();
    }
}
