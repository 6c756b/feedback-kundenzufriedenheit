/* KZB – app.js | Vanilla ES2015+ */

// ── Mobile Navigation ────────────────────────────────────────

function initMobileNav() {
    const toggle  = document.getElementById('be-menu-toggle');
    const overlay = document.getElementById('be-sidebar-overlay');
    const sidebar = document.querySelector('.be-sidebar');
    if (!toggle || !sidebar) return;

    function open() {
        sidebar.classList.add('is-open');
        if (overlay) overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
        toggle.setAttribute('aria-label', 'Navigation schließen');
    }
    function close() {
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
        toggle.setAttribute('aria-label', 'Navigation öffnen');
    }

    toggle.addEventListener('click', () => sidebar.classList.contains('is-open') ? close() : open());
    if (overlay) overlay.addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    sidebar.querySelectorAll('.be-nav-link').forEach(link => link.addEventListener('click', close));
}

// ── Mobile Panel ─────────────────────────────────────────────

function initMobilePanel() {
    const btn   = document.querySelector('.be-panel-mobile-toggle');
    const panel = document.querySelector('.be-panel');
    if (!btn || !panel) return;

    panel.classList.add('is-collapsed');
    btn.setAttribute('aria-expanded', 'false');

    btn.addEventListener('click', () => {
        const collapsed = panel.classList.toggle('is-collapsed');
        btn.setAttribute('aria-expanded', String(!collapsed));
    });
}

'use strict';

// ── Hilfsfunktionen ──────────────────────────────────────────

function getCsrfToken() {
    const el = document.querySelector('input[name="csrf_token"]');
    if (el) return el.value;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function debounce(fn, ms) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), ms);
    };
}

// ── Survey: Slider colour stops (corporate palette) ──────────

const SCORE_STOPS = [
    [1.0, [134,188, 36]],
    [2.5, [184,212, 36]],
    [3.5, [230,224, 32]],
    [4.5, [240,160, 32]],
    [5.5, [232, 80, 32]],
    [6.0, [192, 57, 43]],
];

function sliderColor(val) {
    for (let i = 0; i < SCORE_STOPS.length - 1; i++) {
        const [v0,c0] = SCORE_STOPS[i], [v1,c1] = SCORE_STOPS[i+1];
        if (val <= v1) {
            const t = (val - v0) / (v1 - v0);
            return [0,1,2].map(j => Math.round(c0[j] + t*(c1[j]-c0[j]))).join(',');
        }
    }
    return SCORE_STOPS[SCORE_STOPS.length-1][1].join(',');
}

// ── Survey: Slider ───────────────────────────────────────────

function initSliders() {
    document.querySelectorAll('.survey-slider').forEach(slider => {
        const qid     = slider.dataset.qid;
        const display = document.getElementById('sv-' + qid);

        function updateDisplay() {
            const val = parseFloat(slider.value);
            if (display) {
                display.textContent = val.toFixed(1).replace('.', ',');
                const cs = `rgb(${sliderColor(val)})`;
                display.style.color = cs;
            }
            const pct = (val - 1) / 5 * 100;
            const cs  = `rgb(${sliderColor(val)})`;
            slider.style.setProperty('--tc', cs);
            slider.style.background = `linear-gradient(to right,${cs} 0%,${cs} ${pct}%,#e8e5dc ${pct}%)`;
        }

        slider.addEventListener('input', updateDisplay);
        updateDisplay();

        slider.addEventListener('change', function() {
            saveAnswer(qid, {
                answer_type:  'slider',
                slider_value: parseFloat(this.value),
                freitext_value: getFreitextValue(qid),
            });
        });
    });
}

function getFreitextValue(qid) {
    const ft = document.getElementById('ft-' + qid);
    return ft ? ft.value : null;
}

// ── Survey: Freitext ─────────────────────────────────────────

function initFreitext() {
    const debouncedSave = debounce(function(qid, val) {
        const slider = document.getElementById('slider-' + qid);
        if (slider) {
            saveAnswer(qid, {
                answer_type:   'slider',
                slider_value:  parseFloat(slider.value),
                freitext_value: val,
            });
        } else {
            saveAnswer(qid, {
                answer_type:   'freitext',
                freitext_value: val,
            });
        }
    }, 1000);

    document.querySelectorAll('.survey-freitext').forEach(ft => {
        const qid = ft.dataset.qid;
        ft.addEventListener('input', function() {
            if (this.value.trim()) {
                markQuestionAnswered(qid);
            }
            debouncedSave(qid, this.value);
        });
    });
}

