<?php

namespace PrettyFormsLaravel\Validation;

class ValidatorException extends \RuntimeException {

    private $validator_errors;

    function setValidationErrors($errors) {
        $this->validator_errors = $errors;
        return $this;
    }

    function getValidationErrors() {
        return $this->validator_errors;
    }

}
