#!/usr/bin/env python3
"""Generates the static pages + hero artwork. Run:  python3 build.py
Edit the CONTENT below, re-run, upload the folder. (Admin dashboard can replace this later.)"""
import os, math, random

ROOT = os.path.dirname(os.path.abspath(__file__))
EMAIL = "info@empretechnologies.com.ng"
PHONE_DISPLAY = "+234 813 964 5973"
PHONE_TEL = "+2348139645973"
WEBSITE = "www.empretechnologies.com"
INSTAGRAM_HANDLE = "Empre Technologies"
# TODO: replace "#" with the real profile URLs
SOCIAL = [("instagram", "Instagram", "https://www.instagram.com/empre_technologies"), ("linkedin", "LinkedIn", "https://www.linkedin.com/company/empre-technologies"),
          ("x", "X (Twitter)", "https://twitter.com/empre_tech")]

# ------------------------------------------------------------------ icons
ICONS = {
 "headset": '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15v3a3 3 0 0 1-3 3h-3"/><rect x="2" y="13" width="4" height="6" rx="1.5"/><rect x="18" y="13" width="4" height="6" rx="1.5"/>',
 "cloud": '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z"/>',
 "shield": '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
 "grad": '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
 "monitor": '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
 "code": '<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>',
 "pen": '<path d="M12 19l7-7 3 3-7 7z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18z"/><path d="m2 2 7.6 7.6"/><circle cx="11" cy="11" r="2"/>',
 "spark": '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.7 1.8L21.5 17.5l-1.8.7L19 20l-.7-1.8-1.8-.7 1.8-.7z"/>',
 "wrench": '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>',
 "mail": '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
 "phone": '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
 "globe": '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
 "instagram": '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
 "linkedin": '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
 "x": '<path d="M4 4l16 16M20 4 4 20"/>',
 "facebook": '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
 "check": '<path d="M20 6 9 17l-5-5"/>',
 "arrow": '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
 "menu": '<path d="M4 7h16M4 12h16M4 17h16"/>',
 "close": '<path d="M18 6 6 18M6 6l12 12"/>',
 "bulb": '<path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/>',
 "target": '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
 "users": '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
 "handshake": '<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>',
 "lock": '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
 "refresh": '<path d="M21 12a9 9 0 0 0-15.5-6.2L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 15.5 6.2L21 16"/><path d="M16 16h5v5"/>',
 "clock": '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
 "layers": '<path d="m12 2 10 6-10 6L2 8z"/><path d="m2 16 10 6 10-6"/>',
 "user": '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
 "server": '<rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><path d="M6 7h.01M6 17h.01"/>',
}
def ic(name, cls="ic"):
    return f'<svg class="{cls}" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-{name}"/></svg>'
SPRITE = '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>' + "".join(
    f'<symbol id="i-{k}" viewBox="0 0 24 24">{v}</symbol>' for k, v in ICONS.items()) + '</defs></svg>'

# ------------------------------------------------------------------ content
SERVICES = [
 dict(id="technical-support", icon="wrench", title="Technical Support", new=False,
      short="Ongoing remote or on-site support for resolving system and user issues within Microsoft environments.",
      long="Ongoing remote or on-site support for resolving system and user issues within Microsoft environments. When something breaks, your team gets a responsive, knowledgeable partner who solves complex IT issues quickly.",
      points=["Troubleshooting for systems and users", "Account recovery", "Email issues and more", "Remote or on-site support"]),
 dict(id="microsoft-365", icon="cloud", title="Microsoft 365 Solution Implementation", new=False,
      short="Deployment and configuration of Exchange Online, SharePoint, OneDrive, Teams and Entra ID.",
      long="We deploy and configure Microsoft 365 services like Exchange Online, SharePoint, OneDrive, Teams, and Entra ID, so your people can collaborate better and your business runs on modern digital tools.",
      points=["Assessment", "Setup and configuration", "Migration", "Training for your team"]),
 dict(id="endpoint-security", icon="shield", title="Endpoint Management & Security", new=False,
      short="User device management and protection with Microsoft Intune, Defender for Endpoint and ZOHO Endpoint Central.",
      long="User device management and protection using Microsoft Intune and Defender for Endpoint, and ZOHO Endpoint Central. We secure your devices and infrastructure against modern threats.",
      points=["Policy enforcement", "Real-time threat detection", "Reporting", "Microsoft Intune, Defender for Endpoint & ZOHO Endpoint Central"]),
 dict(id="training", icon="grad", title="Training", new=False,
      short="Virtual or in-person technological capacity-building for users and IT staff.",
      long="A virtual or in-person technological capacity-building programme that helps your team thrive with modern technologies, from everyday users to dedicated IT staff.",
      points=["Admin tasks", "Security awareness", "Certification tracks for users and IT staff", "Virtual or in-person delivery"]),
 dict(id="website-design", icon="monitor", title="Website Design", new=True,
      short="Modern, responsive websites that present your business professionally on every device.",
      long="Modern, responsive and conversion-focused websites that present your business professionally on every device, designed around your audience and your goals.",
      points=["Custom, brand-led design", "Mobile-first responsive layouts", "Clear structure and SEO-ready pages", "Fast, accessible experiences"]),
 dict(id="web-app-development", icon="code", title="Web & Application Development", new=True,
      short="Custom web platforms, portals and applications that are secure, fast and built to scale.",
      long="Custom web platforms, business portals and applications built with modern technologies. Secure, fast and scalable, and ready to grow with your business.",
      points=["Custom web applications and portals", "Dashboards and admin systems", "Integrations with the tools you use", "Ongoing maintenance and support"]),
 dict(id="product-design", icon="pen", title="Product Design", new=True,
      short="UX research, wireframes, prototypes and polished interfaces for digital products people love to use.",
      long="From idea to interface: we research, wireframe and prototype digital products, then polish them into interfaces that people find simple and enjoyable to use.",
      points=["UX research and user flows", "Wireframes and clickable prototypes", "UI design systems", "Design hand-off to development"]),
 dict(id="branding", icon="spark", title="Branding", new=True,
      short="Logos, visual identity and brand guidelines that make your business memorable and consistent.",
      long="Logos, visual identity and brand guidelines that make your business memorable, and keep it consistent across print, web and social media.",
      points=["Logo and visual identity", "Colour and typography systems", "Brand guidelines", "Social media and marketing collateral"]),
]
WHAT_WE_DO = [
 ("headset", "Solve complex IT issues", "with responsive support"),
 ("handshake", "Deploy Microsoft 365", "and other digital tools to boost collaboration"),
 ("shield", "Secure devices and infrastructure", "against modern threats"),
 ("grad", "Train your team to thrive", "with modern technologies"),
]
NAV = [("index.html", "Home"), ("about.html", "About Us"), ("services.html", "Our Services"), ("training.html", "Training"), ("contact.html", "Contact")]

