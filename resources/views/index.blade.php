<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File Converter</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0b1220;
            --card: rgba(255,255,255,.06);
            --border: rgba(255,255,255,.10);
            --text: rgba(255,255,255,.92);
            --muted: rgba(255,255,255,.62);
            --muted2: rgba(255,255,255,.45);
            --accent: #7c3aed;
            --accent2: #22c55e;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
            color: var(--text);
            background: radial-gradient(1200px 600px at 15% 10%, rgba(124, 58, 237, .35), transparent 60%),
                        radial-gradient(900px 600px at 90% 30%, rgba(34, 197, 94, .25), transparent 55%),
                        linear-gradient(180deg, #070b13, #0b1220);
            display: grid;
            place-items: center;
            padding: 28px;
        }

        .shell {
            width: 100%;
            max-width: 980px;
            display: grid;
            gap: 18px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .title h1 { margin: 0; font-size: 26px; letter-spacing: -0.4px; }
        .title p   { margin: 6px 0 0; color: var(--muted); font-size: 14px; }

        .card {
            border: 1px solid var(--border);
            background: var(--card);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,.35);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }

        label {
            display: block;
            font-weight: 650;
            font-size: 13px;
            margin-bottom: 8px;
            color: rgba(255,255,255,.82);
        }

        select, input[type="file"] {
            width: 100%;
            border: 1px solid var(--border);
            background: rgba(0,0,0,.25);
            color: var(--text);
            padding: 12px 12px;
            border-radius: 12px;
            outline: none;
        }

        select:focus, input[type="file"]:focus {
            border-color: rgba(124,58,237,.55);
            box-shadow: 0 0 0 3px rgba(124,58,237,.18);
        }

        select option { background: #ffffff; color: #111827; }

        .hint { margin-top: 8px; font-size: 12px; color: var(--muted2); }

        .drop {
            border: 1px dashed rgba(255,255,255,.18);
            background: rgba(0,0,0,.18);
            border-radius: 14px;
            padding: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            color: var(--text);
            padding: 11px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 650;
            font-size: 13px;
            transition: transform .05s ease, background .2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:active { transform: translateY(1px); }
        .btn-primary { background: linear-gradient(135deg,rgba(124,58,237,.95),rgba(124,58,237,.65)); border-color: rgba(124,58,237,.45); }
        .btn-success { background: linear-gradient(135deg,rgba(34,197,94,.95),rgba(34,197,94,.55)); border-color: rgba(34,197,94,.45); }
        .btn:disabled { opacity: .55; cursor: not-allowed; }

        .files {
            margin-top: 14px;
            border-top: 1px solid rgba(255,255,255,.08);
            padding-top: 12px;
        }

        .files h3 { margin: 0 0 10px; font-size: 13px; color: rgba(255,255,255,.82); }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 8px 10px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(0,0,0,.18);
            border-radius: 12px;
            margin-bottom: 8px;
            font-size: 13px;
            color: rgba(255,255,255,.85);
        }

        .file-item-name {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-item-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .remove-btn {
            background: none;
            border: none;
            color: rgba(239,68,68,.75);
            cursor: pointer;
            font-size: 16px;
            padding: 0 2px;
            line-height: 1;
            transition: color .15s;
        }
        .remove-btn:hover { color: rgba(239,68,68,1); }

        .status { margin-top: 14px; display: grid; gap: 10px; }

        .status-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 12px 12px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.18);
        }

        .status-text { font-size: 13px; color: rgba(255,255,255,.85); }

        .pill {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.80);
        }

        .pill.fail    { border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10); }
        .pill.ok      { border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10); }
        .pill.work    { border-color: rgba(124,58,237,.35); background: rgba(124,58,237,.10); }
        .pill.partial { border-color: rgba(251,191,36,.35); background: rgba(251,191,36,.10); color: rgba(251,191,36,.9); }

        .error {
            color: rgba(255,255,255,.9);
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.28);
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 13px;
        }

        .failed-list { margin-top: 10px; display: grid; gap: 6px; }
    </style>
