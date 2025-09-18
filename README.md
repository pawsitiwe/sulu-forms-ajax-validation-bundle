# Sulu Forms AJAX Validation Bundle

## Installation

```bash
composer require pawsitiwe/sulu-forms-ajax-validation-bundle
```

## Setup

### Service Registration

The extension needs to be registered as [symfony service](http://symfony.com/doc/current/service_container.html).

```yml
services:
    Pawsitiwe\Controller\ValidationController:
        arguments:
            $formBuilder: '@sulu_form.builder'
        tags: ['controller.service_arguments']
```
### Bundle Registration

```php
return [
    Pawsitiwe\SuluFormsAjaxValidationBundle::class => ['all' => true],
]
```

### Route Registration
```yml
sulu_frontend_validation:
    resource: '@SuluFormsAjaxValidationBundle/Resources/config/routes.yaml'
    prefix: /
```

## Usage

The route /ajax/form/validate returns the form validation as JSON

### Example Response

```json
{
    "message": "",
    "valid": false,
    "fields": [
        {
            "id": "dynamic_form1_email",
            "valid": false,
            "modified": true,
            "violation": {
                "message": "This value should not be blank."
            }
        },
        {
            "id": "dynamic_form1_lastName",
            "valid": true,
            "modified": false
        }
    ]
}

```