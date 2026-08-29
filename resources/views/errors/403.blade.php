@include('errors.minimal', [
    'code' => 403,
    'title' => __('Access denied'),
    'message' => $exception->getMessage() ?: __('You are not authorized to access this page.'),
])
