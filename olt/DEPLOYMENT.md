# OLT System - Server Deployment Guide

## 🚨 **SERVER COMPATIBILITY FIX**

If you get **"The string did not match the expected pattern"** error on your server, use the server-compatible version.

---

## 🎯 **TWO VERSIONS AVAILABLE**

### **Version 1: Client-Side (Modern Browsers)** - `index.php`
- Uses JavaScript fetch API
- Loads data client-side
- Works on localhost and modern servers
- **Error on server?** Use Version 2 instead

### **Version 2: Server-Side (Universal)** - `index_server.php`
- Pure PHP rendering (no complex JavaScript)
- Works on ALL servers
- 100% compatible
- Slightly less dynamic but same features

---

## 🔧 **DEPLOYMENT INSTRUCTIONS**

### **Step 1: Choose Your Version**

**Try Version 1 first:**
```
http://your-server/olt/
```

**If you see the error, use Version 2:**
```
http://your-server/olt/index_server.php
```

### **Step 2: Make Version 2 the Default (if needed)**

If server version works better, rename the files:
```bash
cd /path/to/olt
mv index.php index_client.php.bak
mv index_server.php index.php
```

Now `http://your-server/olt/` will use the server-compatible version.

---

## 🛠️ **SERVER SETUP**

### **Required:**
1. **PHP 7.4+** (PHP 8.x recommended)
2. **SQLite Extension** (usually included)
3. **cURL Extension** (for OLT communication)
4. **Write permissions** on `/olt/data/` directory

### **Optional (for best performance):**
5. **Cron job** to run `tasks/update_cache.php` every 2-5 minutes

---

## 📋 **CRON SETUP**

Add to your server's crontab:

```bash
# Update cache every 2 minutes
*/2 * * * * cd /path/to/olt && php tasks/update_cache.php >> collect.log 2>&1
```

Or every 5 minutes for lower load:
```bash
*/5 * * * * cd /path/to/olt && php tasks/update_cache.php >> collect.log 2>&1
```

**Without cron:**
- Still works!
- Click "Refresh Data" button to update
- Enable auto-refresh checkbox (5 min intervals)
- Data updates on demand

---

## 🔍 **TROUBLESHOOTING**

### **Error: "The string did not match the expected pattern"**

**Cause:** JavaScript fetch API compatibility issue or JSON parsing error

**Solutions:**
1. **Use server version**: Visit `/olt/index_server.php`
2. **Check browser console**: Look for actual error message
3. **Run diagnostics**: Visit `/olt/diagnose.php`
4. **Check PHP errors**: Look in server error logs

### **Error: Database errors**

**Cause:** File permissions or SQLite not available

**Solutions:**
```bash
# Fix permissions
chmod 755 /path/to/olt/data
chmod 666 /path/to/olt/data/olt.sqlite

# Or recreate database
rm /path/to/olt/data/olt.sqlite
php /path/to/olt/tasks/migrate.php
php /path/to/olt/tasks/update_cache.php
```

### **Error: API not found**

**Cause:** URL rewriting or .htaccess issues

**Solutions:**
1. Check that `/olt/api/` URLs are accessible
2. Verify web server configuration
3. Use absolute paths in configuration

---

## ✅ **FEATURES IN BOTH VERSIONS**

Both versions have ALL features:
- ✅ Ultra-fast loading
- ✅ Username & MAC address display
- ✅ WAN status for all online ONUs
- ✅ RX power with color coding
- ✅ 24h averages and delta
- ✅ Search and filter
- ✅ Real-time statistics
- ✅ Soft colors (10-15% opacity)
- ✅ Maximized table view
- ✅ Mobile responsive

---

## 📊 **DIFFERENCES**

| Feature | Client Version | Server Version |
|---------|---------------|----------------|
| **Data Loading** | JavaScript fetch | PHP server-side |
| **Live Refresh** | Button triggers API | Link reloads page |
| **Auto-refresh** | JavaScript timer | Manual refresh |
| **Compatibility** | Modern browsers | All browsers |
| **Performance** | Slightly faster | Same speed |
| **Search** | Client-side | Client-side (JS) |
| **Best For** | Modern setup | Legacy servers |

---

## 🚀 **RECOMMENDED SETUP**

### **For Production Server:**

1. **Test both versions** - see which works better
2. **Use diagnostics** - `/olt/diagnose.php` to check compatibility  
3. **Set up cron** - for automatic cache updates
4. **Monitor logs** - check `collect.log` for issues

### **Minimal Setup (No Cron):**

1. Deploy either version to server
2. Click "Refresh Data" when you visit the page
3. Data stays cached for fast subsequent loads
4. Manually refresh when you need current data

---

## 📁 **FILES TO DEPLOY**

Upload the entire `/olt/` directory to your server:
```
olt/
├── index.php                  (Client-side version)
├── index_server.php           (Server-side version - backup)
├── index_enhanced.php         (Alternative live-loading)
├── diagnose.php               (Diagnostics tool)
├── api/                       (All API endpoints)
├── assets/                    (CSS only, JS embedded)
├── lib/                       (Core libraries)
├── tasks/                     (Background tasks)
└── data/                      (SQLite database)
```

**Permissions needed:**
```bash
chmod 755 olt/data
chmod 666 olt/data/olt.sqlite
chmod 755 olt/tasks/*.php
```

---

## 🎯 **TESTING ON SERVER**

### **1. Run Diagnostics:**
```
http://your-server/olt/diagnose.php
```

This will show:
- PHP version
- Database connection
- API endpoint tests
- JavaScript compatibility
- File paths

### **2. Test Client Version:**
```
http://your-server/olt/
```

### **3. Test Server Version:**
```
http://your-server/olt/index_server.php
```

### **4. Populate Cache:**
```bash
ssh your-server
cd /path/to/olt
php tasks/update_cache.php
```

---

## ✅ **VERIFICATION CHECKLIST**

After deployment, verify:
- [ ] Diagnostics page shows all green
- [ ] One of the versions loads correctly
- [ ] Data table shows ONUs
- [ ] Username and MAC visible (where available)
- [ ] WAN status shows correctly
- [ ] Search and filter work
- [ ] Refresh button/link works

---

## 🎉 **RESULT**

Both versions are production-ready:
- **Client version**: Modern, dynamic, all features
- **Server version**: Universal compatibility, same data
- **Your choice**: Use whichever works on your server

**The system will work on any PHP server!** 🚀
