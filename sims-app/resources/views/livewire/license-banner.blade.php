<div wire:init="verifyLicenseBackground">
    @if($status && !$dismissed)
        @if($status['stage'] === 'WARNING')
            <!-- Yellow Banner -->
            <div class="bg-yellow-500 text-slate-900 px-4 py-2.5 shadow-md flex justify-between items-center relative z-50 animate-slide-down">
                <div class="flex items-center space-x-2 text-sm font-semibold mx-auto">
                    <span>⚠️</span>
                    <span>{{ $status['message'] }}</span>
                </div>
                <button wire:click="dismiss" class="text-slate-900 hover:text-slate-700 text-lg font-bold px-2 focus:outline-none">
                    ×
                </button>
            </div>
        @elseif($status['stage'] === 'GRACE')
            <!-- Orange Banner -->
            <div class="bg-orange-500 text-white px-4 py-2.5 shadow-md flex items-center justify-center relative z-50 animate-pulse">
                <div class="flex items-center space-x-2 text-sm font-bold">
                    <span>🟠</span>
                    <span>{{ $status['message'] }}</span>
                </div>
            </div>
        @elseif($status['stage'] === 'LOCKED')
            <!-- Red Banner (Read-Only) -->
            <div class="bg-red-600 text-white px-4 py-2.5 shadow-md flex items-center justify-center relative z-50">
                <div class="flex items-center space-x-2 text-sm font-bold text-center">
                    <span>🚨</span>
                    <span>{{ $status['message'] }}</span>
                </div>
            </div>
        @endif
    @endif

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
</div>
