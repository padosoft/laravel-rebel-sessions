<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Padosoft\Rebel\Sessions\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function subjectUser(int $id = 1): GenericUser
{
    return new GenericUser(['id' => $id]);
}
