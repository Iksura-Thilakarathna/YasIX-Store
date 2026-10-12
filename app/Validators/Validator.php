<?php

namespace App\Validators;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules, array $labels = []): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = is_string($data[$field] ?? null) ? trim($data[$field]) : ($data[$field] ?? null);
            $fieldLabel = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && (empty($value) && $value !== '0')) {
                    $this->addError($field, "The {$fieldLabel} field is required.");
                } elseif ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$fieldLabel} must be a valid email address.");
                } elseif ($rule === 'phone' && !empty($value)) {
                    // Reasonable Sri Lankan phone number format check (+947XXXXXXX, 07XXXXXXX, or standard 9-12 digit numbers)
                    if (!preg_match('/^(\+94|0)?[0-9]{9,10}$/', str_replace([' ', '-'], '', $value))) {
                        $this->addError($field, "The {$fieldLabel} must be a valid phone number format (e.g. +94771234567 or 0771234567).");
                    }
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $this->addError($field, "The {$fieldLabel} must be at least {$min} characters.");
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $this->addError($field, "The {$fieldLabel} must not exceed {$max} characters.");
                    }
                } elseif (str_starts_with($rule, 'match:')) {
                    $targetField = substr($rule, 6);
                    $targetValue = $data[$targetField] ?? null;
                    if ($value !== $targetValue) {
                        $targetLabel = $labels[$targetField] ?? ucfirst(str_replace('_', ' ', $targetField));
                        $this->addError($field, "The {$fieldLabel} does not match {$targetLabel}.");
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
