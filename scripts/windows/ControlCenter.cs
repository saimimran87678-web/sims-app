using System;
using System.Drawing;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Diagnostics;
using System.Windows.Forms;
using System.Threading;
using System.Text.RegularExpressions;

namespace Adminova.ControlCenter
{
    public class ControlCenterForm : Form
    {
        private string rootDir;
        private string appDir;
        private string simsBat;
        private string phpBin;
        private string lanIp = "localhost";
        private string currentUrl = "https://localhost";

        // Controls
        private Label lblStatusBadge;
        private Label lblStatusDetail;
        private Label lblLanAddress;
        private Button btnStart;
        private Button btnStop;
        private Button btnRestart;
        private Button btnOpenBrowser;
        private Button btnCheckUpdates;
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
            DetectLanIp();
            CheckServerStatusAsync();
        }

        private void InitializeEnvironment()
        {
            string baseDir = AppDomain.CurrentDomain.BaseDirectory;
            rootDir = ResolveRootDir(baseDir);
            appDir = Path.Combine(rootDir, "sims-app");
            simsBat = Path.Combine(rootDir, "sims.bat");
            if (!File.Exists(simsBat))
            {
                simsBat = Path.Combine(rootDir, "scripts", "windows", "sims.bat");
            }

            string bundledPhp = Path.Combine(rootDir, "runtime", "php", "php.exe");
            if (File.Exists(bundledPhp))
            {
                phpBin = bundledPhp;
            }
            else
            {
                phpBin = "php.exe";
            }
        }

        private string ResolveRootDir(string startDir)
        {
            DirectoryInfo current = new DirectoryInfo(startDir);
            while (current != null)
            {
                if (File.Exists(Path.Combine(current.FullName, "sims-app", "artisan")) ||
                    File.Exists(Path.Combine(current.FullName, "sims.bat")))
                {
                    return current.FullName;
                }
                current = current.Parent;
            }
            return startDir;
        }

        private void InitializeComponents()
        {
            this.Text = "Adminova Control Center";
            this.Size = new Size(580, 710);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.BackColor = ColorTranslator.FromHtml("#F8FAFC");
            this.Font = new Font("Segoe UI", 9.25f, FontStyle.Regular);

            // Set Application Icon if available
            string icoPath = Path.Combine(rootDir, "resources", "icons", "adminova.ico");
            if (!File.Exists(icoPath))
            {
                icoPath = Path.Combine(rootDir, "scripts", "build", "windows", "app.ico");
            }
            if (File.Exists(icoPath))
            {
                try { this.Icon = new Icon(icoPath); } catch { }
            }

            // 1. Header Panel
            Panel headerPanel = new Panel();
            headerPanel.Location = new Point(16, 14);
            headerPanel.Size = new Size(532, 90);
            headerPanel.BackColor = Color.White;
            headerPanel.BorderStyle = BorderStyle.None;
            headerPanel.Paint += (s, e) => {
                using (Pen p = new Pen(ColorTranslator.FromHtml("#E2E8F0"), 1))
                {
                    e.Graphics.DrawRectangle(p, 0, 0, headerPanel.Width - 1, headerPanel.Height - 1);
                }
            };

            PictureBox picLogo = new PictureBox();
            picLogo.Location = new Point(16, 16);
            picLogo.Size = new Size(56, 56);
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
            headerPanel.Controls.Add(picLogo);

            Label lblTitle = new Label();
            lblTitle.Text = "Adminova School Management";
            lblTitle.Font = new Font("Segoe UI", 13.5f, FontStyle.Bold);
            lblTitle.ForeColor = ColorTranslator.FromHtml("#0F172A");
            lblTitle.Location = new Point(84, 14);
            lblTitle.AutoSize = true;
            headerPanel.Controls.Add(lblTitle);

            Label lblSubTitle = new Label();
            lblSubTitle.Text = "Service Control Center & Update Supervisor";
            lblSubTitle.Font = new Font("Segoe UI", 9f, FontStyle.Regular);
            lblSubTitle.ForeColor = ColorTranslator.FromHtml("#64748B");
            lblSubTitle.Location = new Point(86, 39);
            lblSubTitle.AutoSize = true;
            headerPanel.Controls.Add(lblSubTitle);

            lblLanAddress = new Label();
            lblLanAddress.Text = "Local School LAN: Detecting...";
            lblLanAddress.Font = new Font("Segoe UI", 8.5f, FontStyle.Regular);
            lblLanAddress.ForeColor = ColorTranslator.FromHtml("#3B82F6");
            lblLanAddress.Location = new Point(86, 61);
            lblLanAddress.AutoSize = true;
            headerPanel.Controls.Add(lblLanAddress);

            btnCopyLan = new Button();
            btnCopyLan.Text = "Copy";
            btnCopyLan.Font = new Font("Segoe UI", 8f, FontStyle.Bold);
            btnCopyLan.Size = new Size(48, 22);
            btnCopyLan.Location = new Point(470, 59);
            btnCopyLan.FlatStyle = FlatStyle.Flat;
            btnCopyLan.FlatAppearance.BorderColor = ColorTranslator.FromHtml("#CBD5E1");
            btnCopyLan.BackColor = ColorTranslator.FromHtml("#F1F5F9");
            btnCopyLan.ForeColor = ColorTranslator.FromHtml("#334155");
            btnCopyLan.Cursor = Cursors.Hand;
            btnCopyLan.Click += (s, e) => CopyLanUrl();
            headerPanel.Controls.Add(btnCopyLan);

            this.Controls.Add(headerPanel);

            // 2. Status Card Panel
            Panel statusCard = new Panel();
            statusCard.Location = new Point(16, 116);
            statusCard.Size = new Size(532, 76);
            statusCard.BackColor = Color.White;
            statusCard.Paint += (s, e) => {
                using (Pen p = new Pen(ColorTranslator.FromHtml("#E2E8F0"), 1))
                {
                    e.Graphics.DrawRectangle(p, 0, 0, statusCard.Width - 1, statusCard.Height - 1);
                }
            };

            lblStatusBadge = new Label();
            lblStatusBadge.Text = "● CHECKING...";
            lblStatusBadge.Font = new Font("Segoe UI", 11.5f, FontStyle.Bold);
            lblStatusBadge.TextAlign = ContentAlignment.MiddleCenter;
            lblStatusBadge.Size = new Size(130, 38);
            lblStatusBadge.Location = new Point(16, 19);
            lblStatusBadge.BackColor = ColorTranslator.FromHtml("#E2E8F0");
            lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#475569");
            statusCard.Controls.Add(lblStatusBadge);

            lblStatusDetail = new Label();
            lblStatusDetail.Text = "Probing service ports (443, 80, 8000)...";
            lblStatusDetail.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);
            lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#334155");
            lblStatusDetail.Location = new Point(158, 21);
            lblStatusDetail.Size = new Size(270, 36);
            statusCard.Controls.Add(lblStatusDetail);

