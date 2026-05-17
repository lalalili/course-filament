@if (config('course-filament.upload_center.enabled', true))
    <div
        id="upload-center"
        wire:ignore
        data-endpoints="{{ json_encode([
            'sessions' => route('admin.upload-center.videos.store'),
            'sessionIndex' => route('admin.upload-center.videos.index'),
            'sessionProgress' => url('/admin/upload-center/videos/__SESSION__/progress'),
            'sessionCancel' => url('/admin/upload-center/videos/__SESSION__/cancel'),
            'sessionFail' => url('/admin/upload-center/videos/__SESSION__/fail'),
            'sessionRetry' => url('/admin/upload-center/videos/__SESSION__/retry'),
            'multipartCreate' => route('admin.upload-center.s3.multipart.create'),
            'multipartSignPart' => url('/admin/upload-center/s3/multipart/__SESSION__/sign-part'),
            'multipartComplete' => url('/admin/upload-center/s3/multipart/__SESSION__/complete'),
            'multipartAbort' => url('/admin/upload-center/s3/multipart/__SESSION__'),
        ]) }}"
        data-config="{{ json_encode([
            'concurrency' => (int) config('course-filament.upload_center.concurrency', 2),
            'partSize' => (int) config('course-filament.upload_center.s3_part_size', 8 * 1024 * 1024),
            'syncInterval' => (int) config('course-filament.upload_center.sync_interval_ms', 10000),
            'provider' => config('course-core.default_video_platform', 'vimeo'),
        ]) }}"
    ></div>

    @vite('packages/course-filament/resources/js/upload-center.js')
@endif
