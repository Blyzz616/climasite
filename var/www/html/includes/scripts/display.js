// scripts/display.js

// Globals
const sensorContainer = document.getElementById('sensor-data');

let sensorData = [];
let serverTime = 0;
let refreshTimer = null;
let elapsedTimer = null;

// Utility: clamp number between min and max
function clamp(num, min, max) {
  return Math.min(Math.max(num, min), max);
}

// Temperature color gradient based on temp (°C)
function tempToColor(temp) {
  if (temp <= 15) return '#61aefc';
  if (temp >= 30) return '#d66545';

  const gradientStops = [
    { stop: 15, color: [97, 174, 252] },   // #61aefc
    { stop: 22, color: [137, 209, 110] },  // #89d16e
    { stop: 25, color: [247, 246, 96] },   // #f7f660
    { stop: 28, color: [248, 180, 69] },   // #f8b445
    { stop: 30, color: [214, 101, 69] },   // #d66545
  ];

  // Find stops around temp
  let lower = gradientStops[0];
  let upper = gradientStops[gradientStops.length - 1];

  for (let i = 0; i < gradientStops.length - 1; i++) {
    if (temp >= gradientStops[i].stop && temp <= gradientStops[i + 1].stop) {
      lower = gradientStops[i];
      upper = gradientStops[i + 1];
      break;
    }
  }

  const range = upper.stop - lower.stop;
  const ratio = (temp - lower.stop) / range;

  // Linear interpolate color channels
  const lerp = (start, end, t) => Math.round(start + (end - start) * t);
  const r = lerp(lower.color[0], upper.color[0], ratio);
  const g = lerp(lower.color[1], upper.color[1], ratio);
  const b = lerp(lower.color[2], upper.color[2], ratio);

  return `rgb(${r},${g},${b})`;
}

// Format elapsed seconds to short human-readable string like "1y2m3d4h5m6s"
function formatElapsed(seconds) {
  if (seconds < 1) return 'now';

  const units = [
    { label: 'y', seconds: 365 * 24 * 3600 },
    { label: 'm', seconds: 30 * 24 * 3600 },
    { label: 'd', seconds: 24 * 3600 },
    { label: 'h', seconds: 3600 },
    { label: 'm', seconds: 60 },
    { label: 's', seconds: 1 },
  ];

  let remaining = seconds;
  let result = '';

  // Show max 4 units to avoid overly long strings
  let count = 0;
  for (const unit of units) {
    if (remaining >= unit.seconds) {
      const val = Math.floor(remaining / unit.seconds);
      remaining %= unit.seconds;
      result += `${val}${unit.label}`;
      count++;
      if (count >= 4) break;
    }
  }

  return result || '0s';
}

// Render sensors to DOM
function renderSensors() {
  sensorContainer.innerHTML = '';

  const nowUnix = Math.floor(Date.now() / 1000);  // current UTC timestamp in seconds

  sensorData.forEach(sensor => {
    const elapsedSec = nowUnix - sensor.lastSeen_unix;
    const elapsedStr = formatElapsed(elapsedSec);

    const tempColor = tempToColor(sensor.lastTemp);

    // Create sensor card container
    const sensorCard = document.createElement('div');
    sensorCard.classList.add('sensor');

    sensorCard.innerHTML = `
      <div class="room-name">${sensor.roomName}</div>
      <div class="temperature" style="color:${tempColor}">${sensor.lastTemp.toFixed(1)}°</div>
      <div class="last-seen">(${elapsedStr} ago)</div>
    `;

    sensorContainer.appendChild(sensorCard);
  });
}

// Increment serverTime and update elapsed display every second
function tickElapsed() {
  serverTime++;
  renderSensors();
}

// Fetch sensor data from backend API
async function fetchSensors() {
  try {
    const response = await fetch('api/sensors.php');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const json = await response.json();

    // On first fetch, just assign
    if (sensorData.length === 0) {
      sensorData = json.sensors;
      serverTime = json.server_time;
      renderSensors();
      startTimers();
      scheduleNextFetch();
      return;
    }

    // Otherwise, check if any sensor lastSeen_unix changed
    const prevLastSeen = sensorData.map(s => s.lastSeen_unix).join();
    const newLastSeen = json.sensors.map(s => s.lastSeen_unix).join();

    sensorData = json.sensors;
    serverTime = json.server_time;

    if (prevLastSeen !== newLastSeen) {
      // Data changed: update display and reset timer intervals
      renderSensors();
      scheduleNextFetch(true);  // Reset intervals on change
    } else {
      // No change: use longer interval polling
      scheduleNextFetch(false);
    }
  } catch (error) {
    console.error('Failed to fetch sensors:', error);
    // On error, retry in 30 seconds
    scheduleNextFetch(false, true);
  }
}

let currentInterval = 15000; // default 15s polling

// Schedule next fetch based on elapsed time and change state
function scheduleNextFetch(changed = false, error = false) {
  clearTimeout(refreshTimer);

  // If error, try again in 30s
  if (error) {
    refreshTimer = setTimeout(fetchSensors, 30000);
    return;
  }

  if (sensorData.length === 0) {
    refreshTimer = setTimeout(fetchSensors, 30000);
    return;
  }

  // Get max lastSeen_unix for sensors
  const maxLastSeen = Math.max(...sensorData.map(s => s.lastSeen_unix));
  const age = serverTime - maxLastSeen;

  // Decide polling interval per your rules
  if (changed) {
    if (age < 60) {
      currentInterval = 62000; // 62s after last seen if fresh
    } else if (age < 3600) {
      currentInterval = 15000; // every 15s if last seen 1m-1h ago
    } else {
      currentInterval = 30000; // every 30s if older than 1h
    }
  } else {
    // No change, increase intervals progressively
    if (currentInterval === 15000) currentInterval = 65000;
    else if (currentInterval === 65000) currentInterval = 90000;
    else currentInterval = 30000;
  }

  refreshTimer = setTimeout(fetchSensors, currentInterval);
}

// Start the 1-second elapsed time update timer
function startTimers() {
  if (elapsedTimer) clearInterval(elapsedTimer);
  elapsedTimer = setInterval(tickElapsed, 1000);
}

// Initial fetch
fetchSensors();
