{{-- Instant page navigation visual feedback:
     Dims the main content immediately when a navigation event starts, keeps sidebar scroll position,
     and ensures the Livewire progress bar is prominent at the very top. --}}
<style>
    html.is-navigating main { 
        opacity: 0.55; 
        transition: opacity 0.12s ease-out; 
        pointer-events: none; 
    }
    .livewire-progress-bar {
        height: 3px !important;
        z-index: 99999 !important;
        box-shadow: 0 0 10px rgba(79, 70, 229, 0.7);
    }
</style>
<script data-navigate-once>
    (function () {
        var navScroll = 0;

        document.addEventListener('livewire:navigate', function () {
            var nav = document.querySelector('aside nav');
            navScroll = nav ? nav.scrollTop : 0;
            document.documentElement.classList.add('is-navigating');
        });

        document.addEventListener('livewire:navigated', function () {
            document.documentElement.classList.remove('is-navigating');
            var nav = document.querySelector('aside nav');
            if (nav && navScroll) nav.scrollTop = navScroll;
        });
    })();
</script>