// ── Survey: Nicht zutreffend ─────────────────────────────────

function initNotApplicable() {
    document.querySelectorAll('.btn-na').forEach(btn => {
        btn.addEventListener('click', function() {
            const qid   = this.dataset.qid;
            const block = document.getElementById('q-' + qid);
            const isNA  = this.classList.toggle('is-active');

            block.classList.toggle('is-not-applicable', isNA);

            // Slider + Freitext de-/aktivieren
            const slider = document.getElementById('slider-' + qid);
            const ft     = document.getElementById('ft-' + qid);
            const wrap   = block.querySelectorAll('.slider-wrap, .freitext-wrap');

            if (slider) slider.disabled = isNA;
            if (ft)     ft.disabled     = isNA;
            wrap.forEach(w => w.classList.toggle('is-disabled', isNA));

            this.textContent = isNA ? '✓ Nicht zutreffend' : 'Nicht zutreffend';

            if (isNA) {
                saveAnswer(qid, { answer_type: 'not_applicable' });
            } else {
                // NA aufheben: bestehenden Slider-Wert senden
                const s = document.getElementById('slider-' + qid);
                if (s) {
                    saveAnswer(qid, {
                        answer_type:  'slider',
                        slider_value: parseFloat(s.value),
                        freitext_value: getFreitextValue(qid),
                    });
                }
            }
        });
    });
}

// ── Survey: AJAX Antwort speichern ───────────────────────────

function saveAnswer(qid, data) {
    const form = document.getElementById('survey-form');
    if (!form) return;

    const code = form.dataset.code;
    data.question_id = parseInt(qid, 10);

    fetch('/api/survey/' + code + '/answer', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            markQuestionAnswered(qid);
            if (res.progress) {
                updateProgress(res.progress.answered, res.progress.total);
            }
        }
    })
    .catch(() => {});
}

function updateProgress(answered, total) {
    const bar   = document.getElementById('progress-bar');
    const count = document.getElementById('progress-count');
    const pctEl = document.getElementById('progress-pct');
    const hint  = document.getElementById('submit-hint');
    const form  = document.getElementById('survey-form');
    const pct   = total > 0 ? Math.round(answered / total * 100) : 0;

    if (bar)   bar.style.width = pct + '%';
    if (count) count.textContent = answered + ' / ' + total;
    if (pctEl) pctEl.textContent = pct + ' %';
    if (form)  form.dataset.answered = answered;

    if (hint) {
        const remaining = total - answered;
        hint.textContent = remaining > 0 ? remaining + ' Frage(n) noch offen - Sie können trotzdem abschließen.' : '';
    }
}

function markQuestionAnswered(qid) {
    const block = document.getElementById('q-' + qid);
    if (block) block.classList.add('is-answered');
}

// ── Survey: Abschluss ────────────────────────────────────────

