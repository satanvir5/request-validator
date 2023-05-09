<?php

namespace Satanvir\Validator;

use DateTime;

class Validator
{
    protected array $inputs = [];
    protected array $rules = [];
    protected array $errors = [];
    protected array $messages = [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'min' => 'The :attribute must be at least :min characters.',
        'max' => 'The :attribute may not be greater than :max characters.',
        'numeric' => 'The :attribute must be a numeric value.',
        'string' => 'The :attribute must be a string.',
        'array' => 'The :attribute must be an array.',
        'in' => 'The selected :attribute is invalid.',
        'date' => 'The :attribute must be a valid date.',
        'date_format' => 'The :attribute does not match the format :date_format.',
        'url' => 'The :attribute must be a valid URL.',
        'regex' => 'The :attribute format is invalid.',
        'unique' => 'The :attribute has already been taken.',
    ];

    public function __construct(array $config = [])
    {


    }

    public function request(array $inputs): self
    {
        $this->inputs = $inputs;
        return $this;
    }




    public function rules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    public function rule(string $name, array|string $rule): self
    {
        $this->rules[$name] = $rule;
        return $this;
    }


    public function validate(): self
    {


        foreach ($this->rules as $name => $rules) {
            $value = $this->inputs[$name] ?? null;

            if (!is_array($rules)) {
                $rules = explode('|', $rules);
                if (!is_array($rules)) {
                    $rules = '['.$rules.']';
                }
            }


            foreach ($rules as $rule) {

                [$rule, $parameters] = $this->parseRule($rule);
                $valid = $this->validateRule($rule, $value, $parameters);


                if(  $valid === 2){
                    $this->errors[$name][] = "This validation rules '$rule' is not exist";
                    break;
                }

                if(!$valid) {
                    $this->addError($name, $rule, $parameters);
                    break;
                }
            }

        }




        return $this;
    }



    protected function addError(string $name, string $rule, array $parameters): void
    {


        if(array_key_exists($rule,$this->messages)){
            $message = $this->messages[$rule];
            $message = str_replace(':attribute', $name, $message);
        }else{
            $message = sprintf('The field "%s" did not pass the validation rule "%s".', $name, $rule);
        }
        if (!empty($parameters)) {

            $message = str_replace(':'.$rule, $parameters[0], $message);
            $message = str_replace(':attribute', $name, $message);
            // $message .= ' Parameters: ' . implode(', ', $parameters);
        }
        $this->errors[$name][] = $message;
    }


    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passed(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $name): array
    {
        return $this->errors[$name] ?? [];
    }

    public function errorFirst(?string $name = null): ?string
    {
        if ($name === null) {
            $errors = array_values($this->errors);
            return empty($errors) ? null : $errors[0][0];
        } else {
            return $this->errors[$name][0] ?? null;
        }
    }

    protected function parseRule(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            [$rule, $parameters] = explode(':', $rule, 2);
            $parameters = explode(',', $parameters);
        } else {
            $parameters = [];
        }
        return [$rule, $parameters];
    }

    protected function validateRule(string $rule, $value, array $parameters): bool
    {

        switch ($rule) {
            case 'required':
                return $value !== null && $value !== '';
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'min':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least one parameter.";
                    return false;
                }
                return strlen($value) >= (int) $parameters[0];
            case 'max':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least one parameter.";
                    return false;
                }
                return strlen($value) <= (int) $parameters[0];
            case 'numeric':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'in':
                return in_array($value, $parameters);
            case 'date':
                return strtotime($value) !== false;
            case 'date_format':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least one parameter.";
                    return false;
                }
                $date = DateTime::createFromFormat($parameters[0], $value);
                return $date !== false && $date->format($parameters[0]) === $value;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'regex':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least one parameter.";
                    return false;
                }
                return preg_match($parameters[0], $value) === 1;
            case 'unique':
                        if(count($parameters)<2){
                            $this->errors[$rule][] = "You must have to set table and column name with the unique rules";
                            return false;
                        }

                    $validator = function($value, $parameters, $fields) {
                        list($table, $column) = $parameters;
                        if(array_key_exists(2,$parameters)){
                            $except = $parameters[2];
                        }else{
                            $except = null;
                        }


                            if(array_key_exists(3,$parameters)){
                                $idColumn = $parameters[3];
                            }else{
                                $idColumn = null;
                            }
                            $query = "SELECT COUNT(*) as count FROM $table WHERE $column = :value";

                            $bindings = [':value' => $value];
                            if (!empty($except)) {
                                $query .= " AND $except <> :except";
                                $bindings[':except'] = $fields[$except];
                            }
                            if (!empty($idColumn) && !empty($fields[$idColumn])) {
                                $query .= " AND $idColumn <> :id";
                                $bindings[':id'] = $fields[$idColumn];
                            }
                            $result = \DB::select($query, $bindings);

                        return count($result) > 0 && $result[0]->count == '0';
                    };
                    return $validator($value, $parameters, $this->inputs);
            case 'alpha':
                return ctype_alpha($value);
            case 'alpha_num':
                return ctype_alnum($value);
            case 'alpha_dash':
                return preg_match('/^[A-Za-z0-9-_]+$/', $value) === 1;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
            case 'confirmed':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least one parameter.";
                    return false;
                }
                return isset($this->inputs[$parameters[0]]) && $value === $this->inputs[$parameters[0]];
            case 'date_equals':
                if (count($parameters) < 2) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                $date = DateTime::createFromFormat($parameters[0], $value);
                return $date !== false && $date->format($parameters[0]) === $parameters[1];
            case 'date_before':
                if (count($parameters) < 2) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                $date = DateTime::createFromFormat($parameters[0], $value);
                $limit = DateTime::createFromFormat($parameters[0], $parameters[1]);
                return $date !== false && $limit !== false && $date < $limit;
            case 'date_after':
                if (count($parameters) < 2) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                $date = DateTime::createFromFormat($parameters[0], $value);
                $limit = DateTime::createFromFormat($parameters[0], $parameters[1]);
                return $date !== false && $limit !== false && $date > $limit;
            case 'digits':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                return ctype_digit($value) && strlen($value) === (int) $parameters[0];
            case 'digits_between':
                $length = strlen($value);
                if (count($parameters) < 2) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                return ctype_digit($value) && $length >= (int) $parameters[0] && $length <= (int) $parameters[1];
            case 'file':
                return is_uploaded_file($value) && file_exists($value);
            case 'image':
                return exif_imagetype($value) !== false;
            case 'mimetypes':
                return in_array(mime_content_type($value), $parameters);
            case 'min_value':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                return is_numeric($value) && $value >= (float) $parameters[0];
            case 'max_value':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                return is_numeric($value) && $value <= (float) $parameters[0];
            case 'size':
                if (count($parameters) < 1) {
                    $this->errors[$rule][] = "The $rule validation rule requires at least two parameter.";
                    return false;
                }
                return filesize($value) <= (int) $parameters[0];
            case 'timezone':
                return in_array($value, timezone_identifiers_list());
            default:

                return 2;
        }
    }
}


