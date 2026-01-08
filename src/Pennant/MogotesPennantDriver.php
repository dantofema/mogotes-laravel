<?php

namespace Dantofema\MogotesLaravel\Pennant;

use Laravel\Pennant\Contracts\FeatureDriver;

class MogotesPennantDriver implements FeatureDriver
{
    /**
     * Define a feature.
     */
    public function define(string $feature, mixed $default): void
    {
        //
    }

    /**
     * Get the value of a feature for a scope.
     */
    public function get(string $feature, mixed $scope): mixed
    {
        return false;
    }

    /**
     * Set the value of a feature for a scope.
     */
    public function set(string $feature, mixed $scope, mixed $value): void
    {
        //
    }

    /**
     * Get the value of all features for a scope.
     *
     * @return array<string, mixed>
     */
    public function getAll(array $features, mixed $scope): array
    {
        return [];
    }

    /**
     * Delete the value of a feature for a scope.
     */
    public function delete(string $feature, mixed $scope): void
    {
        //
    }

    /**
     * Purge all features from storage.
     */
    public function purge(array $features): void
    {
        //
    }
}
