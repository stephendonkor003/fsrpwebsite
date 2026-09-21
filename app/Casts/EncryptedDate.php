<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<CarbonImmutable|null, CarbonInterface|string|null>
 */
class EncryptedDate implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decrypted = Crypt::decryptString($value);
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $decrypted);

        if (! $date instanceof CarbonImmutable) {
            throw new InvalidArgumentException("The encrypted {$key} value is not a valid date.");
        }

        return $date;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::createFromFormat('!Y-m-d', (string) $value);

        if (! $date instanceof CarbonInterface) {
            throw new InvalidArgumentException("The {$key} value is not a valid date.");
        }

        return Crypt::encryptString($date->format('Y-m-d'));
    }
}