function initComplete() {
    const btn          = document.getElementById('complete-btn');
    const confirmModal = document.getElementById('confirm-modal');
    const confirmBody  = document.getElementById('confirm-modal-body');
    const confirmBtn   = document.getElementById('confirm-complete');
    const cancelBtn    = document.getElementById('cancel-complete');
    const refModal     = document.getElementById('reference-modal');
    const refYes       = document.getElementById('ref-yes');
    const refNo        = document.getElementById('ref-no');
    const form         = document.getElementById('survey-form');

    if (!btn || !confirmModal) return;

    btn.addEventListener('click', () => {
        const total    = parseInt(form ? form.dataset.total : '0', 10);
        const answered = parseInt(form ? form.dataset.answered : '0', 10);
        const remaining = total - answered;

        if (confirmBody) {
            if (remaining > 0) {
                confirmBody.innerHTML = '<p><strong>Achtung:</strong> Es sind noch ' + remaining + ' Frage(n) offen. Möchten Sie die Befragung trotzdem jetzt abschließen? Eine nachträgliche Änderung ist nicht möglich.</p>';
            } else {
                confirmBody.innerHTML = '<p>Sind Sie sicher, dass Sie die Befragung jetzt abschließen möchten? Eine nachträgliche Änderung ist nicht möglich.</p>';
            }
        }
        confirmModal.hidden = false;
    });
    if (cancelBtn) cancelBtn.addEventListener('click', () => { confirmModal.hidden = true; });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            confirmModal.hidden = true;
            const code = form ? form.dataset.code : '';

            fetch('/api/survey/' + code + '/complete', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken(),
                },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) return;
                if (res.reference_requested && refModal) {
                    refModal.hidden = false;
                } else {
                    window.location.href = '/danke';
                }
            });
        });
    }

    function sendReference(granted, code) {
        fetch('/api/survey/' + code + '/reference', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: JSON.stringify({ granted }),
        })
        .finally(() => { window.location.href = '/danke'; });
    }

    if (refYes) refYes.addEventListener('click', () => sendReference(true,  form ? form.dataset.code : ''));
    if (refNo)  refNo.addEventListener('click',  () => sendReference(false, form ? form.dataset.code : ''));
}

// ── Backend: Gelesen/Ungelesen Toggle (AJAX) ─────────────────

function initMarkReadToggle() {
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-mark-read], [data-mark-unread]');
        if (!btn) return;

        const isMarkRead = 'markRead' in btn.dataset;
        const id         = isMarkRead ? btn.dataset.markRead : btn.dataset.markUnread;
        const action     = isMarkRead ? 'gelesen' : 'ungelesen';

        btn.disabled = true;
        btn.classList.add('is-active');

        fetch('/backend/auswertung/' + id + '/' + action, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: 'csrf_token=' + encodeURIComponent(getCsrfToken()),
        })
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                btn.disabled = false;
                btn.classList.remove('is-active');
                return;
            }

            const row = btn.closest('tr');
            if (!row) return;

            // Badge umschalten
            const badge = row.querySelector('.badge-open, .badge-unread');
            if (badge) {
                badge.className   = isMarkRead ? 'badge badge-open' : 'badge badge-unread';
                badge.textContent = isMarkRead ? 'Gelesen'          : 'Ungelesen';
            }

            // Zeilen-Highlight
            row.classList.toggle('row-unread', !isMarkRead);

            // Buttons tauschen: geklickten ausblenden, anderen einblenden
            const btnRead   = row.querySelector('[data-mark-read]');
            const btnUnread = row.querySelector('[data-mark-unread]');
            if (btnRead)   { btnRead.hidden   = isMarkRead;  btnRead.disabled   = false; btnRead.classList.remove('is-active'); }
            if (btnUnread) { btnUnread.hidden = !isMarkRead; btnUnread.disabled = false; btnUnread.classList.remove('is-active'); }

            // Detail-Button data-survey-read synchronisieren
            const detailBtn = row.querySelector('[data-detail-id]');
            if (detailBtn) detailBtn.dataset.surveyRead = isMarkRead ? '1' : '0';
        })
        .catch(() => {
            btn.disabled = false;
            btn.classList.remove('is-active');
        });
    });
}

// ── Backend: Outlook-Modal (Quill WYSIWYG) ──────────────────

