<div wire:init="verifyLicenseBackground">
    @php
    $vendorPhone = preg_replace('/[^0-9]/', '', config('services.license.vendor_phone', '923220190291'));
    $schoolId    = config('services.license.school_id', '');
@endphp

@if($status && !$dismissed)

    @if($status['stage'] === 'WARNING')
    {{-- ⚠️ Yellow Warning Banner — dismissable --}}
    @php
        $waWarning = 'https://wa.me/' . $vendorPhone
            . '?text=' . rawurlencode(
                "Assalam o Alaikum,\n\nThis is a renewal reminder from School ID: {$schoolId}.\n\nOur SIMS subscription is expiring in " . ($status['days_left'] ?? '') . " day(s). Please share your bank account details so we can process the payment.\n\nThank you."
            );
    @endphp
    <div class="w-full bg-amber-400 text-slate-900 px-4 py-2.5 shadow-md relative z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm font-semibold flex-1 min-w-0">
                <span class="shrink-0">⚠️</span>
                <span>{{ $status['message'] }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button onclick="simsLicenseSync()" id="sims-sync-btn"
                    class="flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap border border-slate-700">
                    <svg id="sims-sync-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span id="sims-sync-text">I've Renewed — Sync Now</span>
                </button>
                <a href="{{ $waWarning }}" target="_blank"
                   class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp to Renew
                </a>
                <button wire:click="dismiss"
                    class="text-slate-700 hover:text-slate-900 text-xl font-bold px-1 focus:outline-none"
                    title="Dismiss">×</button>
            </div>
        </div>
    </div>

    @elseif($status['stage'] === 'GRACE')
    {{-- 🟠 Orange Grace Banner — NOT dismissable --}}
    @php
        $graceLeft = max(0, 3 - ($status['days_past'] ?? 0));
        $waGrace   = 'https://wa.me/' . $vendorPhone
            . '?text=' . rawurlencode(
                "Assalam o Alaikum,\n\nSchool ID: {$schoolId}\n\nOur SIMS subscription has expired and we are in the grace period (ends in {$graceLeft} day(s)). Please find our payment receipt attached to this message to reactivate the system.\n\nKindly activate as soon as possible. Thank you."
            );
    @endphp
    <div class="w-full bg-orange-500 text-white px-4 py-2.5 shadow-md relative z-50 animate-pulse">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm font-bold flex-1 min-w-0">
                <span class="shrink-0">🔔</span>
                <span>{{ $status['message'] }}
                    @if(isset($status['days_past']))
                        Grace ends in <strong>{{ $graceLeft }} {{ $graceLeft === 1 ? 'day' : 'days' }}</strong>.
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button onclick="simsLicenseSync()" id="sims-sync-btn"
                    class="flex items-center gap-1.5 bg-white text-orange-600 hover:bg-orange-50 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    I've Renewed — Sync Now
                </button>
                <a href="{{ $waGrace }}" target="_blank"
                   class="flex items-center gap-1.5 bg-white text-orange-600 text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-orange-50 transition-colors whitespace-nowrap border border-orange-200">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Send Receipt
                </a>
            </div>
        </div>
    </div>

    @elseif($status['stage'] === 'LOCKED')
    {{-- 🔴 Red Read-Only Banner — NOT dismissable --}}
    @php
        $waLocked = 'https://wa.me/' . $vendorPhone
            . '?text=' . rawurlencode(
                "Assalam o Alaikum,\n\nSchool ID: {$schoolId}\n\nOur SIMS system is currently suspended and in READ-ONLY mode. We are sending our payment receipt to restore full access. Please activate urgently.\n\nThank you."
            );
    @endphp
    <div class="w-full bg-red-600 text-white px-4 py-2.5 shadow-md relative z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm font-bold flex-1 min-w-0">
                <span class="shrink-0">🔒</span>
                <span>{{ $status['message'] }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button onclick="simsLicenseSync()" id="sims-sync-btn"
                    class="flex items-center gap-1.5 bg-white text-red-600 hover:bg-red-50 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    I've Renewed — Sync Now
                </button>
                <a href="{{ $waLocked }}" target="_blank"
                   class="flex items-center gap-1.5 bg-white text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors whitespace-nowrap border border-red-200">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Send Receipt
                </a>
            </div>
        </div>
    </div>

    @endif
@endif

    <!-- ── Sync Popup Modal ─────────────────────────────────────── -->
    <div id="sims-sync-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:1rem">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:1rem;padding:2rem;max-width:420px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);font-family:system-ui,sans-serif">
            <div id="sims-modal-loading" style="text-align:center">
                <div style="width:48px;height:48px;border:4px solid #f3f4f6;border-top-color:#9333ea;border-radius:50%;animation:sims-spin 0.8s linear infinite;margin:0 auto 1rem"></div>
                <p style="color:#111827;font-weight:700;font-size:1.1rem;margin:0">Verifying License…</p>
                <p style="color:#6b7280;font-size:0.85rem;margin-top:0.5rem">Securely connecting to Adminova servers. Please wait.</p>
            </div>
            <div id="sims-modal-result" style="display:none">
                <div style="text-align:center;margin-bottom:1.5rem">
                    <div id="sims-modal-icon" style="font-size:3.5rem;margin-bottom:0.5rem"></div>
                    <p id="sims-modal-title" style="color:#111827;font-weight:700;font-size:1.25rem;margin:0 0 0.5rem"></p>
                    <p id="sims-modal-msg" style="color:#4b5563;font-size:0.9rem;white-space:pre-line;margin:0"></p>
                </div>
                <div style="display:flex;justify-content:center;gap:0.75rem">
                    <button id="sims-modal-close" onclick="simsModalClose()" style="padding:0.6rem 1.5rem;background:#f3f4f6;color:#374151;border:none;border-radius:0.5rem;cursor:pointer;font-weight:600;font-size:0.875rem;transition:background 0.2s" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">Close</button>
                    <button id="sims-modal-reload" onclick="location.reload()" style="display:none;padding:0.6rem 1.5rem;background:#9333ea;color:#ffffff;border:none;border-radius:0.5rem;cursor:pointer;font-weight:600;font-size:0.875rem;transition:background 0.2s" onmouseover="this.style.background='#7e22ce'" onmouseout="this.style.background='#9333ea'">↺ Reload Page</button>
                </div>
            </div>
        </div>
    </div>
    <style>@keyframes sims-spin{to{transform:rotate(360deg)}}</style>

    <!-- Global Mismatch / Read-Only Click Interception Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkStatus = () => {
                const stage = @json($status['stage'] ?? '');
                const reason = @json($status['reason'] ?? '');

                if (stage === 'LOCKED') {
                    // Intercept any click on disabled/readonly buttons at capturing phase
                    document.body.addEventListener('click', (e) => {
                        const target = e.target.closest('button, input[type="submit"], a.btn-save, button[type="submit"]');
                        if (!target) return;

                        // Check if parent has can-write restrictions or if the button has disabled attributes
                        const isRestricted = target.hasAttribute('disabled') || 
                                             target.classList.contains('license-disabled') ||
                                             target.closest('.license-disabled-group');

                        if (isRestricted) {
                            e.preventDefault();
                            e.stopPropagation();

                            let alertMsg = "The system is in Read-Only mode. Please renew your subscription to enable editing.";
                            if (reason === 'clock_tampering') {
                                alertMsg = "System clock mismatch detected. Please set your computer clock to the correct date and time to continue editing.";
                            }
                            
                            // Native alerts display reliably on self-hosted computers
                            alert(alertMsg);
                        }
                    }, true);
                }
            };
            
            checkStatus();
            document.addEventListener('livewire:navigated', checkStatus);
        });
    </script>

    <script>
        function simsLicenseSync() {
            const modal   = document.getElementById('sims-sync-modal');
            const loading = document.getElementById('sims-modal-loading');
            const result  = document.getElementById('sims-modal-result');

            // Show modal with loading spinner
            modal.style.display = 'flex';
            loading.style.display = 'block';
            result.style.display  = 'none';
            document.getElementById('sims-modal-reload').style.display = 'none';

            fetch('{{ route("license.sync") }}', {
                method : 'POST',
                headers: {
                    'Content-Type'     : 'application/json',
                    'X-CSRF-TOKEN'     : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With' : 'XMLHttpRequest',
                },
            })
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display  = 'block';

                document.getElementById('sims-modal-icon').textContent  = data.success ? '✅' : '❌';
                document.getElementById('sims-modal-title').textContent = data.success ? 'License Synced!' : 'Sync Failed';
                document.getElementById('sims-modal-msg').textContent   = data.message ?? '';

                if (data.success) {
                    document.getElementById('sims-modal-reload').style.display = 'inline-block';
                }
            })
            .catch(err => {
                loading.style.display = 'none';
                result.style.display  = 'block';

                // Detect offline vs server error
                const isOffline = !navigator.onLine || err.message.toLowerCase().includes('network') || err.message.toLowerCase().includes('fetch');

                document.getElementById('sims-modal-icon').textContent  = isOffline ? '📡' : '⚠️';
                document.getElementById('sims-modal-title').textContent = isOffline ? 'No Internet Connection' : 'Server Unreachable';
                document.getElementById('sims-modal-msg').textContent   = isOffline
                    ? 'Your device is not connected to the internet.\n\nPlease connect to Wi-Fi or mobile data, then tap "Sync Now" again to update your license status.'
                    : 'Could not reach the Adminova License Server. Please try again in a moment.\n\nIf this problem persists, contact your vendor.';
            });
        }

        function simsModalClose() {
            document.getElementById('sims-sync-modal').style.display = 'none';
        }

        // Close modal on backdrop click
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('sims-sync-modal').addEventListener('click', function(e) {
                if (e.target === this) simsModalClose();
            });
        });
    </script>
</div>
