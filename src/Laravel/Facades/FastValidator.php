<?php

declare(strict_types=1);

namespace Vi\Validation\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Vi\Validation\Laravel\FastValidatorFactory;
use Vi\Validation\Laravel\FastValidatorWrapper;

/**
 * @method static FastValidatorWrapper make(iterable $data, array $rules, array $messages = [], array $attributes = [])
 *
 * @see \Vi\Validation\Laravel\FastValidatorFactory
 */
final class FastValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FastValidatorFactory::class;
    }
}
