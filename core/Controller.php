<?php
/**
 * Base Controller class
 */
class Controller {
    protected $db;
    
    public function __construct($database = null) {
        $this->db = $database;
    }
    
    /**
     * Load a view
     */
    protected function view($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
    }
    
    /**
     * Load a model
     */
    protected function model($model) {
        require_once __DIR__ . "/../models/{$model}.php";
        return new $model($this->db);
    }
    
    /**
     * Redirect to a URL
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit();
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Validate input data
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $parameter) {
                switch ($rule) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "{$field} is required";
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "{$field} must be a valid email";
                        }
                        break;
                    case 'min':
                        if (!empty($value) && strlen($value) < $parameter) {
                            $errors[$field][] = "{$field} must be at least {$parameter} characters";
                        }
                        break;
                    case 'max':
                        if (!empty($value) && strlen($value) > $parameter) {
                            $errors[$field][] = "{$field} must not exceed {$parameter} characters";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
}
?>