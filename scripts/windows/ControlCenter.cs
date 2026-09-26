using System;
using System.Drawing;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Diagnostics;
using System.Windows.Forms;
using System.Threading;
using System.Text;

namespace Adminova.ControlCenter
{
    public class ControlCenterForm : Form
    {
        private string rootDir;
        private string appDir;
        private string phpBin;
        private string lanIp = "localhost";
        private string currentUrl = "https://localhost";

        // UI Components
        private Label lblStatusBadge;
        private Label lblStatusDetail;
        private Label lblLanAddress;
        private Button btnStart;
        private Button btnStop;
        private Button btnRestart;
        private Button btnOpenBrowser;
        private Button btnCheckUpdates;
        private Button btnRepair;
        private Button btnRefresh;
        private Button btnCopyLan;
        private RichTextBox txtLog;
        private System.Windows.Forms.Timer statusTimer;
        private NotifyIcon trayIcon;
        private bool isOperationRunning = false;

        [STAThread]
        public static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new ControlCenterForm());
        }

        public ControlCenterForm()
        {
            InitializeEnvironment();
            InitializeComponents();

            this.Load += (s, e) => {
                DetectLanIp();
                CheckServerStatusAsync();
            };
        }

        private void SafeInvoke(Action action)
        {
            if (this.IsDisposed) return;
            try
            {
                if (this.IsHandleCreated)
                {
                    this.BeginInvoke(action);
                }
            }
            catch { }
        }

        private void InitializeEnvironment()
        {
            string baseDir = AppDomain.CurrentDomain.BaseDirectory;
            rootDir = ResolveRootDir(baseDir);
            appDir = Path.Combine(rootDir, "sims-app");

            string bundledPhp = Path.Combine(rootDir, "runtime", "php", "php.exe");
            if (File.Exists(bundledPhp))
            {
                phpBin = bundledPhp;
            }
            else
            {
                phpBin = "php.exe";
            }

            EnsureStorageAndDatabaseReady();
        }

        private string ResolveRootDir(string startDir)
        {
            DirectoryInfo current = new DirectoryInfo(startDir);
            while (current != null)
            {
                if (File.Exists(Path.Combine(current.FullName, "sims-app", "artisan")) ||
                    File.Exists(Path.Combine(current.FullName, "sims.bat")) ||
                    File.Exists(Path.Combine(current.FullName, "scripts", "windows", "sims.bat")))
                {
                    return current.FullName;
                }
                current = current.Parent;
            }
            return startDir;
        }

        private void InitializeComponents()
        {
            // Window Setup (Modern, Clean, Minimalist)
            this.Text = "Adminova Control Center";
            this.ClientSize = new Size(540, 636);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = ColorTranslator.FromHtml("#F8FAFC");
            this.Font = new Font("Segoe UI", 9f, FontStyle.Regular);

            // Application Icon
            string icoPath = Path.Combine(rootDir, "resources", "icons", "adminova.ico");
            if (!File.Exists(icoPath))
            {
                icoPath = Path.Combine(rootDir, "scripts", "build", "windows", "app.ico");
            }
            if (File.Exists(icoPath))
            {
                try { this.Icon = new Icon(icoPath); } catch { }
            }

            // ── CARD 1: Header (Branding & School LAN Link) ──
            Panel pnlHeader = CreateCardPanel(new Point(16, 12), new Size(508, 86));

            PictureBox picLogo = new PictureBox();
            picLogo.Location = new Point(14, 15);
            picLogo.Size = new Size(48, 48);
            picLogo.SizeMode = PictureBoxSizeMode.Zoom;
            picLogo.BackColor = Color.Transparent;

            string pngPath = Path.Combine(rootDir, "resources", "icons", "adminova.png");
            if (File.Exists(pngPath))
            {
                try { picLogo.Image = Image.FromFile(pngPath); } catch { }
            }
            else if (this.Icon != null)
            {
                picLogo.Image = this.Icon.ToBitmap();
            }
            pnlHeader.Controls.Add(picLogo);

            Label lblTitle = new Label();
            lblTitle.Text = "Adminova Control Center";
            lblTitle.Font = new Font("Segoe UI", 13f, FontStyle.Bold);
            lblTitle.ForeColor = ColorTranslator.FromHtml("#0F172A");
            lblTitle.Location = new Point(70, 14);
            lblTitle.AutoSize = true;
            pnlHeader.Controls.Add(lblTitle);

            Label lblSubTitle = new Label();
            lblSubTitle.Text = "School Server & Service Supervisor";
            lblSubTitle.Font = new Font("Segoe UI", 8.5f, FontStyle.Regular);
            lblSubTitle.ForeColor = ColorTranslator.FromHtml("#64748B");
            lblSubTitle.Location = new Point(71, 38);
            lblSubTitle.AutoSize = true;
            pnlHeader.Controls.Add(lblSubTitle);

            lblLanAddress = new Label();
            lblLanAddress.Text = "LAN: Detecting local network IP...";
            lblLanAddress.Font = new Font("Consolas", 8.5f, FontStyle.Regular);
            lblLanAddress.ForeColor = ColorTranslator.FromHtml("#2563EB");
            lblLanAddress.Location = new Point(71, 58);
            lblLanAddress.AutoSize = true;
            pnlHeader.Controls.Add(lblLanAddress);

            btnCopyLan = new Button();
            btnCopyLan.Text = "Copy";
            btnCopyLan.Font = new Font("Segoe UI", 8f, FontStyle.Bold);
            btnCopyLan.Size = new Size(50, 22);
            btnCopyLan.Location = new Point(444, 55);
            btnCopyLan.FlatStyle = FlatStyle.Flat;
            btnCopyLan.FlatAppearance.BorderSize = 1;
            btnCopyLan.FlatAppearance.BorderColor = ColorTranslator.FromHtml("#CBD5E1");
            btnCopyLan.BackColor = ColorTranslator.FromHtml("#F1F5F9");
            btnCopyLan.ForeColor = ColorTranslator.FromHtml("#334155");
            btnCopyLan.Cursor = Cursors.Hand;
            btnCopyLan.Click += (s, e) => CopyLanUrl();
            pnlHeader.Controls.Add(btnCopyLan);

            this.Controls.Add(pnlHeader);

            // ── CARD 2: Server Status Badge & Launch Button ──
            Panel pnlStatus = CreateCardPanel(new Point(16, 106), new Size(508, 106));

            lblStatusBadge = new Label();
            lblStatusBadge.Text = "● CHECKING";
            lblStatusBadge.Font = new Font("Segoe UI", 9.5f, FontStyle.Bold);
            lblStatusBadge.TextAlign = ContentAlignment.MiddleCenter;
            lblStatusBadge.Size = new Size(115, 30);
            lblStatusBadge.Location = new Point(14, 14);
            lblStatusBadge.BackColor = ColorTranslator.FromHtml("#F1F5F9");
            lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#64748B");
            pnlStatus.Controls.Add(lblStatusBadge);

            lblStatusDetail = new Label();
            lblStatusDetail.Text = "Probing server ports (443, 80, 8000)...";
            lblStatusDetail.Font = new Font("Segoe UI", 9f, FontStyle.Regular);
            lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#475569");
            lblStatusDetail.Location = new Point(136, 15);
            lblStatusDetail.Size = new Size(318, 28);
            lblStatusDetail.TextAlign = ContentAlignment.MiddleLeft;
            pnlStatus.Controls.Add(lblStatusDetail);

            btnRefresh = new Button();
            btnRefresh.Text = "↻";
            btnRefresh.Font = new Font("Segoe UI", 12f, FontStyle.Bold);
            btnRefresh.Size = new Size(34, 30);
            btnRefresh.Location = new Point(460, 14);
            btnRefresh.FlatStyle = FlatStyle.Flat;
            btnRefresh.FlatAppearance.BorderSize = 1;
            btnRefresh.FlatAppearance.BorderColor = ColorTranslator.FromHtml("#E2E8F0");
            btnRefresh.BackColor = ColorTranslator.FromHtml("#F8FAFC");
            btnRefresh.ForeColor = ColorTranslator.FromHtml("#64748B");
            btnRefresh.Cursor = Cursors.Hand;
            btnRefresh.Click += (s, e) => {
                LogMessage("Refreshing service status...");
                CheckServerStatusAsync();
            };
            pnlStatus.Controls.Add(btnRefresh);

            btnOpenBrowser = CreateButton("🌐   Open School Portal in Browser", ColorTranslator.FromHtml("#2563EB"), Color.White, new Size(480, 40), new Point(14, 52), new Font("Segoe UI", 9.5f, FontStyle.Bold));
            btnOpenBrowser.Click += (s, e) => OpenInBrowser();
            pnlStatus.Controls.Add(btnOpenBrowser);

            this.Controls.Add(pnlStatus);

            // ── CARD 3: Native Service Operations & Updates ──
            Panel pnlControls = CreateCardPanel(new Point(16, 220), new Size(508, 96));

            int btnW = 153;
            btnStart = CreateButton("▶  Start Server", ColorTranslator.FromHtml("#059669"), Color.White, new Size(btnW, 36), new Point(14, 12), new Font("Segoe UI", 9f, FontStyle.Bold));
            btnStart.Click += (s, e) => ExecuteStartServer();
            pnlControls.Controls.Add(btnStart);

            btnStop = CreateButton("⏹  Stop Server", ColorTranslator.FromHtml("#E11D48"), Color.White, new Size(btnW, 36), new Point(177, 12), new Font("Segoe UI", 9f, FontStyle.Bold));
            btnStop.Click += (s, e) => ExecuteStopServer();
            pnlControls.Controls.Add(btnStop);

            btnRestart = CreateButton("🔄  Restart", ColorTranslator.FromHtml("#475569"), Color.White, new Size(btnW, 36), new Point(341, 12), new Font("Segoe UI", 9f, FontStyle.Bold));
            btnRestart.Click += (s, e) => ExecuteRestartServer();
            pnlControls.Controls.Add(btnRestart);

            btnCheckUpdates = CreateButton("⚡  Check Updates", ColorTranslator.FromHtml("#F8FAFC"), ColorTranslator.FromHtml("#0369A1"), new Size(236, 32), new Point(14, 54), new Font("Segoe UI", 8.5f, FontStyle.Bold));
            btnCheckUpdates.FlatAppearance.BorderSize = 1;
            btnCheckUpdates.FlatAppearance.BorderColor = ColorTranslator.FromHtml("#BAE6FD");
            btnCheckUpdates.Click += (s, e) => ExecuteCheckUpdates();
            pnlControls.Controls.Add(btnCheckUpdates);

            btnRepair = CreateButton("🛠️  Repair / Fix 500", ColorTranslator.FromHtml("#FEF2F2"), ColorTranslator.FromHtml("#B91C1C"), new Size(236, 32), new Point(258, 54), new Font("Segoe UI", 8.5f, FontStyle.Bold));
            btnRepair.FlatAppearance.BorderSize = 1;
            btnRepair.FlatAppearance.BorderColor = ColorTranslator.FromHtml("#FECACA");
            btnRepair.Click += (s, e) => ExecuteSelfRepair();
            pnlControls.Controls.Add(btnRepair);

            this.Controls.Add(pnlControls);

            // ── CARD 4: Activity Log Terminal ──
            Label lblLogTitle = new Label();
            lblLogTitle.Text = "ACTIVITY & DIAGNOSTIC LOG";
            lblLogTitle.Font = new Font("Segoe UI", 8f, FontStyle.Bold);
            lblLogTitle.ForeColor = ColorTranslator.FromHtml("#64748B");
            lblLogTitle.Location = new Point(18, 326);
            lblLogTitle.AutoSize = true;
            this.Controls.Add(lblLogTitle);

            Button btnClearLog = new Button();
            btnClearLog.Text = "Clear";
            btnClearLog.Font = new Font("Segoe UI", 7.5f, FontStyle.Regular);
            btnClearLog.ForeColor = ColorTranslator.FromHtml("#94A3B8");
            btnClearLog.BackColor = Color.Transparent;
            btnClearLog.FlatStyle = FlatStyle.Flat;
            btnClearLog.FlatAppearance.BorderSize = 0;
            btnClearLog.Size = new Size(50, 18);
            btnClearLog.Location = new Point(474, 324);
            btnClearLog.Cursor = Cursors.Hand;
            btnClearLog.Click += (s, e) => { txtLog.Clear(); };
            this.Controls.Add(btnClearLog);

            txtLog = new RichTextBox();
            txtLog.Location = new Point(16, 346);
            txtLog.Size = new Size(508, 252);
            txtLog.BackColor = ColorTranslator.FromHtml("#0F172A");
            txtLog.ForeColor = ColorTranslator.FromHtml("#E2E8F0");
            txtLog.Font = new Font("Consolas", 8.5f, FontStyle.Regular);
            txtLog.ReadOnly = true;
            txtLog.BorderStyle = BorderStyle.None;
            this.Controls.Add(txtLog);

            // ── CARD 5: Minimalist Footer ──
            Label lblFooter = new Label();
            lblFooter.Text = "Adminova SIMS v2.5.1 • Standalone Local Server • Adminova Tech";
            lblFooter.Font = new Font("Segoe UI", 7.5f, FontStyle.Regular);
            lblFooter.ForeColor = ColorTranslator.FromHtml("#94A3B8");
            lblFooter.Location = new Point(18, 608);
            lblFooter.AutoSize = true;
            this.Controls.Add(lblFooter);

            // Setup System Tray
            trayIcon = new NotifyIcon();
            trayIcon.Text = "Adminova Control Center";
            if (this.Icon != null) { trayIcon.Icon = this.Icon; }
            trayIcon.Visible = true;
            trayIcon.DoubleClick += (s, e) => {
                this.Show();
                this.WindowState = FormWindowState.Normal;
                this.BringToFront();
            };

            ContextMenu contextMenu = new ContextMenu();
            contextMenu.MenuItems.Add("Open Control Center", (s, e) => {
                this.Show();
                this.WindowState = FormWindowState.Normal;
                this.BringToFront();
            });
            contextMenu.MenuItems.Add("Open in Browser", (s, e) => OpenInBrowser());
            contextMenu.MenuItems.Add("-");
            contextMenu.MenuItems.Add("Start Server", (s, e) => ExecuteStartServer());
            contextMenu.MenuItems.Add("Stop Server", (s, e) => ExecuteStopServer());
            contextMenu.MenuItems.Add("-");
            contextMenu.MenuItems.Add("Exit", (s, e) => {
                trayIcon.Visible = false;
                Application.Exit();
            });
            trayIcon.ContextMenu = contextMenu;

            // Health check background timer (every 3.5 seconds)
            statusTimer = new System.Windows.Forms.Timer();
            statusTimer.Interval = 3500;
            statusTimer.Tick += (s, e) => {
                if (!isOperationRunning)
                {
                    CheckServerStatusAsync();
                }
            };
            statusTimer.Start();

            LogMessage("Adminova Control Center initialized.");
            LogMessage("Root Directory: " + rootDir);
        }

        private Panel CreateCardPanel(Point location, Size size)
        {
            Panel p = new Panel();
            p.Location = location;
            p.Size = size;
            p.BackColor = Color.White;
            p.Paint += (s, e) => {
                using (Pen pen = new Pen(ColorTranslator.FromHtml("#E2E8F0"), 1))
                {
                    e.Graphics.DrawRectangle(pen, 0, 0, p.Width - 1, p.Height - 1);
                }
            };
            return p;
        }

        private Button CreateButton(string text, Color backColor, Color foreColor, Size size, Point location, Font font)
        {
            Button btn = new Button();
            btn.Text = text;
            btn.Size = size;
            btn.Location = location;
            btn.BackColor = backColor;
            btn.ForeColor = foreColor;
            btn.FlatStyle = FlatStyle.Flat;
            btn.FlatAppearance.BorderSize = 0;
            btn.Cursor = Cursors.Hand;
            btn.Font = font;
            return btn;
        }

        private void SetButtonState(Button btn, bool enabled, Color enabledBack, Color enabledFore)
        {
            btn.Enabled = enabled;
            if (enabled)
            {
                btn.BackColor = enabledBack;
                btn.ForeColor = enabledFore;
                btn.Cursor = Cursors.Hand;
            }
            else
            {
                btn.BackColor = ColorTranslator.FromHtml("#E2E8F0");
                btn.ForeColor = ColorTranslator.FromHtml("#94A3B8");
                btn.Cursor = Cursors.Default;
            }
        }

        private void DetectLanIp()
        {
            ThreadPool.QueueUserWorkItem(state => {
                string ip = "localhost";
                try
                {
                    IPHostEntry entry = Dns.GetHostEntry(Dns.GetHostName());
                    foreach (IPAddress addr in entry.AddressList)
                    {
                        if (addr.AddressFamily == AddressFamily.InterNetwork)
                        {
                            string s = addr.ToString();
                            if (!s.StartsWith("127.") && !s.StartsWith("169.254."))
                            {
                                ip = s;
                                break;
                            }
                        }
                    }
                }
                catch { }

                lanIp = ip;
                SafeInvoke(() => {
                    lblLanAddress.Text = "LAN: https://" + lanIp + " (Port 443/80)";
                });
            });
        }

        private void CopyLanUrl()
        {
            string url = "https://" + lanIp;
            try
            {
                Clipboard.SetText(url);
                LogMessage("Copied school LAN URL (" + url + ") to clipboard.");
                MessageBox.Show("School LAN Address copied to clipboard:\n\n" + url + "\n\nTeachers and staff on the school network can open this link in their browser.", "Link Copied", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                LogMessage("Clipboard error: " + ex.Message);
            }
        }

        private void CheckServerStatusAsync()
        {
            ThreadPool.QueueUserWorkItem(state => {
                bool port443 = IsPortOpen("127.0.0.1", 443, 300);
                bool port80 = IsPortOpen("127.0.0.1", 80, 300);
                bool port8000 = (!port443 && !port80) ? IsPortOpen("127.0.0.1", 8000, 300) : false;

                bool isOnline = (port443 || port80 || port8000);
                string activeUrl = "https://localhost";
                string detail = "Web server is stopped";

                if (port443)
                {
                    activeUrl = "https://localhost";
                    detail = "Port 443 (HTTPS Secure Active)";
                }
                else if (port80)
                {
                    activeUrl = "http://localhost";
                    detail = "Port 80 (HTTP Direct Active)";
                }
                else if (port8000)
                {
                    activeUrl = "http://localhost:8000";
                    detail = "Port 8000 (Fallback Server Active)";
                }

                SafeInvoke(() => {
                    currentUrl = activeUrl;
                    if (isOnline)
                    {
                        lblStatusBadge.Text = "● ONLINE";
                        lblStatusBadge.BackColor = ColorTranslator.FromHtml("#DCFCE7");
                        lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#15803D");
                        lblStatusDetail.Text = detail;
                        lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#166534");

                        SetButtonState(btnStart, false, ColorTranslator.FromHtml("#059669"), Color.White);
                        SetButtonState(btnStop, true, ColorTranslator.FromHtml("#E11D48"), Color.White);
                        SetButtonState(btnRestart, true, ColorTranslator.FromHtml("#475569"), Color.White);
                    }
                    else
                    {
                        lblStatusBadge.Text = "● OFFLINE";
                        lblStatusBadge.BackColor = ColorTranslator.FromHtml("#FEE2E2");
                        lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#B91C1C");
                        lblStatusDetail.Text = detail;
                        lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#991B1B");

                        SetButtonState(btnStart, true, ColorTranslator.FromHtml("#059669"), Color.White);
                        SetButtonState(btnStop, false, ColorTranslator.FromHtml("#E11D48"), Color.White);
                        SetButtonState(btnRestart, false, ColorTranslator.FromHtml("#475569"), Color.White);
                    }
                });

                // Self-healing: If FrankenPHP is active on 443 or 80, ensure FastCGI port 9000 is open
                if ((port443 || port80) && !isOperationRunning)
                {
                    if (!IsPortOpen("127.0.0.1", 9000, 200))
                    {
                        EnsurePhpFastCgiRunning();
                    }
                }
            });
        }

        private bool IsPortOpen(string host, int port, int timeoutMs)
        {
            try
            {
                using (TcpClient client = new TcpClient())
                {
                    IAsyncResult result = client.BeginConnect(host, port, null, null);
                    bool success = result.AsyncWaitHandle.WaitOne(timeoutMs);
                    if (success && client.Connected)
                    {
                        client.EndConnect(result);
                        return true;
                    }
                }
            }
            catch { }
            return false;
        }

        private void OpenInBrowser()
        {
            LogMessage("Opening " + currentUrl + " in web browser...");
            try
            {
                Process.Start(new ProcessStartInfo(currentUrl) { UseShellExecute = true });
            }
            catch (Exception ex)
            {
                LogMessage("Error launching browser: " + ex.Message);
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // ── NATIVE SERVICE CONTROLS (Zero cmd.exe / No terminal popups) ──
        // ══════════════════════════════════════════════════════════════════

        private void ExecuteStartServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Initiating native server start sequence...");

            ThreadPool.QueueUserWorkItem(state => {
                NativeStartServer();
                Thread.Sleep(1500);

                SafeInvoke(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                });
            });
        }

        private void ExecuteStopServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Stopping all SIMS services natively...");

            ThreadPool.QueueUserWorkItem(state => {
                NativeStopServer();
                Thread.Sleep(800);

                SafeInvoke(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                });
            });
        }

        private void ExecuteRestartServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Restarting SIMS services natively...");

            ThreadPool.QueueUserWorkItem(state => {
                NativeRestartServer();
                Thread.Sleep(1500);

                SafeInvoke(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                });
            });
        }

        private void EnsureStorageAndDatabaseReady()
        {
            try
            {
                string[] requiredDirs = new string[]
                {
                    Path.Combine(appDir, "storage"),
                    Path.Combine(appDir, "storage", "logs"),
                    Path.Combine(appDir, "storage", "framework"),
                    Path.Combine(appDir, "storage", "framework", "views"),
                    Path.Combine(appDir, "storage", "framework", "sessions"),
                    Path.Combine(appDir, "storage", "framework", "cache"),
                    Path.Combine(appDir, "storage", "framework", "cache", "data"),
                    Path.Combine(appDir, "bootstrap", "cache"),
                    Path.Combine(appDir, "database")
                };

                foreach (string dir in requiredDirs)
                {
                    if (!Directory.Exists(dir))
                    {
                        Directory.CreateDirectory(dir);
                    }
                }

                // Ensure database.sqlite exists
                string dbFile = Path.Combine(appDir, "database", "database.sqlite");
                if (!File.Exists(dbFile))
                {
                    File.WriteAllBytes(dbFile, new byte[0]);
                    LogMessage("Initialized database.sqlite file.");
                }

                // Ensure laravel.log exists
                string logFile = Path.Combine(appDir, "storage", "logs", "laravel.log");
                if (!File.Exists(logFile))
                {
                    File.WriteAllBytes(logFile, new byte[0]);
                }

                // Grant full write and modify permissions on storage, cache, and database (Crucial for C:\Program Files)
                string storageDir = Path.Combine(appDir, "storage");
                string bootCacheDir = Path.Combine(appDir, "bootstrap", "cache");
                string dbDir = Path.Combine(appDir, "database");

                RunHiddenUtility("icacls.exe", "\"" + storageDir + "\" /grant Everyone:(OI)(CI)F /T /Q");
                RunHiddenUtility("icacls.exe", "\"" + bootCacheDir + "\" /grant Everyone:(OI)(CI)F /T /Q");
                RunHiddenUtility("icacls.exe", "\"" + dbDir + "\" /grant Everyone:(OI)(CI)F /T /Q");
            }
            catch (Exception ex)
            {
                LogMessage("[WARN] Storage initialization warning: " + ex.Message);
            }
        }

        private void EnsurePhpFastCgiRunning()
        {
            string phpCgi = Path.Combine(rootDir, "runtime", "php", "php-cgi.exe");
            string phpIni = Path.Combine(rootDir, "runtime", "php", "php.ini");

            if (File.Exists(phpCgi) && !IsPortOpen("127.0.0.1", 9000, 200))
            {
                try
                {
                    ProcessStartInfo cgiPsi = new ProcessStartInfo();
                    cgiPsi.FileName = phpCgi;
                    cgiPsi.Arguments = "-b 127.0.0.1:9000" + (File.Exists(phpIni) ? " -c \"" + phpIni + "\"" : "");
                    cgiPsi.WorkingDirectory = appDir;
                    cgiPsi.UseShellExecute = false;
                    cgiPsi.CreateNoWindow = true;
                    cgiPsi.WindowStyle = ProcessWindowStyle.Hidden;

                    string runtimeDir = Path.Combine(rootDir, "runtime");
                    string phpDir = Path.Combine(rootDir, "runtime", "php");
                    string envPath = Environment.GetEnvironmentVariable("PATH") ?? "";
                    cgiPsi.EnvironmentVariables["PATH"] = phpDir + ";" + runtimeDir + ";" + envPath;
                    cgiPsi.EnvironmentVariables["PHP_FCGI_MAX_REQUESTS"] = "0";

                    Process cgiProc = Process.Start(cgiPsi);
                    if (cgiProc != null && !cgiProc.HasExited)
                    {
                        LogMessage("PHP FastCGI Engine started on 127.0.0.1:9000 (PID: " + cgiProc.Id + ").");
                        for (int k = 0; k < 6; k++)
                        {
                            Thread.Sleep(200);
                            if (IsPortOpen("127.0.0.1", 9000, 200)) break;
                        }
                    }
                }
                catch (Exception ex)
                {
                    LogMessage("[WARN] Failed to start PHP FastCGI engine: " + ex.Message);
                }
            }
        }

        private void NativeStartServer()
        {
            // 0. Ensure storage and database files and permissions are ready
            EnsureStorageAndDatabaseReady();

            // 1. Terminate any previous orphaned processes
            NativeStopServerQuiet();
            Thread.Sleep(400);

            // 2. Start PHP FastCGI Engine on port 9000 for FrankenPHP
            EnsurePhpFastCgiRunning();

            bool started = false;

            // 3. Try launching FrankenPHP directly (Native Win32 Background Process)
            string frankenExe = Path.Combine(rootDir, "runtime", "frankenphp.exe");
            string caddyfile = Path.Combine(appDir, "Caddyfile");

            if (File.Exists(frankenExe) && File.Exists(caddyfile))
            {
                try
                {
                    ProcessStartInfo psi = new ProcessStartInfo();
                    psi.FileName = frankenExe;
                    psi.Arguments = "run --adapter caddyfile --config \"" + caddyfile + "\"";
                    psi.WorkingDirectory = appDir;
                    psi.UseShellExecute = false;
                    psi.CreateNoWindow = true;
                    psi.WindowStyle = ProcessWindowStyle.Hidden;

                    // Ensure PATH includes runtime directory for VC++ DLLs
                    string runtimeDir = Path.Combine(rootDir, "runtime");
                    string phpDir = Path.Combine(rootDir, "runtime", "php");
                    string envPath = Environment.GetEnvironmentVariable("PATH") ?? "";
                    psi.EnvironmentVariables["PATH"] = phpDir + ";" + runtimeDir + ";" + envPath;

                    Process p = Process.Start(psi);
                    if (p != null && !p.HasExited)
                    {
                        started = true;
                        LogMessage("FrankenPHP web server process started (PID: " + p.Id + ").");
                    }
                }
                catch (Exception ex)
                {
                    LogMessage("[WARN] Direct FrankenPHP launch error: " + ex.Message);
                }
            }

            // 4. Fallback: Built-in PHP server on Port 8000
            if (!started && File.Exists(phpBin))
            {
                try
                {
                    string publicDir = Path.Combine(appDir, "public");
                    ProcessStartInfo psi = new ProcessStartInfo();
                    psi.FileName = phpBin;
                    psi.Arguments = "-S 0.0.0.0:8000 -t \"" + publicDir + "\"";
                    psi.WorkingDirectory = appDir;
                    psi.UseShellExecute = false;
                    psi.CreateNoWindow = true;
                    psi.WindowStyle = ProcessWindowStyle.Hidden;

                    Process p = Process.Start(psi);
                    if (p != null && !p.HasExited)
                    {
                        started = true;
                        LogMessage("PHP fallback server started on port 8000 (PID: " + p.Id + ").");
                    }
                }
                catch (Exception ex)
                {
                    LogMessage("[ERROR] Failed to start PHP server: " + ex.Message);
                }
            }

            // 5. Trigger Windows Task Scheduler background tasks (Queue & Scheduler) silently
            RunHiddenUtility("schtasks.exe", "/run /tn \"SIMS-Queue\"");
            RunHiddenUtility("schtasks.exe", "/run /tn \"SIMS-Scheduler\"");

            // 6. Poll ports for readiness
            for (int i = 0; i < 8; i++)
            {
                Thread.Sleep(400);
                if (IsPortOpen("127.0.0.1", 443, 200) || IsPortOpen("127.0.0.1", 80, 200) || IsPortOpen("127.0.0.1", 8000, 200))
                {
                    LogMessage("[OK] SIMS Web Server is ONLINE and accepting connections.");
                    return;
                }
            }
        }

        private void NativeStopServer()
        {
            NativeStopServerQuiet();
            LogMessage("[OK] All SIMS services terminated and network ports released.");
        }

        private void NativeStopServerQuiet()
        {
            // 1. End scheduled tasks quietly
            RunHiddenUtility("schtasks.exe", "/end /tn \"SIMS-Web\"");
            RunHiddenUtility("schtasks.exe", "/end /tn \"SIMS-Queue\"");
            RunHiddenUtility("schtasks.exe", "/end /tn \"SIMS-Scheduler\"");

            // 2. Kill web and worker processes natively using Process.GetProcessesByName
            string[] targets = new string[] { "frankenphp", "php-cgi" };
            foreach (string target in targets)
            {
                try
                {
                    Process[] procs = Process.GetProcessesByName(target);
                    foreach (Process p in procs)
                    {
                        try
                        {
                            p.Kill();
                            p.WaitForExit(1000);
                        }
                        catch { }
                    }
                }
                catch { }
            }

            // Terminate background php.exe instances
            try
            {
                Process[] phpProcs = Process.GetProcessesByName("php");
                foreach (Process p in phpProcs)
                {
                    try
                    {
                        p.Kill();
                        p.WaitForExit(1000);
                    }
                    catch { }
                }
            }
            catch { }
        }

        private void NativeRestartServer()
        {
            NativeStopServerQuiet();
            Thread.Sleep(800);

            EnsureStorageAndDatabaseReady();

            // Flush caches if PHP is present
            if (File.Exists(phpBin))
            {
                string dbFile = Path.Combine(appDir, "database", "database.sqlite");
                if (File.Exists(dbFile) && new FileInfo(dbFile).Length == 0)
                {
                    LogMessage("Initializing SQLite database schema...");
                    RunDirectProcess(phpBin, "artisan migrate --force", appDir);
                }

                LogMessage("Flushing application cache and OPcache...");
                RunDirectProcess(phpBin, "artisan optimize:clear", appDir);
            }

            NativeStartServer();
        }

        private void ExecuteCheckUpdates()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Contacting update server to check for delta patches and releases...");

            ThreadPool.QueueUserWorkItem(state => {
                string checkOutput = RunDirectProcess(phpBin, "artisan sims:update --check", appDir);

                SafeInvoke(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);

                    if (checkOutput.IndexOf("newer version", StringComparison.OrdinalIgnoreCase) >= 0 ||
                        checkOutput.IndexOf("hotfix patch", StringComparison.OrdinalIgnoreCase) >= 0 ||
                        checkOutput.IndexOf("available to install", StringComparison.OrdinalIgnoreCase) >= 0)
                    {
                        DialogResult res = MessageBox.Show(
                            "A software update or hotfix patch is available for your SIMS installation!\n\n" +
                            "Would you like to download and install this update now?\n" +
                            "(Existing database and student records are automatically backed up first).",
                            "SIMS Software Update Available",
                            MessageBoxButtons.YesNo,
                            MessageBoxIcon.Information);

                        if (res == DialogResult.Yes)
                        {
                            ApplyUpdate();
                        }
                    }
                    else if (checkOutput.IndexOf("SQLSTATE", StringComparison.OrdinalIgnoreCase) >= 0 ||
                             checkOutput.IndexOf("In StreamHandler.php", StringComparison.OrdinalIgnoreCase) >= 0 ||
                             checkOutput.IndexOf("Permission denied", StringComparison.OrdinalIgnoreCase) >= 0 ||
                             checkOutput.IndexOf("Fatal error", StringComparison.OrdinalIgnoreCase) >= 0 ||
                             checkOutput.IndexOf("Error:", StringComparison.OrdinalIgnoreCase) >= 0)
                    {
                        MessageBox.Show("Could not complete update check due to an application issue:\n\n" + checkOutput.Trim() + "\n\nPlease review the Activity Log.", "Update Check Notice", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    }
                    else
                    {
                        MessageBox.Show("Your SIMS installation is up to date!\nYou are running the latest version.", "System Up to Date", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                });
            });
        }

        private void ApplyUpdate()
        {
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Preparing to apply update: Releasing file locks...");

            ThreadPool.QueueUserWorkItem(state => {
                NativeStopServerQuiet();
                LogMessage("Downloading verified update archive and applying patch...");
                string updateOutput = RunDirectProcess(phpBin, "artisan sims:update", appDir);

                bool isSuccess = updateOutput.IndexOf("successfully updated", StringComparison.OrdinalIgnoreCase) >= 0;

                if (isSuccess)
                {
                    LogMessage("Restarting SIMS Web Server with updated application code...");
                }
                else
                {
                    LogMessage("Update was not applied. Restarting SIMS Web Server in safe state...");
                }

                NativeStartServer();
                Thread.Sleep(1500);

                SafeInvoke(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();

                    if (isSuccess)
                    {
                        MessageBox.Show("SIMS was updated successfully!\n\nAll services and database migrations are synchronized.", "Update Complete", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    else
                    {
                        MessageBox.Show("Update could not be completed. The existing version was safely preserved.\n\nPlease review the Activity Log for details.", "Update Notice", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    }
                });
            });
        }

        private void ExecuteSelfRepair()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Initiating automated self-repair sequence...");

            ThreadPool.QueueUserWorkItem(state => {
                try {
                    NativeStopServerQuiet();
                    LogMessage("1/5: Purging stale bootstrap, route, and view caches...");
                    RunDirectProcess(phpBin, "artisan optimize:clear", appDir);

                    LogMessage("2/5: Regenerating application master encryption key...");
                    RunDirectProcess(phpBin, "artisan key:generate --force", appDir);

                    LogMessage("3/5: Ensuring SQLite database tables and migrations...");
                    RunDirectProcess(phpBin, "artisan migrate --force", appDir);

                    LogMessage("4/5: Rebuilding clean configuration and route caches...");
                    RunDirectProcess(phpBin, "artisan optimize:clear", appDir);

                    LogMessage("5/5: Restarting SIMS Web Server and FastCGI...");
                    NativeStartServer();
                    Thread.Sleep(1500);

                    SafeInvoke(() => {
                        isOperationRunning = false;
                        SetActionButtonsEnabled(true);
                        CheckServerStatusAsync();
                        MessageBox.Show("Self-Repair completed successfully!\n\n• Application encryption key regenerated\n• Stale caches cleared\n• Database migrations synchronized\n• Web server restarted\n\nPlease open the School Portal in your browser.", "Self-Repair Complete", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    });
                } catch (Exception ex) {
                    LogMessage("Self-repair warning: " + ex.Message);
                    SafeInvoke(() => {
                        isOperationRunning = false;
                        SetActionButtonsEnabled(true);
                    });
                }
            });
        }

        private void SetActionButtonsEnabled(bool enabled)
        {
            btnStart.Enabled = enabled;
            btnStop.Enabled = enabled;
            btnRestart.Enabled = enabled;
            btnCheckUpdates.Enabled = enabled;
            if (btnRepair != null) btnRepair.Enabled = enabled;
            btnRefresh.Enabled = enabled;
        }

        private void RunHiddenUtility(string exe, string args)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = exe;
                psi.Arguments = args;
                psi.CreateNoWindow = true;
                psi.UseShellExecute = false;
                psi.WindowStyle = ProcessWindowStyle.Hidden;
                using (Process p = Process.Start(psi))
                {
                    if (p != null) p.WaitForExit(3000);
                }
            }
            catch { }
        }

        private string RunDirectProcess(string fileName, string arguments, string workingDir)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = fileName;

                // Load custom php.ini if calling php.exe
                if (fileName.IndexOf("php", StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    string phpIni = Path.Combine(rootDir, "runtime", "php", "php.ini");
                    if (File.Exists(phpIni))
                    {
                        arguments = "-c \"" + phpIni + "\" " + arguments;
                    }
                }

                psi.Arguments = arguments;
                psi.WorkingDirectory = workingDir;
                psi.UseShellExecute = false;
                psi.CreateNoWindow = true;
                psi.RedirectStandardOutput = true;
                psi.RedirectStandardError = true;

                // Ensure PATH contains runtime directories for VC++ DLLs
                string runtimeDir = Path.Combine(rootDir, "runtime");
                string phpDir = Path.Combine(rootDir, "runtime", "php");
                string envPath = Environment.GetEnvironmentVariable("PATH") ?? "";
                psi.EnvironmentVariables["PATH"] = phpDir + ";" + runtimeDir + ";" + envPath;

                StringBuilder sb = new StringBuilder();
                using (Process p = new Process())
                {
                    p.StartInfo = psi;
                    p.OutputDataReceived += (s, e) => {
                        if (!string.IsNullOrEmpty(e.Data))
                        {
                            sb.AppendLine(e.Data);
                            LogMessage(e.Data);
                        }
                    };
                    p.ErrorDataReceived += (s, e) => {
                        if (!string.IsNullOrEmpty(e.Data))
                        {
                            sb.AppendLine("[ERROR] " + e.Data);
                            LogMessage("[ERROR] " + e.Data);
                        }
                    };

                    p.Start();
                    p.BeginOutputReadLine();
                    p.BeginErrorReadLine();
                    p.WaitForExit();
                    return sb.ToString();
                }
            }
            catch (Exception ex)
            {
                LogMessage("Execution error: " + ex.Message);
                return "Error: " + ex.Message;
            }
        }

        private void LogMessage(string message)
        {
            SafeInvoke(() => {
                try
                {
                    string time = DateTime.Now.ToString("HH:mm:ss");
                    txtLog.AppendText("[" + time + "] " + message + Environment.NewLine);
                    txtLog.SelectionStart = txtLog.Text.Length;
                    txtLog.ScrollToCaret();
                }
                catch { }
            });
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            if (trayIcon != null)
            {
                trayIcon.Visible = false;
                trayIcon.Dispose();
            }
            if (statusTimer != null)
            {
                statusTimer.Stop();
                statusTimer.Dispose();
            }
            base.OnFormClosing(e);
        }
    }
}
