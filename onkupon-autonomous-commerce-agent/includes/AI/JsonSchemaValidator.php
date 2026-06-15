<?php
namespace OnKupon\Agent\AI;

class JsonSchemaValidator {
    public function validate( array $data, array $schema ): array {
        $errors = [];
        foreach ( $schema['required'] ?? [] as $field ) {
            if ( ! array_key_exists( $field, $data ) ) {
                $errors[] = 'Missing required field: ' . $field;
            }
        }
        foreach ( $schema['properties'] ?? [] as $field => $rules ) {
            if ( ! array_key_exists( $field, $data ) ) {
                continue;
            }
            $type = $rules['type'] ?? null;
            if ( 'array' === $type && ! is_array( $data[ $field ] ) ) {
                $errors[] = $field . ' must be an array';
            }
            if ( 'number' === $type && ! is_numeric( $data[ $field ] ) ) {
                $errors[] = $field . ' must be numeric';
            }
            if ( 'string' === $type && ! is_string( $data[ $field ] ) ) {
                $errors[] = $field . ' must be a string';
            }
        }
        return [ 'valid' => empty( $errors ), 'errors' => $errors ];
    }
}
