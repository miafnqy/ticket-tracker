<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];
    private array $rules;

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $rulesList) {
            if (is_string($rulesList)) {
                $rulesList = explode('|', $rulesList);
            }

            foreach ($rulesList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule);
                    $params = explode(',', $paramStr);
                    $rule = $ruleName;
                }

                $valueExists = array_key_exists($field, $this->data);

                if (!$valueExists && $rule !== 'required') {
                    continue;
                }

                $methodName = 'validate' . ucfirst($rule);

                if (method_exists($this, $methodName)) {
                    if (!$this->$methodName($field, $params)) {
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function validateRequired(string $field): bool
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || trim((string)$value) === '') {
            $this->addError($field, "The $field field is required.");
            return false;
        }

        return true;
    }

    private function validateMin(string $field, array $params): bool
    {
        $length = (int)$params[0];
        if (mb_strlen($this->data[$field]) < $length) {
            $this->addError($field, "The $field must be at least $length characters.");
            return false;
        }

        return true;
    }

    private function validateMax(string $field, array $params): bool
    {
        $length = (int)$params[0];
        if (mb_strlen($this->data[$field]) > $length) {
            $this->addError($field, "The $field must not exceed $length characters.");
            return false;
        }

        return true;
    }

    private function validateEmail(string $field): bool
    {
        if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The $field must be a valid email address.");
            return false;
        }

        return true;
    }

    private function validateString(string $field): bool
    {
        if (!is_string($this->data[$field])) {
            $this->addError($field, "The $field must be a string.");
            return false;
        }

        return true;
    }

    private function validateInt(string $field): bool
    {
        if (filter_var($this->data[$field], FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The $field must be an integer.");
            return false;
        }

        return true;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}