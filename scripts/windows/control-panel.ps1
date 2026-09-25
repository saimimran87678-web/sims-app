<#
==============================================================================
  Adminova Control Center - Modern Windows Desktop GUI
  School Information Management System (SIMS)
  Features: Real-time service monitoring, Start/Stop/Restart, Update Manager,
  One-Click Web Launch, and Network LAN address discovery.
==============================================================================
#>
param(
    [switch]$Minimized
)

Add-Type -AssemblyName PresentationFramework, PresentationCore, WindowsBase, System.Drawing, System.Windows.Forms

# 1. Resolve Root and Runtime Paths
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = (Resolve-Path "$ScriptDir\..\..").Path
$AppDir = Join-Path $RootDir "sims-app"
$SimsBat = Join-Path $RootDir "sims.bat"
$IconPath = Join-Path $RootDir "resources\icons\adminova.ico"
$LogoPath = Join-Path $RootDir "resources\icons\adminova.png"
$PhpBin = Join-Path $RootDir "runtime\php\php.exe"
if (-not (Test-Path $PhpBin)) { $PhpBin = "php" }

# Helper: Detect Local LAN IPv4
function Get-LocalLanIp {
    try {
        $ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
            $_.InterfaceAlias -notmatch 'Loopback|vEthernet|Virtual|WSL' -and $_.IPAddress -notmatch '^127\.|^169\.254\.' 
        } | Select-Object -First 1).IPAddress
        if ($ip) { return $ip }
    } catch {}
    return "localhost"
}

$LanIp = Get-LocalLanIp

