<?php

namespace App\Services;

use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use Illuminate\Validation\ValidationException;

class DraftVersionMigrator
{
    public function preview(RequestDraft $draft, FormSchemaVersion $target): array
    {
        $oldFields = collect($draft->schemaVersion?->schema['fields'] ?? [])->keyBy('key');
        $newFields = $target->schema['fields'] ?? [];
        $newByKey = collect($newFields)->keyBy('key');
        $responses = $draft->responses ?? [];
        $migrated = array_intersect_key($responses, array_flip(['subject', 'description']));
        $retained = array_keys($migrated);
        $reset = [];

        foreach ($newFields as $field) {
            $key = $field['key'];
            if (! array_key_exists($key, $responses)) {
                continue;
            }
            $old = $oldFields->get($key);
            if (! $old || $old['type'] !== $field['type'] || ! $this->valueFits($field, $responses[$key])) {
                $reset[] = $key;

                continue;
            }
            $migrated[$key] = $responses[$key];
            $retained[] = $key;
        }

        foreach ($responses as $key => $value) {
            if (! in_array($key, ['subject', 'description'], true) && ! $newByKey->has($key) && ! in_array($key, $reset, true)) {
                $reset[] = $key;
            }
        }

        foreach ($newFields as $field) {
            if (array_key_exists($field['key'], $migrated) && ! $this->visible($field, $migrated)) {
                unset($migrated[$field['key']]);
                $retained = array_values(array_diff($retained, [$field['key']]));
                $reset[] = $field['key'];
            }
        }

        $required = array_values(array_map(
            fn (array $field) => $field['key'],
            array_filter($newFields, fn (array $field) => ($field['required'] ?? false)
                && $this->visible($field, $migrated)
                && (! isset($migrated[$field['key']]) || $migrated[$field['key']] === '' || $migrated[$field['key']] === [])),
        ));

        return [
            'from_version_id' => $draft->form_schema_version_id,
            'to_version_id' => $target->id,
            'to_version' => $target->version,
            'schema' => $target->schema,
            'responses' => $migrated,
            'retained_keys' => $retained,
            'reset_keys' => array_values(array_unique($reset)),
            'required_keys' => $required,
        ];
    }

    private function valueFits(array $field, mixed $value): bool
    {
        $standalone = $field;
        unset($standalone['required'], $standalone['visible_if']);
        try {
            app(FormSchemaValidator::class)->validateResponses(['fields' => [$standalone]], [$field['key'] => $value]);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function visible(array $field, array $responses): bool
    {
        if (! isset($field['visible_if'])) {
            return true;
        }
        $mode = array_key_first($field['visible_if']);
        $matches = array_map(function (array $rule) use ($responses): bool {
            $value = $responses[$rule['field']] ?? null;

            return match ($rule['operator']) {
                'equals' => $value === $rule['value'],
                'in' => in_array($value, $rule['value'], true),
                'present' => $value !== null && $value !== '' && $value !== [],
            };
        }, $field['visible_if'][$mode]);

        return $mode === 'all' ? ! in_array(false, $matches, true) : in_array(true, $matches, true);
    }
}
