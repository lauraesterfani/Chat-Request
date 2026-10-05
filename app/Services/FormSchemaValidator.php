<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class FormSchemaValidator
{
    private const TYPES = ['short_text', 'long_text', 'number', 'date', 'single_select', 'multi_select', 'boolean', 'subjects'];

    private const RESERVED_KEYS = ['id', 'user_id', 'type_id', 'type_request_id', 'status', 'protocol', 'document_ids', 'form_schema_version_id', 'form_responses', 'idempotency_key', 'draft_id'];

    public function validateDefinition(array $schema): void
    {
        if (array_diff(array_keys($schema), ['fields'])) {
            $this->fail('schema', 'O formulário contém configuração não permitida.');
        }
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields) || ! array_is_list($fields) || count($fields) > 50) {
            $this->fail('schema.fields', 'O formulário deve conter uma lista de até 50 campos.');
        }

        $knownKeys = [];
        foreach ($fields as $index => $field) {
            $path = "schema.fields.$index";
            if (! is_array($field) || ! is_string($field['key'] ?? null) || ! preg_match('/^[a-z][a-z0-9_]{1,59}$/', $field['key'])) {
                $this->fail("$path.key", 'Chave de campo inválida.');
            }
            $key = $field['key'];
            if (array_diff(array_keys($field), ['key', 'label', 'type', 'required', 'instruction', 'options', 'min', 'max', 'max_length', 'max_items', 'visible_if'])) {
                $this->fail($path, 'Campo contém configuração não permitida.');
            }
            if (in_array($key, $knownKeys, true) || in_array($key, self::RESERVED_KEYS, true)) {
                $this->fail("$path.key", 'Chave de campo duplicada ou reservada.');
            }
            if (! is_string($field['label'] ?? null) || trim($field['label']) === '' || mb_strlen($field['label']) > 120) {
                $this->fail("$path.label", 'Informe um rótulo de até 120 caracteres.');
            }
            if (! in_array($field['type'] ?? null, self::TYPES, true)) {
                $this->fail("$path.type", 'Tipo de campo inválido.');
            }
            if (isset($field['required']) && ! is_bool($field['required'])) {
                $this->fail("$path.required", 'Obrigatoriedade deve ser verdadeira ou falsa.');
            }
            if (isset($field['instruction']) && (! is_string($field['instruction']) || mb_strlen($field['instruction']) > 500)) {
                $this->fail("$path.instruction", 'Instrução inválida.');
            }

            if (in_array($field['type'], ['single_select', 'multi_select'], true)) {
                $options = $field['options'] ?? null;
                if (! is_array($options) || ! array_is_list($options) || count($options) < 1 || count($options) > 100) {
                    $this->fail("$path.options", 'Seleção exige de 1 a 100 opções.');
                }
                foreach ($options as $option) {
                    if (! is_string($option) || trim($option) === '' || mb_strlen($option) > 120) {
                        $this->fail("$path.options", 'Opção inválida.');
                    }
                }
                if (count(array_unique($options)) !== count($options)) {
                    $this->fail("$path.options", 'Opções duplicadas.');
                }
            } elseif (array_key_exists('options', $field)) {
                $this->fail("$path.options", 'Este tipo não aceita opções.');
            }

            if (isset($field['max_length']) && (! in_array($field['type'], ['short_text', 'long_text'], true) || ! is_int($field['max_length']) || $field['max_length'] < 1 || $field['max_length'] > 10000)) {
                $this->fail("$path.max_length", 'Limite de texto inválido.');
            }
            if (isset($field['max_items']) && ($field['type'] !== 'subjects' || ! is_int($field['max_items']) || $field['max_items'] < 1 || $field['max_items'] > 20)) {
                $this->fail("$path.max_items", 'Limite de itens inválido.');
            }
            if (isset($field['min']) || isset($field['max'])) {
                if ($field['type'] !== 'number' || (isset($field['min']) && ! is_numeric($field['min'])) || (isset($field['max']) && ! is_numeric($field['max'])) || (isset($field['min'], $field['max']) && $field['min'] > $field['max'])) {
                    $this->fail($path, 'Limites numéricos inválidos.');
                }
            }

            if (isset($field['visible_if'])) {
                $condition = $field['visible_if'];
                if (! is_array($condition) || count($condition) !== 1 || ! in_array(array_key_first($condition), ['all', 'any'], true)) {
                    $this->fail("$path.visible_if", 'Condição de visibilidade inválida.');
                }
                $rules = reset($condition);
                if (! is_array($rules) || ! array_is_list($rules) || count($rules) < 1 || count($rules) > 10) {
                    $this->fail("$path.visible_if", 'A condição precisa de 1 a 10 regras.');
                }
                foreach ($rules as $rule) {
                    if (! is_array($rule) || array_diff(array_keys($rule), ['field', 'operator', 'value']) || ! in_array($rule['field'] ?? null, $knownKeys, true) || ! in_array($rule['operator'] ?? null, ['equals', 'in', 'present'], true)) {
                        $this->fail("$path.visible_if", 'A condição deve referenciar um campo anterior válido.');
                    }
                    if ($rule['operator'] === 'equals' && (! array_key_exists('value', $rule) || ! is_scalar($rule['value']))) {
                        $this->fail("$path.visible_if", 'Comparação exige valor.');
                    }
                    if ($rule['operator'] === 'in' && (! is_array($rule['value'] ?? null) || ! array_is_list($rule['value']) || $rule['value'] === [] || count(array_filter($rule['value'], 'is_scalar')) !== count($rule['value']))) {
                        $this->fail("$path.visible_if", 'Comparação em lista exige valores.');
                    }
                    if ($rule['operator'] === 'present' && array_key_exists('value', $rule)) {
                        $this->fail("$path.visible_if", 'Presença não aceita valor.');
                    }
                }
            }

            $knownKeys[] = $key;
        }
    }

    public function validateResponses(array $schema, array $responses): void
    {
        $fields = $schema['fields'] ?? [];
        $allowed = array_column($fields, 'key');
        foreach (array_keys($responses) as $key) {
            if (! in_array($key, $allowed, true)) {
                $this->fail("form_responses.$key", 'Campo desconhecido no formulário.');
            }
        }

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $responses[$key] ?? null;
            $empty = $value === null || (is_string($value) && trim($value) === '') || $value === [];
            if (! $this->isVisible($field, $responses)) {
                if (! $empty) {
                    $this->fail("form_responses.$key", 'Remova a resposta de um campo oculto.');
                }

                continue;
            }
            if ($empty) {
                if ($field['required'] ?? false) {
                    $this->fail("form_responses.$key", "Preencha {$field['label']}.");
                }

                continue;
            }

            $valid = match ($field['type']) {
                'short_text', 'long_text' => is_string($value) && mb_strlen($value) <= ($field['max_length'] ?? ($field['type'] === 'short_text' ? 255 : 10000)),
                'number' => (is_int($value) || is_float($value)) && (! isset($field['min']) || $value >= $field['min']) && (! isset($field['max']) || $value <= $field['max']),
                'date' => $this->validDate($value),
                'single_select' => is_string($value) && in_array($value, $field['options'], true),
                'multi_select' => is_array($value) && array_is_list($value) && $value !== [] && count(array_filter($value, 'is_string')) === count($value) && count($value) === count(array_unique($value)) && ! array_diff($value, $field['options']),
                'boolean' => is_bool($value),
                'subjects' => $this->validSubjects($value, $field['max_items'] ?? 20),
                default => false,
            };
            if (! $valid) {
                $this->fail("form_responses.$key", "Valor inválido em {$field['label']}.");
            }
        }
    }

    private function isVisible(array $field, array $responses): bool
    {
        if (! isset($field['visible_if'])) {
            return true;
        }

        $operator = array_key_first($field['visible_if']);
        $matches = array_map(function (array $rule) use ($responses): bool {
            $value = $responses[$rule['field']] ?? null;

            return match ($rule['operator']) {
                'equals' => $value === $rule['value'],
                'in' => in_array($value, $rule['value'], true),
                'present' => $value !== null && $value !== '' && $value !== [],
            };
        }, $field['visible_if'][$operator]);

        return $operator === 'all' ? ! in_array(false, $matches, true) : in_array(true, $matches, true);
    }

    private function validDate(mixed $value): bool
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    private function validSubjects(mixed $value, int $maxItems): bool
    {
        if (! is_array($value) || ! array_is_list($value) || $value === [] || count($value) > $maxItems) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_array($item) || array_diff(array_keys($item), ['subject', 'description']) || ! is_string($item['subject'] ?? null) || trim($item['subject']) === '' || mb_strlen($item['subject']) > 255 || (isset($item['description']) && (! is_string($item['description']) || mb_strlen($item['description']) > 2000))) {
                return false;
            }
        }

        return true;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
