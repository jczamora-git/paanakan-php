# Simple SendGrid Installation Script
# Run this as Administrator

Write-Host "=== SendGrid Installation for Paanakan ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Download CA Certificate
Write-Host "Step 1: Downloading CA Certificate..." -ForegroundColor Yellow
$certsDir = "C:\xampp\htdocs\paanakan\certs"
if (-not (Test-Path $certsDir)) {
    New-Item -ItemType Directory -Path $certsDir | Out-Null
}

$cacertPath = "$certsDir\cacert.pem"
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile $cacertPath
    Write-Host "[OK] Downloaded to: $cacertPath" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Could not download certificate" -ForegroundColor Red
    exit 1
}

# Step 2: Update php.ini
Write-Host "`nStep 2: Updating php.ini..." -ForegroundColor Yellow
$phpIniPath = "C:\xampp\php\php.ini"

if (Test-Path $phpIniPath) {
    try {
        $content = Get-Content $phpIniPath -Raw
        $certPathForIni = $cacertPath -replace '\\', '/'
        
        # Check if curl.cainfo exists
        if ($content -match ';?\s*curl\.cainfo') {
            $content = $content -replace ';?\s*curl\.cainfo\s*=.*', "curl.cainfo = `"$certPathForIni`""
        } else {
            $content += "`ncurl.cainfo = `"$certPathForIni`""
        }
        
        # Check if openssl.cafile exists
        if ($content -match ';?\s*openssl\.cafile') {
            $content = $content -replace ';?\s*openssl\.cafile\s*=.*', "openssl.cafile = `"$certPathForIni`""
        } else {
            $content += "`nopenssl.cafile = `"$certPathForIni`""
        }
        
        Set-Content -Path $phpIniPath -Value $content -NoNewline
        Write-Host "[OK] Updated php.ini" -ForegroundColor Green
    } catch {
        Write-Host "[FAIL] Could not update php.ini - Run as Administrator" -ForegroundColor Red
        Write-Host "Manually add these lines to C:\xampp\php\php.ini:" -ForegroundColor Yellow
        Write-Host "curl.cainfo = `"$certPathForIni`"" -ForegroundColor White
        Write-Host "openssl.cafile = `"$certPathForIni`"" -ForegroundColor White
        exit 1
    }
} else {
    Write-Host "[FAIL] php.ini not found at $phpIniPath" -ForegroundColor Red
    exit 1
}

# Step 3: Install Composer packages
Write-Host "`nStep 3: Installing Composer packages..." -ForegroundColor Yellow
try {
    composer install --no-interaction
    Write-Host "[OK] Packages installed!" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Composer install failed" -ForegroundColor Red
    Write-Host "Try running: composer install" -ForegroundColor Yellow
}

Write-Host "`n=== Installation Complete ===" -ForegroundColor Cyan
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Test the integration: php connections/test_sendgrid.php" -ForegroundColor White
Write-Host "2. Update test email in connections/test_sendgrid.php" -ForegroundColor White
Write-Host "3. Read SENDGRID_SETUP.md for usage examples" -ForegroundColor White
