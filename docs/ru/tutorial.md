# Туториал по созданию первой формы

### Установка

Прежде всего, установите компонент, выполнив в терминале следующую команду:
```bash
composer require "believer-ufa/prettyforms-laravel:~0.2"
```

#### Добавьте необходимые сервис-провайдеры
PrettyForms зависит от компонента `illuminate/html` и подтягивает его за собой во время установки, поэтому вам необходимо подключить к вашему приложению также сервис-провайдер и фасады данного компонента. Чтобы сделать это, добавьте в ваш конфигурационнный файл `config/app.php` следующие строки:
```php
return [
  'providers' => [
  	// ... ваши сервис-провайдеры
        Collective\Html\HtmlServiceProvider::class,
        PrettyFormsLaravel\ServiceProvider::class,
  ],
  
  'aliases' => [
        // ... ваши фасады
        'Form'        => Collective\Html\FormFacade::class,
        'HTML'        => Collective\Html\HtmlFacade::class,
  ],
];  
```

#### Подключите трейт к контроллеру

Подключите к вашему главному контроллеру трейт `PrettyFormsLaravel\FormProcessLogic`. Таким образом, ваш главный контроллер должен выглядеть примерно так:
```php
use PrettyFormsLaravel\FormProcessLogic;

abstract class Controller extends BaseController {

  use DispatchesCommands,
      ValidatesRequests,
      FormProcessLogic;

   // код вашего родительского контроллера
}
```

#### Подключите JS библиотеку prettyforms.js и настройте jQuery Ajax

Скачайте и подключите JS и CSS файлы библиотеки к своему сайту со страницы [prettyforms.js](https://github.com/believer-ufa/prettyforms)

Добавьте в самый конец страницы следующий код:
```html

<!-- данные скрипт и стиль опциональны к подключению: они обеспечивают красивые всплывающие диалоги -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/0.5.0/sweet-alert.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/0.5.0/sweet-alert.min.js"></script>

<link href="{{ asset('/path/to/prettyforms/prettyforms.css') }}" rel="stylesheet">
<script src="{{ asset('/path/to/prettyforms/prettyforms.js') }}"></script>
    
<script>
// Настройка аякса: добавляем защитный токен ко всем ajax-запросам приложения
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
  }
});
</script>
```

#### Создайте контроллер формы

Теперь вся подготовительная работа по настройке завершена, и мы можем начать создавать нашу первую форму. Нам необходимо создать контроллер, который будет содержать всю основную логику работы с формой. Для примера, вы можем создать контроллер для редактирования наших пользователей системы:

```php

<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class Users extends Controller {

  	public function __construct()
  	{
          $this->middleware('auth');
  	}

    /**
     * Название модели, которую мы собираемся менять
     */
    protected $_model_name = 'App\User';
    
    /**
     * Поля формы, которые будут доступны для редактирования
     */
    protected $fields = [
        'name'     => [
            'tag'        => 'input',
            'label'      => 'Имя',
            'attributes' => ['data-validation' => 'notempty'],
        ],
        'email'    => [
            'tag'        => 'input',
            'label'      => 'E-Mail',
            'attributes' => ['data-validation' => 'notempty'],
        ],
        'password'    => [
            'tag'        => 'input',
            'label'      => 'Пароль',
            'attributes' => ['data-validation' => 'notempty'],
        ],
    ];

    /**
     * Возвращает тексты, которые будут использоваться в генерации форм и сообщениях для объекта.
     * Метод не обязательно создавать, по умолчанию класс будет использовать стандартные общие сообщения и заголовки
     */
    protected function getStrings($model) {
        return [
            'add'  => [
                'caption' => 'Новый пользователь',
                'success' => 'Пользователь успешно создан',
            ],
            'edit' => [
                'caption' => 'Редактирование пользователя',
                'success' => 'Пользователь успешно обновлён',
            ],
        ];
    }
    
    /**
     * Правила валидации для текущей модели
     * @param object $model Модель, с которой мы работаем
     * @return array
     */
    protected function getValidationRules($model) {
        $except = $model->exists ? ",{$model->id}" : '';
        return [
            'name'     => 'required|max:45',
            'email'    => 'required|email|max:45|unique:users,email' . $except,
        ];
    }

    /**
    * Отобразить страницу со список пользователей
    **/
    public function getIndex()
  	{
          $view = view('users');
          $view->users = User::withTrashed()->get();
  		    return $view;
  	}

    /**
    * Страницы создания и редактирования пользователя
    **/
    function anySave(Request $request) {
        return $this->defaultSaveLogic($request);
    }
    
    function postDelete() {
        return $this->defaultDeleteLogic();
    }

    function postRestore() {
        return $this->defaultRestoreLogic();
    }

    function postForceDelete() {
        return $this->defaultForceDeleteLogic();
    }
}
```

Далее, нам, конечно же, придётся создать view-файл, на котором будут отображен список пользователей и кнопки для их создания и редактирования. Вот простой пример подобного шаблона:
```html
@extends('app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading">
            Пользователи
            <a class="btn btn-primary btn-xs" href="/users/save">новый</a>
        </div>

        <div class="panel-body">

            <table class="table">
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>E-Mail</th>
                        <th>Управление</th>
                    </tr>
                </thead>
                @foreach ($users as $user)
                <tr <?php if ($user->trashed()) { ?> class="text-muted" <?php } ?>>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if ($user->trashed())
                            <div class="btn btn-default btn-xs senddata" href="/users/restore/{{$user->id}}">Восстановить бедолагу</div>
                            <div class="btn btn-default btn-xs senddata really" data-link="/users/force-delete/{{$user->id}}">Удалить полностью</div>
                        @else
                            <a class="btn btn-default btn-xs" href="/users/save/{{$user->id}}">Редактировать</a>
                            <div class="btn btn-default btn-xs senddata" data-link="/users/delete/{{$user->id}}">Удалить</div>
                        @endif
                    </td>
                </tr>
                @endforeach

            </table>
        </div>
    </div>
		
@endsection
```

В общем-то, это всё. Минимальный функционал для создания, редактирования и удаления пользователей в системе мы написали, можно уже начинать им пользоваться.