# ------------------------------------------------------------------ shared chunks
def head(title, desc, page):
    return f'''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title}</title>
<meta name="description" content="{desc}">
<meta name="theme-color" content="#F77F00">
<meta property="og:title" content="{title}"><meta property="og:description" content="{desc}"><meta property="og:type" content="website">
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
{SPRITE}
<a class="skip" href="#main">Skip to content</a>
'''

def logo():
    return '''<a class="logo" href="index.html" aria-label="Empre Technologies — home">
        <img src="assets/img/logo-icon.png" alt="" width="54" height="50">
        <span class="logo-text"><b>Empre</b><small>TECHNOLOGIES</small></span>
      </a>'''

def social_links(cls_items=""):
    return "".join(f'<a href="{u}" aria-label="{n}" target="_blank" rel="noopener">{ic(k)}</a>' for k, n, u in SOCIAL)

def header(active, php=False):
    if php:
        links = "".join(f'<li><a class="link<?= ($active ?? "")==="{h}" ? " active" : "" ?>" href="{h}">{t}</a></li>' for h, t in NAV)
    else:
        links = "".join(f'<li><a class="link{" active" if h==active else ""}" href="{h}">{t}</a></li>' for h, t in NAV)
    return f'''<div class="topbar"><div class="container">
  <ul>
    <li><a href="mailto:{EMAIL}">{ic("mail")}{EMAIL}</a></li>
    <li class="hide-m"><a href="tel:{PHONE_TEL}">{ic("phone")}{PHONE_DISPLAY}</a></li>
  </ul>
  <div class="social">{social_links()}</div>
</div></div>
<header class="header"><div class="container">
  {logo()}
  <nav class="nav" id="nav" aria-label="Main">
    <button class="nav-close" aria-label="Close menu">{ic("close")}</button>
    <ul>{links}</ul>
  </nav>
  <a class="btn btn-primary btn-sm" href="contact.html">Get a Quote {ic("arrow")}</a>
  <button class="menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="nav">{ic("menu")}</button>
</div></header>
<div class="scrim"></div>
<main id="main">
'''

def footer():
    q = "".join(f'<li><a href="{h}">{ic("arrow")}{t}</a></li>' for h, t in NAV)
    s = "".join(f'<li><a href="services.html#{x["id"]}">{ic("arrow")}{x["title"].replace(" Solution Implementation","")}</a></li>' for x in SERVICES)
    return f'''</main>
<footer class="footer">
  <div class="container footer-main">
    <div>
      {logo()}
      <p style="margin-top:22px;max-width:340px">A forward-thinking IT solutions company delivering scalable, secure and intelligent technology services tailored to the needs of modern businesses.</p>
      <div class="f-social">{social_links()}</div>
    </div>
    <div><h4>Menu</h4><ul>{q}</ul></div>
    <div><h4>Our Services</h4><ul>{s}</ul></div>
    <div><h4>Contact Us</h4>
      <ul class="contact-list">
        <li>{ic("mail")}<a href="mailto:{EMAIL}">{EMAIL}</a></li>
        <li>{ic("phone")}<a href="tel:{PHONE_TEL}">{PHONE_DISPLAY}</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom"><div class="container">
    <span>&copy; <span data-year>2026</span> Empre Technologies Ltd. All rights reserved.</span>
    <span><a href="index.html">Home</a> &nbsp;·&nbsp; <a href="about.html">About</a> &nbsp;·&nbsp; <a href="services.html">Services</a> &nbsp;·&nbsp; <a href="contact.html">Contact</a></span>
  </div></div>
</footer>
<button class="to-top" aria-label="Back to top">{ic("arrow")}</button>
<script src="assets/js/main.js"></script>
</body>
</html>
'''

def cta(title="Ready to move your business forward?", sub="Whether you're a startup or a growing enterprise, Empre is your IT partner for the journey ahead."):
    return f'''<div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>{title}</h2><p>{sub}</p></div>
  <a class="btn btn-white" href="contact.html">Contact Us {ic("arrow")}</a>
</div></div></div>'''

def banner(title, crumb, sub):
    return f'''<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.html">Home</a><span>/</span><span class="cur">{crumb}</span></div>
  <h1>{title}</h1><p>{sub}</p>
</div></section>
'''

