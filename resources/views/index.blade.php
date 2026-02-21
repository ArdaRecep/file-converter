<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File Converter</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 24px; }
        .card { max-width: 860px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        label { display:block; font-weight: 600; margin-bottom: 6px; }
        select, input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 10px; }
        button, a.btn { padding: 10px 14px; border: 0; border-radius: 10px; cursor: pointer; }
        .btn { background: #111827; color: white; text-decoration:none; display:inline-block; }
        .muted { color: #6b7280; font-size: 14px; }
        ul { padding-left: 18px; }
    </style>
</head>
<body>
<div class="card" x-data="converterApp()" x-init="init()">
    <h2>File Converter (MVP)</h2>
    <p class="muted">DOCX → PDF, JPG/PNG → WEBP. Çoklu dosya + ZIP indirme.</p>

    <form @submit.prevent="submit()" enctype="multipart/form-data">
        <div class="row">
            <div>
                <label>Kaynak tür</label>
                <select x-model="sourceType" @change="syncTargets()">
                    <option value="docx">Word (DOCX)</option>
                    <option value="image">Image (JPG/PNG)</option>
                </select>
            </div>
            <div>
                <label>Hedef tür</label>
                <select x-model="targetType">
                    <template x-for="t in targets" :key="t.value">
                        <option :value="t.value" x-text="t.label"></option>
                    </template>
                </select>
            </div>
        </div>

        <div style="margin-top: 12px;">
            <label>Dosyalar</label>
            <input type="file" multiple :accept="accept" x-ref="files" @change="onFilesChange()">
            <p class="muted" x-text="acceptHint" style="margin-top: 8px;"></p>
        </div>

        <div style="margin-top: 12px;">
            <button class="btn" type="submit" :disabled="submitting || fileCount===0">
                <span x-text="submitting ? 'Yükleniyor...' : 'Dönüştür'"></span>
            </button>
        </div>

        <div style="margin-top: 12px;" x-show="fileCount>0">
            <h4>Seçilen dosyalar</h4>
            <ul>
                <template x-for="f in fileNames" :key="f">
                    <li x-text="f"></li>
                </template>
            </ul>
        </div>
    </form>

    <div style="margin-top: 16px;" x-show="conversionId">
        <h4>Durum</h4>
        <p class="muted" x-text="statusText"></p>

        <template x-if="done">
            <a class="btn" :href="`/conversions/${conversionId}/download`">ZIP indir</a>
        </template>
    </div>
</div>

<script>
function converterApp() {
    return {
        sourceType: 'docx',
        targetType: 'pdf',
        targets: [],
        accept: '.docx',
        acceptHint: 'Sadece .docx yükleyin.',
        submitting: false,
        conversionId: null,
        statusText: '',
        done: false,
        fileCount: 0,
        fileNames: [],

        init() { this.syncTargets(); },

        syncTargets() {
            if (this.sourceType === 'docx') {
                this.targets = [{ value: 'pdf', label: 'PDF' }];
                this.targetType = 'pdf';
                this.accept = '.docx';
                this.acceptHint = 'Sadece .docx yükleyin.';
            } else {
                this.targets = [{ value: 'webp', label: 'WEBP' }];
                this.targetType = 'webp';
                this.accept = '.jpg,.jpeg,.png';
                this.acceptHint = 'Sadece .jpg/.jpeg/.png yükleyin.';
            }
            this.onFilesChange();
        },

        onFilesChange() {
            const files = this.$refs.files?.files || [];
            this.fileCount = files.length;
            this.fileNames = Array.from(files).map(f => f.name);
        },

        async submit() {
            const files = this.$refs.files.files;
            if (!files || files.length === 0) return;

            this.submitting = true;
            this.statusText = '';
            this.done = false;

            const fd = new FormData();
            fd.append('source_type', this.sourceType);
            fd.append('target_type', this.targetType);
            for (const f of files) fd.append('files[]', f);

            const res = await fetch('{{ route('conversions.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            });

            const data = await res.json();
            if (!res.ok) {
                this.statusText = data?.message || 'Hata';
                this.submitting = false;
                return;
            }

            this.conversionId = data.id;
            this.submitting = false;

            this.poll();
        },

        async poll() {
            const res = await fetch(`/conversions/${this.conversionId}`);
            const data = await res.json();

            this.statusText = `Conversion #${data.id} - ${data.status} (done: ${data.done}/${data.total}, failed: ${data.failed})`;

            if (data.status === 'done') { this.done = true; return; }
            if (data.status === 'failed') { return; }

            setTimeout(() => this.poll(), 1200);
        },
    }
}
</script>
</body>
</html>