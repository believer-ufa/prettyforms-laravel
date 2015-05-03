<?php

namespace PrettyFormsLaravel;

use Eloquent;
use Exception;
use Input;
use Request;
use Illuminate\Http\Request as LaravelRequest;
use Session;
use View;
use DB;

trait FormProcessLogic {

	/**
	 * Возвращает тексты, которые будут использоваться в генерации форм и сообщений для объекта
	 * метод не обязательно создавать, по умолчанию класс будет использовать стандартные общие сообщения и заголовки
	*/
    protected function getStrings($model)
    {
        return [
            'add' => [
				//'caption' => 'Текст заголовка страницы создания объекта',
				//'success' => 'Текст сообщения об успешном создании объекта',
			],
            'edit' => [
				//'caption' => 'Текст заголовка страницы редактирования объекта'
				//'success' => 'Текст сообщения об успешном редактировании объекта',
			],
        ];
    }

    /**
     * Добавляет/изменяет запись в таблице. При желании, можно указать дополнительные значения в массиве $values, за основу же берутся данные из $_POST
     * @param int $id Номер записи в таблице
     * @param array $values Массив значений
     * @param function $success_save_callback Функция, внутри которой с моделью можно проделать какие-то действия после её сохранения
     * @return boolean
     */
    protected function save(LaravelRequest $request, $id = null, $values = null, $success_save_callback = null)
    {
        $model_name = $this->_model_name;
        $model = $model_name::findOrNew($id); /* @var $model Eloquent */
        $mode = $model->exists ? 'edit' : 'add';

        $values = array_merge(Input::all(), is_null($values) ? [] : $values);
        $fields_names = array_keys($this->getFields($mode));

        // Получаем чистый массив значений, в котором содержатся только те значения,
        // которые перечислены среди полей редактирования модели
        $clean_values = array_only($values, $fields_names);
        
        $this->saveModelItem($request, $model, $fields_names, $clean_values);

        // Если был передан коллбек, вызовем его
        if ($success_save_callback) {
            $success_save_callback($model);
        }

        $controller_strings = $this->getStrings($model);
        
        Session::flash('message','success|' . array_get($controller_strings[$mode],'success',
            ($mode === 'add')
                ? 'Объект успешно создан'
                : 'Объект успешно отредактирован')
        );

        // Если всё прошло хорошо, генерируем ответ с командой редиректа на главную страницу текущего контроллера
        return [
            'redirect' => $this->getHomeLink($model),
        ];
    }

