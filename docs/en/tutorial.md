# First form tutorial

### Installing

First, install the component by running the following command in the terminal:
```bash
composer require "believer-ufa/prettyforms-laravel:~0.2"
```
#### Add the required service providers
PrettyForms are depends from `illuminate/html` and pulls him along during installation, so you need to connect to your application as a service provider, and the facades of the component. To do this, add in your config file `config/app.php` following lines:
```php
return [
  'providers' => [
  	// ... your service providers
        Collective\Html\HtmlServiceProvider::class,
        PrettyFormsLaravel\ServiceProvider::class,
  ],
  
  'aliases' => [
        // ... your facades
        'Form'        => Collective\Html\FormFacade::class,
        'HTML'        => Collective\Html\HtmlFacade::class,
  ],
];  
```

#### Connect trait controller

Connect to your host controller trait `PrettyFormsLaravel\FormProcessLogic`. Thus, your main controller should look like this:
```php
use PrettyFormsLaravel\FormProcessLogic;

abstract class Controller extends BaseController {

  use DispatchesCommands,
      ValidatesRequests,
      FormProcessLogic;

   // code for your parent controller
}
```

#### Connect prettyforms.js library and configure jQuery Ajax

Download and connect the JS and CSS library files to your site from the page [prettyforms.js](https://github.com/believer-ufa/prettyforms)

Add to the end of the page the following code:
```html

<!-- This script and style is optional: they provide a nice pop-up dialogs -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/0.5.0/sweet-alert.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/0.5.0/sweet-alert.min.js"></script>

<link href="{{ asset('/path/to/prettyforms/prettyforms.css') }}" rel="stylesheet">
<script src="{{ asset('/path/to/prettyforms/prettyforms.js') }}"></script>
<script src="{{ asset('/path/to/prettyforms/prettyforms_en.js') }}"></script>
    
<script>
// Configuring ajax: adding a protective token to all ajax-application requests
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
  }
});
</script>
```

#### Create a controller for form

Now, all the preparatory work for setting up is complete and we can start creating our first form. We need to create a controller that will contain all the basic logic of a form. For example, you can create a controller for editing users:

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
     * The name of the model that we are going to change
     */
    protected $_model_name = 'App\User';
    
    /**
     * Form fields that will be available for editing
     */
    protected $fields = [
        'name'     => [
            'tag'        => 'input',
            'label'      => 'Name',
            'attributes' => ['data-validation' => 'notempty'],
        ],
        'email'    => [
            'tag'        => 'input',
            'label'      => 'E-Mail',
            'attributes' => ['data-validation' => 'notempty'],
        ],
        'password'    => [
            'tag'        => 'input',
            'label'      => 'Password',
            'attributes' => ['data-validation' => 'notempty'],
        ],
    ];

    /**
     * It returns the text to be used in the generation of forms and reports for the object.
     * The method does not necessarily to create, a default class will use the standard common messages and headers
     */
    protected function getStrings($model) {
        return [
            'add'  => [
                'caption' => 'New user',
                'success' => 'User created successfully',
            ],
            'edit' => [
                'caption' => 'Edit user',
                'success' => 'User updated successfully',
            ],
        ];
    }
    
    /**
     * Validation rules for the current model
     * @param object $model The model, with which we work
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
    * See page with a list of users
    **/
    public function getIndex()
    {
        $view = view('users');
        $view->users = User::withTrashed()->get();
        return $view;
    }

    /**
    * Page for creating and editing users
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

Next, we of course have to create view-file, which will display a list of users and a buttons for their creation and editing. Here is a simple example of this template:
```html
@extends('app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading">
            Users
            <a class="btn btn-primary btn-xs" href="/users/save">new</a>
        </div>

        <div class="panel-body">

            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Management</th>
                    </tr>
                </thead>
                @foreach ($users as $user)
                <tr <?php if ($user->trashed()) { ?> class="text-muted" <?php } ?>>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if ($user->trashed())
                            <div class="btn btn-default btn-xs senddata" href="/users/restore/{{$user->id}}">Restore</div>
                            <div class="btn btn-default btn-xs senddata really" data-link="/users/force-delete/{{$user->id}}">Remove completely</div>
                        @else
                            <a class="btn btn-default btn-xs" href="/users/save/{{$user->id}}">Edit</a>
                            <div class="btn btn-default btn-xs senddata" data-link="/users/delete/{{$user->id}}">Delete</div>
                        @endif
                    </td>
                </tr>
                @endforeach

            </table>
        </div>
    </div>
		
@endsection
```

In general, that's all. We created a minimum functionality to create, edit and delete users in the system, we have written, we can already start to use it.
