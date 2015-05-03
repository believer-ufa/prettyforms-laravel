<?php

namespace PrettyFormsLaravel;

use DB;

/**
 * Дополнительные методы для Eloquent
 */
trait FormSavesLogic {

    /**
     * Добавляет/изменяет строку в базе
     * @param array $fields_names Список названий колонок, с которыми необходимо работать
     * @param array $values Ассоциативный массив значений, которые надо будет сохранить
     * @param array $validation_rules Правила валидации модели
     * @return bool
     */
    public function saveFormItem($fields_names, $values, $validation_rules = null)
    {
        $columns = $this->getColumnsInfo();

        // Получим массив всех зависимостей многие-многие, чтобы потом добавлять в них элементы
        $has_many_throughs = $this->getThroughs($fields_names);

        $throughs_array = array();

        // Обработаем все основные колонки
        foreach ($values as $field => $value) {
            if (in_array($field, $has_many_throughs)) {
                if (!empty($value) AND $value !== 'null') {
                    $throughs_array[$field] = (array)$value;
                }
            } else {
                if ($columns[$field]['nullable']) {
                    if (empty($value)) {
                        $this->$field = null;
                    } else {
                        $this->$field = $value;
                    }
                } else {

                    // Преобразуем значение в нужный тип данных
                    switch ($columns[$field]['type']) {
                        case 'integer':
                            $value = (int) $value;
                        break;
                    }

                    $this->$field = $value;
                }
            }
        }

        // Обработаем все булевые колонки
        foreach ($columns as $column_name => $column_info) {
            $is_boolean = ($column_info['type'] === 'boolean' OR $column_info['type'] === 'tinyint(1)');
            if ($is_boolean AND in_array($column_name, $fields_names)) {
                if (isset($values[$column_name]) AND $values[$column_name] === 'null') {
                    $this->$column_name = null;
                } elseif (isset($values[$column_name])) {
                    $this->$column_name = true;
                } else {
                    $this->$column_name = false;
                }
            }
        }

        // Если есть связи "многие-ко-многим", запускаем транзакцию
        if (!empty($has_many_throughs)) {
            DB::beginTransaction();
        }
        
        $uses = class_uses($this);
        if (!array_key_exists('PrettyFormsLaravel\Validation\ValidatorTrait', $uses)) {
            throw new \Exception('К вашей модели не подключён трейт валидации "PrettyFormsLaravel\Validation\ValidatorTrait". Пожалуйста, добавьте его в список подключенных трейтов.');
        }

        $this->validateAndSave([], $validation_rules);

        // Заполним связи новыми данными
        foreach ($has_many_throughs as $relation_name) {
            $this->$relation_name()->sync(
                array_get($throughs_array,$relation_name,[])
            );
        }

        if (!empty($has_many_throughs)) {
            DB::commit();
        }

        return $this;
    }

    /**
     * Получить массив всех зависимостей многие-многие у модели
     */
    private function getThroughs($fields)
    {
        if (property_exists($this, 'throughs_relations')) {
            $results_array = [];
            foreach($this->throughs_relations as $throughs_relation) {
                if (in_array($throughs_relation,$fields)) {
                    $results_array[] = $throughs_relation;
                }
            }
            return $results_array;
        } else {
            return [];
        }
    }

    /**
     * Возвращает информацию о колонках текущей таблицы и их типах
     * @return array
     */
    protected function getColumnsInfo() {
        switch (DB::connection()->getConfig('driver')) {
            case 'pgsql':
                $query = "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = '".$this->getTable()."'";
                $column_name     = 'column_name';
                $column_type     = 'data_type';
                $column_nullable = 'is_nullable';
                $reverse = true;
            break;

            case 'mysql':
                $query = 'SHOW COLUMNS FROM '.$this->getTable();
                $column_name = 'Field';
                $column_type = 'Type';
                $column_nullable = 'Null';
                $reverse = false;
            break;
        
        
// Код к другим БД не дописан, если будет использоваться, необходимо доработать
//
//            case 'sqlsrv':
//                $parts = explode('.', $this->getTable());
//                $num = (count($parts) - 1);
//                $table = $parts[$num];
//                $query = "SELECT column_name FROM ".DB::connection()->getConfig('database').".INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'".$table."'";
//                $column_name = 'column_name';
//                $reverse = false;
//            break;

            default:
                $error = 'Database driver not supported: '.DB::connection()->getConfig('driver');
                throw new Exception($error);
        }

        $columns = array();

        foreach(DB::select($query) as $column)
        {
            $columns[$column->$column_name] = [
                'type'     => $column->$column_type,
                'nullable' => ($column->$column_nullable === 'YES')
            ];
        }

        if($reverse)
        {
            $columns = array_reverse($columns);
        }

        return $columns;
    }

    /**
     * Проверить, связана ли модель по указанной связи с указанным номером объекта
     * @param название связи $relation_name
     * @param ключ $id
     */
    function have($relation_name, $id) {
        return (bool) $this->$relation_name()->where('id','=',$id)->count();
    }

}