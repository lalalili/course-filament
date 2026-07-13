@if (config('course-filament.upload_center.enabled', true))
    <div
        id="upload-center"
        wire:ignore
        data-endpoints='@json([
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
        ])'
        data-config='@json([
            'concurrency' => (int) config('course-filament.upload_center.concurrency', 2),
            'partSize' => (int) config('course-filament.upload_center.s3_part_size', 8 * 1024 * 1024),
            'syncInterval' => (int) config('course-filament.upload_center.sync_interval_ms', 10000),
            'provider' => config('course-core.default_video_platform', 'vimeo'),
        ])'
    >
        <div class="flex items-center justify-between gap-3 rounded-t-xl border border-b-0 border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
            <div><strong>上傳中心</strong><p class="mt-1 text-xs text-gray-500">大型影片、多檔佇列與續傳</p></div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700" data-upload-count>0</span>
        </div>
        <label data-upload-dropzone class="flex min-h-40 cursor-pointer flex-col items-center justify-center gap-2 border border-dashed border-gray-300 bg-gray-50 px-6 text-center transition hover:bg-emerald-50 dark:border-white/15 dark:bg-white/5">
            <input data-upload-input class="sr-only" type="file" multiple accept="video/*" />
            <span class="text-sm font-semibold">拖曳影片到這裡，或點擊選取</span>
            <span class="text-xs text-gray-500">影片會直接分段上傳至私有暫存空間。</span>
        </label>
        <div class="overflow-hidden rounded-b-xl border border-t-0 border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900" data-upload-list></div>
    </div>

    @vite('packages/course-filament/resources/js/shared-upload-center.js')
@endif
