@if(canWrite())
    {{ $slot }}
@else
    <div class="license-disabled-group relative opacity-60 select-none">
        {{ $slot }}
        <!-- Overlay to intercept clicks/focus and prevent editing -->
        <div class="absolute inset-0 z-50 cursor-not-allowed bg-transparent license-disabled" 
             title="Editing is disabled due to license status. Please correct system time or renew your license.">
        </div>
    </div>
@endif
