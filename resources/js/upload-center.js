import Uppy from '@uppy/core';
import AwsS3 from '@uppy/aws-s3';
import Dashboard from '@uppy/dashboard';
import GoldenRetriever from '@uppy/golden-retriever';
import zhTW from '@uppy/locales/lib/zh_TW';
import '@uppy/core/css/style.min.css';
import '@uppy/dashboard/css/style.min.css';

const root = document.getElementById('upload-center');

if (root && !window.__uploadCenter) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  const endpoints = JSON.parse(root.dataset.endpoints ?? '{}');
  const config = JSON.parse(root.dataset.config ?? '{}');

  const DISMISSED_KEY = 'auc_dismissed_sessions';
  const loadDismissed = () => {
    try {
      const raw = localStorage.getItem(DISMISSED_KEY);
      return new Set(raw ? JSON.parse(raw) : []);
    } catch {
      return new Set();
    }
  };
  const saveDismissed = (set) => {
    try {
      localStorage.setItem(DISMISSED_KEY, JSON.stringify([...set]));
    } catch {}
  };

  const state = {
    open: false,
    activeContext: {},
    sessions: new Map(),
    fileSessions: new Map(),
    progressSyncAt: new Map(),
    liveSessions: new Set(), // Uppy 正在上傳中的 session id
    pendingVideoInjections: [], // { courseChapterId, videoId } 待注回課程表單
    dismissedSessions: loadDismissed(), // 已 dismiss 的 session id，sync 時跳過（localStorage 持久化）
  };

  const request = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        ...(options.headers ?? {}),
      },
      ...options,
      body: options.body && typeof options.body !== 'string' ? JSON.stringify(options.body) : options.body,
    });

    if (!response.ok) {
      const payload = await response.json().catch(() => ({}));
      throw new Error(payload.message ?? payload.error ?? '上傳中心請求失敗。');
    }

    return response.json();
  };

  // 從 sessionIndex endpoint 推導 admin 路徑前綴（e.g. /admin/upload-center/videos → /admin）
  const adminBase = (() => {
    const s = String(endpoints.sessionIndex ?? endpoints.sessions ?? '');
    const m = s.match(/^(\/[^/]+)/);
    return m ? m[1] : '/admin';
  })();

  const shell = document.createElement('div');
  shell.className = 'auc-shell';
  shell.innerHTML = `
    <button class="auc-trigger" type="button" aria-label="開啟上傳中心">
      <span class="auc-trigger-icon">⇧</span>
      <span class="auc-trigger-count" hidden>0</span>
    </button>
    <section class="auc-panel" aria-label="上傳中心" hidden>
      <header class="auc-header">
        <div>
          <strong>上傳中心</strong>
          <span class="auc-subtitle">大型影片上傳佇列</span>
        </div>
        <button class="auc-close" type="button" aria-label="關閉">×</button>
      </header>
      <div class="auc-dashboard"></div>
      <div class="auc-list"></div>
    </section>
  `;
  root.append(shell);

  const trigger = shell.querySelector('.auc-trigger');
  const panel = shell.querySelector('.auc-panel');
  const close = shell.querySelector('.auc-close');
  const list = shell.querySelector('.auc-list');
  const count = shell.querySelector('.auc-trigger-count');

  const dismissSession = (sessionId) => {
    state.dismissedSessions.add(sessionId);
    saveDismissed(state.dismissedSessions);
    state.sessions.delete(sessionId);
    renderSessions();
  };

  const scheduleAutoDismiss = (sessionId, triggerStatus) => {
    setTimeout(() => {
      const session = state.sessions.get(sessionId);
      if (session?.status === triggerStatus) {
        dismissSession(sessionId);
      }
    }, 3000);
  };

  const setOpen = (open) => {
    state.open = open;
    panel.hidden = !open;

    if (!open && state.pendingVideoInjections.length > 0) {
      window.Livewire?.dispatch('upload-center-closed', { videos: state.pendingVideoInjections });
      state.pendingVideoInjections = [];
    }
  };

  const renderSessions = () => {
    const sessions = Array.from(state.sessions.values());
    const activeCount = sessions.filter((session) => ['created', 'uploading'].includes(session.status)).length;

    count.hidden = activeCount === 0;
    count.textContent = activeCount.toString();

    const successStatuses = ['uploaded', 'importing', 'processing', 'ready'];

    list.innerHTML = sessions
      .slice(0, 8)
      .map((session) => {
        const isSuccess = successStatuses.includes(session.status);
        const progressWidth = isSuccess ? 100 : Number(session.progress ?? 0);

        const courseHint = buildCourseHint(session, adminBase);

        return `
        <article class="auc-item" data-session-id="${escapeHtml(session.id)}" data-status="${escapeHtml(session.status)}">
          <div class="auc-item-main">
            <strong>${escapeHtml(session.title ?? session.name ?? '未命名影片')}</strong>
            <span>${escapeHtml(statusLine(session))}</span>
            <div class="auc-progress" aria-hidden="true"><span style="width: ${progressWidth}%"></span></div>
            ${courseHint}
          </div>
          <div class="auc-item-side">
            <div class="auc-item-meta">${escapeHtml(formatBytes(session.size ?? 0))}</div>
            ${sessionActions(session)}
          </div>
        </article>`;
      })
      .join('');
  };

  const syncSessions = async () => {
    const payload = await request(endpoints.sessionIndex);

    for (const session of payload.data ?? []) {
      if (state.dismissedSessions.has(session.id)) {
        continue;
      }

      if (state.liveSessions.has(session.id)) {
        // 上傳中：保留本地即時進度，僅合併其他欄位
        const local = state.sessions.get(session.id);
        state.sessions.set(session.id, {
          ...session,
          status: local?.status ?? session.status,
          progress: local?.progress ?? session.progress,
          bytes_uploaded: local?.bytes_uploaded ?? session.bytes_uploaded,
        });
      } else {
        const prev = state.sessions.get(session.id);
        state.sessions.set(session.id, session);

        // 首次偵測到 ready：3 秒後自動 dismiss
        if (session.status === 'ready' && prev?.status !== 'ready') {
          scheduleAutoDismiss(session.id, 'ready');
        }
      }
    }

    renderSessions();
  };

  const updateProgress = async (session, bytesUploaded) => {
    const now = Date.now();
    const lastSyncedAt = state.progressSyncAt.get(session.id) ?? 0;

    if (now - lastSyncedAt < 1500 && bytesUploaded < Number(session.size ?? 0)) {
      return;
    }

    state.progressSyncAt.set(session.id, now);

    const updated = await request(endpoints.sessionProgress.replace('__SESSION__', session.id), {
      method: 'PATCH',
      body: { bytes_uploaded: bytesUploaded },
    });

    if (state.liveSessions.has(updated.id)) {
      // 上傳中：保留本地即時進度，不讓伺服器 floored 值蓋回
      const local = state.sessions.get(updated.id);
      state.sessions.set(updated.id, {
        ...updated,
        status: local?.status ?? updated.status,
        progress: local?.progress ?? updated.progress,
        bytes_uploaded: local?.bytes_uploaded ?? updated.bytes_uploaded,
      });
    } else {
      state.sessions.set(updated.id, updated);
    }

    renderSessions();
  };

  const isSessionReusable = (session) =>
    session && !['cancelled', 'failed'].includes(session.status);

  const createSession = async (file) => {
    const cached = state.fileSessions.get(file.id);
    if (isSessionReusable(cached)) {
      return cached;
    }

    const restored = file.meta.uploadSession;
    if (isSessionReusable(restored)) {
      state.fileSessions.set(file.id, restored);
      return restored;
    }

    // GoldenRetriever 可能還原了已取消/失敗的舊 session，建立新 session
    uppy.setFileMeta(file.id, { uploadSession: null });

    const session = await request(endpoints.sessions, {
      method: 'POST',
      body: {
        name: file.name,
        size: file.size,
        type: file.type,
        title: file.meta.title ?? file.name?.replace(/\.[^.]+$/, ''),
        provider: state.activeContext.provider ?? config.provider,
        folder_id: state.activeContext.folder_id ?? null,
        course_id: state.activeContext.course_id ?? null,
        course_chapter_id: state.activeContext.course_chapter_id ?? null,
        source: state.activeContext.source ?? 'upload_center',
        strategy: state.activeContext.strategy ?? 's3_multipart_then_import',
      },
    });

    uppy.setFileMeta(file.id, { uploadSession: session });
    state.fileSessions.set(file.id, session);
    state.sessions.set(session.id, session);

    if (session.course_chapter_id && session.video_id && state.activeContext.source === 'course_unit') {
      state.pendingVideoInjections.push({
        courseChapterId: session.course_chapter_id,
        videoId: session.video_id,
      });
    }

    renderSessions();

    return session;
  };

  const uppy = new Uppy({
    id: 'upload-center',
    autoProceed: false,
    locale: zhTW,
    restrictions: {
      allowedFileTypes: ['video/*'],
    },
  })
    .use(GoldenRetriever, {
      serviceWorker: false,
    })
    .use(Dashboard, {
      target: shell.querySelector('.auc-dashboard'),
      inline: true,
      height: 360,
      width: '100%',
      proudlyDisplayPoweredByUppy: false,
      note: '支援大型影片、多檔佇列與續傳',
    })
    .use(AwsS3, {
      shouldUseMultipart: () => true,
      limit: Number(config.concurrency ?? 2),
      getChunkSize: () => Number(config.partSize ?? 8 * 1024 * 1024),
      async createMultipartUpload(file) {
        const session = await createSession(file);

        const created = await request(endpoints.multipartCreate, {
          method: 'POST',
          body: { upload_session_id: session.id },
        });

        if (created.session) {
          state.sessions.set(created.session.id, created.session);
          state.fileSessions.set(file.id, created.session);
          uppy.setFileMeta(file.id, { uploadSession: created.session });
          renderSessions();
        }

        return created;
      },
      async listParts(file) {
        const session = await createSession(file);

        return request(endpoints.multipartSignPart.replace('__SESSION__', session.id).replace('/sign-part', '/parts'));
      },
      async signPart(file, partData) {
        const session = await createSession(file);

        return request(endpoints.multipartSignPart.replace('__SESSION__', session.id), {
          method: 'POST',
          body: { partNumber: partData.partNumber },
        });
      },
      async completeMultipartUpload(file, data) {
        const session = await createSession(file);
        const completed = await request(endpoints.multipartComplete.replace('__SESSION__', session.id), {
          method: 'POST',
          body: { parts: data.parts },
        });

        if (completed.session) {
          state.sessions.set(completed.session.id, completed.session);
          renderSessions();
        }

        return { location: completed.location };
      },
      async abortMultipartUpload(file) {
        const session = file.meta.uploadSession;

        if (!session) {
          return;
        }

        await request(endpoints.multipartAbort.replace('__SESSION__', session.id), {
          method: 'DELETE',
        });
      },
    });

  uppy.on('file-added', (file) => {
    uppy.setFileMeta(file.id, {
      ...state.activeContext,
      title: file.name?.replace(/\.[^.]+$/, ''),
    });
    setOpen(true);
  });

  uppy.on('upload-progress', (file, progress) => {
    const session = file?.meta?.uploadSession;

    if (!session) {
      return;
    }

    state.liveSessions.add(session.id);
    state.sessions.set(session.id, {
      ...session,
      status: 'uploading',
      bytes_uploaded: progress.bytesUploaded,
      progress: progress.bytesTotal ? Math.floor((progress.bytesUploaded / progress.bytesTotal) * 100) : session.progress,
    });
    renderSessions();
    updateProgress(session, progress.bytesUploaded).catch(() => {});
  });

  uppy.on('upload-success', (file) => {
    const session = file?.meta?.uploadSession;

    if (session) {
      state.liveSessions.delete(session.id);
    }
  });

  uppy.on('upload-error', (file, error) => {
    const session = file?.meta?.uploadSession;

    if (!session) {
      return;
    }

    state.liveSessions.delete(session.id);

    request(endpoints.sessionFail.replace('__SESSION__', session.id), {
      method: 'POST',
      body: { error_message: error.message },
    })
      .then((updated) => {
        state.sessions.set(updated.id, updated);
        renderSessions();
      })
      .catch(() => {
        state.sessions.set(session.id, {
          ...session,
          status: 'failed',
          error_message: error.message,
        });
        renderSessions();
      });
  });

  trigger.addEventListener('click', () => setOpen(!state.open));
  close.addEventListener('click', () => setOpen(false));
  list.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }

    const button = event.target.closest('[data-auc-action]');

    if (!button) {
      return;
    }

    const sessionId = button.closest('[data-session-id]')?.dataset.sessionId;
    const session = state.sessions.get(Number(sessionId));

    if (!session) {
      return;
    }

    const action = button.dataset.aucAction;

    if (action === 'dismiss') {
      dismissSession(session.id);
      return;
    }

    button.disabled = true;

    const endpoint = action === 'retry'
      ? endpoints.sessionRetry
      : endpoints.sessionCancel;

    request(endpoint.replace('__SESSION__', session.id), { method: 'POST' })
      .then((updated) => {
        state.liveSessions.delete(session.id);
        state.sessions.set(updated.id, updated);
        renderSessions();
        if (updated.status === 'cancelled') {
          scheduleAutoDismiss(updated.id, 'cancelled');
        }
      })
      .catch((error) => {
        state.sessions.set(session.id, {
          ...session,
          error_message: error.message,
        });
        renderSessions();
      });
  });

  // 課程單元選定影片後，dismiss 上傳中心中對應的 session
  window.addEventListener('upload-center-video-bound', (event) => {
    const videoId = event.detail?.videoId;

    if (!videoId) {
      return;
    }

    for (const [sessionId, session] of state.sessions) {
      if (session.video_id === videoId) {
        dismissSession(sessionId);
      }
    }
  });

  window.addEventListener('upload-center:open', (event) => {
    state.activeContext = event.detail ?? {};
    setOpen(true);
  });

  window.__uploadCenter = {
    open(context = {}) {
      state.activeContext = context;
      setOpen(true);
    },
    uppy,
  };

  // wire:navigate (SPA mode) 用 Alpine morph 替換 body，不尊重 wire:ignore，
  // 導致 auc-shell 被移除。每次導覽完成後重新掛回新的 #upload-center。
  document.addEventListener('livewire:navigated', () => {
    const newRoot = document.getElementById('upload-center');
    if (newRoot && !newRoot.contains(shell)) {
      newRoot.append(shell);
    }
  });

  syncSessions().catch(() => {});
  window.setInterval(() => {
    syncSessions().catch(() => {});
  }, Number(config.syncInterval ?? 10000));

  const style = document.createElement('style');
  style.textContent = `
    .auc-shell { position: fixed; right: 1rem; bottom: 1rem; z-index: 9999; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .auc-trigger { width: 3rem; height: 3rem; border-radius: 999px; border: 1px solid color-mix(in oklab, CanvasText 16%, transparent); background: Canvas; color: CanvasText; box-shadow: 0 10px 30px rgb(0 0 0 / 18%); display: grid; place-items: center; position: relative; cursor: pointer; }
    .auc-trigger-icon { font-size: 1.25rem; line-height: 1; }
    .auc-trigger-count { position: absolute; top: -0.35rem; right: -0.35rem; min-width: 1.25rem; height: 1.25rem; padding: 0 .3rem; border-radius: 999px; background: #dc2626; color: white; font-size: .75rem; line-height: 1.25rem; }
    .auc-panel { width: min(42rem, calc(100vw - 2rem)); max-height: min(42rem, calc(100vh - 5rem)); margin-bottom: .75rem; border: 1px solid color-mix(in oklab, CanvasText 14%, transparent); border-radius: .5rem; background: Canvas; color: CanvasText; box-shadow: 0 24px 60px rgb(0 0 0 / 24%); overflow: hidden; }
    .auc-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .875rem 1rem; border-bottom: 1px solid color-mix(in oklab, CanvasText 10%, transparent); }
    .auc-subtitle { display: block; margin-top: .125rem; font-size: .75rem; color: color-mix(in oklab, CanvasText 62%, transparent); }
    .auc-close { border: 0; background: transparent; color: currentColor; font-size: 1.5rem; cursor: pointer; line-height: 1; }
    .auc-dashboard .uppy-Dashboard-inner { border: 0; border-radius: 0; }
    .auc-list { max-height: 12rem; overflow: auto; border-top: 1px solid color-mix(in oklab, CanvasText 10%, transparent); }
    .auc-item { display: flex; justify-content: space-between; gap: .75rem; padding: .625rem 1rem; border-bottom: 1px solid color-mix(in oklab, CanvasText 8%, transparent); }
    .auc-item-main { min-width: 0; display: grid; gap: .125rem; }
    .auc-item-main strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .875rem; }
    .auc-item-main span, .auc-item-meta { color: color-mix(in oklab, CanvasText 62%, transparent); font-size: .75rem; }
    .auc-item-side { display: grid; gap: .375rem; justify-items: end; align-content: start; flex: none; }
    .auc-progress { height: .25rem; width: 12rem; max-width: 100%; overflow: hidden; border-radius: 999px; background: color-mix(in oklab, CanvasText 10%, transparent); }
    .auc-progress span { display: block; height: 100%; border-radius: inherit; background: #2563eb; transition: width .2s ease; }
    [data-status="uploaded"] .auc-progress span, [data-status="importing"] .auc-progress span, [data-status="processing"] .auc-progress span, [data-status="ready"] .auc-progress span { background: #16a34a; }
    .auc-action { border: 1px solid color-mix(in oklab, CanvasText 16%, transparent); border-radius: .375rem; background: transparent; color: currentColor; padding: .2rem .45rem; font-size: .75rem; line-height: 1.1; cursor: pointer; }
    .auc-action:disabled { opacity: .5; cursor: wait; }
    .auc-action--dismiss { border-color: transparent; padding: .2rem .4rem; color: color-mix(in oklab, CanvasText 45%, transparent); }
    .auc-action--dismiss:hover { color: currentColor; border-color: color-mix(in oklab, CanvasText 16%, transparent); }
    .auc-course-hint { margin: .25rem 0 0; font-size: .7rem; color: color-mix(in oklab, CanvasText 65%, transparent); }
    .auc-course-link { color: #2563eb; text-decoration: underline; }
    @media (max-width: 640px) { .auc-shell { left: .75rem; right: .75rem; } .auc-panel { width: 100%; } }
  `;
  document.head.append(style);
}

