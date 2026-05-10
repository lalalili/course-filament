<?php

use Lalalili\CourseFilament\Pages\CoursePackageHealth;
use Lalalili\CourseFilament\Resources\Courses\CourseResource;

return [
    'resources' => [
        CourseResource::class,
    ],

    'pages' => [
        CoursePackageHealth::class,
    ],

    'readiness' => [
        'require_product' => env('COURSE_READINESS_REQUIRE_PRODUCT', false),
        'require_ready_videos' => env('COURSE_READINESS_REQUIRE_READY_VIDEOS', false),
        'action_label' => '檢查發布條件',
        'action_icon' => 'heroicon-o-clipboard-document-check',
    ],

    'commerce' => [
        'sync_product_action_label' => '同步課程商品',
        'sync_product_action_icon' => 'heroicon-o-shopping-bag',
    ],

    'video' => [
        'refresh_action_label' => '更新影片狀態',
        'refresh_action_icon' => 'heroicon-o-arrow-path',
        'relation' => 'video',
        'status_columns' => [
            'provider_status',
            'transcode_status',
            'duration',
        ],
    ],

    'upload_center' => [
        'enabled' => false,
        'view' => 'filament.partials.upload-center',
        'default_source' => 'course_unit',
        'default_strategy' => 's3_multipart_then_import',
        'action_label' => '大型影片上傳',
        'action_icon' => 'heroicon-o-arrow-up-tray',
        'concurrency' => env('COURSE_UPLOAD_CENTER_CONCURRENCY', 2),
        's3_part_size' => env('COURSE_UPLOAD_CENTER_S3_PART_SIZE', 8 * 1024 * 1024),
        'sync_interval_ms' => env('COURSE_UPLOAD_CENTER_SYNC_INTERVAL_MS', 10000),
        'required_routes' => [
            'admin.upload-center.videos.index',
            'admin.upload-center.videos.store',
            'admin.upload-center.videos.progress',
            'admin.upload-center.videos.complete',
            'admin.upload-center.videos.cancel',
            'admin.upload-center.videos.fail',
            'admin.upload-center.videos.retry',
            'admin.upload-center.s3.multipart.create',
            'admin.upload-center.s3.multipart.parts',
            'admin.upload-center.s3.multipart.sign-part',
            'admin.upload-center.s3.multipart.complete',
            'admin.upload-center.s3.multipart.abort',
        ],
        'refresh_job' => null,
    ],

];
