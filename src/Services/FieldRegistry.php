<?php

namespace Aftab\LaravelCrud\Services;

class FieldRegistry
{
    /** @var array<string, string> */
    protected array $renderers;

    public function __construct(array $renderers = [])
    {
        // map of logical field type => blade include path
        $this->renderers = array_merge([
            'text' => 'crud::fields.text',
            'textarea' => 'crud::fields.textarea',
            'checkbox' => 'crud::fields.checkbox',
            'date' => 'crud::fields.date',
            'select' => 'crud::fields.select',
        ], $renderers);
    }

    public function getViewFor(string $type): string
    {
        return $this->renderers[$type] ?? $this->renderers['text'];
    }
}