            btnRefresh = CreateFlatButton("⟳", ColorTranslator.FromHtml("#F1F5F9"), ColorTranslator.FromHtml("#1E293B"), new Size(42, 38), new Point(474, 19));
            btnRefresh.Font = new Font("Segoe UI", 13f, FontStyle.Bold);
            btnRefresh.Click += (s, e) => {
                LogMessage("Refreshing service status...");
                CheckServerStatusAsync();
            };
            statusCard.Controls.Add(btnRefresh);

            this.Controls.Add(statusCard);

            // 3. Primary Action Buttons Panel
            btnOpenBrowser = CreateFlatButton("🌐  Open Adminova in Browser", ColorTranslator.FromHtml("#4F46E5"), Color.White, new Size(532, 44), new Point(16, 204));
            btnOpenBrowser.Font = new Font("Segoe UI", 10.5f, FontStyle.Bold);
            btnOpenBrowser.Click += (s, e) => OpenInBrowser();
            this.Controls.Add(btnOpenBrowser);

            int btnY = 258;
            int btnW = 168;
            btnStart = CreateFlatButton("▶  Start Server", ColorTranslator.FromHtml("#10B981"), Color.White, new Size(btnW, 38), new Point(16, btnY));
            btnStart.Click += (s, e) => ExecuteStartServer();
            this.Controls.Add(btnStart);

            btnStop = CreateFlatButton("⏹  Stop Server", ColorTranslator.FromHtml("#EF4444"), Color.White, new Size(btnW, 38), new Point(198, btnY));
            btnStop.Click += (s, e) => ExecuteStopServer();
            this.Controls.Add(btnStop);

            btnRestart = CreateFlatButton("🔄  Restart", ColorTranslator.FromHtml("#64748B"), Color.White, new Size(btnW, 38), new Point(380, btnY));
            btnRestart.Click += (s, e) => ExecuteRestartServer();
            this.Controls.Add(btnRestart);

            btnCheckUpdates = CreateFlatButton("🚀  Check for Software Updates", ColorTranslator.FromHtml("#0284C7"), Color.White, new Size(532, 38), new Point(16, 306));
            btnCheckUpdates.Font = new Font("Segoe UI", 10f, FontStyle.Bold);
            btnCheckUpdates.Click += (s, e) => ExecuteCheckUpdates();
            this.Controls.Add(btnCheckUpdates);

