<?php
/**
 * Input Validator
 *
 * Validate and sanitize input data
 */

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create new validator instance
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate required field
     */
    public function required(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
            $this->errors[$field] = $message ?? "{$field} is required";
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "Invalid email address";
            }
        }
        return $this;
    }

    /**
     * Validate South African phone number
     */
    public function phone(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $phone = preg_replace('/[^0-9+]/', '', $this->data[$field]);

            // Allow +27, 27, or 0 prefix followed by 9 digits
            $valid = preg_match('/^(\+27|27|0)[1-9][0-9]{8}$/', $phone);

            if (!$valid) {
                $this->errors[$field] = $message ?? "Invalid phone number format";
            }
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "{$field} must be at least {$min} characters";
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "{$field} must not exceed {$max} characters";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field] = $message ?? "{$field} must be a number";
            }
        }
        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
                $this->errors[$field] = $message ?? "{$field} must be an integer";
            }
        }
        return $this;
    }

    /**
     * Validate positive number
     */
    public function positive(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!is_numeric($this->data[$field]) || $this->data[$field] <= 0) {
                $this->errors[$field] = $message ?? "{$field} must be a positive number";
            }
        }
        return $this;
    }

    /**
     * Validate minimum value
     */
    public function min(string $field, $min, ?string $message = null): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ($this->data[$field] < $min) {
                $this->errors[$field] = $message ?? "{$field} must be at least {$min}";
            }
        }
        return $this;
    }

    /**
     * Validate maximum value
     */
    public function max(string $field, $max, ?string $message = null): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ($this->data[$field] > $max) {
                $this->errors[$field] = $message ?? "{$field} must not exceed {$max}";
            }
        }
        return $this;
    }

    /**
     * Validate value is in array
     */
    public function in(string $field, array $values, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!in_array($this->data[$field], $values, true)) {
                $this->errors[$field] = $message ?? "{$field} must be one of: " . implode(', ', $values);
            }
        }
        return $this;
    }

    /**
     * Validate array field
     */
    public function isArray(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_array($this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field} must be an array";
        }
        return $this;
    }

    /**
     * Validate non-empty array
     */
    public function notEmptyArray(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || !is_array($this->data[$field]) || count($this->data[$field]) === 0) {
            $this->errors[$field] = $message ?? "{$field} must be a non-empty array";
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "{$field} must be a valid date ({$format})";
            }
        }
        return $this;
    }

    /**
     * Validate datetime format
     */
    public function datetime(string $field, string $format = 'Y-m-d H:i:s', ?string $message = null): self
    {
        return $this->date($field, $format, $message);
    }

    /**
     * Validate regex pattern
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field] = $message ?? "{$field} format is invalid";
            }
        }
        return $this;
    }

    /**
     * Validate URL format
     */
    public function url(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field] = $message ?? "Invalid URL format";
            }
        }
        return $this;
    }

    /**
     * Validate boolean value
     */
    public function boolean(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field])) {
            $valid = is_bool($this->data[$field]) ||
                     $this->data[$field] === 1 ||
                     $this->data[$field] === 0 ||
                     $this->data[$field] === '1' ||
                     $this->data[$field] === '0';
            if (!$valid) {
                $this->errors[$field] = $message ?? "{$field} must be a boolean";
            }
        }
        return $this;
    }

    /**
     * Custom validation
     */
    public function custom(string $field, callable $callback, ?string $message = null): self
    {
        if (isset($this->data[$field])) {
            if (!$callback($this->data[$field], $this->data)) {
                $this->errors[$field] = $message ?? "{$field} is invalid";
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Validate and throw response if failed
     */
    public function validate(): array
    {
        if ($this->fails()) {
            Response::validationError($this->errors);
        }
        return $this->data;
    }

    /**
     * Get validated data
     */
    public function validated(): array
    {
        return $this->data;
    }

    /**
     * Normalize South African phone number to +27 format
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '+27' . substr($phone, 1);
        } elseif (str_starts_with($phone, '27') && !str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+27' . $phone;
        }

        return $phone;
    }

    /**
     * Sanitize string for safe storage
     */
    public static function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
