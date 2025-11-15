<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    |
    | This value sets the root class namespace for Livewire component classes in
    | your application. This value affects component auto-discovery and
    | where you place your Livewire components.
    |
    */
    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This value sets the path where Livewire component views are stored.
    | This affects component auto-discovery and where you place your
    | component views.
    |
    */
    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    | The default layout view that will be used when rendering a component via
    | Route::get('/some-endpoint', SomeComponent::class);. In this case the
    | the view returned by SomeComponent will be wrapped in "layouts.app"
    |
    */
    'layout' => 'components.layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Livewire Assets URL
    |--------------------------------------------------------------------------
    |
    | This value sets the path to Livewire JavaScript assets, for cases where
    | your app's domain root is not the correct path. By default, Livewire
    | will load its JavaScript assets from the app's "relative root".
    |
    | Examples: "/assets", "myurl.com/app".
    |
    */
    'asset_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Livewire App URL
    |--------------------------------------------------------------------------
    |
    | This value should be used if livewire assets are served from CDN.
    | Livewire will communicate with an app through this url.
    |
    | Examples: "https://my-app.com", "myurl.com/app".
    |
    */
    'app_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Livewire Endpoint Middleware Group
    |--------------------------------------------------------------------------
    |
    | This value sets the middleware group that will be applied to the main
    | Livewire "update" endpoint (the endpoint that handles component updates
    | performed by your end users). Defaults to "web"
    |
    */
    'middleware_group' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Livewire Temporary File Uploads Endpoint Configuration
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing uploads in a temporary directory
    | before the file is validated and stored permanently. All file uploads
    | are directed to a global endpoint for temporary storage. The config
    | items below are used for customizing the way the endpoint works.
    |
    */
    'temporary_file_upload' => [
        'disk' => null,        // Example: "local", "s3"              | Default: "default"
        'rules' => null,       // Example: ['file', 'mimes:png,jpg']  | Default: ['required', 'file', 'max:12288'] (12MB)
        'directory' => null,   // Example: "tmp"                      | Default  "livewire-tmp"
        'middleware' => null,  // Example: "throttle:5,1"             | Default: "throttle:60,1"
        'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs.
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5, // Max upload time in minutes.
    ],

    /*
    |--------------------------------------------------------------------------
    | Manifest File Path
    |--------------------------------------------------------------------------
    |
    | This value sets the path to the Livewire manifest file.
    | The default should work for most cases (which is
    | "<app_base_path>/bootstrap/cache/livewire-components.php"), but for Windows
    | systems, the path may need to be adjusted.
    |
    */
    'manifest_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Back Button Cache
    |--------------------------------------------------------------------------
    |
    | This value determines whether the back button cache will be used on pages
    | that contain Livewire. By disabling back button cache, it ensures that
    | the back button shows the correct state of components, instead of
    | potentially stale cached pages.
    |
    */
    'back_button_cache' => false,

    /*
    |--------------------------------------------------------------------------
    | Render On Redirect
    |--------------------------------------------------------------------------
    |
    | This value determines whether Livewire will render before it's redirected
    | or not. Setting this to "false" (default) will mean the render method is
    | skipped when calling redirect(). If you need to render before redirecting,
    | set this to "true".
    |
    */
    'render_on_redirect' => false,

    /*
    |--------------------------------------------------------------------------
    | Inject Assets
    |--------------------------------------------------------------------------
    |
    | This determines if Livewire will automatically inject its JavaScript assets
    | onto pages that contain Livewire components. By default, this is true.
    | If set to false, you should manually include the assets with @livewireScripts
    |
    */
    'inject_assets' => true,

    /*
    |--------------------------------------------------------------------------
    | Inject Morph Markers
    |--------------------------------------------------------------------------
    |
    | This determines if Livewire will inject HTML comment markers to aid in
    | morphing. These markers are used to track which elements should be
    | morphed during DOM updates. Set to false to disable.
    |
    */
    'inject_morph_markers' => true,

    /*
    |--------------------------------------------------------------------------
    | Navigate (SPA mode) Options
    |--------------------------------------------------------------------------
    */
    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Morphing Options
    |--------------------------------------------------------------------------
    */
    'update_html_morph' => true,

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Placeholder
    |--------------------------------------------------------------------------
    |
    | Livewire allows you to lazy load components that would otherwise slow down
    | the initial page load. Every component can have a custom placeholder or
    | you can define the default placeholder view for all components below.
    |
    */
    'lazy_placeholder' => null,

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads Path
    |--------------------------------------------------------------------------
    */
    'temporary_file_upload_path' => 'livewire-tmp',

    /*
    |--------------------------------------------------------------------------
    | Volt Options
    |--------------------------------------------------------------------------
    */
    'volt' => [
        'mount' => [
            resource_path('views/livewire'),
        ],
    ],

];