</head>
<body>
<div class="shell" x-data="converterApp()" x-init="init()">
    <div class="header">
        <div class="title">
            <h1>File Converter</h1>
            <p>Çoklu dosya yükle, desteklenen hedef formatı seç, ZIP olarak indir.</p>
        </div>
    </div>

    <div class="card">
        <form @submit.prevent="submit()">
            <div class="grid">
                <div>
                    <label>Kaynak kategori</label>
                    <select x-model="sourceFormat" @change="syncTargets()">
                        <template x-for="opt in sourceOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                    <div class="hint" x-text="acceptHint"></div>
                </div>

                <div>
                    <label>Hedef format</label>
                    <select x-model="targetFormat">
                        <template x-for="t in targets" :key="t.value">
                            <option :value="t.value" x-text="t.label"></option>
                        </template>
                    </select>
                    <div class="hint">Tüm dosyalar bu formata dönüştürülür.</div>
                </div>
            </div>

            <div class="drop" style="margin-top: 14px;">
                <label>Dosyalar</label>
                <input type="file" multiple :accept="accept" x-ref="files" @change="onFilesChange()">
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" :disabled="submitting || fileNames.length === 0">
                    <span x-text="submitting ? 'Yükleniyor...' : 'Dönüştür'"></span>
                </button>

                <template x-if="done">
                    <a class="btn btn-success" :href="`/conversions/${conversionId}/download`">⬇ ZIP indir</a>
                </template>

                <span class="hint" x-show="fileNames.length > 0" x-text="`Seçili: ${fileNames.length} dosya`"></span>
            </div>

            <template x-if="errorMessage">
                <div class="error" style="margin-top:12px;" x-text="errorMessage"></div>
            </template>

            <!-- Dosya listesi -->
            <div class="files" x-show="fileNames.length > 0">
                <h3>Seçilen dosyalar</h3>
                <template x-for="(f, i) in fileNames" :key="f.name + i">
                    <div class="file-item">
                        <span class="file-item-name" x-text="f.name"></span>
                        <div class="file-item-right">
                            <span class="pill" x-text="f.name.split('.').pop().toUpperCase()"></span>
                            <button type="button" class="remove-btn" @click="removeFile(i)" title="Kaldır">✕</button>
                        </div>
                    </div>
                </template>
            </div>
        </form>

        <!-- Durum -->
        <div class="status" x-show="conversionId">
            <div class="status-line">
                <div class="status-text" x-text="statusText || `Conversion #${conversionId} - ...`"></div>
                <span class="pill"
                      :class="{
                        'work':    statusState === 'processing',
                        'ok':      statusState === 'done',
                        'fail':    statusState === 'failed',
                        'partial': statusState === 'partial'
                      }"
                      x-text="statusState"></span>
            </div>

            <!-- Hatalı dosyalar -->
            <div class="failed-list" x-show="failedFiles.length > 0">
                <template x-for="ff in failedFiles" :key="ff.original_name">
                    <div class="error">
                        <strong x-text="ff.original_name"></strong>:
                        <span x-text="ff.error"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function converterApp() {
    return {
        formatsMap: {},
        sourceOptions: [],
        sourceFormat: 'document',
        targetFormat: 'pdf',
        targets: [],
        accept: '',
        acceptHint: '',
        submitting: false,
        conversionId: null,
        statusText: '',
        statusState: 'processing',
        done: false,
        fileNames: [],   // [{ name, file }]
        failedFiles: [], // [{ original_name, error }]
        errorMessage: '',

        async init() {
            const res = await fetch('{{ route('formats') }}');
            this.formatsMap = await res.json();
            // formatsMap = { document: { accept: [...], targets: [...] }, image: { ... } }

            const labelMap = { document: 'Documents', image: 'Images' };

            this.sourceOptions = Object.keys(this.formatsMap).map(cat => ({
                value: cat,
                label: labelMap[cat] || (cat.charAt(0).toUpperCase() + cat.slice(1)),
            }));

            this.sourceFormat = this.sourceOptions[0]?.value || 'document';
            this.syncTargets(false);
        },

        syncTargets(resetFiles = true) {
            this.errorMessage  = '';
            this.done          = false;
            this.conversionId  = null;
            this.statusText    = '';
            this.statusState   = 'processing';
            this.failedFiles   = [];

            if (resetFiles) {
                if (this.$refs.files) this.$refs.files.value = '';
                this.fileNames = [];
            }

            const def = this.formatsMap[this.sourceFormat] || {};

            this.targets      = (def.targets || []).map(t => ({ value: t, label: t.toUpperCase() }));
            this.targetFormat = this.targets[0]?.value || '';

            const exts    = (def.accept || []).map(e => '.' + e);
            this.accept   = exts.join(',');
            this.acceptHint = 'Kabul edilen: ' + (def.accept || []).map(e => '.' + e).join(', ');
        },

        onFilesChange() {
            const files = this.$refs.files?.files || [];
            // Yeni seçilenleri mevcut listeye ekle (duplicate kontrolü ile)
            const existing = new Set(this.fileNames.map(f => f.name + f.file.size));
            for (const f of files) {
                const key = f.name + f.size;
                if (!existing.has(key)) {
                    this.fileNames.push({ name: f.name, file: f });
                    existing.add(key);
                }
            }
            // input'u sıfırla ki aynı dosya tekrar seçilebilsin
            this.$refs.files.value = '';
        },

        removeFile(index) {
            this.fileNames.splice(index, 1);
        },

        async submit() {
            if (this.fileNames.length === 0) return;

            this.submitting  = true;
            this.errorMessage = '';
            this.done        = false;
            this.failedFiles = [];

            const fd = new FormData();
            fd.append('source_type', this.sourceFormat);
            fd.append('target_type', this.targetFormat);
            for (const f of this.fileNames) fd.append('files[]', f.file);

            const res = await fetch('{{ route('conversions.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                this.errorMessage = data?.message || 'Hata oluştu';
                this.submitting   = false;
                return;
            }

            this.conversionId = data.id;
            this.submitting   = false;
            this.poll();
        },

        async poll() {
            if (!this.conversionId) return;

            const res  = await fetch(`/conversions/${this.conversionId}`);
            const data = await res.json();

            this.statusState  = data.status;
            this.statusText   = `Conversion #${data.id} — ${data.status} (done: ${data.done}/${data.total}, failed: ${data.failed})`;
            this.failedFiles  = data.failed_files || [];

            if (data.status === 'done' || data.status === 'partial') {
                this.done = true;
                return;
            }
            if (data.status === 'failed') {
                return;
            }

            setTimeout(() => this.poll(), 1000);
        },
    }
}
</script>
</body>
</html>