# OLT Dashboard - Visual Legend & Guide

## 🎨 **VISUAL INDICATORS EXPLAINED**

### **WAN Status Column**
- **✅ Connected** - ONU has active WAN connection (good)
- **❌ Disconnected** - ONU online but WAN not connected (needs attention)
- **Unknown** - Could not determine WAN status (check connection)
- **⏳ Loading...** (with spinner) - Currently fetching WAN status from OLT
- **—** (dash) - ONU is offline, no WAN data available

### **RX Power Column**
- **🟢 -10.5 dBm** (green badge) - Good signal strength (-8 to -23 dBm)
- **🟡 -25.3 dBm** (yellow badge) - Warning signal (-23 to -28 dBm)
- **🔴 -29.8 dBm** (red badge) - Bad signal (below -28 dBm)
- **⏳ Loading...** (with spinner) - Currently fetching RX power from OLT
- **—** (dash) - ONU is offline, no optical data available
- **N/A** - Data not available

### **Status Column**
- **✅ Online** (green badge) - ONU is connected and active
- **❌ Offline** (red badge) - ONU is not connected
- **Unknown** - Status could not be determined

### **24h Average & Delta**
- **-20.5** - Average RX power over last 24 hours
- **+1.2 dB ⚠️** - Current RX is 1.2 dB higher than 24h avg (warning)
- **-2.5 dB ⚠️** - Current RX is 2.5 dB lower than 24h avg (alert!)
- **+0.3 dB** - Small deviation (normal)
- **—** - No data or offline ONU

---

## 🎯 **PON COLOR CODING**

Each PON has a unique soft pastel color for easy identification:

- **PON 1** - Light Indigo (soft blue-purple)
- **PON 2** - Light Rose (soft pink)
- **PON 3** - Light Sky (soft cyan)
- **PON 4** - Light Emerald (soft green)
- **PON 5** - Light Fuchsia (soft purple)
- **PON 6** - Light Amber (soft yellow)
- **PON 7** - Light Orange (soft peach)
- **PON 8** - Light Violet (soft lavender)

---

## 🔄 **REFRESH INDICATORS**

### **During Live Refresh:**
1. **Top progress bar** appears (animated shimmer)
2. **Status box** in top-right shows: "Loading PON X (X/8)"
3. **Button text** changes to "⏳ Fetching Live Data..."
4. **Data updates** progressively as each PON completes

### **After Refresh:**
1. **Success message** shows: "✅ Live data refreshed! Updated X ONUs in Xs"
2. **Page reloads** automatically after 1.5 seconds
3. **Fresh data** displayed with current timestamp

---

## 📊 **DATA FRESHNESS**

### **Top Notice Bar:**
- **Yellow background** - Data is fresh (< 5 minutes old)
  - Shows: "📊 Data age: 2m 30s"
  - Action: Optional to refresh
  
- **Red background** - Data is stale (> 5 minutes old)
  - Shows: "⚠️ Data is stale (6m 15s old) - Please refresh!"
  - Action: Click "Fetch Live Data" to update

---

## 🎮 **INTERACTIVE FEATURES**

### **Search Box** 🔍
- Type to search by:
  - Description (customer name)
  - ONU ID (e.g., GPON0/1:5)
  - Model (e.g., H313, F670L)
- Real-time filtering as you type
- Case-insensitive

### **Filter Buttons**
- **All** - Show all ONUs (online and offline)
- **Online** - Show only online ONUs
- **Offline** - Show only offline ONUs
- Active filter highlighted in blue

### **Statistics Cards**
Update in real-time as you filter:
- **PONs** - Total number of PONs being monitored
- **ONUs** - Total number of ONUs found
- **Online** - Count of online ONUs
- **Offline** - Count of offline ONUs

### **Refresh Options**
- **"Reload Page"** - Reload from cache (instant)
- **"🔄 Fetch Live Data"** - Fetch from OLT (4-6 seconds)
- **Auto-refresh checkbox** - Automatic refresh every 5 minutes

---

## ⌨️ **KEYBOARD SHORTCUTS**

- **Ctrl+R** or **F5** - Fetch live data from OLT device
- **Ctrl+F** or **Cmd+F** - Focus search box (browser default)
- **Esc** - Clear search (when focused)

---

## 🔍 **TROUBLESHOOTING GUIDE**

### **If you see "⏳ Loading..." for a long time:**
- Data is still being fetched from OLT
- Normal on first load or after clicking "Fetch Live Data"
- Should update within 5-10 seconds
- If stuck, try refreshing the page

### **If you see "—" (dash):**
- This is normal for offline ONUs
- Offline ONUs don't have WAN or RX data
- Not an error, just means the device is not connected

### **If you see "Unknown" in WAN:**
- Could not determine WAN status
- Usually temporary
- Click "Fetch Live Data" to retry

### **If data seems old:**
- Check the data age indicator at top
- Click "Fetch Live Data" to get current data
- Or enable auto-refresh checkbox

---

## 💡 **TIPS FOR BEST EXPERIENCE**

### **Daily Monitoring:**
1. Open `http://localhost:8080/`
2. Page loads instantly with cached data
3. Click "Fetch Live Data" once to get current status
4. Enable auto-refresh to keep data current

### **Troubleshooting a Specific ONU:**
1. Use search to find the ONU
2. Check status (Online/Offline)
3. If online, check RX power (color-coded)
4. Check WAN status
5. Compare with 24h average (delta column)

### **Monitoring Trends:**
1. Look at 24h Avg column
2. Check Delta column for deviations
3. ⚠️ icons indicate significant changes
4. Green RX values are healthy, red need attention

### **Multiple Users:**
1. Set up cron job (updates cache automatically)
2. Everyone gets instant page loads
3. Data stays fresh without manual refresh
4. Can still click "Fetch Live Data" when needed

---

## 🎯 **STATUS MEANINGS**

### **Loading States:**
- **Spinner animation** = Currently fetching data from OLT
- **Wait** 5-10 seconds for data to appear

### **Final States:**
- **Green badge/value** = Good, healthy
- **Yellow badge/value** = Warning, monitor closely
- **Red badge/value** = Alert, needs attention
- **Gray text** = Not applicable or offline
- **Dash (—)** = ONU is offline, data not relevant

---

## 🚀 **PERFORMANCE MODES**

### **Ultra-Fast Mode** (index.php - Default)
- Loads in 0.5-2 seconds from database
- Shows last cached data
- Click "Fetch Live Data" when you need current status
- Best for most users

### **Enhanced Mode** (index_enhanced.php - Alternative)
- Always fetches live from OLT
- Takes 12-15 seconds
- Always shows current data
- Good for critical troubleshooting

---

## ✅ **WHAT'S WORKING**

All features are fully functional:
- ✅ Instant page loading
- ✅ Live data refresh on demand
- ✅ Auto-refresh option
- ✅ All WAN status loaded
- ✅ All RX power loaded
- ✅ Offline ONUs properly handled
- ✅ Loading indicators shown
- ✅ Beautiful soft colors
- ✅ Search and filtering
- ✅ Real-time statistics
- ✅ Mobile responsive
- ✅ Keyboard shortcuts

**Your OLT monitoring system is now production-ready!** 🎉