    /**
     * Добавляет/изменяет строку в базе
     * @param array $fields_names Список названий колонок, с которыми необходимо работать
     * @param array $values Ассоциативный массив значений, которые надо будет сохранить
     * @return bool
     */
    private function saveModelItem(LaravelRequest $request, $model, $fields_names, $values)
    {
        $columns = $this->getColumnsInfo($model);

        // Получим массив всех зависимостей многие-многие, чтобы потом добавлять в них элементы
        $has_many_throughs = $this->getModelThroughs($model, $fields_names, $columns);

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
                        $model->$field = null;
                    } else {
                        $model->$field = $value;
                    }
                } else {

                    // Преобразуем значение в нужный тип данных
                    switch ($columns[$field]['type']) {
                        case 'integer':
                            $value = (int) $value;
                        break;
                    }

                    $model->$field = $value;
                }
            }
        }

        // Обработаем все булевые колонки
        foreach ($columns as $column_name => $column_info) {
            $is_boolean = ($column_info['type'] === 'boolean' OR $column_info['type'] === 'tinyint(1)');
            if ($is_boolean AND in_array($column_name, $fields_names)) {
                if (isset($values[$column_name]) AND $values[$column_name] === 'null') {
                    $model->$column_name = null;
                } elseif (isset($values[$column_name])) {
                    $model->$column_name = true;
                } else {
                    $model->$column_name = false;
                }
            }
        }

        // Если есть связи "многие-ко-многим", запускаем транзакцию
        if (!empty($has_many_throughs)) {
            DB::beginTransaction();
        }
        
        $validation_rules = null;
        if (method_exists($this, 'getValidationRules')) {
            $validation_rules = $this->getValidationRules($model);
        }
        
        // Если были указаны правила валидации, проверим входящие данные
        if ($validation_rules) {
            $this->validate($request, $validation_rules);
        }
        
        $model->save();

        // Заполним связи новыми данными
        foreach ($has_many_throughs as $relation_name) {
            $model->$relation_name()->sync(
                array_get($throughs_array,$relation_name,[])
            );
        }

        if (!empty($has_many_throughs)) {
            DB::commit();
        }

        return $model;
    }

    /**
     * Получить массив всех связей у модели
     */
    private function getModelThroughs($model, $fields, $columns)
    {
        $results_array = [];
        foreach($fields as $field) {
            if (! isset($columns[$field])) {
                $results_array[] = $field;
            }
        }
        return $results_array;
    }

    /**
     * Возвращает информацию о колонках текущей таблицы и их типах
     * @return array
     */
    protected function getColumnsInfo($model) {
        
        $columns = array();
        
        switch (DB::connection()->getConfig('driver')) {
            case 'pgsql':
                $query = "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = '".$model->getTable()."'";
                foreach(DB::select($query) as $column)
                {
                    $columns[$column->column_name] = [
                        'type'     => strtolower($column->data_type),
                        'nullable' => ($column->is_nullable === 'YES')
                    ];
                }
                
                $columns = array_reverse($columns);
                
            break;

            case 'mysql':
                $query = 'SHOW COLUMNS FROM '.$model->getTable();
                foreach(DB::select($query) as $column)
                {
                    $columns[$column->Field] = [
                        'type'     => strtolower($column->Type),
                        'nullable' => ($column->Null === 'YES')
                    ];
                }
            break;
        
            case 'sqlite':
                $query = 'PRAGMA table_info('.$model->getTable().')';
                foreach(DB::select($query) as $column)
                {
                    $columns[$column->name] = [
                        'type'     => strtolower($column->type),
                        'nullable' => ($column->notnull == '0')
                    ];
                }
            break;
        
            default:
                $error = 'Database driver not supported: '.DB::connection()->getConfig('driver');
                throw new Exception($error);
        }

        return $columns;
    }    
    
    /**
     * Удаление записи из таблицы
     * @param type $id
     * @return boolean
     */
    protected function delete($id)
    {
        $model_name = $this->_model_name;
        $model = $model_name::findOrNew($id); /* @var $model Eloquent */
        if (! $model->exists) {
            return $this->generateNotFoundErrorCommand();
        }

        $model->delete();

        $home_link = $this->getHomeLink($model);

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes',class_uses($model))) {
            Session::flash('message','success|Объект мягко удалён из системы, и его еще возможно легко восстановить.');
        } else {
            Session::flash('message','success|Объект полностью удалён из системы.');
        }

        return [
            'redirect' => $home_link
        ];
    }

    protected function restore($id)
    {
        $model_name = $this->_model_name;
        $model = $model_name::onlyTrashed()->find($id); /* @var $model Eloquent */
        if (is_null($model)) {
            return $this->generateNotFoundErrorCommand();
        }

        $model->restore();

		Session::flash('message','success|Объект восстановлен');

        return [
            'refresh' => ''
        ];
    }

    protected function forceDelete($id)
    {
        $model_name = $this->_model_name;
        $model = $model_name::onlyTrashed()->find($id); /* @var $model Eloquent */
        if (is_null($model)) {
            return $this->generateNotFoundErrorCommand();
        }

        $model->forceDelete();

		Session::flash('message','success|Объект полностью удалён из системы');

        return [
            'refresh' => ''
        ];
    }


    /**
     * Генерирует форму для создания/редактирования записи
     * @param int $id
     * @param array $values Ассоциативный массив со значениями полей, которые будут изначально подставлены в форму
     * @return string
     */
    protected function generateForm($id = null, $values = null) {
        
        if (! property_exists($this, '_model_name')) {
            throw new Exception("Пожалуйста, укажите название модели, с которой работает контроллер.\rПример кода: protected \$_model_name = 'App\Models\User';");
        }
        
        $model_name = $this->_model_name;

        if (empty($id)) {
            $model = new $model_name;
        } else {
            $model = $model_name::findOrFail($id);
        }
        $mode = is_null($model->id) ? 'add' : 'edit';

        $fields = $this->getFields($mode, false);

        $view_theme = config('prettyforms.theme', 'bootstrap3');
        
        $view = View::make('prettyforms::save-' . $view_theme);
        $view->fields  = $fields;
        $view->mode    = $mode;
        $view->strings = $this->getStrings($model);
        $view->item    = $model;
        $view->values  = $values;
        $view->home_link = $this->getHomeLink($model);

        $title = isset($view->strings[$mode]['caption'])
            ? $view->strings[$mode]['caption']
            : (($mode === 'add')
                ? 'Создание объекта'
                : 'Редактирование объекта');
        
        if (method_exists($this, 'setContent')) {
            $this->setContent($view, $title);
        } else {
            $view->title = $title;
            return $view;
        }
    }

    /**
     * Стандартный метод для создания/редактирования записи
     * При обычном обращении генерирует форму, которая отправляет данные на тот же URL
     * Когда приходят данные, обрабатывает их.
     * @param Request $request Объект запроса
     * @param array $values Значения, которые переопределят введённые
     */
    protected function defaultSaveLogic(LaravelRequest $request, $values = null)
    {
        if (Request::wantsJson() AND Request::isMethod('post')) {
            return $this->save($request, pf_param());
        } else {
            return $this->generateForm(pf_param(), $values);
        }
    }

    /**
     * Стандартный метод для удаления записи.
     * Работает также, как controller_save()
     */
    protected function defaultDeleteLogic() {
        if (Request::wantsJson() AND Request::isMethod('post')) {
            return $this->delete(pf_param());
        } else {
            abort(404);
        }
    }

    /**
     * Стандартный метод для восстановления записи, ранее удалённой мягким методом.
     */
    protected function defaultRestoreLogic() {
        if (Request::wantsJson() AND Request::isMethod('post')) {
            return $this->restore(pf_param());
        } else {
            abort(404);
        }
    }

    /**
     * Стандартный метод для окончательного удаления записи, ранее удалённой мягким методом.
     */
    protected function defaultForceDeleteLogic() {
        if (Request::wantsJson() AND Request::isMethod('post')) {
            return $this->forceDelete(pf_param());
        } else {
            abort(404);
        }
    }

    protected function generateNotFoundErrorCommand() {
        return [
            'error' => 'Указанный объект не найден в системе'
        ];
    }

    /**
     * Возвращает текущие редактируемые поля для модели.
     * Если второй параметр был указан как true, то отбираются только те поля, которые
     * не начинаются со слова "info", так как те поля, которые начинаются с "info",
     * являются лишь информационными и не должны изменяться во время сохранения данных
     */
    protected function getFields($mode, $only_edited_fields = true) {
        
        if (! property_exists($this, 'fields')) {
            throw new Exception("Пожалуйста, укажите в вашем классе свойство \$fields, в котором будут описаны те поля, которые должны редактироваться у объекта."
                . "\rПример кода: protected \$fields = [ /* список редактируемых полей */ ];");
        }
        
        if ($only_edited_fields) {
            if ($mode === 'edit' AND isset($this->fields_edit)) {
                $fields_param = 'fields_edit';
            } else {
                $fields_param = 'fields';
            }

            $fields_list = [];
            foreach($this->{$fields_param} as $field_key => $field_data) {
                if (mb_substr($field_data['tag'],0,5) !== 'info-') {
                    $fields_list[$field_key] = $field_data;
                }
            }

            return $fields_list;
        } else {
            if ($mode === 'edit' AND isset($this->fields_edit)) {
                return $this->fields_edit;
            } else {
                return $this->fields;
            }
        }
    }

    /**
     * После успешного сохранения, удаления и создания модели,
     * пользователь будет перенаправлен на URL, сгенерированный
     * данным методом
     */
    protected function getHomeLink($model) {
        return pf_controller();
    }

}
