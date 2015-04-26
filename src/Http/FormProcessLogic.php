<?php

namespace PrettyFormsLaravel\Http;

use Eloquent;
use Exception;
use Input;
use PrettyFormsLaravel\Http\Commands;
use Request;
use Session;
use View;

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
				//'legend' => 'Описание формы',
			],
            'edit' => [
				//'caption' => 'Текст заголовка страницы редактирования объекта'
				//'success' => 'Текст сообщения об успешном редактировании объекта',
                //'legend' => 'Описание формы',
			],
        ];
    }

    /**
     * Добавляет/изменяет запись в таблице. При желании, можно указать дополнительные значения в массиве $values, за основу же берутся данные из $_POST
     * @param int $id Номер записи в таблице
     * @param array $values Массив значений
     * @param array $params Дополнительные параметры метода
     * @return boolean
     */
    protected function save($id = null, $values = null, $success_save_callback = null)
    {
        $model_name = $this->_model_name;
        $model = $model_name::findOrNew($id); /* @var $model Eloquent */
        $mode = $model->exists ? 'edit' : 'add';

        $values = array_merge(Input::all(), is_null($values) ? [] : $values);
        $fields_names = array_keys($this->getFields($mode));

        // Получаем чистый массив значений, в котором содержатся только те значения,
        // которые перечислены среди полей редактирования модели
        $clean_values = array_only($values, $fields_names);

        $model->saveFormItem($fields_names, $clean_values);

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
        return Commands::generate([
            'redirect' => $this->getHomeLink($model),
        ]);
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

        $home_link = $this->getHomeLink($model);

        $model->delete();

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes',class_uses($model))) {
            Session::flash('message','success|Объект мягко удалён из системы, и его еще возможно легко восстановить.');
        } else {
            Session::flash('message','success|Объект полностью удалён из системы.');
        }

        return Commands::generate([
            'redirect' => $home_link
        ]);
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

        return Commands::generate([
            'refresh' => ''
        ]);
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

        return Commands::generate([
            'refresh' => ''
        ]);
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
     * @param type $values
     */
    protected function defaultSaveLogic($values = null)
    {
        if (Request::wantsJson() AND Request::isMethod('post')) {
            return $this->save(pf_param());
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
        return Commands::generate([
            'error' => 'Указанный объект не найден в системе'
        ]);
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