def who_we_are_split(button=True):
    btn = f'<a class="btn btn-primary" href="about.html">Learn more about us {ic("arrow")}</a>' if button else ""
    return f'''<section class="section"><div class="container split">
  <div class="visual reveal">
    <div class="panel"></div>
    <div class="mark"><img src="assets/img/logo-icon.png" alt="Empre Technologies logo mark"></div>
    <div class="float f2"><span class="ico">{ic("shield")}</span><div><b>Secure</b><small>Devices &amp; infrastructure</small></div></div>
    <div class="float f1"><span class="ico">{ic("cloud")}</span><div><b>Scalable</b><small>Cloud productivity</small></div></div>
  </div>
  <div class="reveal d1">
    <span class="eyebrow">Who We Are</span>
    <h2>Forward-thinking IT solutions for modern businesses</h2>
    <p>We are a forward-thinking IT solutions company that delivers scalable, secure, and intelligent technology services tailored to the needs of modern businesses.</p>
    <p>From technical support and endpoint security to cloud productivity and hands-on training, Empre helps companies run smoother and grow faster.</p>
    <ul class="checks" style="margin:26px 0 34px">
      <li><span class="tick">{ic("check")}</span><span><b>Scalable &amp; secure.</b> Technology that grows with you and protects what matters.</span></li>
      <li><span class="tick">{ic("check")}</span><span><b>Intelligent &amp; tailored.</b> Solutions shaped around your business, not a template.</span></li>
    </ul>
    {btn}
  </div>
</div></section>'''

def what_we_do():
    cards = "".join(f'''<div class="card reveal d{i%4}"><span class="num">0{i+1}</span><div class="ico">{ic(icn)}</div><h3>{t}</h3><p>{s}</p></div>''' for i, (icn, t, s) in enumerate(WHAT_WE_DO))
    return f'''<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">What We Do</span><h2>Technology that keeps your business moving</h2>
  <p>Practical IT services, from everyday support to security and training, delivered by people who understand your business.</p></div>
  <div class="cards-4">{cards}</div>
</div></section>'''

def services_grid():
    cards = "".join(f'''<a class="card reveal d{i%4}{" is-new" if s["new"] else ""}" href="services.html#{s["id"]}"><div class="ico">{ic(s["icon"])}</div><h3>{s["title"]}</h3><p>{s["short"]}</p><span class="more">Learn more {ic("arrow")}</span></a>''' for i, s in enumerate(SERVICES))
    return f'''<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Services</span><h2>Everything your business needs to run, grow and look its best</h2>
  <p>IT support, cloud and security, plus design and software development, all from one trusted team.</p></div>
  <div class="cards-4 services">{cards}</div>
  <div style="text-align:center;margin-top:48px"><a class="btn btn-dark" href="services.html">View all services {ic("arrow")}</a></div>
</div></section>'''

def training_teaser():
    names = ["Front End Development", "Cloud Computing", "Cyber Security", "DevOps Engineering", "Product Design"]
    chips = "".join(f'<a class="chip-lg" href="training.php">{n}</a>' for n in names)
    return f'''<section class="section"><div class="container"><div class="tease reveal">
  <div><span class="eyebrow">Empre Training</span><h2>Build in-demand tech skills with hands-on training</h2>
  <p>Virtual or in-person capacity-building programmes taught by practitioners. Pick a course, read the details and enrol online in minutes.</p>
  <div class="chips-lg" id="tease-courses">{chips}</div>
  <a class="btn btn-primary" href="training.php">Explore courses &amp; enrol {ic("arrow")}</a></div>
  <div class="tease-art" aria-hidden="true"><span>{ic("grad")}</span></div>
</div></div></section>
<script>fetch('api-courses.php').then(function(r){{return r.json()}}).then(function(j){{if(!j.length)return;document.getElementById('tease-courses').innerHTML=j.map(function(c){{var a=document.createElement('a');a.className='chip-lg';a.href='course.php?c='+encodeURIComponent(c.slug);a.textContent=c.title;return a.outerHTML}}).join('')}}).catch(function(){{}});</script>'''

def why():
    p = [("bulb", "Innovation", "We keep pace with modern technologies and bring practical, forward-looking ideas to every project."),
         ("target", "Excellence", "Careful work, clear communication and high standards from the first call to the final hand-over."),
         ("handshake", "Real business outcomes", "A client-first approach focused on results you can measure, not just tasks completed.")]
    cards = "".join(f'<div class="pillar reveal d{i+1}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{c}</p></div>' for i, (a, b, c) in enumerate(p))
    return f'''<section class="section dark"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Why Empre</span><h2>Technical expertise with a client-first approach</h2>
  <p>We combine technical expertise with a client-first approach, focusing on innovation, excellence, and real business outcomes.</p></div>
  <div class="pillars">{cards}</div>
  <p class="why-tag reveal">Whether you're a startup or a growing enterprise, <em>Empre is your IT partner</em> for the journey ahead.</p>
</div></section>'''

