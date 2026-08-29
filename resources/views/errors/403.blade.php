@include('errors.minimal', [
    'code' => 403,
    'title' => __('Δεν επιτρέπεται η πρόσβαση'),
    'message' => $exception->getMessage() ?: __('Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.'),
])
