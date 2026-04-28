# PowerShell Deployment Script for EC2
# Run from your local machine

param(
    [string]$Ec2PublicIp = "51.20.44.24",
    
    [string]$KeyPath = "C:\Users\adarsh k\Downloads\locallink-key-current.pem",
    
    [Parameter(Mandatory=$true)]
    [string]$DbPassword,
    
    [string]$LocalProjectPath = "c:\Users\adarsh k\OneDrive\Desktop\sk\locallink-main"
)

$ErrorActionPreference = "Stop"

Write-Host "=== LocalLink Digital - EC2 Deployment ===" -ForegroundColor Green

# Validate key file exists
if (-not (Test-Path $KeyPath)) {
    throw "Key file not found: $KeyPath"
}

# Validate project path
if (-not (Test-Path $LocalProjectPath)) {
    throw "Project path not found: $LocalProjectPath"
}

Write-Host "Step 1: Uploading application files..." -ForegroundColor Yellow

# Create tar archive of project
$tarPath = "$env:TEMP\locallink-deploy.tar.gz"
Set-Location $LocalProjectPath
tar -czf $tarPath .

# Upload to EC2
scp -i "$KeyPath" -o StrictHostKeyChecking=no $tarPath "ubuntu@${Ec2PublicIp}:/tmp/"

Write-Host "Step 2: Extracting on EC2..." -ForegroundColor Yellow

$extractScript = @"
cd /tmp
sudo rm -rf /var/www/locallink/*
sudo tar -xzf locallink-deploy.tar.gz -C /var/www/locallink/
sudo chown -R www-data:www-data /var/www/locallink
sudo chmod -R 755 /var/www/locallink
sudo chmod -R 775 /var/www/locallink/assets/img/uploads
sudo chmod -R 775 /var/www/locallink/assets/img/products
sudo chmod -R 775 /var/www/locallink/assets/img/screenshots
sudo chmod -R 775 /var/www/locallink/assets/downloads
"@

ssh -i "$KeyPath" -o StrictHostKeyChecking=no "ubuntu@${Ec2PublicIp}" $extractScript

Write-Host "Step 3: Setting database password..." -ForegroundColor Yellow

$dbPassScript = @"
sudo sed -i 's/SetEnv DB_PASS .*/SetEnv DB_PASS ${DbPassword}/' /etc/apache2/conf-available/locallink-env.conf
sudo sed -i 's|SetEnv SITE_URL .*|SetEnv SITE_URL http://${Ec2PublicIp}|' /etc/apache2/conf-available/locallink-env.conf
sudo systemctl restart apache2
"@

ssh -i "$KeyPath" -o StrictHostKeyChecking=no "ubuntu@${Ec2PublicIp}" $dbPassScript

Write-Host "Step 4: Verifying deployment..." -ForegroundColor Yellow

# Cleanup temp file
Remove-Item $tarPath -ErrorAction SilentlyContinue

Write-Host "=== Deployment Complete ===" -ForegroundColor Green
Write-Host "Site URL: http://${Ec2PublicIp}" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Import database to RDS: mysql -h locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com -u admin -p < database.sql"
Write-Host "2. Test the application at http://${Ec2PublicIp}"
Write-Host "3. Set up SSL/HTTPS with certbot if needed"
