# Fix Composer SSL Certificate Issue
Write-Host "Downloading CA Certificate Bundle..." -ForegroundColor Yellow

# Create certs directory if it doesn't exist
$certsDir = "C:\xampp\htdocs\paanakan\certs"
if (-not (Test-Path $certsDir)) {
    New-Item -ItemType Directory -Path $certsDir | Out-Null
}

# Download the CA certificate bundle
$cacertPath = "$certsDir\cacert.pem"
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile $cacertPath
    Write-Host "[OK] CA Certificate downloaded successfully!" -ForegroundColor Green
} catch {
    Write-Host "[FAIL] Failed to download CA certificate" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}

# Find php.ini location
Write-Host "`nFinding php.ini location..." -ForegroundColor Yellow
$phpIniPath = php --ini | Select-String "Loaded Configuration File" | ForEach-Object { $_.ToString().Split(':')[1].Trim() }

if ($phpIniPath) {
    Write-Host "[OK] Found php.ini at: $phpIniPath" -ForegroundColor Green
    
    # Read php.ini content
    $phpIniContent = Get-Content $phpIniPath -Raw
    
    # Update or add curl.cainfo setting
    $cacertPathEscaped = $cacertPath -replace '\\', '\\'
    if ($phpIniContent -match "curl\.cainfo\s*=") {
        $phpIniContent = $phpIniContent -replace "curl\.cainfo\s*=.*", "curl.cainfo = `"$cacertPathEscaped`""
        Write-Host "[OK] Updated existing curl.cainfo setting" -ForegroundColor Green
    } else {
        $phpIniContent += "`ncurl.cainfo = `"$cacertPathEscaped`""
        Write-Host "[OK] Added curl.cainfo setting" -ForegroundColor Green
    }
    
    # Update or add openssl.cafile setting
    if ($phpIniContent -match "openssl\.cafile\s*=") {
        $phpIniContent = $phpIniContent -replace "openssl\.cafile\s*=.*", "openssl.cafile = `"$cacertPathEscaped`""
        Write-Host "[OK] Updated existing openssl.cafile setting" -ForegroundColor Green
    } else {
        $phpIniContent += "`nopenssl.cafile = `"$cacertPathEscaped`""
        Write-Host "[OK] Added openssl.cafile setting" -ForegroundColor Green
    }
    
    # Save php.ini
    try {
        $phpIniContent | Set-Content $phpIniPath -NoNewline
        Write-Host "[OK] php.ini updated successfully!" -ForegroundColor Green
    } catch {
        Write-Host "[FAIL] Failed to update php.ini (may need admin rights)" -ForegroundColor Red
        Write-Host "Please manually add these lines to php.ini:" -ForegroundColor Yellow
        Write-Host "curl.cainfo = `"$cacertPathEscaped`"" -ForegroundColor White
        Write-Host "openssl.cafile = `"$cacertPathEscaped`"" -ForegroundColor White
    }
} else {
    Write-Host "[FAIL] Could not find php.ini" -ForegroundColor Red
}

Write-Host "`n=== Setup Complete ===" -ForegroundColor Cyan
Write-Host "Now try running composer install" -ForegroundColor Yellow