# ------------------------------------------------------------------ pages
def page_home():
    slides = [
      ("1", "Welcome to Empre Technologies", "Smart IT solutions for <em>modern</em> businesses",
       "Scalable, secure and intelligent technology services that help your company run smoother and grow faster.", "Explore Services", "services.html"),
      ("2", "Microsoft 365", "Collaborate better with <em>Microsoft 365</em>",
       "Exchange Online, SharePoint, OneDrive, Teams and Entra ID: assessed, set up, migrated and taught.", "See How We Help", "services.html#microsoft-365"),
      ("3", "Endpoint Management &amp; Security", "Secure every device, <em>every day</em>",
       "Policy enforcement, real-time threat detection and reporting with Intune, Defender for Endpoint and ZOHO Endpoint Central.", "Protect Your Business", "services.html#endpoint-security"),
      ("4", "Design &amp; Development", "Websites and apps that make your <em>brand</em> shine",
       "Website design, web and application development, product design and branding from one creative team.", "Start a Project", "contact.html"),
      ("5", "Training", "Build a team that <em>thrives</em> with technology",
       "Virtual or in-person capacity-building: admin tasks, security awareness and certification tracks.", "Train Your Team", "training.html"),
    ]
    sl = "".join(f'''<div class="slide{" is-active" if n=="1" else ""}" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-{n}.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">{eb}</span><h1>{h}</h1><p class="lead">{p}</p>
        <div class="slide-actions"><a class="btn btn-primary" href="{href}">{cta_t} {ic("arrow")}</a><a class="btn btn-outline" href="contact.html">Contact Us</a></div>
      </div></div></div>''' for n, eb, h, p, cta_t, href in slides)
    dots = "".join(f'<button aria-label="Go to slide {i+1}"></button>' for i in range(len(slides)))
    strip = [("headset", "Technical Support", "Remote &amp; on-site"), ("cloud", "Microsoft 365", "Deployment &amp; migration"),
             ("shield", "Endpoint Security", "Intune · Defender · ZOHO"), ("grad", "Training", "Virtual or in-person")]
    st = "".join(f'<div class="strip-item"><span class="ico">{ic(i)}</span><div><b>{t}</b><span>{s}</span></div></div>' for i, t, s in strip)
    body = head("Empre Technologies Ltd | IT Solutions, Microsoft 365, Security & Digital Design",
                "Empre Technologies delivers scalable, secure and intelligent IT services: technical support, Microsoft 365, endpoint security, training, web development and branding.", "home") + header("index.html")
    body += f'''<section class="hero" aria-roledescription="carousel" aria-label="Featured">
  <div class="slides">{sl}</div>
  <div class="hero-ctrl"><div class="container"><div class="dots">{dots}</div>
    <div class="arrows"><button class="prev" aria-label="Previous slide">{ic("arrow")}</button><button class="next" aria-label="Next slide">{ic("arrow")}</button></div></div></div>
</section>
<div class="strip"><div class="container"><div class="strip-grid">{st}</div></div></div>
'''
    body += who_we_are_split() + what_we_do() + services_grid() + training_teaser() + why() + cta() + footer()
    return body

def page_about():
    vals = [("bulb", "Innovation", "Embracing modern technologies to solve real problems."),
            ("target", "Excellence", "High standards in every project and every support call."),
            ("users", "Client-first", "Your goals guide every recommendation we make."),
            ("handshake", "Real outcomes", "We measure success by the impact on your business.")]
    v = "".join(f'<div class="card reveal d{i%4}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{c}</p></div>' for i, (a, b, c) in enumerate(vals))
    body = head("About Us | Empre Technologies Ltd", "Learn about Empre Technologies, a forward-thinking IT solutions company delivering scalable, secure and intelligent technology services.", "about") + header("about.html")
    body += banner("About Us", "About Us", "A forward-thinking IT solutions company helping businesses run smoother and grow faster.")
    body += who_we_are_split(button=False)
    body += f'''<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">What Drives Us</span><h2>Our values</h2><p>The principles behind everything we deliver.</p></div>
  <div class="cards-4">{v}</div></div></section>'''
    body += what_we_do().replace('section soft', 'section') + why() + cta() + footer()
    return body

def enq(s):
    if s["id"] == "training":
        return f'<a class="btn btn-primary" style="margin-top:14px" href="training.html">View courses &amp; enrol {ic("arrow")}</a>'
    return f'<a class="btn btn-primary" style="margin-top:14px" href="contact.html?service={s["id"]}">Enquire about this {ic("arrow")}</a>'

def page_services():
    blocks = ""
    for i, s in enumerate(SERVICES):
        pts = "".join(f'<li><span class="tick">{ic("check")}</span><span>{p}</span></li>' for p in s["points"])
        blocks += f'''<article class="svc" id="{s["id"]}">
  <div class="svc-art reveal"><span class="tag">{"0"+str(i+1)} · {"DESIGN &amp; DEVELOPMENT" if s["new"] else "IT SERVICES"}</span><div class="big">{ic(s["icon"])}</div></div>
  <div class="reveal d1"><span class="eyebrow">Service 0{i+1}</span><h2>{s["title"]}</h2><p>{s["long"]}</p>
    <ul class="checks">{pts}</ul>
    {enq(s)}</div>
</article>'''
    body = head("Our Services | Empre Technologies Ltd", "IT support, Microsoft 365, endpoint security, training, website design, web & app development, product design and branding.", "services") + header("services.html")
    body += banner("Our Services", "Our Services", "IT support, cloud and security, training, and digital design and development, all from one team.")
    body += f'''<section class="section" style="padding-bottom:40px"><div class="container">
  <div class="sec-head center reveal" style="margin-bottom:0"><span class="eyebrow">What we offer</span><h2>Eight services, one trusted partner</h2>
  <p>From keeping your systems running to building the websites, apps and brand your customers see.</p></div></div></section>
<section style="padding-bottom:70px"><div class="container">{blocks}</div></section>'''
    body += cta("Not sure which service you need?", "Tell us about your business and we'll recommend the right solution.") + footer()
    return body

