<?php

namespace App\Services;

class OperatingCatalog
{
    public function entities(): array
    {
        return config('operating.entities');
    }

    public function entity(string $type): array
    {
        abort_unless(isset($this->entities()[$type]), 404);

        return $this->entities()[$type];
    }

    public function label(array|string|null $value): string
    {
        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value['en'] ?? '';
        }

        return __($value ?? '');
    }

    public function status(string $status): string
    {
        return $this->label(config('operating.statuses.'.$status, $status));
    }

    public function data(object $record): array
    {
        return json_decode($record->data, true, 512, JSON_THROW_ON_ERROR);
    }
}
