PrettyForms for Laravel 5
===========

The component that makes it easy to create and handle a variety of forms for your website.

## Introduction

We all know about the amount of routine work that needs to be do every time when we need to create and edit a particular form on the page. This may be the profile edit page, set a new password for the user, similar forms in admin, or just some kind of new application in a support.

All of these forms are united by common characteristics: they are quite simple and very similar to each other in terms of implementation: to create a method in the controller, to create view-template, organize the validation, shove the data into some kind of model.. Pain, trivial round, boredom. How many times it should be done on a large project? In the initial stages of the problem is often "solved" scaffolding, but this is only a small billet, which was subsequently still need to change and modify. That's why I wrote this library, greatly reduced the amount of resources required to create and maintain such simple forms.

With all of this, I wanted to create the most minimalistic and simple tool that would have carried only a small supporting role, did not limit the developer and it filled up several layers of abstraction. And it seems I have managed to achieve this: at the moment, all the component installation is reduced to a single connection JS-library (Prettyforms.js), service provider and trait to your main controller. Then you can begin to create their own forms based on the same old kind of controllers, which will now have a few extra powerful methods that facilitate you to develop and support forms.

At this point in the component to implement the following:
<ul>
	<li>Data validation on the client and on the server side without any change from the current page</li>
	<li>The ability to edit the connection in models such as "many-to-many"</li>
	<li>The possibility, when necessary, easily connect their field types</li>
	<li>Support from a box of two CSS-frameworks: Twitter Bootstrap and Foundation</li>
	<li>Support for "breadcrumbs" that appear on the entry form, on the basis of component Laravel Breadcrumbs</li>
	<li>Support soft removal, complete removal and restoration</li>
	<li>In addition to standard input and Selects, component supports the following types of fields:
            <ul>
                 <li>Input field telephone with the ability to set his mask</li>
                 <li>CKEditor</li>
                 <li>Group checkboxes, each of which represents a connection to any model in the database</li>
                 <li>Ability to create a Select, which will search for value through Ajax (based on "select2" library)</li>
            </ul>
        </li>
	<li>All necessary for non-standard components INPUT JS-default loaded via CDN, but it can be easily disabled via konfiguratsionnny component file</li>
</ul>

### Пример приложения

Вы можете скачать и изучить пример реального работающего приложения по следующей ссылке:
https://github.com/believer-ufa/prettyforms-laravel5-app

Практически все фичи, описанные выше, присутствуют в рабочем виде в приложении-примере: валидация, редактирование связей "многие-ко-многим", аяксовый поиск, мягкое удаление и восстановление, загрузка редактора и прочих JS-компонетов по CDN, хлебные крошки, и так далее.

Данный пример рабочего приложения можно также использовать как основу для своего собственного приложения: просто установите его себе и начните работу над новым проектом на основе того, что содержится в примерах.

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
        'Illuminate\Html\HtmlServiceProvider',
        'PrettyFormsLaravel\ServiceProvider',
  ],
  
  'aliases' => [
        // ... ваши фасады
        'Form'        => 'Illuminate\Html\FormFacade',
        'HTML'        => 'Illuminate\Html\HtmlFacade',
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

#### 

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



#PrettyForms для Laravel 5
===========

Компонент, который позволяет легко создавать и обрабатывать различные формы для вашего сайта.

## Введение

В последнее время я долго работал над своей небольшой библиотекой PrettyForms, предназначенной для генерации и обработки форм. Все мы знаем о том объёме рутинной работы, который необходимо каждый раз проделывать тогда, когда нам необходимо создать и обработать определённую форму на странице сайта. Это может быть страница редактирования профиля, установки нового пароля для пользователя, однотипные формы в админке или просто какая-то новая заявка в саппорт.

Все эти формы объединяются общими свойствами: они довольно простые и очень сильно похожи друг на друга с точки зрения реализации: создать метод в контроллере, создать view-шаблон, организовать валидацию данных, пихнуть эти данные в какую-то модель.. Боль, рутина, скукота. Сколько раз это надо проделать на большом проекте? На начальных стадиях проблему часто "решают" скаффолдингом, но ведь это только маленькая заготовка, которую впоследствии всё равно надо будет изменять и дорабатывать. Вот поэтому я и написал эту библиотеку, сильно сокращающую то количество ресурсов, которое необходимо для создания и поддержки подобных простых форм.

При всём этом, я стремился создать как можно более минималистичный и простой инструмент, который бы нёс лишь небольшую вспомогательную роль, не ограничивал разработчика и не заваливал его несколькими слоями абстракций. И кажется, у меня удалось этого добиться: на данный момент, вся установка компонента сводится к подключению одной JS-библиотеки (Prettyforms.js), сервис-провайдера и трейта к вашему родительскому контроллеру. После этого вы можете начать создавать свои формы на основе тех же самых старых добрых контроллеров, которые теперь получат несколько дополнительных мощных методов, облегчающих вам разработку и поддержку форм.

На данный момент в компоненте реализовано следующее:
<ul>
	<li>Валидация данных на клиентской и на на серверной сторонах без какого-либо перехода с текущей страницы</li>
	<li>Возможность редактировать связи у моделей типа "многие-ко-многим"</li>
	<li>Возможность, при необходимости, легко подключать свои типы полей</li>
	<li>Поддержка из коробки сразу двух CSS-фреймворков: Twitter Bootstrap и Foundation</li>
	<li>Поддержка "хлебных крошек", отображающихся над формой ввода, на основе компонента Laravel Breadcrumbs</li>
	<li>Поддержка мягкого удаления, полного удаления и восстановления объектов</li>
	<li>Помимо стандартных инпутов и селектов, поддерживаются следующие виды полей:
            <ul>
                 <li>Поле ввода номера телефона с возможностью задать его маску </li>
                 <li>Редактор CKEditor</li>
                 <li>Группа чекбоксов, каждый из которых представляет из себя связь с какой-то моделью в БД</li>
                 <li>Возможность создать Select, который будет искать значения через Ajax (на основе плагина "select2")</li>
            </ul>
        </li>
	<li>Все необходимые для нестандартных инпутов JS-компоненты по-умолчанию загружаются через CDN, но это можно легко отключить через конфигурационнный файл компонента</li>
</ul>

### Пример приложения

Вы можете скачать и изучить пример реального работающего приложения по следующей ссылке:
https://github.com/believer-ufa/prettyforms-laravel5-app

Практически все фичи, описанные выше, присутствуют в рабочем виде в приложении-примере: валидация, редактирование связей "многие-ко-многим", аяксовый поиск, мягкое удаление и восстановление, загрузка редактора и прочих JS-компонетов по CDN, хлебные крошки, и так далее.

Данный пример рабочего приложения можно также использовать как основу для своего собственного приложения: просто установите его себе и начните работу над новым проектом на основе того, что содержится в примерах.

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
        'Illuminate\Html\HtmlServiceProvider',
        'PrettyFormsLaravel\ServiceProvider',
  ],
  
  'aliases' => [
        // ... ваши фасады
        'Form'        => 'Illuminate\Html\FormFacade',
        'HTML'        => 'Illuminate\Html\HtmlFacade',
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

#### 

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
