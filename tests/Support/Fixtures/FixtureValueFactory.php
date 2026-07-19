<?php

declare(strict_types=1);

namespace Tests\Support\Fixtures;

final class FixtureValueFactory
{
    private int $sequence = 0;

    public function __construct(string $scope)
    {
        $normalized = preg_replace('/[^a-z0-9]+/i', '-', strtolower($scope)) ?? 'case';
        $normalized = trim($normalized, '-');
        $this->scope = substr($normalized !== '' ? $normalized : 'case', 0, 16);
    }

    private string $scope;

    public function text(string $role, string $variant = ''): string
    {
        return $this->prefix($role, $variant);
    }

    public function slug(string $role, string $variant = ''): string
    {
        return strtolower($this->prefix($role, $variant));
    }

    public function locale(int $position): string
    {
        return chr(97 + intdiv($position, 26)) . chr(97 + ($position % 26));
    }

    private function prefix(string $role, string $variant): string
    {
        $this->sequence++;
        $parts = ['fixture', $this->scope, $role];

        if ($variant !== '') {
            $parts[] = $variant;
        }

        $parts[] = (string) $this->sequence;

        return implode('-', array_map(
            static fn (string $part): string => trim($part, '-_ '),
            $parts,
        ));
    }
}