function initOutlookModal() {
    const modal = document.getElementById('outlook-modal');
    if (!modal) return;

    let quill = null;
    let quillReady = false;
    const editorEl  = document.getElementById('modal-body-editor');
    const fallbackEl = document.getElementById('modal-body-fallback');

    function ensureQuill() {
        if (quillReady) return;
        quillReady = true;
        if (editorEl && typeof Quill !== 'undefined') {
            quill = new Quill(editorEl, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ size: ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline'],
                        [{ color: [] }, { background: [] }],
                        [{ align: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image'],
                        ['clean'],
                    ],
                },
            });
        } else {
            if (editorEl)   editorEl.style.display   = 'none';
            if (fallbackEl) fallbackEl.style.display  = 'block';
        }
    }

    function setBody(html) {
        if (quill) {
            quill.root.innerHTML = html;
        } else if (fallbackEl) {
            fallbackEl.value = html;
        }
    }

    function getBody() {
        if (quill)      return quill.root.innerHTML;
        if (fallbackEl) return fallbackEl.value;
        return '';
    }

    // Vor dem Absenden HTML aus dem Editor in das hidden-Feld kopieren
    const form = document.getElementById('email-send-form');
    if (form) {
        form.addEventListener('submit', () => {
            const hidden = document.getElementById('modal-body-hidden');
            if (hidden) hidden.value = getBody();
        });
    }

    const sigSelect   = document.getElementById('modal-signature');
    const sigPreview  = document.getElementById('modal-sig-preview');
    const sigContent  = document.getElementById('modal-sig-content');
    const sigData     = window.__signatures || {};

    function updateSigPreview() {
        const slug = sigSelect ? sigSelect.value : '';
        if (slug && sigData[slug] !== undefined) {
            if (sigContent) sigContent.innerHTML = sigData[slug];
            if (sigPreview) sigPreview.hidden = false;
        } else {
            if (sigPreview) sigPreview.hidden = true;
            if (sigContent) sigContent.innerHTML = '';
        }
    }

    if (sigSelect) sigSelect.addEventListener('change', updateSigPreview);

    document.querySelectorAll('[data-outlook-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.outlookId;
            modal.hidden = false;
            ensureQuill();

            const setText = (elId, val) => {
                const el = document.getElementById(elId);
                if (el) el.textContent = val;
            };
            const subjectEl = document.getElementById('modal-subject');

            setText('modal-from', 'Lade…');
            setText('modal-to',   'Lade…');
            if (subjectEl) subjectEl.value = '';
            setBody('<p><em>Lade…</em></p>');
            updateSigPreview();

            fetch('/backend/befragungen/' + id + '/outlook')
            .then(r => r.json())
            .then(res => {
                setText('modal-from', res.from    || '');
                setText('modal-to',   res.to      || '');
                if (subjectEl) subjectEl.value = res.subject || '';
                setBody(res.body || '');
            })
            .catch(() => setText('modal-from', 'Fehler beim Laden.'));
        });
    });

    const copyBtn = document.getElementById('copy-outlook');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const sigHtml = (sigContent && sigPreview && !sigPreview.hidden) ? sigContent.innerHTML : '';
            const html    = getBody() + sigHtml;
            const plain = html.replace(/<[^>]+>/g, '')
                              .replace(/&nbsp;/g, ' ')
                              .replace(/&amp;/g, '&')
                              .replace(/&lt;/g, '<')
                              .replace(/&gt;/g, '>');
            const confirm = () => {
                const orig = copyBtn.textContent;
                copyBtn.textContent = 'Kopiert!';
                setTimeout(() => { copyBtn.textContent = orig; }, 2000);
            };
            navigator.clipboard.write([
                new ClipboardItem({
                    'text/html':  new Blob([html],  { type: 'text/html'  }),
                    'text/plain': new Blob([plain], { type: 'text/plain' }),
                }),
            ]).then(confirm).catch(() => navigator.clipboard.writeText(html).then(confirm));
        });
    }

    modal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', () => { modal.hidden = true; });
    });
}

// ── Backend: Detail-Modal Auswertung ─────────────────────────

function initDetailModal() {
    const modal = document.getElementById('detail-modal');
    const body  = document.getElementById('detail-modal-body');
    const title = document.getElementById('detail-modal-title');

    if (!modal) return;

    const footer = document.getElementById('detail-modal-footer');

    document.querySelectorAll('[data-detail-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id         = btn.dataset.detailId;
            const isRead     = btn.dataset.surveyRead === '1';
            const surveyStatus = btn.dataset.surveyStatus || '';
            modal.hidden = false;
            if (body)   body.innerHTML  = '<div class="loading">Lade…</div>';
            if (title)  title.textContent = 'Auswertungsdetail';
            if (footer) footer.innerHTML = '<button type="button" class="btn-ghost" data-close-modal>Schließen</button>';

            fetch('/backend/auswertung/' + id)
            .then(r => r.json())
            .then(res => {
                if (body) body.innerHTML = buildDetailHtml(res);
                if (footer) footer.innerHTML = buildModalFooter(id, surveyStatus, isRead);
                // re-bind close buttons in footer
                footer.querySelectorAll('[data-close-modal]').forEach(el => {
                    el.addEventListener('click', () => { modal.hidden = true; });
                });
            })
            .catch(() => {
                if (body) body.innerHTML = '<div class="error">Fehler beim Laden der Daten.</div>';
            });
        });
    });

    modal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', () => { modal.hidden = true; });
    });
}

