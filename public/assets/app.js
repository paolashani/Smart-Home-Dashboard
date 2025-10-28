const $ = (s, root=document)=>root.querySelector(s);
const $$ = (s, root=document)=>Array.from(root.querySelectorAll(s));

async function api(path, opts={}) {
  const res = await fetch(path, { credentials:'same-origin', ...opts });
  const data = await res.json().catch(()=>({}));
  if (!res.ok) throw Object.assign(new Error(data.error || 'API error'), {status: res.status, data});
  return data;
}

async function checkMe() {
  const me = await api('api/auth.php?action=me', { method:'GET' });
  return me.user;
}

async function loadDevices() {
  const { devices } = await api('api/devices.php?action=list', { method:'GET' });
  const wrap = $('#devices');
  wrap.innerHTML = '';
  for (const d of devices) {
    wrap.appendChild(renderDevice(d));
  }
}

function renderDevice(d) {
  const el = document.createElement('div');
  el.className = 'device-card';
  el.innerHTML = `
    <div class="row">
      <h3>${d.name}</h3>
      <span class="badge">${d.type}</span>
    </div>
    <div class="row">
      <span>${d.room_name}</span>
      <span class="badge">${d.online ? 'online' : 'offline'}</span>
    </div>
    <div class="row" id="control"></div>
  `;
  const ctrl = $('.row#control', el);
  const state = d.state || {};
  if (d.type === 'LIGHT') {
    const toggle = document.createElement('div');
    toggle.className = 'toggle' + (state.on ? ' on' : '');
    toggle.innerHTML = '<div class="dot"></div>';
    toggle.onclick = async () => {
      await sendCommand(d.id, { on: !(state.on) });
      await loadDevices();
    };
    ctrl.appendChild(toggle);

    const rng = document.createElement('input');
    rng.type = 'range'; rng.min = 0; rng.max = 100; rng.value = state.brightness ?? 70; rng.className='range';
    rng.onchange = async e => {
      await sendCommand(d.id, { brightness: Number(e.target.value) });
      await loadDevices();
    };
    ctrl.appendChild(rng);
  } else if (d.type === 'THERMOSTAT') {
    const input = document.createElement('input');
    input.type = 'number'; input.value = state.target ?? 24; input.className='range';
    input.onchange = async e => {
      await sendCommand(d.id, { target: Number(e.target.value) });
      await loadDevices();
    };
    ctrl.appendChild(input);
  } else if (d.type === 'BLIND') {
    const rng = document.createElement('input');
    rng.type = 'range'; rng.min = 0; rng.max = 100; rng.value = state.position ?? 50; rng.className='range';
    rng.onchange = async e => {
      await sendCommand(d.id, { position: Number(e.target.value) });
      await loadDevices();
    };
    ctrl.appendChild(rng);
  } else if (d.type === 'SENSOR_TEMP') {
    ctrl.textContent = (state.current ?? '?') + ' °C';
  } else {
    ctrl.textContent = 'No controls';
  }
  return el;
}

async function sendCommand(deviceId, patch) {
  $('#status').textContent = 'Sending...';
  try {
    await api('api/devices.php?action=command', {
      method:'POST',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify({ deviceId, statePatch: patch })
    });
    $('#status').textContent = 'Updated';
    setTimeout(()=> $('#status').textContent = '', 1200);
  } catch (e) {
    alert(e.message);
    $('#status').textContent = '';
  }
}

async function init() {
  const user = await checkMe();
  const authEl = $('#auth-section');
  const dashEl = $('#dashboard');
  const logoutBtn = $('#btn-logout');

  if (user) {
    authEl.style.display = 'none';
    dashEl.style.display = '';
    logoutBtn.style.display = '';
    logoutBtn.onclick = async () => { await api('api/auth.php?action=logout', {method:'POST'}); location.reload(); };
    await loadDevices();
    // Polling
    setInterval(loadDevices, 3000);
  } else {
    authEl.style.display = '';
    dashEl.style.display = 'none';
    logoutBtn.style.display = 'none';
  }

  $('#login-form').onsubmit = async (e) => {
    e.preventDefault();
    const email = $('#login-email').value.trim();
    const password = $('#login-password').value;
    try {
      await api('api/auth.php?action=login', { method:'POST', body: new URLSearchParams({ email, password }) });
      location.reload();
    } catch (err) { alert(err.message); }
  };

  $('#register-form').onsubmit = async (e) => {
    e.preventDefault();
    const name = $('#reg-name').value.trim();
    const email = $('#reg-email').value.trim();
    const password = $('#reg-password').value;
    try {
      await api('api/auth.php?action=register', { method:'POST', body: new URLSearchParams({ name, email, password }) });
      location.reload();
    } catch (err) { alert(err.message); }
  };
}

document.addEventListener('DOMContentLoaded', init);
