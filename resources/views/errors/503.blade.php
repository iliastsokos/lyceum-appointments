@include('errors.minimal', [
    'code' => 503,
    'title' => __('Down for maintenance'),
    'message' => __('The application is temporarily unavailable while we perform maintenance. Please check back shortly.'),
])
