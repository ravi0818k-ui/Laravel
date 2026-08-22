# Running PG A1 Locally So Other Devices on the Same Wi-Fi Can Reach It

By default `php artisan serve` binds to `127.0.0.1`, which only accepts connections from the same machine. This guide covers exposing it on the local network so other laptops/phones connected to the same Wi-Fi can open it too — no code changes required, just how the server is started plus one firewall rule.

## Why no code changes are needed

`public/dashboard/js/api.js` and `index.html` both auto-detect the API base:

```javascript
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
  ? 'http://127.0.0.1:8000/api/v1'
  : '/api/v1';
```

When another device opens the app via the LAN IP (e.g. `http://192.168.1.9:8000`), `window.location.hostname` is `192.168.1.9`, which is neither `localhost` nor `127.0.0.1`, so it falls through to the relative `/api/v1` — which correctly resolves against whatever host/port the page was loaded from. So this only requires the server to actually listen on the LAN interface, not `127.0.0.1` only.

## Step 1: Find this machine's LAN IP

```bash
ipconfig
# Look for "IPv4 Address" under your active adapter (e.g. "Wireless LAN adapter Wi-Fi")
```

Example: `192.168.1.9`. This is a DHCP-leased address — it **can change** (e.g. after a router reboot or reconnecting to Wi-Fi). Re-run `ipconfig` if the app stops being reachable from other devices.

## Step 2: Start the server bound to all interfaces

From `backend/`:

```bash
php -c php-server.ini artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` makes it listen on every network interface (loopback + Wi-Fi LAN), not just `127.0.0.1`. If a server is already running bound to `127.0.0.1` only, stop it first (find its PID via `tasklist` and `taskkill`, or just close the terminal it's running in), then start it again with `--host=0.0.0.0`.

## Step 3: Allow the port through Windows Firewall (one-time)

Windows Firewall blocks inbound connections from other devices by default, even though loopback (`127.0.0.1`) and connections *from this same machine* to its own LAN IP still work — so this step is easy to miss if you only test from the host machine.

Open PowerShell **as Administrator** and run:

```powershell
New-NetFirewallRule -DisplayName "PG A1 Laravel Dev Server (8000)" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow -Profile Private
```

`-Profile Private` scopes this to trusted/home networks only (check your Wi-Fi's profile with `Get-NetConnectionProfile` — it should show `Private`, not `Public`). This needs admin rights; a non-elevated PowerShell/terminal will fail with "Access is denied."

Alternative: instead of the command above, you can just start the server once and click "Allow access" on the "Windows Defender Firewall has blocked some features of this app" popup if Windows shows one — check both Private and Public/Domain networks it offers.

## Step 4: Connect from another device

Any laptop/phone on the same Wi-Fi network can now open:

| What | URL |
|------|-----|
| Public website | `http://192.168.1.9:8000` |
| Dashboard login | `http://192.168.1.9:8000/dashboard/login.html` |
| API (for testing) | `http://192.168.1.9:8000/api/v1/public/pg-locations` |

(Replace `192.168.1.9` with whatever `ipconfig` showed in Step 1.)

## Verifying it actually works cross-device

Testing with `curl http://192.168.1.9:8000/...` **from the same machine running the server** is not a valid test of the firewall — loopback-to-self-via-LAN-IP can succeed even when the firewall would block a genuinely external device. Always verify by opening the URL from a *different* device on the network.

## Troubleshooting

| Symptom | Likely cause |
|---------|--------------|
| Works on this machine, not from other devices | Firewall rule missing/not applied — redo Step 3 |
| Worked yesterday, not today | LAN IP changed (DHCP lease renewed) — redo Step 1 |
| Other device isn't on the same network | Confirm both are connected to the same Wi-Fi SSID, not one on Wi-Fi and one on mobile data/a guest network |
| `php artisan serve` still shows `127.0.0.1` in its startup message | You didn't pass `--host=0.0.0.0` — stop and restart with the flag |
| Antivirus/third-party firewall still blocks it | Windows Firewall rule alone isn't enough if another security suite (Norton, McAfee, etc.) is also filtering — add an equivalent allow rule there too |

## Reverting to localhost-only

Just start the server the normal way (no `--host` flag, or explicitly `--host=127.0.0.1`) — see `LOCAL-DEV-RUNBOOK.md`. The firewall rule can be left in place harmlessly (it only opens port 8000 on Private networks and does nothing when nothing is listening on it), or removed with:

```powershell
Remove-NetFirewallRule -DisplayName "PG A1 Laravel Dev Server (8000)"
```
