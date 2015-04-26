<?php

namespace PrettyFormsLaravel\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Данный трейт следует подключать к тем моделям, которые будут использоваться
 * для управления через механизмы PrettyForms. Он добавляет к модели несколько новых методов, которые
 * будут использоваться для проверки валидации данных.
 */

trait ValidatorTrait {

    private $errors;

    public function validate() {
        $data = $this->getAttributes();

        if (method_exists($this, 'create_rules') AND method_exists($this, 'update_rules')) {
            $rules = $this->exists ? $this->update_rules($data) : $this->create_rules($data);
        } elseif (method_exists($this, 'validation_rules') OR property_exists($this, 'validation_rules')) {
            $rules = method_exists($this, 'validation_rules') ? $this->validation_rules($data) : $this->validation_rules;
        } else {
            throw new \Exception('Вы должны указать правила валидации для вашей модели.');
        }

        $messages = array();
        if (method_exists($this, 'validation_messages')) {
            $messages = $this->validation_messages();
        }

        // make a new validator object
        $validator = \Validator::make($data, $rules, $messages);

        // check for failure
        if ($validator->fails()) {
            // set errors and return false
            $this->errors = $validator->errors();
            return false;
        }

        // validation pass
        return true;
    }

    public function errors() {
        return $this->errors;
    }

    /**
     * Validate model data and save it, otherwise cancels execution and returns error array to client
     * @param array $options
     * @return type
     */
    public function validateAndSave(array $options = array()) {
        if ($this->validate()) {
            return $this->save($options);
        } else {
            if (DB::transactionLevel() == 1) {
                // Rollback started transaction
                DB::rollback();
            }
            
            pf_validation_error($this->errors->getMessages());
        }
    }

}
