<?php
namespace App\Core;

abstract class Controller
{
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    protected function view(string $template, array $data = []): void
    {
        echo View::render($template, $data);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function request(): array
    {
        $rawInput = file_get_contents('php://input');
        $input = [];

        if (is_string($rawInput) && $rawInput !== '') {
            $decoded = json_decode($rawInput, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        return array_merge($_GET, $_POST, $input);
    }

    protected function validate(array $rules): array
    {
        // Simple validation helper
        $errors = [];
        $data = $this->request();
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            // Required
            $ruleParts = explode('|', $rule);
            $isEmpty = $value === null || $value === '';
            if (in_array('required', $ruleParts, true) && $isEmpty) {
                $errors[$field] = "$field is required.";
                continue;
            }
            // Email
            if (!$isEmpty && in_array('email', $ruleParts, true) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "$field must be a valid email.";
            }
            // etc.
        }
        if (!empty($errors)) {
            $this->json(['errors' => $errors], 422);
        }
        return $data;
    }
}