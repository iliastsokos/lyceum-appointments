@include('errors.minimal', [
    'code' => 404,
    'title' => __('Page not found'),
    'message' => __("The page you're looking for doesn't exist or may have been moved."),
])
