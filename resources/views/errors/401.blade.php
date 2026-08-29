@include('errors.minimal', [
    'code' => 401,
    'title' => __('Please log in'),
    'message' => __('You need to be logged in to view this page.'),
])
