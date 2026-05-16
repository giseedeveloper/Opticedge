<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">Add product (IMEI)</h1>
                    <p class="admin-prod-subtitle">Capture barcode photos or paste many codes. Pick stock and model.</p>
            </div>
            <a href="{{ route('admin.stock.stocks') }}" class="admin-prod-back shrink-0">Back to stocks</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden space-y-0">
            <div class="admin-prod-form-body space-y-6">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">Capture & Scan barcodes</h2>
                    <p class="text-xs text-slate-600 mb-3">Capture a photo of the IMEI barcode (Code 128, QR, EAN), and the codes will be read directly in your browser.</p>
                    <input type="file" id="barcode_photos" accept="image/*" class="admin-prod-file">
                    <button type="button" id="btn_decode_photos" class="mt-3 bg-slate-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-slate-700">Capture & Scan</button>
                    <p id="decode_status" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
                </div>

                <form action="{{ route('admin.stock.store-add-product') }}" method="POST" id="add-product-form">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="imei_numbers" class="admin-prod-label">IMEI / serial numbers</label>
                            <p class="text-xs text-slate-500 mb-1">Put <strong>one code per line</strong>, or separate with <strong>spaces</strong>, <strong>commas</strong>, or <strong>semicolons</strong>. Long runs of digits-only text are split every 15 digits (IMEI length) when needed.</p>
                            <textarea name="imei_numbers" id="imei_numbers" rows="8" required
                                class="admin-prod-textarea font-mono text-sm"
                                placeholder="Example:&#10;352123456789012&#10;352123456789013&#10;Or: 352123456789012, 352123456789013&#10;Or from Capture & Scan: use button above">{{ old('imei_numbers') }}</textarea>
                            @error('imei_numbers') 
                                <div class="text-red-500 text-xs mt-1 p-2 bg-red-50 rounded whitespace-pre-wrap">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            @if(($addProductTarget ?? 'stock') === 'purchase')
                                <label for="purchase_id" class="admin-prod-label">Purchase</label>
                                <select name="purchase_id" id="add_product_target" required class="admin-prod-select"
                                    data-models-mode="purchase"
                                    data-models-url-template="{{ route('admin.stock.add-product.purchase.models', ['purchase' => '__ID__']) }}">
                                    <option value="">Select purchase</option>
                                    @foreach($purchasePickerRows ?? [] as $p)
                                        <option value="{{ $p->id }}" {{ (string) old('purchase_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name ?? 'Purchase #'.$p->id }}</option>
                                    @endforeach
                                </select>
                                @error('purchase_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                @if(($purchasePickerRows ?? collect())->isEmpty())
                                    <p class="text-xs text-amber-700 mt-1">No purchases with remaining slots. Create a purchase with quantity / limit first.</p>
                                @endif
                            @else
                                <label for="stock_id" class="admin-prod-label">Stock</label>
                                <select name="stock_id" id="add_product_target" required class="admin-prod-select"
                                    data-models-mode="stock"
                                    data-models-url-template="{{ url('admin/stock/stocks') }}/__ID__/models">
                                    <option value="">Select stock</option>
                                    @foreach($stocks as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('stock_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('stock_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            @endif
                        </div>
                        <div>
                            <label for="catalog_product_id" class="admin-prod-label">Model <span class="text-slate-500 font-normal">(from selected {{ ($addProductTarget ?? 'stock') === 'purchase' ? 'purchase' : 'stock' }})</span></label>
                            <select name="catalog_product_id" id="catalog_product_id" required class="admin-prod-select">
                                <option value="">{{ ($addProductTarget ?? 'stock') === 'purchase' ? 'Select purchase first' : 'Select stock first' }}</option>
                            </select>
                            @error('catalog_product_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="admin-prod-form-footer !mt-6 !px-0 !border-0 !shadow-none">
                        <button type="submit" class="admin-prod-btn-primary px-8">Save all</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('add_product_target').addEventListener('change', function() {
            const targetId = this.value;
            const mode = this.dataset.modelsMode || 'stock';
            const urlTemplate = this.dataset.modelsUrlTemplate || '';
            const modelSelect = document.getElementById('catalog_product_id');
            const emptyLabel = mode === 'purchase' ? 'Select purchase first' : 'Select stock first';
            modelSelect.innerHTML = '<option value="">Loading…</option>';
            if (!targetId) {
                modelSelect.innerHTML = '<option value="">' + emptyLabel + '</option>';
                return;
            }
            const url = urlTemplate.replace('__ID__', encodeURIComponent(targetId));
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()).then(data => {
                const list = data.data || [];
                modelSelect.innerHTML = '<option value="">Select model</option>';
                list.forEach(item => {
                    const pid = item.product_id;
                    if (!pid) return;
                    const opt = document.createElement('option');
                    opt.value = String(pid);
                    opt.textContent = item.label || item.model || ('#' + pid);
                    opt.dataset.categoryId = item.category_id || '';
                    opt.dataset.model = item.model || '';
                    modelSelect.appendChild(opt);
                });
            }).catch(() => {
                modelSelect.innerHTML = '<option value="">Error loading models</option>';
            });
        });
        @if(old('stock_id') || old('purchase_id'))
            document.getElementById('add_product_target').dispatchEvent(new Event('change'));
            setTimeout(function() {
                const modelSelect = document.getElementById('catalog_product_id');
                const m = @json(old('catalog_product_id'));
                if (m && modelSelect.options.length) {
                    for (let i = 0; i < modelSelect.options.length; i++) {
                        if (modelSelect.options[i].value === String(m)) {
                            modelSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }, 500);
        @endif

        (function() {
            var token = document.querySelector('meta[name="csrf-token"]');
            var fileInput = document.getElementById('barcode_photos');
            var btn = document.getElementById('btn_decode_photos');
            var statusEl = document.getElementById('decode_status');
            var ta = document.getElementById('imei_numbers');

            // ZXing UMD is loaded lazily on first use (pure JS, works in all browsers).
            var _zxingReady = false;
            var _zxingLoadPromise = null;
            function loadZXing() {
                if (_zxingReady) return Promise.resolve();
                if (_zxingLoadPromise) return _zxingLoadPromise;
                _zxingLoadPromise = new Promise(function(resolve, reject) {
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
                    s.onload = function() { _zxingReady = true; resolve(); };
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
                return _zxingLoadPromise;
            }

            function mergeCodes(codes) {
                var existing = ta.value.replace(/\r\n/g, '\n').split('\n')
                    .map(function(s) { return s.trim(); }).filter(Boolean);
                var seen = {};
                existing.forEach(function(c) { seen[c] = true; });
                var added = [];
                codes.forEach(function(c) {
                    c = (c || '').trim();
                    if (c && !seen[c]) { seen[c] = true; existing.push(c); added.push(c); }
                });
                ta.value = existing.join('\n');
                return added.length;
            }

            /**
             * Decode all barcodes from a single File using ZXing JS.
             *
             * Strategy: decode the full image first, then try a grid of crops
             * so that sheets with many barcodes (e.g. 3×4 IMEI label pages)
             * are fully extracted.
             */
            async function decodeFileZXing(reader, file) {
                var found = new Set();

                // Load image into a canvas so we can crop sub-regions.
                var imgUrl = URL.createObjectURL(file);
                var img = await new Promise(function(res, rej) {
                    var i = new Image();
                    i.onload = function() { res(i); };
                    i.onerror = rej;
                    i.src = imgUrl;
                });

                var W = img.naturalWidth;
                var H = img.naturalHeight;
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');

                /**
                 * Attempt to decode a rectangular sub-region of the image.
                 * sx/sy = source top-left; sw/sh = source width/height.
                 */
                async function tryRegion(sx, sy, sw, sh) {
                    if (sw < 10 || sh < 10) return;
                    canvas.width = sw;
                    canvas.height = sh;
                    ctx.clearRect(0, 0, sw, sh);
                    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, sw, sh);
                    // toDataURL is synchronous – ZXing accepts data: URLs via an <img> element.
                    var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                    try {
                        var result = await reader.decodeFromImageUrl(dataUrl);
                        var text = (result && (result.text || (result.getText && result.getText()))) || '';
                        if (text.trim()) found.add(text.trim());
                    } catch(e) { /* no barcode in this region */ }
                }

                // 1. Full image
                await tryRegion(0, 0, W, H);

                // 2. Grid crops – try multiple grid sizes to cover common label sheets.
                //    We try rows × cols combinations most likely for printed IMEI sheets.
                var grids = [[4,3],[3,3],[4,2],[3,2],[4,1],[3,1],[2,1]];
                for (var g = 0; g < grids.length; g++) {
                    var rows = grids[g][0], cols = grids[g][1];
                    // Skip grids that would make cells smaller than 30px – unreliable.
                    if (Math.floor(W / cols) < 30 || Math.floor(H / rows) < 30) continue;
                    var cellW = Math.floor(W / cols);
                    var cellH = Math.floor(H / rows);
                    for (var r = 0; r < rows; r++) {
                        for (var c = 0; c < cols; c++) {
                            await tryRegion(c * cellW, r * cellH, cellW, cellH);
                        }
                    }
                    // Stop once we have found codes from a grid pass
                    // (avoid redundant work for simple single-barcode images)
                    if (found.size > 0 && g >= 1) break;
                }

                URL.revokeObjectURL(imgUrl);
                return Array.from(found);
            }

            btn.addEventListener('click', async function() {
                var files = fileInput.files;
                if (!files || !files.length) {
                    statusEl.textContent = 'Choose a photo first.';
                    return;
                }
                btn.disabled = true;
                statusEl.textContent = 'Loading decoder…';

                try {
                    await loadZXing();
                } catch(e) {
                    statusEl.textContent = 'Could not load barcode library. Check your internet connection and try again.';
                    btn.disabled = false;
                    return;
                }

                statusEl.textContent = 'Scanning barcode from photo…';

                var allCodes = [];
                try {
                    // ZXing.BrowserMultiFormatReader handles Code128, QR, EAN, and all common formats.
                    var reader = new ZXing.BrowserMultiFormatReader();
                    for (var i = 0; i < files.length; i++) {
                        var codes = await decodeFileZXing(reader, files[i]);
                        allCodes = allCodes.concat(codes);
                    }
                } catch(e) {
                    statusEl.textContent = 'Decode error: ' + (e.message || e);
                    btn.disabled = false;
                    return;
                }

                btn.disabled = false;

                if (allCodes.length) {
                    var added = mergeCodes(allCodes);
                    statusEl.textContent = 'Found ' + allCodes.length + ' barcode(s). Added ' + added + ' new code(s).' +
                        (added < allCodes.length ? ' (duplicates skipped)' : '');
                } else {
                    statusEl.textContent =
                        'No barcode found. Make sure the photo is clear and the barcode is not blurry. ' +
                        'You can also type or paste IMEIs manually below.';
                }
            });
        })();
    </script>
</x-admin-layout>