# 2. XAML User Interface (Clean Light Theme)
[xml]$xaml = @"
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="Adminova Control Center"
        Height="650" Width="550"
        WindowStartupLocation="CenterScreen"
        ResizeMode="CanMinimize"
        Background="#F1F5F9"
        FontFamily="Segoe UI, Inter, sans-serif">
    
    <Window.Resources>
        <Style TargetType="Button">
            <Setter Property="FontWeight" Value="SemiBold"/>
            <Setter Property="FontSize" Value="13"/>
            <Setter Property="Cursor" Value="Hand"/>
            <Setter Property="BorderThickness" Value="0"/>
            <Setter Property="Height" Value="42"/>
            <Setter Property="Template">
                <Setter.Value>
                    <ControlTemplate TargetType="Button">
                        <Border x:Name="border" Background="{TemplateBinding Background}" CornerRadius="10" Padding="{TemplateBinding Padding}">
                            <ContentPresenter HorizontalAlignment="Center" VerticalAlignment="Center"/>
                        </Border>
                        <ControlTemplate.Triggers>
                            <Trigger Property="IsMouseOver" Value="True">
                                <Setter TargetName="border" Property="Opacity" Value="0.9"/>
                            </Trigger>
                            <Trigger Property="IsPressed" Value="True">
                                <Setter TargetName="border" Property="Opacity" Value="0.8"/>
                            </Trigger>
                            <Trigger Property="IsEnabled" Value="False">
                                <Setter TargetName="border" Property="Opacity" Value="0.5"/>
                            </Trigger>
                        </ControlTemplate.Triggers>
                    </ControlTemplate>
                </Setter.Value>
            </Setter>
        </Style>
    </Window.Resources>

    <Grid Margin="20">
        <Grid.RowDefinitions>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="*"/>
            <RowDefinition Height="Auto"/>
        </Grid.RowDefinitions>

        <!-- Header -->
        <Border Grid.Row="0" Background="#FFFFFF" CornerRadius="16" Padding="16" Margin="0,0,0,16" BorderBrush="#E2E8F0" BorderThickness="1">
            <Grid>
                <Grid.ColumnDefinitions>
                    <ColumnDefinition Width="Auto"/>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="Auto"/>
                </Grid.ColumnDefinitions>

                <Image x:Name="ImgLogo" Grid.Column="0" Width="48" Height="48" Margin="0,0,14,0" VerticalAlignment="Center"/>

                <StackPanel Grid.Column="1" VerticalAlignment="Center">
                    <TextBlock Text="Adminova School Portal" FontWeight="Bold" FontSize="18" Foreground="#0F172A"/>
                    <TextBlock Text="SIMS Service Control Center v2.5.1" FontSize="12" Foreground="#64748B"/>
                </StackPanel>

                <!-- Status Badge -->
                <Border x:Name="BadgeStatus" Grid.Column="2" CornerRadius="20" Padding="12,6" Background="#DCFCE7" BorderBrush="#86EFAC" BorderThickness="1" VerticalAlignment="Center">
                    <StackPanel Orientation="Horizontal">
                        <Ellipse x:Name="DotStatus" Width="8" Height="8" Fill="#16A34A" Margin="0,0,6,0" VerticalAlignment="Center"/>
                        <TextBlock x:Name="TxtStatus" Text="CHECKING..." FontWeight="Bold" FontSize="11" Foreground="#15803D" VerticalAlignment="Center"/>
                    </StackPanel>
                </Border>
            </Grid>
        </Border>

        <!-- Quick Launch Card -->
        <Border Grid.Row="1" Background="#FFFFFF" CornerRadius="16" Padding="18" Margin="0,0,0,16" BorderBrush="#E2E8F0" BorderThickness="1">
            <StackPanel>
                <TextBlock Text="PORTAL ACCESS" FontWeight="Bold" FontSize="11" Foreground="#94A3B8" Margin="0,0,0,8"/>
                <Grid Margin="0,0,0,12">
                    <Grid.ColumnDefinitions>
                        <ColumnDefinition Width="*"/>
                        <ColumnDefinition Width="Auto"/>
                    </Grid.ColumnDefinitions>
                    <StackPanel Grid.Column="0">
                        <TextBlock Text="https://localhost" FontWeight="SemiBold" FontSize="14" Foreground="#1E293B"/>
                        <TextBlock x:Name="TxtLanIp" Text="LAN IP: http://127.0.0.1" FontSize="12" Foreground="#64748B" Margin="0,2,0,0"/>
                    </StackPanel>
                    <Button x:Name="BtnCopyLan" Grid.Column="1" Content="📋 Copy LAN Link" Background="#F1F5F9" Foreground="#334155" FontSize="11" Height="32" Padding="10,0" Margin="4,0,0,0"/>
                </Grid>

                <Button x:Name="BtnOpenBrowser" Content="🌐 Open School Portal in Browser" Background="#4F46E5" Foreground="#FFFFFF" FontSize="14" Height="46"/>
            </StackPanel>
        </Border>

        <!-- Service Operations Grid -->
        <Border Grid.Row="2" Background="#FFFFFF" CornerRadius="16" Padding="18" Margin="0,0,0,16" BorderBrush="#E2E8F0" BorderThickness="1">
            <StackPanel>
                <TextBlock Text="SERVICE CONTROLS" FontWeight="Bold" FontSize="11" Foreground="#94A3B8" Margin="0,0,0,12"/>
                <Grid>
                    <Grid.ColumnDefinitions>
                        <ColumnDefinition Width="*"/>
                        <ColumnDefinition Width="10"/>
                        <ColumnDefinition Width="*"/>
                        <ColumnDefinition Width="10"/>
                        <ColumnDefinition Width="*"/>
                    </Grid.ColumnDefinitions>

                    <Button x:Name="BtnStart" Grid.Column="0" Content="▶️ Start" Background="#10B981" Foreground="#FFFFFF"/>
                    <Button x:Name="BtnRestart" Grid.Column="2" Content="🔄 Restart" Background="#2563EB" Foreground="#FFFFFF"/>
                    <Button x:Name="BtnStop" Grid.Column="4" Content="⏹️ Stop" Background="#EF4444" Foreground="#FFFFFF"/>
                </Grid>

                <Button x:Name="BtnUpdate" Content="🚀 Check &amp; Install System Updates" Background="#F8FAFC" Foreground="#4338CA" BorderBrush="#C7D2FE" BorderThickness="1" Margin="0,10,0,0"/>
            </StackPanel>
        </Border>

        <!-- Console & Activity Log -->
        <Border Grid.Row="3" Background="#FFFFFF" CornerRadius="16" Padding="14" BorderBrush="#E2E8F0" BorderThickness="1">
            <Grid>
                <Grid.RowDefinitions>
                    <RowDefinition Height="Auto"/>
                    <RowDefinition Height="*"/>
                </Grid.RowDefinitions>
                <TextBlock Grid.Row="0" Text="ACTIVITY &amp; DIAGNOSTIC LOG" FontWeight="Bold" FontSize="10" Foreground="#94A3B8" Margin="2,0,0,6"/>
                <TextBox x:Name="TxtLog" Grid.Row="1" IsReadOnly="True" TextWrapping="Wrap" VerticalScrollBarVisibility="Auto"
                         Background="#F8FAFC" Foreground="#334155" BorderBrush="#E2E8F0" BorderThickness="1"
                         FontFamily="Consolas, monospace" FontSize="11" Padding="8"/>
            </Grid>
        </Border>

        <!-- Footer -->
        <Grid Grid.Row="4" Margin="4,12,4,0">
            <TextBlock Text="Adminova Tech • All Services Protected" FontSize="11" Foreground="#94A3B8" VerticalAlignment="Center"/>
            <Button x:Name="BtnRefresh" Content="🔄 Refresh Status" HorizontalAlignment="Right" Background="Transparent" Foreground="#6366F1" FontSize="11" Height="28" Padding="6,0"/>
        </Grid>
    </Grid>
