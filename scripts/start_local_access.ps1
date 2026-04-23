<#
.\start_local_access.ps1

Finds the first usable IPv4 address and prints a URL you can open on a phone
to access the local site. Optionally adds a Windows Firewall rule for the port.

Usage:
  .\start_local_access.ps1
  .\start_local_access.ps1 -Port 80 -Path '/clinicapp/' -AddFirewallRule
#>

param(
    [int]$Port = 80,
    [string]$Path = '/clinicapp/',
    [switch]$AddFirewallRule
)

function Get-LocalIPv4 {
    # Try Get-NetIPAddress first (modern PowerShell)
    try {
        $addrs = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop | Where-Object {
            $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*'
        }
        if ($addrs -and $addrs.Count -gt 0) {
            return $addrs[0].IPAddress
        }
    } catch {
        # Fall back to ipconfig parsing
    }

    $out = ipconfig
    foreach ($line in $out) {
        if ($line -match 'IPv4 Address.*?:\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)') {
            $ip = $matches[1]
            if ($ip -and $ip -notlike '127.*' -and $ip -notlike '169.254.*') { return $ip }
        }
    }
    return $null
}

$ip = Get-LocalIPv4
if (-not $ip) {
    Write-Error "Could not determine a non-loopback IPv4 address. Ensure network is up."
    exit 2
}

# Check whether a server is listening on the requested port on the local IP
Write-Host "Local IP: $ip" -ForegroundColor Cyan
Write-Host "Checking port $Port on $ip..." -ForegroundColor Cyan
try {
    $test = Test-NetConnection -ComputerName $ip -Port $Port -WarningAction SilentlyContinue
} catch {
    $test = $null
}

if ($test -and $test.TcpTestSucceeded) {
    $selectedPort = $Port
    $serverPresent = $true
} else {
    # find a free ephemeral port
    $listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Any,0)
    $listener.Start()
    $selectedPort = ($listener.LocalEndpoint).Port
    $listener.Stop()
    $serverPresent = $false
}

if ($selectedPort -eq 80) { $url = "http://$ip$Path" } else { $url = "http://$ip`:$selectedPort$Path" }

Write-Host "Open this URL on a device on the same Wi‑Fi/network:" -ForegroundColor Green
Write-Host "  $url`n"

if ($serverPresent) {
    Write-Host "A server (likely Apache) appears to be listening on port $selectedPort." -ForegroundColor Green
} else {
    Write-Warning "No server detected on port $Port. Suggested free port: $selectedPort";
    Write-Host "To serve the site temporarily on that port using PHP's built-in server (for testing), run:" -ForegroundColor Yellow
    $escapedPath = (Resolve-Path -LiteralPath "$PSScriptRoot\..\").ProviderPath
    Write-Host "  php -S 0.0.0.0:$selectedPort -t `"$escapedPath`"" -ForegroundColor Magenta
    Write-Host "Then open the URL above from your phone. Note: PHP built-in server is for testing only." -ForegroundColor Gray
}

if ($AddFirewallRule) {
    $isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $isAdmin) {
        Write-Warning "Adding a firewall rule requires Administrator rights. Re-run PowerShell as Administrator or omit -AddFirewallRule."
        exit 3
    }

    $ruleName = "ClinicApp HTTP Port $selectedPort"
    # Check for existing rule
    $existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
    if ($existing) {
        Write-Host "Firewall rule '$ruleName' already exists." -ForegroundColor Yellow
    } else {
        try {
            New-NetFirewallRule -DisplayName $ruleName -Direction Inbound -LocalPort $selectedPort -Protocol TCP -Action Allow -Profile Any | Out-Null
            Write-Host "Created firewall rule '$ruleName' to allow TCP port $selectedPort." -ForegroundColor Green
        } catch {
            Write-Error "Failed to create firewall rule: $_"
            exit 4
        }
    }
}

Write-Host "If you still cannot connect from your phone:"
Write-Host " - Ensure phone is on same Wi-Fi network as this machine." -ForegroundColor Gray
Write-Host " - If using a router with client isolation, disable that or connect both devices to same segment." -ForegroundColor Gray
Write-Host " - If Apache listens on a non-standard port, re-run with -Port <port>." -ForegroundColor Gray
