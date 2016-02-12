<?php

namespace PrettyFormsLaravel;

use DB;
use Eloquent;
use Exception;
use Input;
use Request;
use Session;
use View;

trait FormProcessLogic
{
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
     *
     * @param int $id Номер записи в таблице
     * @param array $values Массив значений, которые надо применить к сохраняемому объекту поверх значений из запроса клиента
     * @param function $onSuccessSave Функция, внутри которой с моделью можно проделать какие-то действия после её сохранения
     *
     * @return bool
     */
    protected function save($id = null, $values = null, $onSuccessSave = null)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::findOrNew($id); /* @var $model Eloquent */
        $mode       = $model->exists ? 'edit' : 'add';

        $values       = array_merge(Input::all(), is_null($values) ? [] : $values);
        $fields_names = array_keys($this->getFields($mode));

        // Получаем чистый массив значений, в котором содержатся только те значения,
        // которые перечислены среди полей редактирования модели
        $clean_values = array_only($values, $fields_names);

        if (property_exists($this, '_form_params')) {
            // При создании новой записи и при включенном режиме order, вставляем нужный порядковый номер для сортировки
            if (in_array('order', $this->_form_params) && $mode === 'add') {
                $order                 = new Order($model->getTable());
                $clean_values['order'] = $order->get_insert_order();
            }

            if (in_array('gorder', $this->_form_params)) {
                $gorder_group_field = $this->_form_gorder_group_field;
                $gorder             = new GroupOrder($model->getTable(), $gorder_group_field);

                if ($mode === 'edit') {
                    $old_order_value = $model->order;
                    $old_group_id    = $model->$gorder_group_field;
                    // Если это редактирование записи, то обновляем поле order только в том случае, когда была изменена группа записи
                    if ($clean_values[$gorder_group_field] != $old_group_id) {
                        $gorder->set_group($clean_values[$gorder_group_field]);
                        $clean_values['order'] = $gorder->get_insert_order();
                    }
                } else {
                    $gorder->set_group($clean_values[$gorder_group_field]);
                    $clean_values['order'] = $gorder->get_insert_order();
                }
            }
        }

        $this->saveModelItem($model, $fields_names, $clean_values);

        if (property_exists($this, '_form_params')) {
            // Если включен режим gorder, то проверим, не изменилась ли группа у записи
            if ($mode === 'edit' && in_array('gorder', $this->_form_params)) {
                if ($model->$gorder_group_field != $old_group_id) {
                    // Вернёмся к старой группе и сдвинем все записи чтобы закрыть пустое место
                    $gorder->set_group($old_group_id);
                    $gorder->update_after_del($old_order_value);
                }
            }
        }

        $controller_strings = $this->getStrings($model);

        Session::flash('message', 'success|'.array_get($controller_strings[$mode], 'success',
            ($mode === 'add')
                ? 'Объект успешно создан'
                : 'Объект успешно отредактирован')
        );

        // Если был передан коллбек, вызовем его вместо стандартного редиректа
        if ($onSuccessSave) {
            return $onSuccessSave($model);
        }