</Window>
"@

$reader = New-Object System.Xml.XmlNodeReader $xaml
$window = [Windows.Markup.XamlReader]::Load($reader)

# 3. Connect Controls
$ImgLogo        = $window.FindName("ImgLogo")
$BadgeStatus    = $window.FindName("BadgeStatus")
$DotStatus      = $window.FindName("DotStatus")
$TxtStatus      = $window.FindName("TxtStatus")
$TxtLanIp       = $window.FindName("TxtLanIp")
$BtnCopyLan     = $window.FindName("BtnCopyLan")
$BtnOpenBrowser = $window.FindName("BtnOpenBrowser")
$BtnStart       = $window.FindName("BtnStart")
$BtnRestart     = $window.FindName("BtnRestart")
$BtnStop        = $window.FindName("BtnStop")
$BtnUpdate      = $window.FindName("BtnUpdate")
$TxtLog         = $window.FindName("TxtLog")
$BtnRefresh     = $window.FindName("BtnRefresh")

# Set Window Icon and Logo
if (Test-Path $LogoPath) {
    try {
        $bitmap = New-Object System.Windows.Media.Imaging.BitmapImage
        $bitmap.BeginInit()
        $bitmap.UriSource = New-Object System.Uri($LogoPath, [System.UriKind]::Absolute)
        $bitmap.EndInit()
        $ImgLogo.Source = $bitmap
        $window.Icon = $bitmap
    } catch {}
}

$TxtLanIp.Text = "School LAN IP: https://$LanIp (Port 80/443)"

function Log-Message([string]$msg) {
    $time = (Get-Date).ToString("HH:mm:ss")
    $TxtLog.AppendText("[$time] $msg`r`n")
    $TxtLog.ScrollToEnd()
}

# 4. Service Health Checker
function Update-ServerStatus {
    $isOnline = $false
    try {
        # Check port 80 or 443
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $connectTask = $tcpClient.ConnectAsync("127.0.0.1", 443)
        if ($connectTask.Wait(500) -and $tcpClient.Connected) {
            $isOnline = $true
            $tcpClient.Close()
        } else {
            $tcpClient2 = New-Object System.Net.Sockets.TcpClient
            $connectTask2 = $tcpClient2.ConnectAsync("127.0.0.1", 80)
            if ($connectTask2.Wait(500) -and $tcpClient2.Connected) {
                $isOnline = $true
                $tcpClient2.Close()
            } else {
                $tcpClient3 = New-Object System.Net.Sockets.TcpClient
                $connectTask3 = $tcpClient3.ConnectAsync("127.0.0.1", 8000)
                if ($connectTask3.Wait(500) -and $tcpClient3.Connected) {
                    $isOnline = $true
                    $tcpClient3.Close()
                }
            }
        }
    } catch {}

    if ($isOnline) {
        $BadgeStatus.Background = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#DCFCE7")
        $BadgeStatus.BorderBrush = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#86EFAC")
        $DotStatus.Fill = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#16A34A")
        $TxtStatus.Text = "ONLINE"
        $TxtStatus.Foreground = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#15803D")
        $BtnStart.IsEnabled = $false
        $BtnStop.IsEnabled = $true
        $BtnRestart.IsEnabled = $true
    } else {
        $BadgeStatus.Background = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#FEE2E2")
        $BadgeStatus.BorderBrush = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#FCA5A5")
        $DotStatus.Fill = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#DC2626")
        $TxtStatus.Text = "OFFLINE"
        $TxtStatus.Foreground = [System.Windows.Media.BrushConverter]::new().ConvertFromString("#B91C1C")
        $BtnStart.IsEnabled = $true
        $BtnStop.IsEnabled = $false
        $BtnRestart.IsEnabled = $false
    }
}

# 5. Event Handlers
$BtnOpenBrowser.Add_Click({
    Log-Message "Opening https://localhost in web browser..."
    Start-Process "https://localhost"
})

$BtnCopyLan.Add_Click({
    $lanUrl = "https://$LanIp"
    [System.Windows.Forms.Clipboard]::SetText($lanUrl)
    Log-Message "Copied LAN Address ($lanUrl) to clipboard."
    [System.Windows.MessageBox]::Show("Copied to clipboard:`n$lanUrl`n`nTeachers and staff on the same Wi-Fi/LAN network can use this link to access SIMS.", "Link Copied", [System.Windows.MessageBoxButton]::OK, [System.Windows.MessageBoxImage]::Information)
})

