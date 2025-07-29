# 🚀 SCMS Backend Deployment Guide

## Overview
This guide will help you deploy your SCMS backend to various hosting platforms when your frontend is deployed on Vercel.

## 📋 Prerequisites
- GitHub repository with your backend code
- Database (MySQL/PostgreSQL)
- Domain name (optional)

## 🎯 Recommended: Railway Deployment

### Step 1: Sign Up
1. Go to [railway.app](https://railway.app)
2. Sign up with your GitHub account

### Step 2: Create New Project
1. Click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. Choose your repository

### Step 3: Configure Environment Variables
Add these environment variables in Railway dashboard:
```
DB_HOST=your-database-host
DB_USERNAME=your-database-username
DB_PASSWORD=your-database-password
DB_NAME=your-database-name
```

### Step 4: Add Database
1. Click **"New"** → **"Database"**
2. Choose **"MySQL"** or **"PostgreSQL"**
3. Railway will automatically provide connection details

### Step 5: Deploy
1. Railway will auto-deploy your app
2. Get your deployment URL (e.g., `https://your-app.railway.app`)

## 🔧 Alternative: Render Deployment

### Step 1: Sign Up
1. Go to [render.com](https://render.com)
2. Sign up with your GitHub account

### Step 2: Create Web Service
1. Click **"New"** → **"Web Service"**
2. Connect your GitHub repository

### Step 3: Configure Service
- **Name**: `scms-backend`
- **Environment**: `PHP`
- **Build Command**: `composer install`
- **Start Command**: `php -S 0.0.0.0:$PORT -t .`

### Step 4: Add Environment Variables
Add the same database variables as above.

### Step 5: Deploy
1. Click **"Create Web Service"**
2. Render will deploy your app

## 🐘 Alternative: Heroku Deployment

### Step 1: Install Heroku CLI
```bash
# Windows
winget install --id=Heroku.HerokuCLI

# macOS
brew tap heroku/brew && brew install heroku
```

### Step 2: Login and Create App
```bash
heroku login
heroku create your-scms-backend
```

### Step 3: Add Buildpack
```bash
heroku buildpacks:set heroku/php
```

### Step 4: Add Database
```bash
heroku addons:create heroku-postgresql:mini
```

### Step 5: Set Environment Variables
```bash
heroku config:set DB_HOST=your-database-host
heroku config:set DB_USERNAME=your-database-username
heroku config:set DB_PASSWORD=your-database-password
heroku config:set DB_NAME=your-database-name
```

### Step 6: Deploy
```bash
git push heroku main
```

## 🌐 Alternative: DigitalOcean App Platform

### Step 1: Sign Up
1. Go to [digitalocean.com](https://digitalocean.com)
2. Create an account

### Step 2: Create App
1. Go to **"Apps"** → **"Create App"**
2. Connect your GitHub repository

### Step 3: Configure
- **Environment**: `PHP`
- **Build Command**: `composer install`
- **Run Command**: `php -S 0.0.0.0:$PORT -t .`

### Step 4: Add Database
1. Click **"Create/Attach Database"**
2. Choose **"MySQL"** or **"PostgreSQL"**

### Step 5: Deploy
1. Click **"Create Resources"**
2. DigitalOcean will deploy your app

## 🔄 Update Frontend Configuration

After deploying your backend, update your frontend's API base URL:

### For Vercel Frontend:
1. Go to your Vercel project settings
2. Add environment variable:
   ```
   REACT_APP_API_URL=https://your-backend-url.com
   ```

### Update Frontend Code:
```javascript
// In your frontend API configuration
const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost/scms_new/index.php/api';
```

## 🔒 Security Considerations

### 1. CORS Configuration
Update your backend CORS settings to allow your Vercel domain:
```php
// In application/hooks/CORS_hook.php
$allowed_origins = [
    'https://your-frontend.vercel.app',
    'http://localhost:3000' // for development
];
```

### 2. Environment Variables
Never commit sensitive data. Use environment variables for:
- Database credentials
- API keys
- JWT secrets

### 3. SSL/HTTPS
All production deployments should use HTTPS.

## 📊 Database Migration

### Option 1: Manual Import
1. Export your local database
2. Import to production database
3. Update connection settings

### Option 2: Automated Migration
Create a migration script:
```php
// application/migrations/001_initial_schema.php
class Migration_Initial_schema extends CI_Migration {
    public function up() {
        // Your database schema
    }
}
```

## 🧪 Testing Deployment

### 1. Health Check
Test your deployed API:
```bash
curl https://your-backend-url.com/api/admin/sections
```

### 2. CORS Test
Test from your frontend:
```javascript
fetch('https://your-backend-url.com/api/admin/sections')
  .then(response => response.json())
  .then(data => console.log(data));
```

## 🚨 Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Check environment variables
   - Verify database is accessible
   - Check firewall settings

2. **CORS Errors**
   - Update CORS configuration
   - Check frontend URL is allowed

3. **500 Server Error**
   - Check application logs
   - Verify PHP version compatibility
   - Check file permissions

4. **Build Failures**
   - Check composer.json
   - Verify PHP extensions
   - Check build commands

## 📞 Support

- **Railway**: [docs.railway.app](https://docs.railway.app)
- **Render**: [render.com/docs](https://render.com/docs)
- **Heroku**: [devcenter.heroku.com](https://devcenter.heroku.com)
- **DigitalOcean**: [docs.digitalocean.com](https://docs.digitalocean.com)

## 🎉 Success Checklist

- [ ] Backend deployed successfully
- [ ] Database connected and working
- [ ] API endpoints responding
- [ ] CORS configured for frontend
- [ ] Frontend updated with new API URL
- [ ] SSL/HTTPS working
- [ ] Environment variables set
- [ ] Health check passing

**Your SCMS backend is now ready for production!** 🚀