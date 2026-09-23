<?php

namespace App\Support;

use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Normalizer;

final class EventRegistrationSearchIndex
{
    public const DELEGATION_CAPACITY = 'delegation_capacity';

    public const GENDER = 'gender';

    public const MEMBER_STATE = 'member_state';

    public const NAME_PREFIX = 'name_prefix';

    public const NATIONALITY = 'nationality';

    public const ORGANISATION_PREFIX = 'organisation_prefix';

    public const MIN_PREFIX_LENGTH = 2;

    public const MAX_PREFIX_LENGTH = 64;

    public const MAX_QUERY_WORDS = 8;

    private const DOMAIN = 'event-registration-search:v1';

    private const EXACT_FIELDS = [
        self::MEMBER_STATE,
        self::NATIONALITY,
        self::GENDER,
        self::DELEGATION_CAPACITY,
    ];

    private bool $tableAvailable = false;

    public function exactToken(string $field, string $value): string
    {
        if (! in_array($field, self::EXACT_FIELDS, true)) {
            throw new InvalidArgumentException("Unsupported event registration search field [{$field}].");
        }

        $normalized = $this->normalize($value);

        if ($normalized === '') {
            throw new InvalidArgumentException('The event registration search value cannot be empty.');
        }

        return $this->token($field, $normalized);
    }

    /**
     * @return array<int, array{name_prefix: string, organisation_prefix: string}>
     */
    public function queryTokenHashes(string $search): array
    {
        $words = array_slice($this->words($search), 0, self::MAX_QUERY_WORDS);

        return array_map(function (string $word): array {
            $prefix = Str::substr($word, 0, self::MAX_PREFIX_LENGTH);

            return [
                self::NAME_PREFIX => $this->token(self::NAME_PREFIX, $prefix),
                self::ORGANISATION_PREFIX => $this->token(self::ORGANISATION_PREFIX, $prefix),
            ];
        }, $words);
    }

    public function synchronize(EventRegistration $registration): void
    {
        if (! $registration->exists || $registration->getKey() === null) {
            throw new LogicException('Only persisted event registrations can be indexed.');
        }

        if (! $this->tableAvailable()) {
            return;
        }

        DB::transaction(function () use ($registration): void {
            $current = EventRegistration::query()
                ->whereKey($registration->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $tokens = $this->tokensFor($current);

            $current->searchTokens()->delete();

            if ($tokens !== []) {
                $current->searchTokens()->createMany($tokens);
            }
        });
    }

    /** @return array<int, array{namespace: string, token_hash: string}> */
    private function tokensFor(EventRegistration $registration): array
    {
        $tokens = [];

        foreach (self::EXACT_FIELDS as $field) {
            $value = $registration->getAttribute($field);

            if (! is_string($value) || $this->normalize($value) === '') {
                continue;
            }

            $this->addToken($tokens, $field, $this->exactToken($field, $value));
        }

        foreach ([$registration->first_name, $registration->surname] as $name) {
            if (is_string($name)) {
                $this->addPrefixes($tokens, self::NAME_PREFIX, $name);
            }
        }

        if (is_string($registration->organisation)) {
            $this->addPrefixes($tokens, self::ORGANISATION_PREFIX, $registration->organisation);
        }

        return array_values($tokens);
    }

    /**
     * @param  array<string, array{namespace: string, token_hash: string}>  $tokens
     */
    private function addPrefixes(array &$tokens, string $namespace, string $value): void
    {
        foreach ($this->words($value) as $word) {
            $maximumLength = min(Str::length($word), self::MAX_PREFIX_LENGTH);

            for ($length = self::MIN_PREFIX_LENGTH; $length <= $maximumLength; $length++) {
                $prefix = Str::substr($word, 0, $length);
                $this->addToken($tokens, $namespace, $this->token($namespace, $prefix));
            }
        }
    }

    /**
     * @param  array<string, array{namespace: string, token_hash: string}>  $tokens
     */
    private function addToken(array &$tokens, string $namespace, string $hash): void
    {
        $tokens[$namespace.':'.$hash] = [
            'namespace' => $namespace,
            'token_hash' => $hash,
        ];
    }

    /** @return array<int, string> */
    private function words(string $value): array
    {
        $normalized = $this->normalize($value);

        if ($normalized === '') {
            return [];
        }

        $words = array_filter(
            explode(' ', $normalized),
            static fn (string $word): bool => Str::length($word) >= self::MIN_PREFIX_LENGTH,
        );

        return array_values(array_unique($words));
    }

    private function normalize(string $value): string
    {
        $unicode = Normalizer::normalize($value, Normalizer::FORM_KC);
        $normalized = Str::lower(is_string($unicode) ? $unicode : $value);
        $normalized = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $normalized) ?? '';

        return Str::squish($normalized);
    }

    private function token(string $namespace, string $normalized): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new LogicException('APP_KEY must be configured before registration search tokens can be created.');
        }

        return hash_hmac(
            'sha256',
            self::DOMAIN."\0".$namespace."\0".$normalized,
            $key,
        );
    }

    private function tableAvailable(): bool
    {
        if ($this->tableAvailable) {
            return true;
        }

        return $this->tableAvailable = Schema::hasTable('event_registration_search_tokens');
    }
}
