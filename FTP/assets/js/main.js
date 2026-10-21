document.addEventListener('DOMContentLoaded', () => {
    function attachCopyEvent(btn) {
        btn.addEventListener('click', (e) => {
            const url = e.target.getAttribute('data-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    const origText = e.target.textContent;
                    e.target.textContent = 'コピー完了';
                    e.target.classList.add('btn-primary');
                    e.target.classList.remove('btn-outline');
                    setTimeout(() => {
                        e.target.textContent = origText;
                        e.target.classList.remove('btn-primary');
                        e.target.classList.add('btn-outline');
                    }, 2000);
                });
            } else {
                // Fallback
                const input = e.target.previousElementSibling;
                input.select();
                try {
                    document.execCommand('copy');
                    alert('URLをコピーしました');
                } catch(err) {
                    alert('コピーに失敗しました');
                }
            }
        });
    }

    // URL コピーボタンの制御（既存一覧）
    const copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(btn => attachCopyEvent(btn));

    // HTMLエスケープヘルパー
    function escapeHTML(str) {
        return String(str).replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    // 非同期(Ajax) アップロード処理
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadResult = document.getElementById('uploadResult');

    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(uploadForm);
            
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'アップロード中...';
            uploadResult.style.display = 'none';
            uploadResult.innerHTML = '';
            uploadResult.className = 'mt-3';

            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json().catch(() => {
                throw new Error('サーバーからの応答が不正です。');
            }))
            .then(data => {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'アップロード';
                uploadResult.style.display = 'block';

                if (data.success) {
                    uploadResult.className = 'mt-3 upload-result-box';
                    uploadResult.innerHTML = `
                        <div class="mb-2"><strong style="color: #0366d6;">ファイルが正常にアップロードされました。</strong></div>
                        <div class="d-flex align-items-center mb-2">
                            <strong class="mr-2">ダウンロードURL:</strong> 
                            <a href="${data.url}" target="_blank" class="mr-2">${data.url}</a>
                            <button class="btn btn-sm btn-outline copy-result-url-btn" data-url="${data.url}" title="案内文と一緒にURLをコピー">📋 コピー</button>
                        </div>
                        <div class="mt-2 mb-2"><strong>パスワード:</strong> <span class="pw-highlight">${data.password}</span></div>
                        <small class="text-danger">※このパスワードは今後表示されません。必ずコピーして共有先に連絡してください。</small>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline" onclick="location.reload()">一覧を更新する</button>
                        </div>
                    `;
                    
                    // アップロード結果のURLコピー機能
                    const copyResultBtn = uploadResult.querySelector('.copy-result-url-btn');
                    if (copyResultBtn) {
                        copyResultBtn.addEventListener('click', () => {
                            const url = copyResultBtn.getAttribute('data-url');
                            const copyText = `以下のURLよりダウンロードをお願いします。\n${url}`;
                            
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(copyText).then(() => {
                                    const origHTML = copyResultBtn.innerHTML;
                                    copyResultBtn.innerHTML = '✅ コピー完了';
                                    copyResultBtn.classList.add('btn-primary');
                                    copyResultBtn.classList.remove('btn-outline');
                                    setTimeout(() => {
                                        copyResultBtn.innerHTML = origHTML;
                                        copyResultBtn.classList.remove('btn-primary');
                                        copyResultBtn.classList.add('btn-outline');
                                    }, 2000);
                                });
                            } else {
                                // Fallback (簡易版)
                                alert('お使いのブラウザはクリップボード操作をサポートしていません。リンクを手動でコピーしてください。');
                            }
                        });
                    }

                    // アップロード済みファイル一覧へDOMで動的追加
                    const tbody = document.querySelector('.table tbody');
                    if (tbody) {
                        // "ファイルがありません" の行があればクリア
                        if (tbody.querySelector('.text-muted')) {
                            tbody.innerHTML = '';
                        }
                        
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${escapeHTML(data.original_name)}</td>
                            <td>
                                <div class="input-group">
                                    <input type="text" value="${escapeHTML(data.url)}" readonly class="form-control url-input" style="min-width:200px;">
                                    <button type="button" class="btn btn-sm btn-outline copy-btn" data-url="${escapeHTML(data.url)}">コピー</button>
                                </div>
                            </td>
                            <td class="text-center">0</td>
                            <td>${escapeHTML(data.created_at)}</td>
                            <td>
                                <form action="delete.php" method="post" onsubmit="return confirm('このファイルを本当に削除しますか？');" style="display:inline;">
                                    <input type="hidden" name="id" value="${escapeHTML(data.id)}">
                                    <button type="submit" class="btn btn-sm btn-danger">削除</button>
                                </form>
                            </td>
                        `;
                        
                        // 先頭に追加
                        tbody.insertBefore(tr, tbody.firstChild);
                        
                        // 動的追加された要素のコピーボタンにイベントをバインド
                        const newCopyBtn = tr.querySelector('.copy-btn');
                        if(newCopyBtn) attachCopyEvent(newCopyBtn);
                    }

                    uploadForm.reset();
                } else {
                    uploadResult.className = 'mt-3 alert alert-danger';
                    uploadResult.innerHTML = `アップロード失敗: ${data.error}`;
                }
            })
            .catch(error => {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'アップロード';
                uploadResult.style.display = 'block';
                uploadResult.className = 'mt-3 alert alert-danger';
                uploadResult.innerHTML = `通信エラーが発生しました: ${error.message}`;
            });
        });
    }
});
