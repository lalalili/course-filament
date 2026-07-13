import { createUploadCenter } from '@lalalili/filament-upload-center';

const replace = (template, value, marker = '__SESSION__') => template.replace(marker, encodeURIComponent(String(value)));

const boot = () => {
  const root = document.getElementById('upload-center');
  if (!root || root.__filamentUploadCenter) return;

  const endpoints = JSON.parse(root.dataset.endpoints ?? '{}');
  const config = JSON.parse(root.dataset.config ?? '{}');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  let activeContext = {};

  const request = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers ?? {}) },
      ...options,
      body: options.body && typeof options.body !== 'string' ? JSON.stringify(options.body) : options.body,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message ?? '上傳中心請求失敗。');
    return payload;
  };

  const center = createUploadCenter(root, {
    id: 'course-upload-center',
    allowedFileTypes: ['video/*'],
    serviceWorker: false,
    context: () => activeContext,
    adapter: {
      shouldUseMultipart: () => true,
      createSession: async (file) => {
        const session = await request(endpoints.sessions, {
          method: 'POST',
          body: { name: file.name, size: file.size, type: file.type, title: file.name.replace(/\.[^.]+$/, ''), provider: activeContext.provider ?? config.provider, folder_id: activeContext.folder_id ?? null, course_id: activeContext.course_id ?? null, course_chapter_id: activeContext.course_chapter_id ?? null, source: activeContext.source ?? 'upload_center', strategy: activeContext.strategy ?? 's3_multipart_then_import' },
        });
        return { session: { id: session.id, status: session.status, videoId: session.video_id, courseChapterId: session.course_chapter_id }, upload: session };
      },
      directParameters: () => { throw new Error('課程影片一律使用分段上傳。'); },
      createMultipart: async (session) => { await request(endpoints.multipartCreate, { method: 'POST', body: { upload_session_id: session.id } }); },
      restoreSession: async (session) => {
        const parts = await request(replace(endpoints.multipartSignPart, session.id).replace('/sign-part', '/parts'));
        return { uploadedParts: (parts.parts ?? []).map((part) => ({ partNumber: part.PartNumber, etag: part.ETag, size: part.Size })), status: 'uploading' };
      },
      signPart: async (session, partNumber) => request(replace(endpoints.multipartSignPart, session.id), { method: 'POST', body: { partNumber } }),
      complete: async (session, parts) => {
        const result = await request(replace(endpoints.multipartComplete, session.id), { method: 'POST', body: { parts: parts.map((part) => ({ PartNumber: part.partNumber, ETag: part.etag })) } });
        if (activeContext.source === 'course_unit' && result.session?.course_chapter_id && result.session?.video_id) window.Livewire?.dispatch('upload-center-closed', { videos: [{ courseChapterId: result.session.course_chapter_id, videoId: result.session.video_id }] });
      },
      cancel: async (session) => { await request(replace(endpoints.multipartAbort, session.id), { method: 'DELETE' }); },
      poll: async (session) => {
        const payload = await request(endpoints.sessionIndex);
        const fresh = (payload.data ?? []).find((item) => String(item.id) === String(session.id));
        return { status: fresh?.status === 'ready' ? 'completed' : fresh?.status === 'failed' ? 'failed' : fresh?.status === 'processing' ? 'processing' : session.status };
      },
    },
  });

  window.__uploadCenter = { open(context = {}) { activeContext = context; root.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, uppy: center.uppy };
};

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