def page_contact():
    opts = '<option value="">Select a service (optional)</option>' + "".join(f'<option value="{s["title"]}" data-id="{s["id"]}">{s["title"]}</option>' for s in SERVICES) + '<option value="Other">Other / Not sure</option>'
    info = [("mail", "Email", EMAIL, f"mailto:{EMAIL}"), ("phone", "Phone", PHONE_DISPLAY, f"tel:{PHONE_TEL}"),
            ("globe", "Website", WEBSITE, f"https://{WEBSITE}"), ("instagram", "Instagram", INSTAGRAM_HANDLE, "#")]
    il = "".join(f'<a class="info" href="{h}"><span class="ico">{ic(i)}</span><div><small>{l}</small><b>{v}</b></div></a>' for i, l, v, h in info)
    body = head("Contact Us | Empre Technologies Ltd", "Get in touch with Empre Technologies for IT support, Microsoft 365, security, training, web development and branding.", "contact") + header("contact.html")
    body += banner("Contact Us", "Contact", "Have a question or a project in mind? We'd love to hear from you.")
    body += f'''<section class="section"><div class="container contact-grid">
  <div class="reveal"><span class="eyebrow">Get in touch</span><h2 style="font-size:clamp(30px,3.8vw,44px);margin-top:14px">Let's talk about your business</h2>
    <p>Send us a message and a member of the Empre team will respond as soon as possible. You can also reach us directly using the details below.</p>
    <div class="info-list">{il}</div></div>
  <div class="form-card reveal d1">
    <h3>Send us a message</h3><p>Fields marked * are required.</p>
    <form id="contact-form" action="send-mail.php" method="post" novalidate>
      <div class="form-row">
        <div class="field"><label for="name">Full name *</label><input id="name" name="name" type="text" autocomplete="name" required><span class="err">Please enter your name.</span></div>
        <div class="field"><label for="email">Email address *</label><input id="email" name="email" type="email" autocomplete="email" required><span class="err">Please enter a valid email.</span></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" type="tel" autocomplete="tel"></div>
        <div class="field"><label for="service">Service of interest</label><select id="service" name="service">{opts}</select></div>
      </div>
      <div class="field"><label for="message">How can we help? *</label><textarea id="message" name="message" required></textarea><span class="err">Please tell us a little more (at least 10 characters).</span></div>
      <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <button class="btn btn-primary" type="submit">Send Message {ic("arrow")}</button>
      <div class="form-note" role="status" aria-live="polite"></div>
    </form>
  </div>
</div></section>
<script>
(function(){{var m=location.search.match(/service=([\\w-]+)/);if(!m)return;var o=document.querySelector('#service option[data-id="'+m[1]+'"]');if(o)o.parentNode.value=o.value;}})();
</script>
'''
    body += footer()
    return body


# ------------------------------------------------------------------ training
COURSES = [
 dict(slug="front-end-development", title="Front End Development", icon="code",
      short="Learn to design and build fast, responsive, modern websites and web interfaces from scratch.",
      overview="Front End Development teaches you how to turn designs into real, interactive websites and web apps. You will move from the fundamentals of the web to building modern interfaces with industry-standard tools, finishing with projects you can show employers and clients.",
      learn=["HTML5 and semantic page structure", "CSS3, Flexbox, Grid and responsive design", "JavaScript (ES6+) and working with the DOM", "Git and GitHub for version control", "React and component-based thinking", "TypeScript basics", "Consuming APIs and handling data", "Building and deploying portfolio projects"],
      who=["Beginners starting a tech career", "Designers who want to code their ideas", "Professionals changing careers"]),
 dict(slug="cloud-computing", title="Cloud Computing", icon="cloud",
      short="Understand cloud platforms and learn to deploy, manage and secure cloud services for any business.",
      overview="Cloud Computing introduces the concepts and hands-on skills behind modern cloud infrastructure. You will learn how businesses host, scale, secure and manage applications and data in the cloud, with a practical focus on Microsoft cloud services.",
      learn=["Cloud concepts and service models (IaaS, PaaS, SaaS)", "Virtual machines, storage and networking", "Identity and access with Microsoft Entra ID", "Cloud security and governance basics", "Monitoring, backup and cost management", "Migrating workloads to the cloud", "Introduction to containers", "Preparing for certification tracks"],
      who=["IT support staff growing into cloud roles", "Graduates entering IT infrastructure", "Business owners who want to understand the cloud"]),
 dict(slug="cyber-security", title="Cyber Security", icon="shield",
      short="Learn to identify threats, protect systems and data, and respond to security incidents with confidence.",
      overview="Cyber Security gives you the knowledge to defend people, devices and data against modern threats. The course covers how attacks work, how to protect systems, and how to detect and respond to incidents, in both technical and awareness-focused tracks.",
      learn=["Security fundamentals and common threats", "Network and endpoint security", "Identity and access management", "Device protection with Intune and Defender for Endpoint", "Vulnerability assessment basics", "Incident detection and response", "Security awareness and good practice", "Introduction to ethical hacking"],
      who=["IT staff responsible for protecting systems", "Beginners drawn to security careers", "Teams that need security awareness training"]),
 dict(slug="devops-engineering", title="DevOps Engineering", icon="server",
      short="Master the tools and practices that automate building, testing, deploying and running software at scale.",
      overview="DevOps Engineering bridges development and operations. You will learn to automate the path from code to production with pipelines, containers and infrastructure as code, so software can be released faster and more reliably.",
      learn=["Linux and the command line", "Git workflows and collaboration", "CI/CD pipelines", "Containers with Docker", "Introduction to Kubernetes", "Infrastructure as Code", "Monitoring and logging", "Deploying applications to the cloud"],
      who=["Developers who want to ship faster", "System administrators moving into automation", "Aspiring DevOps and cloud engineers"]),
]