            // 4. Activity Log Area
            Label lblLogHeader = new Label();
            lblLogHeader.Text = "ACTIVITY & DIAGNOSTIC LOG";
            lblLogHeader.Font = new Font("Segoe UI", 8f, FontStyle.Bold);
            lblLogHeader.ForeColor = ColorTranslator.FromHtml("#64748B");
            lblLogHeader.Location = new Point(16, 356);
            lblLogHeader.AutoSize = true;
            this.Controls.Add(lblLogHeader);

            txtLog = new RichTextBox();
            txtLog.Location = new Point(16, 376);
            txtLog.Size = new Size(532, 260);
            txtLog.BackColor = ColorTranslator.FromHtml("#0F172A");
            txtLog.ForeColor = ColorTranslator.FromHtml("#E2E8F0");
            txtLog.Font = new Font("Consolas", 9f, FontStyle.Regular);
            txtLog.ReadOnly = true;
            txtLog.BorderStyle = BorderStyle.None;
            this.Controls.Add(txtLog);

            // 5. Footer info
            Label lblFooter = new Label();
            lblFooter.Text = "SIMS v2.5.1 • Standalone Desktop Server • Adminova Tech";
            lblFooter.Font = new Font("Segoe UI", 8f, FontStyle.Regular);
            lblFooter.ForeColor = ColorTranslator.FromHtml("#94A3B8");
            lblFooter.Location = new Point(16, 646);
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

            // Timer for health checks (every 3.5 seconds)
            statusTimer = new System.Windows.Forms.Timer();
            statusTimer.Interval = 3500;
            statusTimer.Tick += (s, e) => {
                if (!isOperationRunning)
                {
                    CheckServerStatusAsync();
                }
            };
            statusTimer.Start();