        // Если всё прошло хорошо, генерируем ответ с командой редиректа на главную страницу текущего контроллера
        return [
            'redirect' => $this->getHomeLink($model),
        ];
    }

    /**
     * Добавляет/изменяет строку в базе
     *
     * @param array $model Модель, которую мы сохраняем
     * @param array $fields_names Список названий колонок, с которыми необходимо работать
     * @param array $values Ассоциативный массив значений, которые надо будет сохранить
     *
     * @return bool
     */
    private function saveModelItem($model, $fields_names, $values)
    {
        $columns = $this->getColumnsInfo($model);

        // Получим массив всех зависимостей многие-многие, чтобы потом добавлять в них элементы
        $has_many_throughs = $this->getModelThroughs($model, $fields_names, $columns);

        $throughs_array = [];

        // Обработаем все основные колонки
        foreach ($values as $field => $value) {
            if (in_array($field, $has_many_throughs)) {
                if ( ! empty($value) and $value !== 'null') {
                    $throughs_array[$field] = (array) $value;
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
            $is_boolean = ($column_info['type'] === 'boolean' or $column_info['type'] === 'tinyint(1)');
            if ($is_boolean and in_array($column_name, $fields_names)) {
                if (isset($values[$column_name]) and $values[$column_name] === 'null') {
                    $model->$column_name = null;
                } elseif (isset($values[$column_name])) {
                    $model->$column_name = true;
                } else {
                    $model->$column_name = false;
                }
            }
        }

        // Если есть связи "многие-ко-многим", запускаем транзакцию
        if ( ! empty($has_many_throughs)) {
            DB::beginTransaction();
        }

        $validation_rules = null;
        if (method_exists($this, 'getValidationRules')) {
            $validation_rules = $this->getValidationRules($model);
        }

        // Если были указаны правила валидации, проверим входящие данные
        if ($validation_rules) {
            $this->validate(Request::instance(), $validation_rules);
        }

        $model->save();

        // Заполним связи новыми данными
        foreach ($has_many_throughs as $relation_name) {
            $model->$relation_name()->sync(
                array_get($throughs_array, $relation_name, [])
            );
        }

        if ( ! empty($has_many_throughs)) {
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
        foreach ($fields as $field) {
            if ( ! isset($columns[$field])) {
                $results_array[] = $field;
            }
        }

        return $results_array;
    }

    /**
     * Возвращает информацию о колонках текущей таблицы и их типах
     *
     * @return array
     */
    protected function getColumnsInfo($model)
    {
        $columns = [];

        foreach(DB::connection()
            ->getDoctrineConnection()
            ->getSchemaManager()
            ->listTableColumns($model->getTable()) as $field_name => $field_info)
        {
            // странно, иногда Doctrine SchemaManager возвращает
            // название колонки в кавычках. пришлось поправить это небольшим костылём
            $columns[str_replace('"','',$field_name)] = [
                'type'     => strtolower($field_info->getType()->getName()),
                'nullable' => $field_info->getNotNull() === false,
            ];
        }

        return $columns;
    }

    /**
     * Удаление записи из таблицы
     *
     * @param type $id
     * @param function $onSuccessDelete Выполнить какие-то действия при успешном удалении и вернуть особый ответ клиенту
     *
     * @return bool
     */
    protected function delete($id, $onSuccessDelete = null)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::findOrNew($id); /* @var $model Eloquent */
        if ( ! $model->exists) {
            return $this->generateNotFoundErrorCommand();
        }

        if (property_exists($this, '_form_params')) {
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes',class_uses($model))) {
                if (in_array('order', $this->_form_params)) {
                    $old_order = $model->order;
                } elseif (in_array('gorder', $this->_form_params)) {
                    $old_order = $model->order;
                    $gorder_group_field = $this->_form_gorder_group_field;
                    $gorder = new Gorder($model->getTable(), $gorder_group_field);
                    $gorder->set_group($model->$gorder_group_field);
                }
            }
        }

        $model->delete();

        $home_link = $this->getHomeLink($model);

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            Session::flash('message', 'success|Объект мягко удалён из системы, и его еще возможно легко восстановить.');
        } else {

            if (property_exists($this, '_form_params')) {
                if (in_array('order', $this->_form_params)) {
                    $order = new Order($model->getTable());
                    $order->update_after_del($old_order);
                } elseif (in_array('gorder', $this->_form_params)) {
                    $gorder->update_after_del($old_order);
                }
            }

            Session::flash('message', 'success|Объект полностью удалён из системы.');
        }

        if (is_callable($onSuccessDelete)){
            return $onSuccessDelete($model);
        }

        return [
            'redirect' => $home_link,
        ];
    }

    /**
     * Восстановление записи в таблице
     *
     * @param type $id
     * @param function $onSuccessRestore Выполнить какие-то действия при успешном восстановлении и вернуть особый ответ клиенту
     *
     * @return bool
     */
    protected function restore($id, $onSuccessRestore = null)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::onlyTrashed()->find($id); /* @var $model Eloquent */
        if (is_null($model)) {
            return $this->generateNotFoundErrorCommand();
        }

        $model->restore();

        Session::flash('message', 'success|Объект восстановлен');

        if (is_callable($onSuccessRestore)){
            return $onSuccessRestore($model);
        }

        return [
            'refresh' => '',
        ];
    }

    /**
     * Полное физическое удаление записи в таблице
     *
     * @param type $id
     * @param function $onSuccessForcedelete Выполнить какие-то действия при успешном удалении и вернуть особый ответ клиенту
     *
     * @return bool
     */
    protected function forceDelete($id, $onSuccessForcedelete = null)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::onlyTrashed()->find($id); /* @var $model Eloquent */
        if (is_null($model)) {
            return $this->generateNotFoundErrorCommand();
        }

        if (property_exists($this, '_form_params')) {
            if (in_array('order', $this->_form_params)) {
                $old_order = $model->order;
            } elseif (in_array('gorder', $this->_form_params)) {
                $old_order = $model->order;
                $gorder_group_field = $this->_form_gorder_group_field;
                $gorder = new Gorder($model->getTable(), $gorder_group_field);
                $gorder->set_group($model->$gorder_group_field);
            }
        }

        $model->forceDelete();

        Session::flash('message', 'success|Объект полностью удалён из системы');

        if (property_exists($this, '_form_params')) {
            if (in_array('order', $this->_form_params)) {
                $order = new Order($model->getTable());
                $order->update_after_del($old_order);
            } elseif (in_array('gorder', $this->_form_params)) {
                $gorder->update_after_del($old_order);
            }
        }

        if (is_callable($onSuccessForcedelete)) {
            return $onSuccessForcedelete($model);
        }

        return [
            'refresh' => '',
        ];
    }

    /**
     * Генерирует форму для создания/редактирования записи
     *
     * @param int $id
     * @param array $values Ассоциативный массив со значениями полей, которые будут изначально подставлены в форму
     * @param $onlyReturn Только вернуть сгенерированную форму в ответ?
     *
     * @return string
     */
    protected function generateForm($id = null, $values = null, $onlyReturn = false)
    {
        if ( ! property_exists($this, '_model_name')) {
            throw new Exception("Пожалуйста, укажите название модели, с которой работает контроллер.\rПример кода: protected \$_model_name = 'App\Models\User';");
        }

        $model_name = $this->_model_name;

        if (empty($id)) {
            $model = new $model_name;
        } else {
            $model = $model_name::findOrFail($id);
        }

        $mode = is_null($model->id) ? 'add' : 'edit';

        $view_theme = config('prettyforms.theme', 'bootstrap3');

        $view            = View::make('prettyforms::save-'.$view_theme);
        $view->fields    = $this->getFields($mode, false);
        $view->mode      = $mode;
        $view->strings   = $this->getStrings($model);
        $view->item      = $model;
        $view->values    = $values;
        $view->home_link = $this->getHomeLink($model);

        $title = isset($view->strings[$mode]['caption'])
            ? $view->strings[$mode]['caption']
            : (($mode === 'add')
                ? 'Создание объекта'
                : 'Редактирование объекта');

        if (method_exists($this, 'setContent') and $onlyReturn === false) {
            $this->setContent($view, $title);
        } else {
            $view->title = $title;

            return $view->render();
        }
    }

    /**
     * Стандартный метод для создания/редактирования записи
     * При обычном обращении генерирует форму, которая отправляет данные на тот же URL
     * Когда приходят данные, обрабатывает их.
     *
     * @param array $values Значения, которые переопределят введённые
     */
    protected function defaultSaveLogic($values = null, $onSuccessSave = null)
    {
        if (Request::wantsJson() and Request::isMethod('post')) {
            return $this->save(param(), $values, $onSuccessSave);
        } else {
            return $this->generateForm(param(), $values);
        }
    }

    /**
     * Стандартный метод для удаления записи.
     * Работает также, как controller_save()
     */
    protected function defaultDeleteLogic($onSuccessDelete = null)
    {
        if (Request::wantsJson() and Request::isMethod('post')) {
            return $this->delete(param(), $onSuccessDelete);
        } else {
            abort(404);
        }
    }

    /**
     * Стандартный метод для восстановления записи, ранее удалённой мягким методом.
     */
    protected function defaultRestoreLogic($onSuccessRestore = null)
    {
        if (Request::wantsJson() and Request::isMethod('post')) {
            return $this->restore(param(), $onSuccessRestore);
        } else {
            abort(404);
        }
    }

    /**
     * Стандартный метод для окончательного удаления записи, ранее удалённой мягким методом.
     */
    protected function defaultForceDeleteLogic()
    {
        if (Request::wantsJson() and Request::isMethod('post')) {
            return $this->forceDelete(param());
        } else {
            abort(404);
        }
    }

    protected function generateNotFoundErrorCommand()
    {
        return [
            'error' => 'Указанный объект не найден в системе',
        ];
    }

    /**
     * Возвращает текущие редактируемые поля для модели.
     * Если второй параметр был указан как true, то отбираются только те поля, которые
     * не начинаются со слова "info", так как те поля, которые начинаются с "info",
     * являются лишь информационными и не должны изменяться во время сохранения данных
     */
    protected function getFields($mode, $only_edited_fields = true)
    {
        if ( ! method_exists($this, 'getFormFields')) {
            throw new Exception('Пожалуйста, укажите в вашем классе метод getFormFields, который будет возвращать те поля, которые должны редактироваться у объекта.'
                ."\rПример кода: public function getFormFields() { return [ /* список редактируемых полей */ ]; }");
        }

        if ($only_edited_fields) {
            $fieldsParams = $this->getFormFields($mode);
            $fields_list = [];
            foreach ($fieldsParams as $field_key => $field_data) {
                if (mb_substr($field_data['tag'], 0, 5) !== 'info-') {
                    $fields_list[$field_key] = $field_data;
                }
            }
            return $fields_list;
        } else {
            return $this->getFormFields($mode);
        }
    }

    /**
     * После успешного сохранения, удаления и создания модели,
     * пользователь будет перенаправлен на URL, сгенерированный
     * данным методом
     */
    protected function getHomeLink($model)
    {
        return controller_index();
    }

    /**
     * Поднять определенную запись вверх в списке
     *
     * @param int $id Номер записи в таблице
     *
     * @return bool
     */
    protected function upRecord($id)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::findOrFail($id);

        if (in_array('gorder', $this->_form_params)) {
            $order_field = $this->_form_gorder_group_field;
            $gorder      = new GroupOrder($model->getTable(), $order_field);
            $gorder->set_group($model->$order_field);
            $result = $gorder->up($model->id);
        } else {
            $order  = new Order($model->getTable());
            $result = $order->up($model->id);
        }

        if ($result) {
            return ['refresh' => ''];
        } else {
            return ['error' => ['title' => '', 'text' => 'Невозможно поднять запись']];
        }
    }

    /**
     * Опустить определенную запись вверх в списке
     *
     * @param int $id Номер записи в таблице
     *
     * @return bool
     */
    protected function downRecord($id)
    {
        $model_name = $this->_model_name;
        $model      = $model_name::findOrFail($id);

        if (in_array('gorder', $this->_form_params)) {
            $group_order_field = $this->_form_gorder_group_field;

            $gorder      = new GroupOrder($model->getTable(), $group_order_field);
            $gorder->set_group($model->$group_order_field);

            $result = $gorder->down($model->id);
        } else {
            $order  = new Order($model->getTable());
            $result = $order->down($model->id);
        }

        if ($result) {
            return ['refresh' => ''];
        } else {
            return ['error' => ['title' => '', 'text' => 'Невозможно опустить запись']];
        }
    }
}