def course_cards(cls=""):
    return "".join(f'''<a class="card reveal d{i%4}" href="course-{c["slug"]}.html"><span class="num">0{i+1}</span><div class="ico">{ic(c["icon"])}</div><h3>{c["title"]}</h3><p>{c["short"]}</p><span class="more">View details &amp; enrol {ic("arrow")}</span></a>''' for i, c in enumerate(COURSES))

def training_teaser():
    return f'''<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Empre Training</span><h2>Build in-demand tech skills with our courses</h2>
  <p>Virtual or in-person capacity-building programmes for individuals and teams. Pick a course to see the details and enrol.</p></div>
  <div class="cards-4">{course_cards()}</div>
  <div style="text-align:center;margin-top:48px"><a class="btn btn-dark" href="training.html">Explore all training {ic("arrow")}</a></div>
</div></section>'''

def page_training():
    body = head("Training Courses | Empre Technologies Ltd", "Enrol in Front End Development, Cloud Computing, Cyber Security and DevOps Engineering courses with Empre Technologies, virtual or in-person.", "training") + header("training.html")
    body += banner("Training", "Training", "Virtual or in-person capacity-building programmes for users and IT staff.")
    body += f'''<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Courses</span><h2>Choose a course and start learning</h2>
  <p>Select a course to view what you will learn and complete your enrolment online. Each applicant receives a unique student ID.</p></div>
  <div class="cards-4">{course_cards()}</div></div></section>
<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">How enrolment works</span><h2>Three simple steps</h2></div>
  <div class="pillars">
   <div class="pillar light reveal d1"><div class="ico">{ic("target")}</div><h3>1. Pick a course</h3><p>Open the course page to see the topics covered, who it is for and how it is delivered.</p></div>
   <div class="pillar light reveal d2"><div class="ico">{ic("pen")}</div><h3>2. Enrol online</h3><p>Complete the short enrolment form. You receive your unique student ID immediately.</p></div>
   <div class="pillar light reveal d3"><div class="ico">{ic("mail")}</div><h3>3. Get your invoice</h3><p>Our team reviews your enrolment and sends your invoice and next steps by email.</p></div>
  </div></div></section>'''
    body += cta("Training for your whole team?", "Tell us about your organisation and we will tailor a programme for your users and IT staff.") + footer()
    return body

def page_course(c):
    learn = "".join(f'<li><span class="tick">{ic("check")}</span><span>{x}</span></li>' for x in c["learn"])
    who = "".join(f'<li><span class="tick">{ic("check")}</span><span>{x}</span></li>' for x in c["who"])
    others = "".join(f'<a class="card" href="course-{o["slug"]}.html"><div class="ico">{ic(o["icon"])}</div><h3>{o["title"]}</h3><span class="more">View details {ic("arrow")}</span></a>' for o in COURSES if o["slug"] != c["slug"])
    body = head(f'{c["title"]} Course | Empre Technologies Training', c["short"], "training") + header("training.html")
    body += f'''<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.html">Home</a><span>/</span><a href="training.html">Training</a><span>/</span><span class="cur">{c["title"]}</span></div>
  <h1>{c["title"]}</h1><p>{c["short"]}</p>
  <div style="margin-top:28px"><a class="btn btn-primary" href="#enrol">Enrol now {ic("arrow")}</a></div>
</div></section>
<section class="section"><div class="container course-grid">
  <div>
    <div class="reveal"><span class="eyebrow">Course overview</span><h2 class="h2s">About this course</h2><p>{c["overview"]}</p></div>
    <div class="reveal" style="margin-top:40px"><h3>What you will learn</h3><ul class="checks two">{learn}</ul></div>
    <div class="reveal" style="margin-top:40px"><h3>Who it is for</h3><ul class="checks">{who}</ul></div>
    <div class="reveal facts" style="margin-top:40px">
      <div class="fact"><span class="ico">{ic("globe")}</span><div><small>Delivery</small><b>Virtual or in-person</b></div></div>
      <div class="fact"><span class="ico">{ic("grad")}</span><div><small>Certification</small><b>Certification-track preparation</b></div></div>
      <div class="fact"><span class="ico">{ic("mail")}</span><div><small>Fees &amp; schedule</small><b>Sent with your invoice</b></div></div>
    </div>
  </div>
  <aside class="enrol-card reveal d1" id="enrol">
    <div id="enrol-form-wrap">
      <h3>Enrol for this course</h3><p class="sub">Complete the form to receive your unique student ID.</p>
      <form id="enrol-form" action="enrol.php" method="post" novalidate>
        <input type="hidden" name="course" value="{c["title"]}">
        <div class="course-pill">{ic(c["icon"])}<span>{c["title"]}</span></div>
        <div class="field"><label for="e-name">Full name *</label><input id="e-name" name="name" type="text" autocomplete="name"><span class="err">Please enter your full name.</span></div>
        <div class="form-row">
          <div class="field"><label for="e-age">Age *</label><input id="e-age" name="age" type="number" min="10" max="99" inputmode="numeric"><span class="err">Enter a valid age.</span></div>
          <div class="field"><label for="e-sex">Sex *</label><select id="e-sex" name="sex"><option value="">Select</option><option>Male</option><option>Female</option></select><span class="err">Please select.</span></div>
        </div>
        <div class="field"><label for="e-loc">Location *</label><input id="e-loc" name="location" type="text" placeholder="City, State / Country" autocomplete="address-level2"><span class="err">Please enter your location.</span></div>
        <div class="field"><label for="e-email">Email address *</label><input id="e-email" name="email" type="email" autocomplete="email"><span class="err">Please enter a valid email.</span></div>
        <div class="field"><label for="e-phone">Phone number *</label><input id="e-phone" name="phone" type="tel" autocomplete="tel" placeholder="+234 …"><span class="err">Please enter a valid phone number.</span></div>
        <div class="hp" aria-hidden="true"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Complete Enrolment {ic("arrow")}</button>
        <div class="form-note" role="status" aria-live="polite"></div>
      </form>
    </div>
    <div id="enrol-done" hidden>
      <div class="done-ico">{ic("check")}</div>
      <h3>You are enrolled!</h3>
      <p class="sub">Keep your student ID safe. Our team will review your enrolment and email your invoice.</p>
      <div class="sid"><small>Your Student ID</small><b id="sid-val">—</b></div>
      <dl class="sum"><dt>Name</dt><dd id="sum-name"></dd><dt>Course</dt><dd id="sum-course"></dd></dl>
      <a class="btn btn-dark btn-sm" href="training.html">Back to courses</a>
    </div>
  </aside>
</div></section>
<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">More courses</span><h2>Explore other courses</h2></div>
  <div class="cards-4 three">{others}</div></div></section>'''
    body += cta("Questions before you enrol?", "Talk to our team and we will help you choose the right course.") + footer()
    return body

