<?php

declare(strict_types=1);

namespace Vi\Validation\Rules;

/**
 * @internal Not part of the public API; may change in any release. See docs/public-api.md.
 */
interface NumericAwareInterface
{
    /**
     * Set whether the field under validation is numeric.
     */
    public function setNumeric(bool $numeric): void;
}
