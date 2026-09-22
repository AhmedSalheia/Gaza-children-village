<?php
return [
    // Amount above which an issue requires additional approval levels.
    'large_issue_threshold' => (float) env('INVENTORY_LARGE_ISSUE_THRESHOLD', 1000),
];
