# EC2 Deployment Guide - LocalLink Digital

## Prerequisites

1. **AWS EC2 Instance**: Ubuntu 22.04 LTS, t2.micro or higher
2. **RDS MySQL Database**: `locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com`
3. **Security Groups**: 
   - EC2: Allow HTTP (80), HTTPS (443), SSH (22) from your IP
   - RDS: Allow MySQL (3306) from EC2 Security Group only
4. **Key Pair**: `locallink-key-current.pem` downloaded

## Quick Deploy (One Command)

```powershell
.\deploy\deploy-to-ec2.ps1 -Ec2PublicIp "YOUR_EC2_IP" -DbPassword "Adarshkudachi"
```

## Manual Deployment Steps

### 1. Initial EC2 Setup

SSH into your EC2 instance:
```bash
ssh -i "locallink-key-current.pem" ubuntu@<EC2_PUBLIC_IP>
```

Run the setup script:
```bash
curl -fsSL https://raw.githubusercontent.com/yourrepo/ec2-setup.sh | sudo bash
```

Or manually:
```bash
# Install dependencies
sudo apt-get update
sudo apt-get install -y apache2 php8.1 php8.1-mysql php8.1-gd php8.1-curl php8.1-mbstring php8.1-xml libapache2-mod-php
sudo a2enmod rewrite ssl headers

# Download RDS SSL bundle
cd /var/www/html
wget https://truststore.pki.rds.amazonaws.com/eu-north-1/eu-north-1-bundle.pem
```

### 2. Upload Application Files

From your local machine (PowerShell):
```powershell
# Copy all files to EC2
scp -i "C:\Users\adarsh k\Downloads\locallink-key-current.pem" -r * ubuntu@<EC2_IP>:/var/www/locallink/
```

### 3. Configure Environment

Create Apache environment config:
```bash
sudo nano /etc/apache2/conf-available/locallink-env.conf
```

Add:
```apache
SetEnv DB_HOST locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com
SetEnv DB_NAME ybt_digital
SetEnv DB_USER admin
SetEnv DB_PASS Adarshkudachi
SetEnv DB_SSL_MODE VERIFY_IDENTITY
SetEnv DB_SSL_CA /var/www/locallink/global-bundle.pem
SetEnv SITE_URL http://YOUR_EC2_IP
```

Enable:
```bash
sudo a2enconf locallink-env
sudo systemctl restart apache2
```

### 4. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/locallink
sudo chmod -R 755 /var/www/locallink
sudo chmod -R 775 /var/www/locallink/assets/img/uploads
sudo chmod -R 775 /var/www/locallink/assets/img/products
sudo chmod -R 775 /var/www/locallink/assets/img/screenshots
sudo chmod -R 775 /var/www/locallink/assets/downloads
```

### 5. Import Database to RDS

From your local machine:
```bash
mysql -h locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com -P 3306 -u admin -p --ssl-mode=VERIFY_IDENTITY --ssl-ca=global-bundle.pem < database.sql
```

Or connect to EC2 first and import from there:
```bash
ssh -i locallink-key-current.pem ubuntu@<EC2_IP>
mysql -h locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com -u admin -p < database.sql
```

### 6. Test Connection

```bash
# Test MySQL connection from EC2
mysql -h locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com -P 3306 -u admin -p --ssl-mode=VERIFY_IDENTITY --ssl-ca=/var/www/locallink/global-bundle.pem -e "SHOW DATABASES;"
```

### 7. Verify Deployment

Open `http://<EC2_PUBLIC_IP>` in browser.

Admin credentials:
- Email: `admin@ybtdigital.com`
- Password: `admin123`

## SSL/HTTPS Setup (Optional but Recommended)

```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

## Troubleshooting

### Database Connection Failed

1. Check RDS Security Group allows EC2 IP
2. Verify environment variables: `cat /etc/apache2/conf-available/locallink-env.conf`
3. Test MySQL connection manually
4. Check Apache error logs: `sudo tail -f /var/log/apache2/locallink-error.log`

### Permission Denied

```bash
sudo chown -R www-data:www-data /var/www/locallink
sudo chmod -R 755 /var/www/locallink
```

### 500 Internal Server Error

```bash
# Check PHP errors
sudo tail -f /var/log/apache2/locallink-error.log

# Enable PHP display errors (temporarily)
sudo sed -i 's/display_errors = Off/display_errors = On/' /etc/php/8.1/apache2/php.ini
sudo systemctl restart apache2
```

## Security Notes

1. **Change default admin password** after first login
2. **Move DB password to AWS Systems Manager Parameter Store** or Secrets Manager for production
3. **Enable AWS WAF** if using a custom domain
4. **Set up CloudWatch alarms** for monitoring
5. **Configure automated backups** for RDS

## Files Created

- `deploy/ec2-setup.sh` - EC2 initialization script
- `deploy/deploy-to-ec2.ps1` - PowerShell deployment script
- `deploy/EC2-DEPLOYMENT.md` - This guide
- `global-bundle.pem` - RDS SSL CA bundle (auto-downloaded)
