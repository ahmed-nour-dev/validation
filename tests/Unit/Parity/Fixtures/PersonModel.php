<?php

declare(strict_types=1);

namespace Vi\Validation\Tests\Unit\Parity\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model with a non-default table and key, for exists/unique model-class parity tests.
 */
class PersonModel extends Model
{
    protected $table = 'people';
    protected $primaryKey = 'pk';
    public $timestamps = false;
}
