<?php
// contact.php
// Simple contact page for the FlowGuard project team.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Contact — FlowGuard Team</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Bauhaus / Industrial Theme */
    :root {
      --primary: #0F4C5C;      /* Deep Teal */
      --secondary: #1CA7EC;    /* Aqua Blue */
      --accent: #4F772D;       /* Olive Green */
      --bg-body: #F4F4F4;      /* Light Neutral */
      --surface: #FFFFFF;      /* Pure White */
      --text-main: #1E1E1E;    /* Charcoal */
      --text-light: #555555;
      
      --border-width: 2px;
      --border-color: #1E1E1E;
      --radius: 0px;
    }

    *{box-sizing:border-box; margin: 0; padding: 0;}
    html,body{height:100%;font-family:'Poppins',sans-serif;background: var(--bg-body); color: var(--text-main); line-height: 1.6;}
    a{color:var(--primary);text-decoration:none; font-weight: 700;}
    a:hover{text-decoration:underline; color: var(--secondary);}

    /* ---------- NAVBAR ---------- */
    .navbar{
      position:fixed;
      top:0;
      left:0;
      right:0;
      height:72px;
      background: var(--primary);
      color: #fff;
      padding:0 32px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      z-index:1100;
      border-bottom: 4px solid var(--accent);
    }
    .brand{display:flex;align-items:center;gap:12px}
    .brand h1{font-size:24px;font-weight:800;margin:0;color: #fff; letter-spacing: -0.5px; text-transform: uppercase;}
    .nav-links a{color: rgba(255,255,255,0.8);text-decoration:none;margin-left:20px;font-size:14px; font-weight: 700; transition: color 0.2s; text-transform: uppercase;}
    .nav-links a:hover{color: #fff; text-decoration: underline;}

    .page{max-width:1200px;margin:0 auto;margin-top: calc(72px + 40px); padding:0 20px 40px 20px;display:grid;grid-template-columns:1fr 340px;gap:24px}
    @media (max-width:980px){ .page{grid-template-columns:1fr; margin-top: 100px;} }

    /* main content */
    .content{display:flex;flex-direction:column;gap:24px}
    
    .hero{
      background: var(--surface);
      padding:32px; 
      border-radius:var(--radius); 
      border: var(--border-width) solid var(--border-color);
      box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
      display:flex;justify-content:space-between;align-items:center;gap:24px;
    }
    .hero h1{margin:0;color: var(--primary); font-size:2rem; font-weight: 800; text-transform: uppercase;}
    .hero p{margin-top:8px; color: var(--text-light); font-weight: 600;}

    .team-grid{
      display:grid;
      grid-template-columns:repeat(2,1fr);
      gap:24px;
    }
    @media (max-width:860px){ .team-grid{grid-template-columns:1fr} }

    /* card */
    .card{
      background: var(--surface); 
      padding:24px; 
      border-radius:var(--radius); 
      border: var(--border-width) solid var(--border-color);
      box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .card:hover{transform: translateY(-2px);}
    
    .dev{
      display:flex;gap:20px;align-items:flex-start;
    }
    .avatar{
      min-width:80px;height:80px;border-radius:var(--radius);background: var(--text-main);
      display:flex;align-items:center;justify-content:center;font-weight:800;color: #fff;
      font-size:24px; border: var(--border-width) solid var(--border-color);
    }
    .dev-meta h3{margin:0 0 6px 0; color: var(--text-main); font-size:18px; font-weight: 800; text-transform: uppercase;}
    .dev-meta p.role{margin:0; color: var(--primary); font-weight:700; font-size: 0.85rem; text-transform: uppercase; background: #EBF1FF; display: inline-block; padding: 2px 6px; border: 1px solid #D0DFFF;}
    .dev-meta .contact-list{margin-top:16px;display:flex;flex-direction:column;gap:8px}
    .contact-item{display:inline-flex;align-items:center;gap:12px;color: var(--text-light);font-size:0.95rem; font-weight: 600;}
    .contact-item a{color: var(--text-main); font-weight:700; transition: color 0.2s;}
    .contact-item a:hover{color: var(--primary);}
    .icon{width:18px;height:18px;display:inline-block;flex-shrink:0;}

    /* right column */
    .sidebar{display:flex;flex-direction:column;gap:24px}
    
    .panel{
        background: var(--surface);
        border: var(--border-width) solid var(--border-color);
        padding:24px;
        border-radius:var(--radius);
        box-shadow: 8px 8px 0px rgba(0,0,0,0.1);
    }
    .panel h3 { color: var(--primary); font-weight: 800; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase; border-bottom: 2px solid var(--bg-body); padding-bottom: 10px;}
    
    .panel p.muted a { color: var(--text-main); }
    .panel p.muted a:hover { color: var(--primary); }

    /* footer */
    footer{padding: 30px; background: var(--text-main); color: #fff; border-top: 4px solid var(--accent); text-align: center; font-size: 14px; margin-top: auto; font-weight: 600; text-transform: uppercase;}
    footer a { color: #fff; text-decoration: underline; }

    /* focus states */
    a:focus, button:focus, input:focus{outline:3px solid var(--primary); outline-offset:2px;}

    /* helper */
    .muted{color: var(--text-light); font-size:0.95rem}
    
    .btn-action {
        display: block;
        width: 100%;
        padding: 12px;
        background: var(--text-main);
        color: #fff;
        text-align: center;
        text-transform: uppercase;
        font-weight: 700;
        border: none;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.2s;
    }
    .btn-action:hover { background: var(--primary); text-decoration: none;}
  </style>
</head>
<body>

  <!-- Navbar -->
  <header class="navbar" role="banner">
    <div class="brand">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2C9 5 6 6 4 7v9c0 1 0 3 2 4 2 1 6 1 10 0 3-1 3-4 3-5V7c-2-1-5-2-7-5z" fill="#fff" opacity="0.12"></path>
        <circle cx="12" cy="10" r="3" fill="#fff"></circle>
      </svg>
      <h1>FlowGuard</h1>
    </div>
    <nav class="nav-links" role="navigation">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php" style="color:#fff; text-decoration: underline;">Contact</a>
        <a href="farmer_login.php">Login</a>
    </nav>
  </header>

  <main class="page" role="main">
    <section class="content">
      <div class="hero" role="region" aria-labelledby="team-heading">
        <div>
          <h1 id="team-heading">Meet the FlowGuard Team</h1>
          <p>Innovating for sustainable agriculture through technology.</p>
        </div>
      </div>

      <div class="team-grid" role="list">
        <!-- Sahil -->
        <article class="card dev" role="listitem" aria-labelledby="sahil-name">
          <div class="avatar" aria-hidden="true">S</div>
          <div class="dev-meta">
            <h3 id="sahil-name">Sahil</h3>
            <p class="role">ML &amp; Web Dev</p>

            <div class="contact-list">
              <div class="contact-item">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6.5h18v11H3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 6.5l-9 7-9-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="mailto:sahil@flowguard.local">sahil@flowguard.local</a>
              </div>
              <div class="contact-item">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                   <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" fill="currentColor"/>
                </svg>
                <a href="#">@sahil-dev</a>
              </div>
            </div>
          </div>
        </article>

        <!-- Parikshit -->
        <article class="card dev" role="listitem" aria-labelledby="parikshit-name">
          <div class="avatar" aria-hidden="true">P</div>
          <div class="dev-meta">
            <h3 id="parikshit-name">Parikshit</h3>
            <p class="role">Documentation</p>

            <div class="contact-list">
              <div class="contact-item">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6.5h18v11H3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 6.5l-9 7-9-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="mailto:parikshit@flowguard.local">parikshit@flowguard.local</a>
              </div>
              <div class="contact-item">
                  <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                     <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" fill="currentColor"/>
                  </svg>
                  <a href="#">@parikshit-docs</a>
                </div>
            </div>
          </div>
        </article>

        <!-- Suyash -->
        <article class="card dev" role="listitem" aria-labelledby="suyash-name">
          <div class="avatar" aria-hidden="true">Su</div>
          <div class="dev-meta">
            <h3 id="suyash-name">Suyash</h3>
            <p class="role">Hardware Engineer</p>

            <div class="contact-list">
              <div class="contact-item">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6.5h18v11H3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 6.5l-9 7-9-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="mailto:suyash@flowguard.local">suyash@flowguard.local</a>
              </div>
              <div class="contact-item">
                  <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                     <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" fill="currentColor"/>
                  </svg>
                  <a href="#">@suyash-hw</a>
                </div>
            </div>
          </div>
        </article>

        <!-- Samarth -->
        <article class="card dev" role="listitem" aria-labelledby="samarth-name">
          <div class="avatar" aria-hidden="true">Sm</div>
          <div class="dev-meta">
            <h3 id="samarth-name">Samarth</h3>
            <p class="role">3D &amp; Hardware Support</p>

            <div class="contact-list">
              <div class="contact-item">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6.5h18v11H3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 6.5l-9 7-9-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="mailto:samarth@flowguard.local">samarth@flowguard.local</a>
              </div>
              <div class="contact-item">
                  <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                     <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12" fill="currentColor"/>
                  </svg>
                  <a href="#">@ssamarth-3d</a>
                </div>
            </div>
          </div>
        </article>
      </div>
    </section>

    <aside class="sidebar" role="complementary" aria-label="Contact sidebar">
      <div class="panel">
        <h3>Quick Links</h3>
        <p class="muted" style="margin:0 0 8px 0"><a href="index.php">← Return to Home</a></p>
        <p class="muted" style="margin:0"><a href="#">System Status</a></p>
        <p class="muted" style="margin:8px 0 0 0"><a href="#">Project Repo</a></p>
      </div>

      <div class="panel">
        <h3>Need help?</h3>
        <p class="muted" style="margin:0">If you have questions about the project or need access, contact <a href="mailto:support@flowguard.local" style="font-weight:700">support@flowguard.local</a>.</p>
        <a href="mailto:support@flowguard.local" class="btn-action">Email Support</a>
      </div>
    </aside>
  </main>

  <footer role="contentinfo">
    <div class="wrap">
      <div>© <?= date('Y') ?> FlowGuard • Built by the FlowGuard Team</div>
    </div>
  </footer>
</body>
</html>
