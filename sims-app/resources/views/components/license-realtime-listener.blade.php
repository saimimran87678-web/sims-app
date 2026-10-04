@php
    $licenseKey = trim(config('services.license.key', ''));
    if (empty($licenseKey)) {
        $rec = \Illuminate\Support\Facades\DB::table('software_licenses')->first();
        if ($rec && !empty($rec->license_key)) {
            try { $licenseKey = trim(decrypt($rec->license_key)); } catch (\Exception $e) {}
        }
    }
    $firebaseApiKey = config('services.firebase.api_key');
    $firebaseProjectId = config('services.firebase.project_id');
    $initialSchedulePolicy = \App\Services\LicenseStatus::getScheduleTypePolicy();
    $initialConfigVersion = \Illuminate\Support\Facades\DB::table('software_licenses')->value('config_version') ?? 1;
@endphp

@if(!empty($licenseKey) && !empty($firebaseApiKey) && !empty($firebaseProjectId))
<!-- Firebase Real-Time Firestore Cloud Snapshot Listener (Sub-second cloud sync) -->
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>
<script>
(function() {
    try {
        if (typeof firebase === 'undefined') return;

        const config = {
            apiKey: "{{ $firebaseApiKey }}",
            projectId: "{{ $firebaseProjectId }}"
        };

        if (!firebase.apps.length) {
            firebase.initializeApp(config);
        }

        const db = firebase.firestore();
        const licenseKey = "{{ $licenseKey }}";
        let isInitialLoad = true;
        let lastKnownSignature = null;
        let lastKnownStatus = null;
        let lastKnownConfigVer = {{ (int) $initialConfigVersion }};
        let lastKnownBoundUuid = null;
        let lastKnownExpiresAt = null;
        let lastKnownUpdatedAt = null;
        let lastKnownBroadcast = null;
        let lastKnownSchedulePolicy = "{{ $initialSchedulePolicy }}";

        // Establish persistent WebSocket snapshot listener with Google Firestore
        db.collection('licenses').doc(licenseKey).onSnapshot((docSnapshot) => {
            if (!docSnapshot.exists) return;

            const cloudData = docSnapshot.data();
            const newSig = cloudData.rsa_signature || '';
            const newStatus = (cloudData.status || '').toLowerCase();
            const newVer = cloudData.config_version || 1;
            const newBoundUuid = cloudData.bound_machine_uuid || '';
            const newExpiresAt = cloudData.expires_at || '';
            const newUpdatedAt = cloudData.updated_at || '';
            const newBroadcast = (cloudData.broadcast_announcement || '').trim();
            const newSchedulePolicy = (cloudData.schedule_type_policy || 'configurable').toLowerCase();

            // Real-time DOM update for Broadcast Banner
            const bannerEl = document.getElementById('sims-broadcast-banner');
            const textEl = document.getElementById('sims-broadcast-text');
            if (bannerEl && textEl) {
                if (newBroadcast) {
                    textEl.textContent = newBroadcast;
                    bannerEl.classList.remove('hidden');
                    bannerEl.style.display = 'block';
                } else {
                    bannerEl.style.display = 'none';
                    bannerEl.classList.add('hidden');
                }
            }

            // Real-time Push Notification to Bell Dropdown
            if (newBroadcast && newBroadcast !== lastKnownBroadcast && !isInitialLoad) {
                window.dispatchEvent(new CustomEvent('sims-cloud-notification', {
                    detail: {
                        id: 'broadcast_' + Math.abs(newBroadcast.split('').reduce((a,b)=>{a=((a<<5)-a)+b.charCodeAt(0);return a&a},0)),
                        type: 'broadcast',
                        title: '📢 Adminova Cloud Notice',
                        message: newBroadcast,
                        action_url: null,
                        action_label: null,
                        badge: 'Broadcast',
                        badge_color: 'bg-purple-100 text-purple-800 border-purple-200',
                        icon_bg: 'bg-gradient-to-br from-indigo-500 to-purple-600',
                        created_at: 'Just now'
                    }
                }));
            }

            if (cloudData.latest_version && cloudData.latest_version !== '{{ config("app.version", "2.5.2") }}') {
                window.dispatchEvent(new CustomEvent('sims-cloud-notification', {
                    detail: {
                        id: 'update_' + cloudData.latest_version,
                        type: 'update',
                        title: '🚀 New Update Available (v' + cloudData.latest_version + ')',
                        message: cloudData.patch_notes || 'A newer version or hotfix is available for your system.',
                        action_url: '{{ route("admin.settings") }}',
                        action_label: 'Review & Install',
                        badge: 'v' + cloudData.latest_version,
                        badge_color: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        icon_bg: 'bg-gradient-to-br from-emerald-500 to-teal-600',
                        created_at: 'Update Notice'
                    }
                }));
            }

            // Check if local SQLite needs catch-up on initial load
            const needsCatchup = isInitialLoad && (
                newSchedulePolicy !== lastKnownSchedulePolicy ||
                newVer > lastKnownConfigVer
            );

            // Detect if admin changed status, modules, expiry, machine binding, announcement, schedule policy, or signature
            const hasChanged = (newSig !== lastKnownSignature && lastKnownSignature !== null) || 
                               (newStatus !== lastKnownStatus && lastKnownStatus !== null) || 
                               (newVer > lastKnownConfigVer) ||
                               (newBoundUuid !== lastKnownBoundUuid && lastKnownBoundUuid !== null) ||
                               (newExpiresAt !== lastKnownExpiresAt && lastKnownExpiresAt !== null) ||
                               (newUpdatedAt !== lastKnownUpdatedAt && lastKnownUpdatedAt !== null) ||
                               (newBroadcast !== lastKnownBroadcast && lastKnownBroadcast !== null) ||
                               (newSchedulePolicy !== lastKnownSchedulePolicy);

            if (isInitialLoad) {
                isInitialLoad = false;
                lastKnownSignature = newSig;
                lastKnownStatus = newStatus;
                lastKnownConfigVer = newVer;
                lastKnownBoundUuid = newBoundUuid;
                lastKnownExpiresAt = newExpiresAt;
                lastKnownUpdatedAt = newUpdatedAt;
                lastKnownBroadcast = newBroadcast;
                lastKnownSchedulePolicy = newSchedulePolicy;
                if (!needsCatchup) return;
            }

            if (hasChanged || needsCatchup) {
                console.log('⚡ Real-time license snapshot received from Adminova Cloud:', cloudData);
                const prevPolicy = lastKnownSchedulePolicy;
                lastKnownSignature = newSig;
                lastKnownStatus = newStatus;
                lastKnownConfigVer = newVer;
                lastKnownBoundUuid = newBoundUuid;
                lastKnownExpiresAt = newExpiresAt;
                lastKnownUpdatedAt = newUpdatedAt;
                lastKnownBroadcast = newBroadcast;
                lastKnownSchedulePolicy = newSchedulePolicy;

                // Sync locally via /license/sync to verify RSA & update SQLite
                fetch('{{ route("license.sync") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        license_payload: cloudData
                    })
                })
                .then(r => r.json())
                .then(result => {
                    if (result.status === 'suspended' || result.status === 'revoked' || result.status === 'locked_readonly') {
                        if (window.location.pathname !== '/license-blocked') {
                            window.location.href = "{{ route('license.blocked') }}";
                        }
                    } else if (result.status === 'active') {
                        if (window.location.pathname === '/license-blocked') {
                            window.location.href = "{{ route('dashboard') }}";
                        } else {
                            console.log('✅ License synchronized in real time.', result);
                            // If schedule policy changed, or user is on settings or schedule manager, soft reload
                            if (newSchedulePolicy !== prevPolicy || window.location.pathname.includes('/settings') || window.location.pathname.includes('/schedule')) {
                                window.dispatchEvent(new CustomEvent('license-enforcement-updated', { detail: result }));
                                setTimeout(() => {
                                    window.location.reload();
                                }, 350);
                            }
                        }
                    }
                })
                .catch(() => {});
            }
        }, (err) => {
            console.warn('Real-time snapshot stream notice:', err.message);
        });
    } catch (e) {
        console.warn('Real-time listener initialization notice:', e.message);
    }
})();
</script>
@endif
