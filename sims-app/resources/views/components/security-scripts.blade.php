@auth
<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .animate-fade-in {
        animation: fadeIn 0.2s ease-out forwards;
    }
</style>
<script>
    // --- 1. Tab Closure Security (Anti-Flash) ---
    // If this tab was newly opened or reopened from history, sessionStorage is empty.
    if (!sessionStorage.getItem('sims_tab_auth')) {
        // Instantly hide the HTML document BEFORE it renders to prevent visual glitch
        document.documentElement.style.display = 'none';
        
        // Wait for DOM to load, then redirect to GET logout route
        document.addEventListener('DOMContentLoaded', function() {
            window.location.href = '{{ route('logout.get') }}';
        });
    } else {
        // --- 2. Professional Inactivity Timeout (15 minutes) ---
        document.addEventListener('DOMContentLoaded', function() {
            let timeoutTime = 15 * 60 * 1000; // 15 minutes
            let warningThreshold = 60 * 1000; // 60 seconds warning
            let lastActivityTime = Date.now();
            let isModalOpen = false;
            let isExpired = false;
            let checkInterval;
            let pingDebounce = false;
            let lastLoginTime = 0; // Prevents duplicate popups right after a successful login

            let currentUserEmail = '{{ auth()->user()->email }}';
            let loginUrl = '{{ route('login') }}';
            let refreshCsrfUrl = '{{ route('csrf.refresh') }}';
            let logoutUrl = '{{ route('logout.get') }}';

            function obfuscateEmail(email) {
                if (!email || !email.includes('@')) return email;
                let [name, domain] = email.split('@');
                if (name.length <= 2) {
                    name = name.charAt(0) + '*'.repeat(name.length > 1 ? 1 : 0);
                } else {
                    name = name.charAt(0) + '*'.repeat(name.length - 2) + name.charAt(name.length - 1);
                }
                return name + '@' + domain;
            }

            function updateActivity() {
                if (isExpired) return; // Do not update activity if session has already expired
                
                if (isModalOpen && !pingDebounce) {
                    // Auto-renew session if user shows activity when warning is visible
                    keepWorking();
                }
                lastActivityTime = Date.now();
            }

            // Bind activity events
            const events = ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'];
            events.forEach(event => {
                document.addEventListener(event, updateActivity);
            });

            function showWarningModal(secondsLeft) {
                if (document.getElementById('session-timeout-modal') || document.getElementById('session-expired-modal')) {
                    let secsSpan = document.getElementById('timeout-countdown-secs');
                    if (secsSpan) secsSpan.innerText = Math.max(0, secondsLeft);
                    return;
                }
                
                isModalOpen = true;
                
                let modalHTML = `
                    <div id="session-timeout-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center border border-slate-100 transform scale-95 transition-all duration-300">
                            <!-- Icon -->
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-50 mb-4 animate-pulse">
                                <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            
                            <!-- Content -->
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Session Timeout Warning</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                You have been inactive for a while. For your security, you will be logged out automatically in <span id="timeout-countdown-secs" class="font-bold text-amber-600 text-lg">${secondsLeft}</span> seconds.
                            </p>
                            
                            <!-- Action Buttons -->
                            <div class="flex gap-3 justify-center">
                                <button id="timeout-logout-btn" class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-medium text-sm transition-all focus:outline-none focus:ring-2 focus:ring-gray-200">
                                    Logout
                                </button>
                                <button id="timeout-keep-btn" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium text-sm transition-all shadow-md shadow-blue-500/10 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Keep Working
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                let wrapper = document.createElement('div');
                wrapper.innerHTML = modalHTML;
                document.body.appendChild(wrapper.firstElementChild);
                
                document.getElementById('timeout-logout-btn').addEventListener('click', forceLogout);
                document.getElementById('timeout-keep-btn').addEventListener('click', keepWorking);
            }

            function hideWarningModal() {
                let modal = document.getElementById('session-timeout-modal');
                if (modal) {
                    modal.remove();
                }
                isModalOpen = false;
            }

            function forceLogout() {
                clearInterval(checkInterval);
                sessionStorage.removeItem('sims_tab_auth');
                window.location.href = logoutUrl;
            }

            function keepWorking() {
                if (pingDebounce) return;
                pingDebounce = true;
                
                // Ping the server to refresh Laravel session lifetime
                fetch('{{ route('ping') }}')
                    .then(response => {
                        pingDebounce = false;
                        if (response.ok) {
                            hideWarningModal();
                            lastActivityTime = Date.now();
                            
                            // Dynamically update document CSRF token as well
                            return fetch(refreshCsrfUrl)
                                .then(r => r.json())
                                .then(data => {
                                    let meta = document.querySelector('meta[name="csrf-token"]');
                                    if (meta && data.token) meta.setAttribute('content', data.token);
                                });
                        } else {
                            triggerExpiration();
                        }
                    })
                    .catch(() => {
                        pingDebounce = false;
                        hideWarningModal();
                    });
            }

            function triggerExpiration() {
                isExpired = true;
                clearInterval(checkInterval);
                hideWarningModal();
                showExpiredModal();
            }

            function showExpiredModal(errorMessage = '') {
                if (document.getElementById('session-expired-modal')) {
                    if (errorMessage) {
                        let errDiv = document.getElementById('expired-error-msg');
                        errDiv.innerText = errorMessage;
                        errDiv.classList.remove('hidden');
                    }
                    return;
                }

                let modalHTML = `
                    <div id="session-expired-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[9999] flex items-center justify-center p-4 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-100 transform scale-95 transition-all duration-300">
                            <!-- Icon -->
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-50 mb-4">
                                <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0-8v6m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            
                            <!-- Content -->
                            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Session Expired</h3>
                            <p class="text-sm text-gray-500 text-center mb-6">
                                Your session has timed out. Please enter your password to resume working without losing your changes.
                            </p>
                            
                            <!-- Error Msg -->
                            <div id="expired-error-msg" class="hidden mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-xs font-medium border border-red-100 text-center">
                                ${errorMessage}
                            </div>
                            
                            <!-- Login Form -->
                            <form id="expired-login-form" class="space-y-4 text-left">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Email Address</label>
                                    <input type="email" id="expired-email" value="${obfuscateEmail(currentUserEmail)}" readonly class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 focus:outline-none cursor-not-allowed">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Password</label>
                                    <input type="password" id="expired-password" placeholder="Enter your password" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>
                                
                                <button type="submit" id="expired-submit-btn" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium text-sm transition-all shadow-lg shadow-blue-500/20 hover:shadow-xl focus:outline-none flex items-center justify-center gap-2">
                                    <span id="expired-btn-text">Unlock Session</span>
                                </button>
                            </form>
                            
                            <!-- Switch Account / Logout -->
                            <div class="text-center mt-4">
                                <a href="${logoutUrl}" class="text-xs text-blue-600 hover:underline">
                                    Sign in as a different user
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                
                let wrapper = document.createElement('div');
                wrapper.innerHTML = modalHTML;
                document.body.appendChild(wrapper.firstElementChild);
                
                document.getElementById('expired-login-form').addEventListener('submit', handleExpiredLogin);
                
                // Focus password input automatically
                setTimeout(() => {
                    let pwdInput = document.getElementById('expired-password');
                    if (pwdInput) pwdInput.focus();
                }, 100);
            }

            function handleExpiredLogin(e) {
                e.preventDefault();
                
                let submitBtn = document.getElementById('expired-submit-btn');
                let btnText = document.getElementById('expired-btn-text');
                let passwordInput = document.getElementById('expired-password');
                let errorDiv = document.getElementById('expired-error-msg');
                
                submitBtn.disabled = true;
                btnText.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Unlocking...
                `;
                errorDiv.classList.add('hidden');
                
                // 1. Fetch fresh CSRF first
                fetch(refreshCsrfUrl)
                    .then(r => r.json())
                    .then(data => {
                        let freshToken = data.token;
                        
                        let meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', freshToken);
                        
                        // 2. Submit the credentials
                        return fetch(loginUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': freshToken
                            },
                            body: JSON.stringify({
                                email: currentUserEmail,
                                password: passwordInput.value
                            })
                        });
                    })
                    .then(response => {
                        if (response.ok) {
                            // Session restored successfully!
                            lastLoginTime = Date.now();
                            
                            let modal = document.getElementById('session-expired-modal');
                            if (modal) modal.remove();
                            
                            sessionStorage.setItem('sims_tab_auth', 'active');
                            lastActivityTime = Date.now();
                            isModalOpen = false;
                            isExpired = false;
                            
                            showToast('Session restored successfully. You can continue working.');
                            
                            // Restart inactivity checker ticker
                            startChecker();
                        } else if (response.status === 422) {
                            return response.json().then(errData => {
                                let errMsg = 'Authentication failed. Please verify your password.';
                                if (errData.errors && errData.errors.password) {
                                    errMsg = errData.errors.password[0];
                                }
                                throw new Error(errMsg);
                            });
                        } else {
                            throw new Error('An error occurred. Please try again.');
                        }
                    })
                    .catch(err => {
                        submitBtn.disabled = false;
                        btnText.innerText = 'Unlock Session';
                        errorDiv.innerText = err.message || 'Connection error. Please try again.';
                        errorDiv.classList.remove('hidden');
                    });
            }

            function showToast(message) {
                let toast = document.createElement('div');
                toast.className = 'fixed bottom-5 right-5 px-6 py-3 rounded-xl text-white font-medium text-sm shadow-2xl z-[99999] transform translate-y-10 opacity-0 transition-all duration-300 flex items-center gap-2 bg-emerald-600';
                toast.innerHTML = `
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ${message}
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.remove('translate-y-10', 'opacity-0');
                }, 10);
                
                setTimeout(() => {
                    toast.classList.add('translate-y-10', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            function startChecker() {
                if (checkInterval) clearInterval(checkInterval);
                checkInterval = setInterval(function() {
                    let idleTime = Date.now() - lastActivityTime;
                    
                    if (idleTime >= timeoutTime) {
                        triggerExpiration();
                    } else if (idleTime >= timeoutTime - warningThreshold) {
                        let secondsLeft = Math.ceil((timeoutTime - idleTime) / 1000);
                        showWarningModal(secondsLeft);
                    } else {
                        hideWarningModal();
                    }
                }, 1000);
            }

            // Start checker initially
            startChecker();
            
            // --- 3. Livewire 419 Error Interceptor ---
            document.addEventListener('livewire:init', function() {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            // Ignore 419 errors if we successfully logged in less than 5 seconds ago.
                            // (Prevents duplicate popups from lagging background requests that failed while the modal was open)
                            if (Date.now() - lastLoginTime > 5000) {
                                preventDefault();
                                triggerExpiration();
                            }
                        }
                    });
                });
            });
        });
    }
</script>
@endauth
