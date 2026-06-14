<?php
/**
 * Input Validator
 */

namespace Exqpay\Core\Validation;

class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    private array $messages = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Validate
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
        return empty($this->errors);
    }

    /**
     * Validate single field
     */
    private function validateField(string $field, $rules): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $rule) {
            $this->applyRule($field, $rule);
        }
    }

    /**
     * Apply single rule
     */
    private function applyRule(string $field, string $rule): void
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $value = $this->data[$field] ?? null;

        match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, $params[0] ?? null),
            'max' => $this->validateMax($field, $value, $params[0] ?? null),
            'minlength' => $this->validateMinLength($field, $value, $params[0] ?? null),
            'maxlength' => $this->validateMaxLength($field, $value, $params[0] ?? null),
            'numeric' => $this->validateNumeric($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'regex' => $this->validateRegex($field, $value, $params[0] ?? null),
            'in' => $this->validateIn($field, $value, $params),
            'unique' => $this->validateUnique($field, $value, $params),
            default => null,
        };
    }

    /**
     * Validate required
     */
    private function validateRequired(string $field, $value): void
    {
        if (empty($value)) {
            $this->addError($field, $this->getMessage($field, 'required', 'This field is required'));
        }
    }

    /**
     * Validate email
     */
    private function validateEmail(string $field, $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $this->getMessage($field, 'email', 'Invalid email format'));
        }
    }

    /**
     * Validate minimum
     */
    private function validateMin(string $field, $value, $min): void
    {
        if ($value !== null && $value < $min) {
            $this->addError($field, $this->getMessage($field, 'min', "Value must be at least {$min}"));
        }
    }

    /**
     * Validate maximum
     */
    private function validateMax(string $field, $value, $max): void
    {
        if ($value !== null && $value > $max) {
            $this->addError($field, $this->getMessage($field, 'max', "Value must not exceed {$max}"));
        }
    }

    /**
     * Validate minimum length
     */
    private function validateMinLength(string $field, $value, $length): void
    {
        if ($value !== null && strlen((string)$value) < (int)$length) {
            $this->addError($field, $this->getMessage($field, 'minlength', "Must be at least {$length} characters"));
        }
    }

    /**
     * Validate maximum length
     */
    private function validateMaxLength(string $field, $value, $length): void
    {
        if ($value !== null && strlen((string)$value) > (int)$length) {
            $this->addError($field, $this->getMessage($field, 'maxlength', "Must not exceed {$length} characters"));
        }
    }

    /**
     * Validate numeric
     */
    private function validateNumeric(string $field, $value): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, $this->getMessage($field, 'numeric', 'Must be numeric'));
        }
    }

    /**
     * Validate integer
     */
    private function validateInteger(string $field, $value): void
    {
        if ($value !== null && !is_int($value) && !ctype_digit((string)$value)) {
            $this->addError($field, $this->getMessage($field, 'integer', 'Must be an integer'));
        }
    }

    /**
     * Validate regex
     */
    private function validateRegex(string $field, $value, $pattern): void
    {
        if ($value !== null && !preg_match($pattern, (string)$value)) {
            $this->addError($field, $this->getMessage($field, 'regex', 'Invalid format'));
        }
    }

    /**
     * Validate in
     */
    private function validateIn(string $field, $value, array $options): void
    {
        if ($value !== null && !in_array($value, $options)) {
            $this->addError($field, $this->getMessage($field, 'in', 'Invalid value'));
        }
    }

    /**
     * Validate unique in database
     */
    private function validateUnique(string $field, $value, array $params): void
    {
        if (!$value || empty($params)) {
            return;
        }

        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;

        if (!$table) {
            return;
        }

        $pdo = \Exqpay\Core\Database::connection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);

        if ($stmt->fetchColumn() > 0) {
            $this->addError($field, $this->getMessage($field, 'unique', 'Already exists'));
        }
    }

    /**
     * Add error
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get message
     */
    private function getMessage(string $field, string $rule, string $default): string
    {
        return $this->messages["{$field}.{$rule}"] ?? $default;
    }

    /**
     * Get errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