# ------------------------------------------------------------------ hero art
def hero_svg(n):
    random.seed(n * 7)
    W, H = 1600, 900
    s = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" preserveAspectRatio="xMidYMid slice">',
         '<defs>'
         '<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#141212"/><stop offset="1" stop-color="#2d2828"/></linearGradient>'
         '<radialGradient id="g1"><stop offset="0" stop-color="#F77F00" stop-opacity=".55"/><stop offset="1" stop-color="#F77F00" stop-opacity="0"/></radialGradient>'
         '<linearGradient id="or" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFA23A"/><stop offset="1" stop-color="#F77F00"/></linearGradient>'
         '<pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M60 0H0V60" fill="none" stroke="#fff" stroke-opacity=".05"/></pattern>'
         '</defs>',
         f'<rect width="{W}" height="{H}" fill="url(#bg)"/><rect width="{W}" height="{H}" fill="url(#grid)"/>']
    gx, gy = [(1200, 420), (1150, 520), (1250, 380), (1180, 450), (1220, 500)][n - 1]
    s.append(f'<circle cx="{gx}" cy="{gy}" r="560" fill="url(#g1)"/>')
    cx, cy = 1170, 450
    if n == 1:  # network of nodes
        pts = [(cx + 260 * math.cos(a), cy + 240 * math.sin(a)) for a in [i * 2 * math.pi / 7 + .3 for i in range(7)]]
        pts += [(cx + 420 * math.cos(a), cy + 340 * math.sin(a)) for a in [i * 2 * math.pi / 9 + .1 for i in range(9)]]
        for p in pts:
            s.append(f'<line x1="{cx}" y1="{cy}" x2="{p[0]:.0f}" y2="{p[1]:.0f}" stroke="#F77F00" stroke-opacity=".35" stroke-width="2"/>')
        for i, p in enumerate(pts):
            for q in pts[i + 1:i + 3]:
                s.append(f'<line x1="{p[0]:.0f}" y1="{p[1]:.0f}" x2="{q[0]:.0f}" y2="{q[1]:.0f}" stroke="#fff" stroke-opacity=".15"/>')
        for p in pts:
            s.append(f'<circle cx="{p[0]:.0f}" cy="{p[1]:.0f}" r="{random.choice([8,12,16])}" fill="#fff" fill-opacity=".12" stroke="#F77F00" stroke-opacity=".8" stroke-width="2"/>')
        s.append(f'<circle cx="{cx}" cy="{cy}" r="96" fill="url(#or)"/><circle cx="{cx}" cy="{cy}" r="130" fill="none" stroke="#F77F00" stroke-opacity=".5" stroke-width="2"/><circle cx="{cx}" cy="{cy}" r="170" fill="none" stroke="#fff" stroke-opacity=".12" stroke-width="2" stroke-dasharray="6 10"/>')
        s.append(f'<rect x="{cx-40}" y="{cy-28}" width="80" height="56" rx="8" fill="none" stroke="#fff" stroke-width="6"/><path d="M{cx-22} {cy+44}h44" stroke="#fff" stroke-width="6" stroke-linecap="round"/>')
    elif n == 2:  # cloud + app tiles
        s.append(f'<path d="M{cx-250} {cy+120}a110 110 0 0 1 20-218 170 170 0 0 1 320-30 120 120 0 0 1 30 248z" fill="url(#or)" opacity=".95"/>')
        s.append(f'<path d="M{cx-250} {cy+120}a110 110 0 0 1 20-218 170 170 0 0 1 320-30 120 120 0 0 1 30 248z" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width="3" transform="translate(22 26)"/>')
        for i, (dx, dy) in enumerate([(-300, 190), (-130, 230), (60, 190), (230, 240)]):
            s.append(f'<rect x="{cx+dx}" y="{cy+dy}" width="92" height="92" rx="22" fill="#fff" fill-opacity=".1" stroke="#F77F00" stroke-opacity=".7" stroke-width="2"/><rect x="{cx+dx+26}" y="{cy+dy+26}" width="40" height="40" rx="10" fill="#F77F00" fill-opacity="{.5+.12*i}"/>')
        for dx, dy in [(-60, 40), (60, 40), (0, -20)]:
            s.append(f'<circle cx="{cx+dx}" cy="{cy+dy}" r="26" fill="#fff" fill-opacity=".9"/>')
    elif n == 3:  # shield
        for r, o in [(360, .06), (300, .1), (240, .16)]:
            s.append(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="none" stroke="#F77F00" stroke-opacity="{o+.1}" stroke-width="2"/>')
        s.append(f'<path d="M{cx} {cy-210}l170 64v120c0 120-80 190-170 230-90-40-170-110-170-230v-120z" fill="url(#or)"/>')
        s.append(f'<path d="M{cx} {cy-210}l170 64v120c0 120-80 190-170 230-90-40-170-110-170-230v-120z" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="3" transform="translate(18 18)"/>')
        s.append(f'<path d="M{cx-70} {cy+10}l50 54 100-120" fill="none" stroke="#fff" stroke-width="22" stroke-linecap="round" stroke-linejoin="round"/>')
        for _ in range(14):
            a = random.random() * 6.28; r = random.randint(300, 400)
            s.append(f'<circle cx="{cx+r*math.cos(a):.0f}" cy="{cy+r*math.sin(a):.0f}" r="{random.randint(4,9)}" fill="#F77F00"/>')
    elif n == 4:  # browser windows
        s.append(f'<g transform="rotate(-6 {cx} {cy})">')
        s.append(f'<rect x="{cx-330}" y="{cy-230}" width="560" height="380" rx="22" fill="#fff" fill-opacity=".08" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>')
        s.append(f'<rect x="{cx-330}" y="{cy-230}" width="560" height="56" rx="22" fill="#fff" fill-opacity=".12"/>')
        for i, c in enumerate(["#F77F00", "#fff", "#fff"]):
            s.append(f'<circle cx="{cx-296+i*28}" cy="{cy-202}" r="8" fill="{c}" fill-opacity="{1 if i==0 else .5}"/>')
        s.append(f'<rect x="{cx-300}" y="{cy-140}" width="300" height="34" rx="8" fill="#F77F00"/><rect x="{cx-300}" y="{cy-90}" width="440" height="16" rx="8" fill="#fff" fill-opacity=".35"/><rect x="{cx-300}" y="{cy-60}" width="380" height="16" rx="8" fill="#fff" fill-opacity=".2"/>')
        for i in range(3):
            s.append(f'<rect x="{cx-300+i*190}" y="{cy-10}" width="170" height="130" rx="14" fill="#fff" fill-opacity=".1" stroke="#F77F00" stroke-opacity=".5"/>')
        s.append('</g>')
        s.append(f'<g transform="rotate(5 {cx+160} {cy+160})"><rect x="{cx+60}" y="{cy-60}" width="230" height="420" rx="36" fill="#141212" stroke="url(#or)" stroke-width="5"/><rect x="{cx+86}" y="{cy-20}" width="178" height="110" rx="14" fill="#F77F00"/><rect x="{cx+86}" y="{cy+110}" width="178" height="20" rx="10" fill="#fff" fill-opacity=".35"/><rect x="{cx+86}" y="{cy+148}" width="130" height="20" rx="10" fill="#fff" fill-opacity=".2"/><rect x="{cx+86}" y="{cy+200}" width="178" height="110" rx="14" fill="#fff" fill-opacity=".1"/></g>')
    else:  # training: board + people
        s.append(f'<rect x="{cx-320}" y="{cy-240}" width="640" height="330" rx="22" fill="#fff" fill-opacity=".08" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>')
        s.append(f'<rect x="{cx-280}" y="{cy-200}" width="250" height="26" rx="13" fill="#F77F00"/>')
        for i in range(4):
            s.append(f'<rect x="{cx-280}" y="{cy-140+i*38}" width="{[420,360,470,300][i]}" height="16" rx="8" fill="#fff" fill-opacity="{.35-.05*i}"/>')
        s.append(f'<path d="M{cx+120} {cy-70}l110-50 110 50-110 50z" fill="url(#or)"/><path d="M{cx+160} {cy-40}v50c40 36 120 36 140 0v-50" fill="none" stroke="#F77F00" stroke-width="10" stroke-linecap="round"/>')
        for i in range(5):
            x = cx - 300 + i * 150
            s.append(f'<circle cx="{x}" cy="{cy+200}" r="34" fill="{"#F77F00" if i==2 else "#fff"}" fill-opacity="{1 if i==2 else .22}"/><path d="M{x-56} {cy+310}a56 56 0 0 1 112 0z" fill="{"#F77F00" if i==2 else "#fff"}" fill-opacity="{1 if i==2 else .22}"/>')
    s.append('</svg>')
    return "".join(s)

# ------------------------------------------------------------------ php partials
def write_partials():
    h = head("__T__", "__D__", "") + header(None, php=True)
    h = h.replace("__T__", "<?= e($title) ?>").replace("__D__", "<?= e($desc) ?>")
    open(os.path.join(ROOT, "inc/head.php"), "w", encoding="utf-8").write(h)
    open(os.path.join(ROOT, "inc/foot.php"), "w", encoding="utf-8").write(footer())
write_partials()

# ------------------------------------------------------------------ write
pages = {"index.html": page_home, "about.html": page_about, "services.html": page_services, "training.html": page_training, "contact.html": page_contact}
for _c in COURSES:
    pages[f"course-{_c['slug']}.html"] = (lambda c=_c: page_course(c))
for fn, f in pages.items():
    open(os.path.join(ROOT, fn), "w", encoding="utf-8").write(f())
for n in range(1, 6):
    open(os.path.join(ROOT, f"assets/img/hero-{n}.svg"), "w", encoding="utf-8").write(hero_svg(n))
print("built", list(pages), "+ 5 hero images")