$BtnStart.Add_Click({
    Log-Message "Starting SIMS Web Server..."
    $BtnStart.IsEnabled = $false
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "`"$SimsBat`" start" -WindowStyle Hidden -Wait
    Start-Sleep -Seconds 2
    Update-ServerStatus
    Log-Message "Start command executed."
})

$BtnStop.Add_Click({
    Log-Message "Stopping SIMS Web Server..."
    $BtnStop.IsEnabled = $false
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "`"$SimsBat`" stop" -WindowStyle Hidden -Wait
    Start-Sleep -Seconds 1
    Update-ServerStatus
    Log-Message "Stop command executed."
})

$BtnRestart.Add_Click({
    Log-Message "Restarting SIMS Web Server & clearing OPcache..."
    $BtnRestart.IsEnabled = $false
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "`"$SimsBat`" restart" -WindowStyle Hidden -Wait
    Start-Sleep -Seconds 2
    Update-ServerStatus
    Log-Message "Restart complete."
})

$BtnUpdate.Add_Click({
    Log-Message "Checking for software updates..."
    $BtnUpdate.IsEnabled = $false

    try {
        $proc = Start-Process -FilePath "$PhpBin" -ArgumentList "artisan sims:update --check" -WorkingDirectory "$AppDir" -NoNewWindow -PassThru -RedirectStandardOutput "$env:TEMP\sims_update_check.log" -Wait
        $output = Get-Content "$env:TEMP\sims_update_check.log" -Raw -ErrorAction SilentlyContinue
        Log-Message $output

        if ($output -match "newer version|available to install|hotfix patch") {
            $res = [System.Windows.MessageBox]::Show("A new SIMS update is available!`n`nWould you like to download and install it now?", "SIMS Update Available", [System.Windows.MessageBoxButton]::YesNo, [System.Windows.MessageBoxImage]::Question)
            if ($res -eq [System.Windows.MessageBoxResult]::Yes) {
                Log-Message "Releasing file locks for update..."
                Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "`"$SimsBat`" stop" -WindowStyle Hidden -Wait

                Log-Message "Downloading patch, running migrations, and updating files..."
                $applyProc = Start-Process -FilePath "$PhpBin" -ArgumentList "artisan sims:update" -WorkingDirectory "$AppDir" -NoNewWindow -PassThru -RedirectStandardOutput "$env:TEMP\sims_update_apply.log" -RedirectStandardError "$env:TEMP\sims_update_apply.err" -Wait

                $applyOutput = Get-Content "$env:TEMP\sims_update_apply.log" -Raw -ErrorAction SilentlyContinue
                if ($applyOutput) { Log-Message $applyOutput }

                Log-Message "Restarting SIMS Web Server..."
                Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "`"$SimsBat`" start" -WindowStyle Hidden -Wait
                Update-ServerStatus

                if ($applyProc.ExitCode -eq 0) {
                    [System.Windows.MessageBox]::Show("SIMS was updated successfully!`n`nYour application is now running the latest version with all database migrations applied.", "Update Complete", [System.Windows.MessageBoxButton]::OK, [System.Windows.MessageBoxImage]::Information)
                } else {
                    $applyErr = Get-Content "$env:TEMP\sims_update_apply.err" -Raw -ErrorAction SilentlyContinue
                    Log-Message "[ERROR] $applyErr"
                    [System.Windows.MessageBox]::Show("Update could not be applied cleanly. Check the Activity Log for details.`n`nAutomatic rollback preserved your database and files safely.", "Update Warning", [System.Windows.MessageBoxButton]::OK, [System.Windows.MessageBoxImage]::Warning)
                }
            }
        } else {
            [System.Windows.MessageBox]::Show("SIMS is up to date! You are running the latest version.", "Up to Date", [System.Windows.MessageBoxButton]::OK, [System.Windows.MessageBoxImage]::Information)
        }
    } catch {
        Log-Message "Update check error: $_"
    } finally {
        $BtnUpdate.IsEnabled = $true
    }
})

$BtnRefresh.Add_Click({
    Update-ServerStatus
    Log-Message "Status refreshed."
})

# 6. Auto-Refresh Timer (Every 5 seconds)
$timer = New-Object System.Windows.Threading.DispatcherTimer
$timer.Interval = [TimeSpan]::FromSeconds(5)
$timer.Add_Tick({
    Update-ServerStatus
})
$timer.Start()

# Initial load
Log-Message "Adminova Control Center initialized."
Log-Message "System Root: $RootDir"
Update-ServerStatus

[void]$window.ShowDialog()
