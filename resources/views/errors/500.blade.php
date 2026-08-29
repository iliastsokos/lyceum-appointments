@include('errors.minimal', [
    'code' => 500,
    'title' => __('Something went wrong'),
    'message' => __("We're sorry, an unexpected error occurred. Please try again in a moment."),
])