            LogMessage("Adminova Control Center initialized successfully.");
            LogMessage("Installation Root: " + rootDir);
        }

        private Button CreateFlatButton(string text, Color backColor, Color foreColor, Size size, Point location)
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
            btn.Font = new Font("Segoe UI", 9.5f, FontStyle.Bold);
            return btn;
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
                this.Invoke(new Action(() => {
                    lblLanAddress.Text = "School LAN: https://" + lanIp + " (Port 443/80)";
                }));
            });
        }

        private void CopyLanUrl()
        {
            string url = "https://" + lanIp;
            Clipboard.SetText(url);
            LogMessage("Copied school LAN URL (" + url + ") to clipboard.");
            MessageBox.Show("School LAN Address copied to clipboard:\n\n" + url + "\n\nTeachers and staff on the school network can open this link in their browser.", "Link Copied", MessageBoxButtons.OK, MessageBoxIcon.Information);
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

                this.Invoke(new Action(() => {
                    currentUrl = activeUrl;
                    if (isOnline)
                    {
                        lblStatusBadge.Text = "● ONLINE";
                        lblStatusBadge.BackColor = ColorTranslator.FromHtml("#DCFCE7");
                        lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#15803D");
                        lblStatusDetail.Text = detail;
                        lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#166534");

                        btnStart.Enabled = false;
                        btnStop.Enabled = true;
                        btnRestart.Enabled = true;
                    }
                    else
                    {
                        lblStatusBadge.Text = "● OFFLINE";
                        lblStatusBadge.BackColor = ColorTranslator.FromHtml("#FEE2E2");
                        lblStatusBadge.ForeColor = ColorTranslator.FromHtml("#B91C1C");
                        lblStatusDetail.Text = detail;
                        lblStatusDetail.ForeColor = ColorTranslator.FromHtml("#991B1B");

                        btnStart.Enabled = true;
                        btnStop.Enabled = false;
                        btnRestart.Enabled = false;
                    }
                }));
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

        private void ExecuteStartServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Starting SIMS Web Server and services...");

            ThreadPool.QueueUserWorkItem(state => {
                RunCommand("cmd.exe", "/c \"" + simsBat + "\" start", rootDir);
                Thread.Sleep(2000);
                this.Invoke(new Action(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                }));
            });
        }

        private void ExecuteStopServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Stopping SIMS services and freeing network ports...");

            ThreadPool.QueueUserWorkItem(state => {
                RunCommand("cmd.exe", "/c \"" + simsBat + "\" stop", rootDir);
                Thread.Sleep(1000);
                this.Invoke(new Action(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                }));
            });
        }

        private void ExecuteRestartServer()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Restarting SIMS services & clearing OPcache...");

            ThreadPool.QueueUserWorkItem(state => {
                RunCommand("cmd.exe", "/c \"" + simsBat + "\" restart", rootDir);
                Thread.Sleep(2000);
                this.Invoke(new Action(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();
                }));
            });
        }

        private void ExecuteCheckUpdates()
        {
            if (isOperationRunning) return;
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Contacting update server to check for delta patches and releases...");

            ThreadPool.QueueUserWorkItem(state => {
                string checkOutput = RunCommand(phpBin, "artisan sims:update --check", appDir);

                this.Invoke(new Action(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);

                    if (checkOutput.IndexOf("newer version", StringComparison.OrdinalIgnoreCase) >= 0 ||
                        checkOutput.IndexOf("hotfix patch", StringComparison.OrdinalIgnoreCase) >= 0 ||
                        checkOutput.IndexOf("available to install", StringComparison.OrdinalIgnoreCase) >= 0)
                    {
                        DialogResult res = MessageBox.Show(
                            "A software update / hotfix patch is available for your SIMS installation!\n\n" +
                            "Would you like to download and install this update now?\n" +
                            "(Your existing database and student records are automatically backed up first).",
                            "SIMS Software Update Available",
                            MessageBoxButtons.YesNo,
                            MessageBoxIcon.Information);

                        if (res == DialogResult.Yes)
                        {
                            ApplyUpdate();
                        }
                    }
                    else
                    {
                        MessageBox.Show("Your SIMS installation is up to date!\nYou are running the latest version.", "System Up to Date", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }));
            });
        }

        private void ApplyUpdate()
        {
            isOperationRunning = true;
            SetActionButtonsEnabled(false);
            LogMessage("Preparing to apply update: Releasing file locks...");

            ThreadPool.QueueUserWorkItem(state => {
                RunCommand("cmd.exe", "/c \"" + simsBat + "\" stop", rootDir);
                LogMessage("Downloading verified update archive and running database migrations...");
                string updateOutput = RunCommand(phpBin, "artisan sims:update", appDir);

                LogMessage("Restarting SIMS Web Server with updated application code...");
                RunCommand("cmd.exe", "/c \"" + simsBat + "\" start", rootDir);
                Thread.Sleep(2000);

                this.Invoke(new Action(() => {
                    isOperationRunning = false;
                    SetActionButtonsEnabled(true);
                    CheckServerStatusAsync();

                    if (updateOutput.IndexOf("successfully updated", StringComparison.OrdinalIgnoreCase) >= 0)
                    {
                        MessageBox.Show("SIMS was updated successfully!\n\nAll services and database migrations are synchronized.", "Update Complete", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    else
                    {
                        MessageBox.Show("The update operation finished. Please check the Activity Log for details.", "Update Result", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }));
            });
        }

        private void SetActionButtonsEnabled(bool enabled)
        {
            btnStart.Enabled = enabled;
            btnStop.Enabled = enabled;
            btnRestart.Enabled = enabled;
            btnCheckUpdates.Enabled = enabled;
            btnRefresh.Enabled = enabled;
        }

        private string RunCommand(string file, string args, string workingDir)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = file;
                psi.Arguments = args;
                psi.WorkingDirectory = workingDir;
                psi.UseShellExecute = false;
                psi.CreateNoWindow = true;
                psi.RedirectStandardOutput = true;
                psi.RedirectStandardError = true;

                System.Text.StringBuilder outputBuilder = new System.Text.StringBuilder();
                using (Process proc = new Process())
                {
                    proc.StartInfo = psi;
                    proc.OutputDataReceived += (s, e) => {
                        if (!string.IsNullOrEmpty(e.Data))
                        {
                            outputBuilder.AppendLine(e.Data);
                            LogMessage(e.Data);
                        }
                    };
                    proc.ErrorDataReceived += (s, e) => {
                        if (!string.IsNullOrEmpty(e.Data))
                        {
                            outputBuilder.AppendLine("[ERROR] " + e.Data);
                            LogMessage("[ERROR] " + e.Data);
                        }
                    };

                    proc.Start();
                    proc.BeginOutputReadLine();
                    proc.BeginErrorReadLine();
                    proc.WaitForExit();

                    return outputBuilder.ToString();
                }
            }
            catch (Exception ex)
            {
                LogMessage("Command error: " + ex.Message);
                return "Error: " + ex.Message;
            }
        }

        private void LogMessage(string message)
        {
            if (this.IsDisposed || !this.IsHandleCreated) return;

            this.BeginInvoke(new Action(() => {
                string time = DateTime.Now.ToString("HH:mm:ss");
                txtLog.AppendText("[" + time + "] " + message + Environment.NewLine);
                txtLog.SelectionStart = txtLog.Text.Length;
                txtLog.ScrollToCaret();
            }));
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