function formatBytes(value) {
  if (!Number.isFinite(Number(value)) || Number(value) <= 0) {
    return '0 B';
  }

  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let size = Number(value);
  let index = 0;

  while (size >= 1024 && index < units.length - 1) {
    size /= 1024;
    index += 1;
  }

  return `${size.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

function statusLine(session) {
  if (['uploaded', 'importing', 'processing'].includes(session.status)) {
    return `${providerLabel(session.provider)} · ✓ 上傳成功 · 影片處理中，完成後會自動上架，可關閉視窗`;
  }

  const statusLabels = {
    created: '等待上傳',
    uploading: `上傳中 ${Number(session.progress ?? 0)}%`,
    ready: '✓ 已完成',
    failed: '失敗',
    cancelled: '已取消',
  };

  return [
    providerLabel(session.provider),
    statusLabels[session.status] ?? session.status,
    session.error_message,
  ].filter(Boolean).join(' · ');
}

function sessionActions(session) {
  if (session.status === 'failed' || session.status === 'cancelled') {
    return `
      <button class="auc-action" type="button" data-auc-action="retry">重試</button>
      <button class="auc-action auc-action--dismiss" type="button" data-auc-action="dismiss" aria-label="移除">×</button>
    `;
  }

  if (['created', 'uploading'].includes(session.status)) {
    return '<button class="auc-action" type="button" data-auc-action="cancel">取消</button>';
  }

  if (['uploaded', 'importing', 'processing'].includes(session.status)) {
    return `
      <button class="auc-action" type="button" data-auc-action="cancel">取消</button>
      <button class="auc-action auc-action--dismiss" type="button" data-auc-action="dismiss" aria-label="移除">×</button>
    `;
  }

  return '';
}

function buildCourseHint(session, adminBase) {
  if (!session.course_id || session.course_chapter_id) {
    return '';
  }

  if (!['uploaded', 'importing', 'processing', 'ready'].includes(session.status)) {
    return '';
  }

  const url = `${adminBase}/courses/${encodeURIComponent(session.course_id)}/edit`;

  return `<p class="auc-course-hint">影片未綁定至特定單元，請前往 <a class="auc-course-link" href="${escapeHtml(url)}">課程編輯頁</a> 手動選定。</p>`;
}

function providerLabel(provider) {
  const labels = {
    vimeo: 'Vimeo',
    cloudflare_stream: 'Cloudflare Stream',
    vdocipher: 'VdoCipher',
  };

  return labels[provider] ?? provider ?? '未指定平台';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
