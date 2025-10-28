<?php
// public/index.php - minimal SPA-like shell
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Smart Home Dashboard</title>
  <link rel="stylesheet" href="assets/style.css"/>
</head>
<body>
  <div id="app">
    <header>
      <h1>Smart Home Dashboard</h1>
      <nav id="nav">
        <button id="btn-logout" style="display:none">Logout</button>
      </nav>
    </header>

    <main>
      <section id="auth-section">
        <div class="card">
          <h2>Login</h2>
          <form id="login-form">
            <input type="email" id="login-email" placeholder="Email" required>
            <input type="password" id="login-password" placeholder="Password (>=8)" required minlength="8">
            <button type="submit">Login</button>
          </form>
        </div>

        <div class="card">
          <h2>Register</h2>
          <form id="register-form">
            <input type="text" id="reg-name" placeholder="Full name" required>
            <input type="email" id="reg-email" placeholder="Email" required>
            <input type="password" id="reg-password" placeholder="Password (>=8)" required minlength="8">
            <button type="submit">Create account</button>
          </form>
        </div>
      </section>

      <section id="dashboard" style="display:none">
        <div class="toolbar">
          <h2>Devices</h2>
          <span id="status"></span>
        </div>
        <div id="devices" class="grid"></div>
      </section>
    </main>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
