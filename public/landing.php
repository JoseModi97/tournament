<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Free Fire Tournament Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #0e1320; color: #fff; }
    header { padding: 3rem 1rem; text-align: center; background: linear-gradient(135deg, #ff512f, #dd2476); }
    header h1 { margin: 0; font-size: 2.5rem; }
    header p { font-size: 1.1rem; margin-top: 1rem; }
    main { padding: 2rem 1rem; max-width: 960px; margin: 0 auto; }
    .card-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .card { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .card h3 { margin-top: 0; color: #ff8a65; }
    .download { text-align: center; margin-top: 2rem; }
    .download a { display: inline-block; padding: 0.9rem 2.4rem; border-radius: 999px; background: #ff512f; color: #fff; font-weight: bold; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .download a:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(255,81,47,0.35); }
    footer { text-align: center; padding: 1.5rem; background: #0b0f1a; font-size: 0.9rem; color: rgba(255,255,255,0.6); }
    @media (prefers-color-scheme: light) {
      body { background: #f5f6fb; color: #2c3144; }
      header { color: #fff; }
      .card { background: #fff; color: #2c3144; }
      footer { background: #fff; color: #5b6180; }
    }
  </style>
</head>
<body>
  <header>
    <h1>Free Fire Tournament Platform</h1>
    <p>Compete, manage, and win with our all-in-one esports management suite.</p>
  </header>
  <main>
    <section class="card-grid">
      <article class="card">
        <h3>Players</h3>
        <p>Register, top up your wallet, join upcoming tournaments, and review match results in real time.</p>
      </article>
      <article class="card">
        <h3>Admins</h3>
        <p>Create events, monitor finances, manage staff, and control payouts through a secure dashboard.</p>
      </article>
      <article class="card">
        <h3>Staff</h3>
        <p>Moderate assigned lobbies, capture final standings, and flag issues directly from the field.</p>
      </article>
    </section>
    <div class="download">
      <a href="/downloads/app.apk">Download Android APK</a>
    </div>
    <section style="margin-top:3rem;">
      <h2>Need help?</h2>
      <p>Email <a href="mailto:support@tournament.local" style="color:#ff8a65;">support@tournament.local</a> for onboarding and partnership requests.</p>
    </section>
  </main>
  <footer>
    &copy; <?php echo date('Y'); ?> Free Fire Tournament Platform. All rights reserved.
  </footer>
</body>
</html>