function buildModalFooter(id, status, isRead) {
    const csrf = getCsrfToken();
    let html = '<button type="button" class="btn-ghost" data-close-modal>Schließen</button>';
    html += `<span style="flex:1"></span>`;
    html += `<a href="/backend/auswertung/${id}/pdf" class="btn-primary" target="_blank">${iconSvg('document')} PDF</a>`;

    if (status === 'completed') {
        html += `<form method="post" action="/backend/auswertung/${id}/archivieren" style="display:inline" data-confirm="Wirklich archivieren?">
            <input type="hidden" name="csrf_token" value="${escAttr(csrf)}">
            <button type="submit" class="btn-ghost">${iconSvg('archive')} Archivieren</button>
        </form>`;
    }

    return html;
}

function escAttr(v) { return (v || '').toString().replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }

function iconSvg(name) {
    const icons = {
        document: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;display:inline-block;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
        archive:  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;display:inline-block;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
    };
    return icons[name] || '';
}

function buildDetailHtml(res) {
    const s = res.survey;
    const esc   = v => (v || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const nl2br = v => esc(v).replace(/\n/g, '<br>');
    const gradeColor = val => {
        if (val <= 1.5) return '#86bc24';
        if (val <= 2.5) return '#b8d424';
        if (val <= 3.5) return '#e6e020';
        if (val <= 4.5) return '#f0a020';
        if (val <= 5.5) return '#e85020';
        return '#c0392b';
    };

    const questions = res.questions || [];
    const answered  = questions.filter(({answer: a}) => a && a.answer_type !== null && a.answer_type !== undefined).length;
    const total     = questions.length;

    let html = '';

    // Gesamtnote + Statistik ganz oben
    if (res.avg !== null && res.avg !== undefined) {
        html += `<div class="eval-avg" style="margin-top:0;margin-bottom:16px;padding-bottom:16px;border-bottom:2px solid #86bc24">
            <span style="font-size:22px;color:#86bc24">${parseFloat(res.avg).toFixed(2).replace('.', ',')}</span>
            <span style="font-size:13px;color:#737587;margin-left:6px">Ø Gesamtnote</span>
            <span style="float:right;font-size:13px;color:#737587">${answered} / ${total} Fragen ausgefüllt</span>
        </div>`;
    } else {
        html += `<div style="margin-bottom:16px;font-size:13px;color:#737587">${answered} / ${total} Fragen ausgefüllt</div>`;
    }

    html += '<dl class="eval-meta">';
    [
        ['Kunde',           s.customer_name],
        ['Ansprechpartner', s.contact_person],
        ['E-Mail',          s.contact_email],
        ['Projektname',     s.project_name],
        ['Bereiche',         s.area_names],
        ['Vertrieb',         s.sales_user_name],
        ['Projektleitung',   s.project_lead_name],
        ['Metropolregion',   s.metropolregion_name],
        ['Befrager',         s.created_by_name],
        ['Code',             s.code],
    ].forEach(([k,v]) => { if (v !== undefined && v !== null && v !== '') html += `<dt>${esc(k)}</dt><dd>${esc(v)}</dd>`; });
    if (s.internal_notes) html += `<dt>Anmerkungen</dt><dd>${esc(s.internal_notes)}</dd>`;
    html += '</dl>';

    // Fragen nach Bereich gruppieren
    const areaGroups = {};
    const areaOrder  = [];
    questions.forEach(({question: q, answer: a}) => {
        const area = q.area_name || '–';
        if (!areaGroups[area]) { areaGroups[area] = []; areaOrder.push(area); }
        areaGroups[area].push({question: q, answer: a});
    });

    html += '<div class="eval-questions">';
    areaOrder.forEach(areaName => {
        const areaItems   = areaGroups[areaName];
        const areaTotal   = areaItems.length;
        const areaAnswered = areaItems.filter(({answer: a}) => a && a.answer_type !== null && a.answer_type !== undefined).length;
        const areaSliders  = areaItems
            .filter(({answer: a}) => a && a.answer_type === 'slider' && a.slider_value !== null && a.slider_value !== undefined)
            .map(({answer: a}) => parseFloat(a.slider_value));
        const areaAvg = areaSliders.length > 0 ? areaSliders.reduce((s, v) => s + v, 0) / areaSliders.length : null;

        let areaStat = `<span class="eval-area-stat">${areaAnswered} / ${areaTotal} Fragen ausgefüllt`;
        if (areaAvg !== null) {
            areaStat = `<span class="eval-area-stat">${areaAvg.toFixed(2).replace('.', ',')} Ø Bereichsnote &nbsp;|&nbsp; ${areaAnswered} / ${areaTotal} Fragen ausgefüllt`;
        }
        areaStat += '</span>';

        html += `<div class="eval-area-header">${esc(areaName)}<span class="eval-area-sep"> | </span>${areaStat}</div>`;
        areaGroups[areaName].forEach(({question: q, answer: a}) => {
        html += `<div class="eval-question">
            <div class="eval-question-title">${esc(q.label_short)}</div>
            <div class="eval-question-long">${esc(q.label_long)}</div>`;

        if (!a || a.answer_type === null) {
            html += '<div class="eval-answer-na">Nicht beantwortet</div>';
        } else if (a.answer_type === 'not_applicable') {
            html += '<div class="eval-answer-na">Nicht zutreffend</div>';
        } else if (a.answer_type === 'slider' || a.slider_value) {
            const val   = parseFloat(a.slider_value || 0);
            const pct   = Math.max(4, (6 - val) / 5 * 100).toFixed(1);
            const color = gradeColor(val);
            html += `<div class="eval-slider-bar-wrap">
                <div class="eval-slider-track">
                    <div class="eval-slider-fill" style="width:${pct}%;background:${color}"></div>
                </div>
                <div class="eval-slider-value" style="color:${color}">${val.toFixed(1)}</div>
            </div>`;
            if (a.freitext_value) {
                html += `<div style="margin-top:8px;font-size:13px;color:#737587;font-weight:700">${esc(q.freitext_context || 'Anmerkung')}:</div>`;
                html += `<div style="font-size:13px;color:#1a1c28;margin-top:2px">${nl2br(a.freitext_value)}</div>`;
            }
        } else if (a.answer_type === 'freitext') {
            html += `<div style="margin-top:4px;font-size:14px;color:#1a1c28">${nl2br(a.freitext_value || '')}</div>`;
        }
        html += '</div>';
        }); // end areaGroups[areaName].forEach
    }); // end areaOrder.forEach
    html += '</div>';

    return html;
}


// ── Backend: Fragen-Sequenz ───────────────────────────────────

function initSequenceButtons() {
    document.querySelectorAll('.btn-seq').forEach(btn => {
        btn.addEventListener('click', function() {
            const isUp = this.classList.contains('btn-seq-up');
            const row  = this.closest('tr');
            const sibling = isUp ? row.previousElementSibling : row.nextElementSibling;

            if (!sibling || !sibling.dataset.questionId) return;

            // DOM tauschen
            if (isUp) row.parentNode.insertBefore(row, sibling);
            else       row.parentNode.insertBefore(sibling, row);

            // IDs in neuer Reihenfolge sammeln
            const tbody = row.closest('tbody');
            const ids   = Array.from(tbody.querySelectorAll('tr[data-question-id]'))
                              .map(r => r.dataset.questionId);

            fetch('/backend/fragen/sequenz', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'csrf_token=' + encodeURIComponent(getCsrfToken())
                       + ids.map((id, i) => '&ids[' + i + ']=' + id).join(''),
            })
            .catch(() => {
                // DOM-Änderung rückgängig machen
                if (isUp) row.parentNode.insertBefore(sibling, row);
                else       row.parentNode.insertBefore(row, sibling);
            });
        });
    });
}

