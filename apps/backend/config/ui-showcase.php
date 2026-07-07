<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Showcase Viewports
    |--------------------------------------------------------------------------
    |
    | Define the standard responsive viewports used across the component
    | showcase. These values are used to constrain the preview area to
    | realistic device dimensions rather than arbitrary breakpoints.
    |
    */

    'viewports' => [
        'mobile' => 'max-w-[390px]',
        'tablet' => 'max-w-[768px]',
        'desktop' => 'w-full',
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Categories
    |--------------------------------------------------------------------------
    |
    | Define the organizational structure of the UI component library here.
    | This registry automatically generates the sidebar navigation and
    | structurally groups the component preview sections.
    |
    */

    'categories' => [
        'Forms' => [
            'Input' => 'form-input',
            'Select' => 'form-select',
            'Checkbox' => 'form-checkbox',
            'Button' => 'button',
        ],
        'Data Display' => [
            'DataTable' => 'data-table',
            'Timeline' => 'timeline',
            'Stats Grid' => 'stats-grid',
            'Empty State' => 'empty-state',
            'Badge' => 'badge',
            'Card' => 'card',
            'Avatar' => 'avatar',
            'File Card' => 'file-card',
            'File Preview' => 'file-preview',
        ],
        'Navigation' => [
            'Tabs' => 'tabs',
            'Pagination' => 'pagination',
            'Breadcrumb' => 'breadcrumb',
            'Dropdown' => 'dropdown',
            'Stepper' => 'stepper',
        ],
        'Feedback' => [
            'Modal' => 'modal',
            'Drawer' => 'drawer',
            'Alert' => 'alert',
            'Toast' => 'toast',
            'Skeleton' => 'skeleton',
            'Spinner' => 'spinner',
        ],
    ],

];
