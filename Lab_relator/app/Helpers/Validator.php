<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use InvalidArgumentException;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $labels
     * @return array<string, string>
     */
    public static function validar(array $data, array $rules, array $labels = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $valueString = is_scalar($value) ? trim((string)$value) : '';
            $fieldRules = array_filter(explode('|', $ruleString));
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $isRequired = in_array('required', $fieldRules, true);

            if (!$isRequired && $valueString === '') {
                continue;
            }

            foreach ($fieldRules as $rule) {
                [$ruleName, $argument] = array_pad(explode(':', $rule, 2), 2, null);

                if ($ruleName === 'required' && $valueString === '') {
                    $errors[$field] = "{$label} e obrigatorio.";
                    break;
                }

                if ($valueString === '') {
                    continue;
                }

                if ($ruleName === 'min' && self::length($valueString) < (int)$argument) {
                    $errors[$field] = "{$label} deve ter pelo menos {$argument} caracteres.";
                    break;
                }

                if ($ruleName === 'max' && self::length($valueString) > (int)$argument) {
                    $errors[$field] = "{$label} deve ter no maximo {$argument} caracteres.";
                    break;
                }

                if ($ruleName === 'email' && !filter_var($valueString, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "{$label} deve ser um e-mail valido.";
                    break;
                }

                if (($ruleName === 'numeric' || $ruleName === 'integer') && !self::isPositiveInteger($valueString)) {
                    $errors[$field] = "{$label} deve ser um numero inteiro positivo.";
                    break;
                }

                if ($ruleName === 'in' && $argument !== null) {
                    $allowed = array_map('trim', explode(',', $argument));
                    if (!in_array($valueString, $allowed, true)) {
                        $errors[$field] = "{$label} possui um valor invalido.";
                        break;
                    }
                }

                if ($ruleName === 'unique' && $argument !== null && !self::isUnique($valueString, $argument)) {
                    $errors[$field] = "{$label} ja esta em uso.";
                    break;
                }

                if ($ruleName === 'exists' && $argument !== null && !self::exists($valueString, $argument, false)) {
                    $errors[$field] = "{$label} informado nao foi encontrado.";
                    break;
                }

                if ($ruleName === 'exists_active' && $argument !== null && !self::exists($valueString, $argument, true)) {
                    $errors[$field] = "{$label} informado nao esta ativo.";
                    break;
                }
            }
        }

        return $errors;
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function isPositiveInteger(string $value): bool
    {
        return ctype_digit($value) && (int)$value > 0;
    }

    private static function isUnique(string $value, string $argument): bool
    {
        [$table, $column, $exceptId] = array_pad(array_map('trim', explode(',', $argument)), 3, null);
        self::assertIdentifier($table);
        self::assertIdentifier($column);

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        if ($exceptId !== null && $exceptId !== '' && ctype_digit((string)$exceptId)) {
            $sql .= ' AND id <> :id';
            $params['id'] = (int)$exceptId;
        }

        return (int)self::fetchColumn($sql, $params) === 0;
    }

    private static function exists(string $value, string $argument, bool $activeOnly): bool
    {
        [$table, $column] = array_pad(array_map('trim', explode(',', $argument)), 2, null);
        self::assertIdentifier($table);
        self::assertIdentifier($column);

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
        if ($activeOnly) {
            $sql .= ' AND ativo = 1';
        }

        return (int)self::fetchColumn($sql, ['value' => $value]) > 0;
    }

    /** @param array<string, mixed> $params */
    private static function fetchColumn(string $sql, array $params): mixed
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    private static function assertIdentifier(?string $value): void
    {
        if (!is_string($value) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
            throw new InvalidArgumentException('Identificador SQL invalido.');
        }
    }
}