// ── Frontend: Willkommensscreen ──────────────────────────────

function initWelcomeScreen() {
    const welcomeScreen  = document.getElementById('welcome-screen');
    const surveyContent  = document.getElementById('survey-content');
    const continueBtn    = document.getElementById('welcome-continue');

    if (!welcomeScreen || !continueBtn) return;

    continueBtn.addEventListener('click', () => {
        welcomeScreen.hidden = true;
        if (surveyContent) surveyContent.hidden = false;
        window.scrollTo(0, 0);
    });
}

// ── Backend: Bereichsfilter Fragenverwaltung ─────────────────

function initAreaFilter() {
    const select = document.getElementById('area-filter');
    if (!select) return;

    select.addEventListener('change', function() {
        const val = this.value;
        document.querySelectorAll('.question-group').forEach(section => {
            section.style.display = (!val || section.id === val) ? '' : 'none';
        });
    });
}

// ── Toggle-Buttons (Aktiv-Schalter) ─────────────────────────

function initToggleButtons() {
    document.querySelectorAll('.toggle-btn[data-target]').forEach(btn => {
        const target = document.getElementById(btn.dataset.target);
        const label  = document.getElementById('active-label');
        if (!target) return;

        btn.addEventListener('click', function() {
            const active = this.classList.toggle('is-active');
            const offVal = this.dataset.valueOff !== undefined ? this.dataset.valueOff : '0';
            target.value = active ? '1' : offVal;
            if (label) label.textContent = active ? 'Aktiv' : 'Inaktiv';
        });
    });
}

