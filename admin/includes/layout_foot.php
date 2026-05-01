
</div><!-- /main-content -->
</div><!-- /main-wrap -->

<script>
// 側邊欄切換（行動版）
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}
// 點擊主內容區關閉側邊欄
document.querySelector('.main-wrap').addEventListener('click', function() {
  if (window.innerWidth < 768) {
    document.getElementById('sidebar').classList.remove('open');
  }
});

// 確認刪除
function confirmDelete(url, name) {
  if (confirm('確定要刪除「' + name + '」嗎？\n此動作無法還原。')) {
    window.location.href = url;
  }
}

// 圖片預覽
function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  if (!preview) return;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}

// ── Quill WYSIWYG 編輯器初始化 ────────────────────
// 任何 textarea 加 class="wysiwyg" 就會自動轉成富文字編輯器
function initQuillEditors() {
  if (typeof Quill === 'undefined') return;
  document.querySelectorAll('textarea.wysiwyg').forEach(textarea => {
    if (textarea.dataset.quillInit) return;
    textarea.dataset.quillInit = '1';

    // 建立 wrapper 跟 editor div
    const wrapper = document.createElement('div');
    wrapper.style.background = '#fff';
    const editorDiv = document.createElement('div');
    editorDiv.innerHTML = textarea.value;
    editorDiv.style.minHeight = '300px';
    wrapper.appendChild(editorDiv);

    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);

    const quill = new Quill(editorDiv, {
      theme: 'snow',
      placeholder: '輸入內容...',
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, 4, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ color: [] }, { background: [] }],
          [{ align: [] }],
          [{ list: 'ordered' }, { list: 'bullet' }],
          [{ indent: '-1' }, { indent: '+1' }],
          ['blockquote', 'code-block'],
          ['link', 'image', 'video'],
          ['clean']
        ]
      }
    });

    // 同步內容到 textarea（每次改動）
    quill.on('text-change', () => {
      textarea.value = quill.root.innerHTML;
    });

    // 表單送出前再同步一次
    const form = textarea.closest('form');
    if (form) form.addEventListener('submit', () => {
      textarea.value = quill.root.innerHTML;
    });

    // 自訂圖片上傳（透過我們的 upload_handler.php）
    quill.getModule('toolbar').addHandler('image', function() {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/jpeg,image/png,image/gif,image/webp';
      input.click();
      input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { alert('檔案不能超過 5MB'); return; }
        const fd = new FormData();
        fd.append('file', file);
        try {
          const res = await fetch('<?= BASE_URL ?>/admin/upload_handler.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });
          const data = await res.json();
          if (data.location) {
            const range = quill.getSelection(true) || { index: quill.getLength() };
            quill.insertEmbed(range.index, 'image', data.location);
            quill.setSelection(range.index + 1);
          } else {
            alert('上傳失敗：' + (data.error || '未知錯誤'));
          }
        } catch (err) {
          alert('上傳失敗：' + err.message);
        }
      };
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initQuillEditors);
} else {
  initQuillEditors();
}
</script>

<style>
/* Quill 編輯器外觀微調 */
.ql-toolbar.ql-snow { border-radius: 7px 7px 0 0; background: #f8fafc; border-color: var(--border); }
.ql-container.ql-snow { border-radius: 0 0 7px 7px; border-color: var(--border); font-family: 'Noto Sans TC', sans-serif; font-size: 15px; }
.ql-editor { min-height: 300px; line-height: 1.7; }
.ql-editor img { max-width: 100%; height: auto; }
.ql-editor h1, .ql-editor h2, .ql-editor h3, .ql-editor h4 { font-weight: 700; margin: 14px 0 8px; }
</style>
</body>
</html>
