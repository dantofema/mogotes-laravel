<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Pennant;

final class MogotesPennantDriver
{
    /**
     * Define a feature.
     */
    public function define(): void
    {
        //
    }

    /**
     * Get the value of a feature for a scope.
     */
    public function get(): mixed
    {
        return false;
    }

    /**
     * Set the value of a feature for a scope.
     */
    public function set(): void
    {
        //
    }

    /**
     * Get the value of all features for a scope.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return [];
    }

    /**
     * Delete the value of a feature for a scope.
     */
    public function delete(): void
    {
        //
    }

    /**
     * Purge all features from storage.
     */
    public function purge(): void
    {
        //
    }
}
