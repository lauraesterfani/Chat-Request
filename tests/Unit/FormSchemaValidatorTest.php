<?php

namespace Tests\Unit;

use App\Services\FormSchemaValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormSchemaValidatorTest extends TestCase
{
    public function test_definition_rejects_reserved_keys_invalid_options_and_forward_references(): void
    {
        $validator = new FormSchemaValidator;
        $invalidSchemas = [
            ['fields' => [['key' => 'user_id', 'label' => 'Identificador', 'type' => 'short_text']]],
            ['fields' => [['key' => 'choice', 'label' => 'Escolha', 'type' => 'single_select', 'options' => ['A', 'A']]]],
            ['fields' => [['key' => 'dependent', 'label' => 'Dependente', 'type' => 'short_text', 'visible_if' => ['all' => [['field' => 'choice', 'operator' => 'equals', 'value' => 'A']]]], ['key' => 'choice', 'label' => 'Escolha', 'type' => 'short_text']]],
            ['fields' => [['key' => 'custom', 'label' => 'Customizado', 'type' => 'short_text', 'script' => 'alert(1)']]],
        ];

        foreach ($invalidSchemas as $schema) {
            try {
                $validator->validateDefinition($schema);
                $this->fail('Schema inválido foi aceito.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_responses_obey_types_limits_and_conditional_visibility(): void
    {
        $validator = new FormSchemaValidator;
        $schema = ['fields' => [
            ['key' => 'choice', 'label' => 'Escolha', 'type' => 'single_select', 'options' => ['A', 'B'], 'required' => true],
            ['key' => 'details', 'label' => 'Detalhes', 'type' => 'short_text', 'required' => true, 'max_length' => 5, 'visible_if' => ['all' => [['field' => 'choice', 'operator' => 'equals', 'value' => 'A']]]],
            ['key' => 'count', 'label' => 'Quantidade', 'type' => 'number', 'min' => 1, 'max' => 3],
            ['key' => 'date', 'label' => 'Data', 'type' => 'date'],
            ['key' => 'tags', 'label' => 'Opções', 'type' => 'multi_select', 'options' => ['X', 'Y']],
            ['key' => 'subjects', 'label' => 'Assuntos', 'type' => 'subjects', 'max_items' => 2],
        ]];
        $validator->validateDefinition($schema);
        $validator->validateResponses($schema, ['choice' => 'B', 'count' => 2, 'date' => '2024-02-29', 'tags' => ['X', 'Y'], 'subjects' => [['subject' => 'Primeiro']]]);

        $invalid = [
            ['choice' => 'A'],
            ['choice' => 'B', 'details' => 'oculto'],
            ['choice' => 'B', 'count' => '2'],
            ['choice' => 'B', 'date' => '2024-02-30'],
            ['choice' => 'B', 'tags' => ['X', 'X']],
            ['choice' => 'B', 'subjects' => [['subject' => 'Um'], ['subject' => 'Dois'], ['subject' => 'Três']]],
            ['choice' => 'B', 'user_id' => 'injetado'],
            ['choice' => 'A', 'details' => '   '],
        ];
        foreach ($invalid as $answers) {
            try {
                $validator->validateResponses($schema, $answers);
                $this->fail('Resposta inválida foi aceita.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }
}
