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
        let lastKnownConfigVer = null;
        let lastKnownBoundUuid = null;
        let lastKnownExpiresAt = null;
        let lastKnownUpdatedAt = null;

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

            if (isInitialLoad) {
                isInitialLoad = false;
                lastKnownSignature = newSig;
                lastKnownStatus = newStatus;
                lastKnownConfigVer = newVer;
                lastKnownBoundUuid = newBoundUuid;
                lastKnownExpiresAt = newExpiresAt;
                lastKnownUpdatedAt = newUpdatedAt;
                return;
            }

            // Detect if admin changed status, modules, expiry, machine binding, or signature in the portal
            const hasChanged = (newSig !== lastKnownSignature) || 
                               (newStatus !== lastKnownStatus) || 
                               (newVer !== lastKnownConfigVer) ||
                               (newBoundUuid !== lastKnownBoundUuid) ||
                               (newExpiresAt !== lastKnownExpiresAt) ||
                               (newUpdatedAt !== lastKnownUpdatedAt);

            if (hasChanged) {
                console.log('⚡ Real-time license snapshot received from Adminova Cloud:', cloudData);
                lastKnownSignature = newSig;
                lastKnownStatus = newStatus;
                lastKnownConfigVer = newVer;
                lastKnownBoundUuid = newBoundUuid;
                lastKnownExpiresAt = newExpiresAt;
                lastKnownUpdatedAt = newUpdatedAt;

                // Sync locally via /license/sync to verify RSA & update SQLite
                fetch('{{ route("license.sync") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
                            console.log('✅ License synchronized in real time.');
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
