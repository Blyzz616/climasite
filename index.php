<?php
// display.php
// No DB access here — the frontend polls api.php every 60 seconds.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Climasite Live Temperatures</title>
    <style>
        body {
            font-family: sans-serif;
            background: #111;
            color: #eee;
            padding: 2em;
        }
        #output {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1em;
        }
        .room {
            padding: 1em;
            background: #222;
            border-left: 5px solid #4caf50;
            font-size: 1.2em;
        }
        .room.stale {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <h1>Live Temperature Display</h1>
    <div id="output">Loading…</div>

    <script>
        async function fetchData() {
            try {
                const res = await fetch('api.php');
                const data = await res.json();
                const output = document.getElementById('output');
                output.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    output.innerHTML = '<p>No recent sensor data.</p>';
                    return;
                }

                data.forEach(entry => {
                    const div = document.createElement('div');
                    div.className = 'room';
                    div.innerHTML = `<strong>${entry.room_name}</strong><br>${entry.temperature.toFixed(1)}°C`;
                    output.appendChild(div);
                });
            } catch (err) {
                console.error('Fetch error:', err);
                document.getElementById('output').innerHTML = '<p>Error fetching data.</p>';
            }
        }

        fetchData();
        setInterval(fetchData, 60000); // Every 60 seconds
    </script>
</body>
</html>
