/**
 * SIMS Interactive Guided Product Tour (Driver.js)
 * Highlights key modules: Institute branding, Academic sessions/shifts,
 * Student management, Timetable/Schedule, Teacher Substitutions, and System Settings.
 */

(function () {
    'use strict';

    function initSIMSTour() {
        if (!window.driver || !window.driver.js || !window.driver.js.driver) {
            console.warn('Driver.js is not loaded.');
            return;
        }

        const driver = window.driver.js.driver;

        window.startSIMSTour = function (force = false) {
            if (!force && localStorage.getItem('sims_tour_completed') === 'true') {
                return;
            }

            // Define tour steps
            const allSteps = [
                {
                    element: '#sidebar-branding',
                    popover: {
                        title: '🏫 Institute Branding',
                        description: 'Your institute name and logo configured during setup. All report cards, student lists, and fee receipts automatically carry this branding.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '#session-shift-selector',
                    popover: {
                        title: '📅 Session & Shift Switcher',
                        description: 'Switch between Morning and Evening shifts or view past Academic Sessions with one click. Dashboard metrics and class rosters update immediately.',
                        side: 'bottom',
                        align: 'center'
                    }
                },
                {
                    element: '#nav-students',
                    popover: {
                        title: '🎓 Student & Class Setup',
                        description: 'Admit students, organize sections, assign roll numbers, and manage class profiles. Includes instant search and bulk Excel import.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '#nav-schedule',
                    popover: {
                        title: '🗓️ Timetable & Bell Schedule',
                        description: 'Configure period timings, assign teacher workloads, and generate conflict-free timetable routines for every class and shift.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '#nav-substitutions',
                    popover: {
                        title: '🔄 Teacher Substitutions Engine',
                        description: 'Quickly resolve daily teacher absences. The smart substitution assistant suggests available teachers for each vacant period in real time.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '#nav-settings',
                    popover: {
                        title: '⚙️ Settings & Offline License',
                        description: 'Review system configuration, school shifts, academic sessions, and manage your offline license key anytime.',
                        side: 'right',
                        align: 'start'
                    }
                }
            ];

            // Filter to steps whose elements are currently visible in the DOM
            const validSteps = allSteps.filter(step => {
                const el = document.querySelector(step.element);
                return el && (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0);
            });

            if (validSteps.length === 0) {
                console.info('SIMS Tour: No matching target elements on current page.');
                return;
            }

            function markTourComplete() {
                localStorage.setItem('sims_tour_completed', 'true');

                // Notify backend to clear launch_first_tour setting
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    fetch('/admin/tour/complete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).catch(err => console.debug('Tour completion sync error:', err));
                }
            }

            const driverObj = driver({
                showProgress: true,
                animate: true,
                allowClose: true,
                popoverClass: 'sims-tour-popover',
                nextBtnText: 'Next &rarr;',
                prevBtnText: '&larr; Previous',
                doneBtnText: 'Finish Tour &#x2714;',
                progressText: 'Step {{current}} of {{total}}',
                steps: validSteps,
                onDestroyed: () => {
                    markTourComplete();
                }
            });

            driverObj.drive();
        };

        // Auto-launch if server requested initial tour
        if (window.SIMS_LAUNCH_TOUR === true) {
            // Small timeout to allow DOM and Livewire components to stabilize
            setTimeout(() => {
                window.startSIMSTour(false);
            }, 600);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSIMSTour);
    } else {
        initSIMSTour();
    }
})();
