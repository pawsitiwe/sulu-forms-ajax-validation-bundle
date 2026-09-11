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
            $formHandler: '@sulu_form.handler'
            $formConfigurationFactory: '@Sulu\Bundle\FormBundle\Configuration\FormConfigurationFactory'
        tags: ['controller.service_arguments']

    Pawsitiwe\EventListener\ValidationRequestLocaleListener:
        tags:
            - { name: 'kernel.event_listener', event: 'kernel.request', priority: 10 }
```

The `/ajax/form/validate` path carries no locale/webspace segment, so Sulu
would otherwise always resolve it to the default presence. The listener
re-resolves the real webspace/locale from the `Referer` header (the page the
form was actually submitted from) before any other `kernel.request` listener
(e.g. maintenance mode) sees the request — this is why it needs a priority
higher than 0.
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

## Twig Integration

Include the AJAX-enabled form in your templates using the provided partial:

```twig
{% include '@SuluFormsAjaxValidation/forms/partials/ajax_form.html.twig' with {
    form: content.form,
    errorText: 'Custom error message' # optional
} %}
```

This partial automatically:
- Uses the custom form theme ajax_form.html.twig
- Displays error and success messages
- Adds type-specific and additional CSS classes for styling

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

# Notes

- The `form` parameter must be a **Sulu Form object**.
- `successText` is optional; if empty, the default translation `ajax_form.success` will be used.
- `errorText` is optional; if empty, the default translation `ajax_form.error` will be used.
