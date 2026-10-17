<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lesson videos
    |--------------------------------------------------------------------------
    |
    | Uploaded lesson videos are private: they live on a non-public disk and
    | are played through short-lived signed URLs (or S3 temporary URLs), so
    | only people the API hands a link to can watch them.
    |
    */

    'video_disk' => env('LESSON_VIDEO_DISK', 'local'),

    // Max upload size in kilobytes (PHP's upload_max_filesize must be at least this).
    'video_max_kb' => (int) env('LESSON_VIDEO_MAX_KB', 512000),

    // How long a signed playback URL stays valid.
    'video_url_ttl_minutes' => (int) env('LESSON_VIDEO_URL_TTL', 240),

    'video_mimetypes' => ['video/mp4', 'video/webm', 'video/quicktime'],

];