// ── Backend: Auto-Filter (AJAX) ───────────────────────────────

function initAutoFilter() {
    const container = document.getElementById('list-results');
    if (!container) return;

    const form = document.querySelector('form[data-auto-filter]');

    // ── Filterformular ────────────────────────────────────────
    if (form) {
        // Selects und Datum → sofort
        form.querySelectorAll('select, input[type="date"]').forEach(el => {
            el.addEventListener('change', () => applyFilter(form));
        });

        // Checkboxen → sofort (note_range[] u.ä.)
        form.querySelectorAll('input[type="checkbox"]').forEach(el => {
            el.addEventListener('change', () => applyFilter(form));
        });

        // Text → 400 ms Debounce
        form.querySelectorAll('input[type="text"], input[type="search"]').forEach(el => {
            el.addEventListener('input', debounce(() => applyFilter(form), 400));
        });

        // "× zurücksetzen" Link ohne Seiten-Reload
        document.querySelectorAll('.filter-reset').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                if (link.classList.contains('filter-reset-inactive')) return;
                form.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
                form.querySelectorAll('input[type="text"], input[type="date"], input[type="search"]')
                    .forEach(i => { i.value = ''; });
                form.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = false; });
                history.pushState(null, '', link.href);
                updateFilterResetState(form);
                loadResults('');
            });
        });
    }

    // ── Pagination-Links innerhalb der Ergebnisse ─────────────
    container.addEventListener('click', e => {
        const link = e.target.closest('a.page-link');
        if (!link) return;
        e.preventDefault();
        const url = new URL(link.href, location.origin);
        history.pushState(null, '', link.href);
        loadResults(url.search);
    });

    // ── Browser Vor/Zurück ────────────────────────────────────
    window.addEventListener('popstate', () => loadResults(location.search));
}

function applyFilter(form) {
    const params = new URLSearchParams();
    new FormData(form).forEach((val, key) => {
        if (val !== '' && val !== null) params.append(key, val);
    });
    const search = params.size ? '?' + params.toString() : '';
    history.pushState(null, '', form.action + search);
    updateFilterResetState(form);
    loadResults(search);
}

function updateFilterResetState(form) {
    const hasFilters = Array.from(
        form.querySelectorAll('select, input[type="text"], input[type="date"], input[type="search"]')
    ).some(el => el.value !== '');
    document.querySelectorAll('.filter-reset').forEach(link => {
        link.classList.toggle('filter-reset-inactive', !hasFilters);
    });
}

function loadResults(search) {
    const container = document.getElementById('list-results');
    if (!container) return;

    container.classList.add('is-loading');

    fetch(location.pathname + (search || ''), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
    .then(html => {
        container.innerHTML = html;
        container.classList.remove('is-loading');
        // Detail-Modal-Buttons im neuen HTML aktivieren
        initDetailModal();
    })
    .catch(() => container.classList.remove('is-loading'));
}

// ── Backend: Signatur-Editor (HTML + Vorschau-Tab) ───────────

function initSignatureEditor() {
    const form       = document.getElementById('signature-form');
    if (!form) return;

    const textaEl    = document.getElementById('sig-html-textarea');
    const hiddenEl   = document.getElementById('sig-content-hidden');
    const tabHtml    = document.getElementById('sig-tab-html');
    const tabPreview = document.getElementById('sig-tab-preview');
    const divHtml    = document.getElementById('sig-editor-html');
    const divPreview = document.getElementById('sig-editor-preview');
    const previewEl  = document.getElementById('sig-preview-content');

    let mode = 'html';

    if (tabPreview) {
        tabPreview.addEventListener('click', () => {
            if (mode === 'preview') return;
            mode = 'preview';
            if (previewEl && textaEl) previewEl.innerHTML = textaEl.value;
            if (divHtml)    divHtml.style.display    = 'none';
            if (divPreview) divPreview.style.display = '';
            tabPreview.classList.add('btn-tab-active');
            if (tabHtml) tabHtml.classList.remove('btn-tab-active');
        });
    }

    if (tabHtml) {
        tabHtml.addEventListener('click', () => {
            if (mode === 'html') return;
            mode = 'html';
            if (divHtml)    divHtml.style.display    = '';
            if (divPreview) divPreview.style.display = 'none';
            tabHtml.classList.add('btn-tab-active');
            if (tabPreview) tabPreview.classList.remove('btn-tab-active');
        });
    }

    form.addEventListener('submit', () => {
        if (hiddenEl && textaEl) hiddenEl.value = textaEl.value;
    });
}

// ── Autocomplete ─────────────────────────────────────────────

function initAutocomplete() {
    document.querySelectorAll('input[data-ac]').forEach(input => {
        const list     = JSON.parse(input.dataset.ac || '[]');
        const dropdown = input.parentElement.querySelector('.ac-dropdown');
        if (!dropdown) return;

        let activeIdx = -1;

        function show(items) {
            activeIdx = -1;
            dropdown.innerHTML = '';
            items.forEach((text, i) => {
                const item = document.createElement('div');
                item.className = 'ac-item';
                item.textContent = text;
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    input.value = text;
                    hide();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
                dropdown.appendChild(item);
            });
            dropdown.hidden = items.length === 0;
        }

        function hide() {
            dropdown.hidden = true;
            activeIdx = -1;
        }

        function setActive(idx) {
            const items = dropdown.querySelectorAll('.ac-item');
            items.forEach(el => el.classList.remove('is-active'));
            activeIdx = Math.max(-1, Math.min(idx, items.length - 1));
            if (activeIdx >= 0) items[activeIdx].classList.add('is-active');
        }

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            if (!q) { hide(); return; }
            const matches = list.filter(s => s.toLowerCase().includes(q)).slice(0, 10);
            show(matches);
        });

        input.addEventListener('keydown', e => {
            if (dropdown.hidden) return;
            const items = dropdown.querySelectorAll('.ac-item');
            if (e.key === 'ArrowDown')  { e.preventDefault(); setActive(activeIdx + 1); }
            if (e.key === 'ArrowUp')    { e.preventDefault(); setActive(activeIdx - 1); }
            if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                input.value = items[activeIdx].textContent;
                hide();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (e.key === 'Escape') hide();
        });

        input.addEventListener('blur', () => setTimeout(hide, 150));
    });
}

// ── User Panel ───────────────────────────────────────────────

function initUserPanel() {
    document.querySelectorAll('.be-panel-group-header').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const body = btn.nextElementSibling;
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            body.hidden = open;
        });
    });
}

// ── Init ─────────────────────────────────────────────────────

document.addEventListener('submit', e => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
});

document.addEventListener('DOMContentLoaded', function() {
    initSliders();
    initFreitext();
    initNotApplicable();
    initComplete();
    initWelcomeScreen();
    initOutlookModal();
    initSignatureEditor();
    initDetailModal();
    initMarkReadToggle();
    initAreaFilter();
    initToggleButtons();
    initAutoFilter();
    initAutocomplete();
    initUserPanel();
    initMobileNav();
    initMobilePanel();

    // Code-Input: automatisch Kleinschreibung + Trim
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
        });
    }
});
