#!/usr/bin/env python3
"""Generates the public PHP pages, header/footer partials and hero artwork. Run:  python3 build.py
Services are NOT edited here: they are managed in Admin > Services (database).
This file holds the brand copy (tagline, positioning, values, process, customers)."""
import os, math, random

ROOT = os.path.dirname(os.path.abspath(__file__))
EMAIL = "info@empretechnologies.com.ng"
PHONE_DISPLAY = "+234 813 964 5973"
PHONE_TEL = "+2348139645973"
WEBSITE = "empretechnologies.com.ng"
INSTAGRAM_URL = "https://www.instagram.com/empre_technologies"
LINKEDIN_URL = "https://www.linkedin.com/company/empre-technologies"
INSTAGRAM_HANDLE = "@empre_technologies"
# TODO: replace "#" with the real profile URLs
SOCIAL = [("instagram", "Instagram", INSTAGRAM_URL), ("linkedin", "LinkedIn", LINKEDIN_URL)]

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
 "chart": '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
 "compass": '<circle cx="12" cy="12" r="10"/><path d="m16 8-2 6-6 2 2-6z"/>',
 "server": '<rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><path d="M6 7h.01M6 17h.01"/>',
}
def ic(name, cls="ic"):
    return f'<svg class="{cls}" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-{name}"/></svg>'
SPRITE = '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>' + "".join(
    f'<symbol id="i-{k}" viewBox="0 0 24 24">{v}</symbol>' for k, v in ICONS.items()) + '</defs></svg>'

# ------------------------------------------------------------------ content

# ------------------------------------------------------------------ content (brand + positioning)
# Services themselves are NOT here any more: they live in the database and are managed by the
# Super Admin in Admin > Services. Everything below is brand copy from the Enhanced Company Profile.
TAGLINE = "Technology that empowers businesses to grow."
DESCRIPTOR = "IT • Cloud • Cybersecurity • Digital Solutions • Training"
VALUES_LINE = "Innovation • Excellence • Integrity • Client Success"
NAV = [("index.php", "Home"), ("about.php", "About Us"), ("services.php", "Our Services"), ("training.php", "Training"), ("contact.php", "Contact")]

def phpq(s):
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"

def icp(v):
    """icon from a database row held in PHP variable v"""
    return f'<svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e(svc_icon({v}[\'icon\'])) ?>"/></svg>'

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
<link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body>
{SPRITE}
<a class="skip" href="#main">Skip to content</a>
'''

def logo():
    return '''<a class="logo" href="index.php" aria-label="Empre Technologies — home">
        <img src="assets/img/logo-icon.png" alt="" width="54" height="50">
        <span class="logo-text"><b>Empre</b><small>TECHNOLOGIES</small></span>
      </a>'''

def social_links():
    return "".join(f'<a href="{u}" aria-label="{n}" target="_blank" rel="noopener">{ic(k)}</a>' for k, n, u in SOCIAL)

def header(active=None, php=False):
    links = "".join(f'<li><a class="link<?= ($active ?? "")==="{h}" ? " active" : "" ?>" href="{h}">{t}</a></li>' for h, t in NAV)
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
    <div class="nav-extra">
      <a class="btn btn-primary" href="contact.php">Get a Quote {ic("arrow")}</a>
      <a class="nav-phone" href="tel:{PHONE_TEL}">{ic("phone")}{PHONE_DISPLAY}</a>
      <div class="nav-social">{social_links()}</div>
    </div>
  </nav>
  <a class="btn btn-primary btn-sm" href="contact.php">Get a Quote {ic("arrow")}</a>
  <button class="menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="nav">{ic("menu")}</button>
</div></header>
<div class="scrim"></div>
<main id="main">
'''

def footer():
    q = "".join(f'<li><a href="{h}">{ic("arrow")}{t}</a></li>' for h, t in NAV)
    svc = ('<?php $__fs = function_exists("site_services") ? site_services() : []; foreach ($__fs as $__s): ?>'
           f'<li><a href="services.php#<?= e($__s[\'slug\']) ?>">{ic("arrow")}<?= e($__s[\'title\']) ?></a></li><?php endforeach; ?>')
    return f'''</main>
<footer class="footer">
  <div class="container footer-main">
    <div>
      {logo()}
      <p style="margin-top:22px;max-width:340px"><b style="color:#fff">{TAGLINE}</b><br>A technology solutions company helping businesses and individuals with IT infrastructure, cloud, cybersecurity, software, digital design and professional training.</p>
      <div class="f-social">{social_links()}</div>
    </div>
    <div><h4>Menu</h4><ul>{q}</ul></div>
    <div><h4>Our Services</h4><ul>{svc}</ul></div>
    <div><h4>Contact Us</h4>
      <ul class="contact-list">
        <li>{ic("mail")}<a href="mailto:{EMAIL}">{EMAIL}</a></li>
        <li>{ic("phone")}<a href="tel:{PHONE_TEL}">{PHONE_DISPLAY}</a></li>
        <li>{ic("globe")}<a href="https://{WEBSITE}" target="_blank" rel="noopener">{WEBSITE}</a></li>
        <li>{ic("instagram")}<a href="{INSTAGRAM_URL}" target="_blank" rel="noopener">Instagram: {INSTAGRAM_HANDLE}</a></li>
        <li>{ic("linkedin")}<a href="{LINKEDIN_URL}" target="_blank" rel="noopener">LinkedIn: Empre Technologies</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom"><div class="container">
    <span>&copy; <span data-year>2026</span> Empre Technologies Ltd. All rights reserved. &nbsp;|&nbsp; {VALUES_LINE}</span>
    <span><a href="index.php">Home</a> &nbsp;·&nbsp; <a href="about.php">About</a> &nbsp;·&nbsp; <a href="services.php">Services</a> &nbsp;·&nbsp; <a href="training.php">Training</a> &nbsp;·&nbsp; <a href="contact.php">Contact</a></span>
  </div></div>
</footer>
<button class="to-top" aria-label="Back to top">{ic("arrow")}</button>
<script src="assets/js/main.js"></script>
<!-- Conversa AI chat widget: sits at the bottom-right of every public page (the back-to-top button is lifted above it in style.css) -->
<script src="https://conversaai.com.ng/widget.js" data-key="pk_c534d3ea0bb85253d069" async></script>
</body>
</html>
'''

def cta(title="Make technology work for your business.", sub="Talk to Empre Technologies about your next project, support requirement or training need."):
    return f'''<div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>{title}</h2><p>{sub}</p></div>
  <a class="btn btn-white" href="contact.php">Get in Touch {ic("arrow")}</a>
</div></div></div>'''

def banner(title, crumb, sub):
    return f'''<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><span class="cur">{crumb}</span></div>
  <h1>{title}</h1><p>{sub}</p>
</div></section>
'''

def who_we_are_split(button=True):
    btn = f'<a class="btn btn-primary" href="about.php">Learn more about us {ic("arrow")}</a>' if button else ""
    return f'''<section class="section"><div class="container split">
  <div class="visual reveal">
    <div class="panel"></div>
    <div class="mark"><img src="assets/img/logo-icon.png" alt="Empre Technologies logo mark"></div>
    <div class="float f2"><span class="ico">{ic("shield")}</span><div><b>Protect</b><small>Systems, devices &amp; data</small></div></div>
    <div class="float f1"><span class="ico">{ic("cloud")}</span><div><b>Enable</b><small>Reliable digital foundations</small></div></div>
  </div>
  <div class="reveal d1">
    <span class="eyebrow">Who We Are</span>
    <h2>A technology partner for organisations and individuals navigating a digital world</h2>
    <p>We are a forward-thinking technology solutions company delivering scalable, secure and practical services tailored to the needs of modern businesses.</p>
    <p>From responsive technical support and modern workplace solutions to digital product development and hands-on training, we help clients solve technology challenges and make progress with confidence.</p>
    <ul class="checks" style="margin:26px 0 34px">
      <li><span class="tick">{ic("check")}</span><span><b>Businesses.</b> Technology support, transformation and digital delivery.</span></li>
      <li><span class="tick">{ic("check")}</span><span><b>Startups.</b> Digital foundations, product design and development.</span></li>
      <li><span class="tick">{ic("check")}</span><span><b>Teams.</b> Workplace productivity, security and capability building.</span></li>
      <li><span class="tick">{ic("check")}</span><span><b>Individuals.</b> Practical technology training and career development.</span></li>
    </ul>
    {btn}
  </div>
</div></section>'''

def positioning(soft=True):
    p = [("layers", "Enable", "Build reliable digital foundations."), ("shield", "Protect", "Secure systems, devices and data."), ("users", "Empower", "Develop products, people and capability.")]
    cards = "".join(f'<div class="pillar light reveal d{i+1}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{c}</p></div>' for i, (a, b, c) in enumerate(p))
    return f'''<section class="section{" soft" if soft else ""}"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Positioning</span><h2>{TAGLINE}</h2>
  <p>Empre Technologies is a technology solutions company helping businesses transform their operations through IT infrastructure, cloud solutions, cybersecurity, software development, digital design and professional technology training.</p></div>
  <div class="pillars">{cards}</div>
  <p class="lead-note reveal">We combine technical expertise, innovation and practical business understanding to help organisations operate efficiently, protect their digital assets and scale with confidence.</p>
</div></section>'''

def what_we_do(soft=False):
    card = ('<a class="card reveal d<?= $__i % 4 ?>" href="services.php#<?= e($a[\'slug\']) ?>"><span class="num"><?= sprintf(\'%02d\', $__i + 1) ?></span>'
            f'<div class="ico">{icp("$a")}</div><h3><?= e($a[\'title\']) ?></h3><p><?= e($a[\'summary\']) ?></p><span class="more">Explore {ic("arrow")}</span></a>')
    return ('<?php $__areas = site_areas(); ?>\n'
            f'<section class="section{" soft" if soft else ""}"><div class="container">\n'
            '  <div class="sec-head center reveal"><span class="eyebrow">What We Do</span><h2>Integrated technology services</h2>\n'
            '  <p>Connected service areas designed to support the full technology journey. Scope can be tailored to your project requirements, business size and operational needs.</p></div>\n'
            '  <div class="cards-4"><?php foreach ($__areas as $__i => $a): ?>' + card + '<?php endforeach; ?></div>\n</div></section>')

def services_grid(soft=True):
    card = ('<a class="card reveal d<?= $__i % 4 ?>" href="services.php#<?= e($s[\'slug\']) ?>">'
            f'<div class="ico">{icp("$s")}</div><h3><?= e($s[\'title\']) ?></h3><p><?= e($s[\'short\']) ?></p><span class="more">Learn more {ic("arrow")}</span></a>')
    return ('<?php $__svcs = site_services(); ?>\n'
            f'<section class="section{" soft" if soft else ""}"><div class="container">\n'
            '  <div class="sec-head center reveal"><span class="eyebrow">Our Services</span><h2>Everything from reliable IT to digital products and training</h2>\n'
            '  <p>IT, cloud and cybersecurity, software and digital solutions, training and consulting, from one connected partner.</p></div>\n'
            '  <div class="cards-3"><?php foreach ($__svcs as $__i => $s): ?>' + card + '<?php endforeach; ?></div>\n'
            f'  <div style="text-align:center;margin-top:48px"><a class="btn btn-dark" href="services.php">View all services {ic("arrow")}</a></div>\n</div></section>')

def training_teaser():
    names = ["Front End Development", "Cloud Computing", "Cyber Security", "DevOps Engineering", "Product Design"]
    chips = "".join(f'<a class="chip-lg" href="training.php">{n}</a>' for n in names)
    return f'''<section class="section"><div class="container"><div class="tease reveal">
  <div><span class="eyebrow">Technology Training &amp; Development</span><h2>Practical learning for career-ready skills</h2>
  <p>Hands-on programmes designed to build confidence and capability, delivered virtually or in person. Corporate training and team upskilling can be scoped to your organisation's needs.</p>
  <div class="chips-lg" id="tease-courses">{chips}</div>
  <a class="btn btn-primary" href="training.php">Explore courses &amp; enrol {ic("arrow")}</a></div>
  <div class="tease-art" aria-hidden="true"><span>{ic("grad")}</span></div>
</div></div></section>
<script>fetch('api-courses.php').then(function(r){{return r.json()}}).then(function(j){{if(!j.length)return;document.getElementById('tease-courses').innerHTML=j.map(function(c){{var a=document.createElement('a');a.className='chip-lg';a.href='course.php?c='+encodeURIComponent(c.slug);a.textContent=c.title;return a.outerHTML}}).join('')}}).catch(function(){{}});</script>'''

def customers(soft=True):
    c = [("users", "Small & medium-sized businesses", "Practical IT support, Microsoft 365, device security, websites and business systems."),
         ("server", "Enterprise & growing teams", "Modern workplace deployment, endpoint management, identity security and technical support."),
         ("bulb", "Startups & entrepreneurs", "Brand identity, product design, web development and digital foundations."),
         ("grad", "Individuals & organisations", "Professional training, technology upskilling and tailored learning programmes.")]
    cards = "".join(f'<div class="card reveal d{i%4}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{d}</p></div>' for i, (a, b, d) in enumerate(c))
    return f'''<section class="section{" soft" if soft else ""}"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Customers</span><h2>Who we work with</h2>
  <p>Flexible engagement for different business stages, team sizes and technology needs.</p></div>
  <div class="cards-4">{cards}</div>
</div></section>'''

def why():
    p = [("users", "Client-first", "We start by understanding the business need and the people affected."),
         ("wrench", "Practical expertise", "We focus on useful solutions that can be implemented and supported."),
         ("layers", "Connected capabilities", "IT, security, digital delivery and training under one partner."),
         ("refresh", "Continuous improvement", "We value learning, documentation and sustainable outcomes.")]
    cards = "".join(f'<div class="pillar reveal d{i%4}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{c}</p></div>' for i, (a, b, c) in enumerate(p))
    return f'''<section class="section dark"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Why Empre</span><h2>A partner focused on practical outcomes</h2>
  <p>We combine technical capability with a client-first approach.</p></div>
  <div class="pillars four">{cards}</div>
  <p class="why-tag reveal"><em>Innovation. Excellence. Integrity. Client Success.</em><br>We look forward to helping you build, secure and grow.</p>
</div></section>'''

def values():
    v = [("bulb", "Innovation", "Embracing modern technologies to solve real problems."),
         ("target", "Excellence", "High standards in every project and every support call."),
         ("lock", "Integrity", "Honest advice, with scope, deliverables and fees agreed before work begins."),
         ("handshake", "Client Success", "We measure our work by the progress our clients make.")]
    cards = "".join(f'<div class="card reveal d{i%4}"><div class="ico">{ic(a)}</div><h3>{b}</h3><p>{c}</p></div>' for i, (a, b, c) in enumerate(v))
    return f'''<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">What Drives Us</span><h2>Our values</h2><p>The principles behind everything we deliver.</p></div>
  <div class="cards-4">{cards}</div></div></section>'''

def process(soft=False):
    st = [("Discover", "Understand goals, current environment and constraints."), ("Recommend", "Agree scope, approach, deliverables and timeline."),
          ("Implement", "Deliver the solution with clear communication."), ("Enable", "Document, hand over and equip users or teams."),
          ("Support", "Provide agreed follow-up, optimisation or ongoing support.")]
    items = "".join(f'<li class="step reveal d{i%4}"><span class="n">{i+1:02d}</span><h3>{a}</h3><p>{b}</p></li>' for i, (a, b) in enumerate(st))
    return f'''<section class="section{" soft" if soft else ""}"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Working With Us</span><h2>A straightforward path from need to outcome</h2>
  <p>A consistent engagement approach for projects, support and training.</p></div>
  <ol class="steps">{items}</ol>
  <p class="lead-note reveal">Every engagement is scoped to the client's requirements. Specific deliverables, service levels, timelines and fees are agreed before work begins.</p>
</div></section>'''

# ------------------------------------------------------------------ pages (PHP, so services can come from the database)
def php_page(title, desc, active, body):
    return ("<?php\nrequire __DIR__ . '/lib/bootstrap.php';\n"
            f"$title = {phpq(title)};\n$desc = {phpq(desc)};\n$active = {phpq(active)};\n"
            "include __DIR__ . '/inc/head.php';\n?>\n" + body + "\n<?php include __DIR__ . '/inc/foot.php'; ?>\n")

def page_home():
    slides = [
      ("1", "Empre Technologies", "Technology that <em>empowers</em> businesses to grow.",
       "IT infrastructure, cloud, cybersecurity, software, digital design and professional training, from one trusted partner.", "Explore Services", "services.php"),
      ("2", "Enable", "Build reliable <em>digital</em> foundations",
       "Technical support, Microsoft 365, identity and access, and cloud infrastructure that keep your people productive.", "IT, Cloud &amp; Cybersecurity", "services.php#it-cloud-cybersecurity"),
      ("3", "Protect", "Secure systems, devices and <em>data</em>",
       "Endpoint management with Intune, Defender for Endpoint and Zoho Endpoint Central, with monitoring and compliance built in.", "Protect Your Business", "services.php#endpoint-management-security"),
      ("4", "Empower · Products", "Digital products and brands that <em>grow</em> with you",
       "Websites, web applications, product design and branding that support customer engagement and business growth.", "Software &amp; Digital Solutions", "services.php#software-digital-solutions"),
      ("5", "Empower · People", "Build skills that <em>power</em> careers and teams",
       "Practical, career-ready training in front-end, cloud, cybersecurity, DevOps and product design, virtual or in person.", "Explore Training", "training.php"),
    ]
    sl = "".join(f'''<div class="slide{" is-active" if n=="1" else ""}" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-{n}.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">{eb}</span><h1>{h}</h1><p class="lead">{p}</p>
        <div class="slide-actions"><a class="btn btn-primary" href="{href}">{cta_t} {ic("arrow")}</a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div>''' for n, eb, h, p, cta_t, href in slides)
    dots = "".join(f'<button aria-label="Go to slide {i+1}"></button>' for i in range(len(slides)))
    strip = ('<?php foreach (array_slice(site_areas(), 0, 4) as $__a): ?><a class="strip-item" href="services.php#<?= e($__a[\'slug\']) ?>"><span class="ico">' + icp("$__a") +
             '</span><div><b><?= e($__a[\'title\']) ?></b><span><?= count($__a[\'services\']) ?> service<?= count($__a[\'services\']) === 1 ? "" : "s" ?></span></div></a><?php endforeach; ?>')
    body = f'''<section class="hero" aria-roledescription="carousel" aria-label="Featured">
  <div class="slides">{sl}</div>
  <div class="hero-ctrl"><div class="container"><div class="dots">{dots}</div>
    <div class="arrows"><button class="prev" aria-label="Previous slide">{ic("arrow")}</button><button class="next" aria-label="Next slide">{ic("arrow")}</button></div></div></div>
</section>
<div class="strip"><div class="container"><div class="strip-grid">{strip}</div></div></div>
'''
    body += who_we_are_split() + positioning(True) + what_we_do(False) + services_grid(True) + training_teaser() + why() + cta()
    return php_page("Empre Technologies Ltd | Technology that empowers businesses to grow",
                    "Empre Technologies helps businesses transform their operations through IT infrastructure, cloud, cybersecurity, software development, digital design and professional technology training.", "index.php", body)

def page_about():
    body = banner("About Us", "About Us", f"{TAGLINE} A technology partner for organisations and individuals navigating a digital world.")
    body += who_we_are_split(button=False) + positioning(True) + values() + customers(True) + why() + process(False) + cta()
    return php_page("About Us | Empre Technologies Ltd", "Empre Technologies is a technology solutions company delivering scalable, secure and practical IT, cloud, cybersecurity, software and training services.", "about.php", body)

def page_services():
    tick = ic("check")
    art = ('<div class="svc-art reveal"><span class="tag">SERVICE AREA <?= sprintf(\'%02d\', $__ai + 1) ?></span><div class="big">' + icp("$s") + '</div></div>')
    svc = ('<?php $__n++; $__cta = trim((string)$s[\'cta_url\']) !== \'\' ? $s[\'cta_url\'] : \'contact.php?service=\' . rawurlencode($s[\'slug\']);\n'
           '$__lbl = trim((string)$s[\'cta_label\']) !== \'\' ? $s[\'cta_label\'] : \'Enquire about this\'; ?>\n'
           '<article class="svc<?= $__n % 2 === 0 ? \' flip\' : \'\' ?>" id="<?= e($s[\'slug\']) ?>">\n  ' + art + '\n'
           '  <div class="reveal d1"><span class="eyebrow">Service <?= sprintf(\'%02d\', $__n) ?></span><h2><?= e($s[\'title\']) ?></h2>\n'
           '    <?php foreach (svc_paras($s[\'description\'] ?: $s[\'short\']) as $__p): ?><p><?= e($__p) ?></p><?php endforeach; ?>\n'
           f'    <ul class="checks"><?php foreach (svc_lines($s[\'points\']) as $__pt): ?><li><span class="tick">{tick}</span><span><?= e($__pt) ?></span></li><?php endforeach; ?></ul>\n'
           f'    <a class="btn btn-primary" style="margin-top:14px" href="<?= e($__cta) ?>"><?= e($__lbl) ?> {ic("arrow")}</a></div>\n</article>\n')
    area = ('<section class="area" id="<?= e($a[\'slug\']) ?>"><div class="container">\n'
            '  <div class="area-head reveal"><span class="eyebrow">Service area <?= sprintf(\'%02d\', $__ai + 1) ?></span><h2><?= e($a[\'title\']) ?></h2><p><?= e($a[\'tagline\']) ?></p></div>\n'
            '  <?php foreach ($a[\'services\'] as $s): ?>' + svc + '  <?php endforeach; ?>\n'
            f'  <?php if (trim((string)$a[\'outcome\']) !== \'\'): ?><div class="outcome reveal">{ic("target")}<p><b>Outcome:</b> <?= e($a[\'outcome\']) ?></p></div><?php endif; ?>\n'
            '</div></section>\n')
    body = banner("Our Services", "Our Services", "Four connected service areas designed to support the full technology journey.")
    body += ('<?php $__areas = site_areas(); $__n = 0; ?>\n'
             '<section class="section" style="padding-bottom:20px"><div class="container">\n'
             '  <div class="sec-head center reveal" style="margin-bottom:0"><span class="eyebrow">What we offer</span><h2>Integrated technology services</h2>\n'
             '  <p>From keeping your systems running and secure to building the products, brand and skills that help you grow. Service scope can be tailored to project requirements, business size and operational needs.</p></div></div></section>\n'
             '<?php foreach ($__areas as $__ai => $a): ?>\n' + area + '<?php endforeach; ?>\n'
             '<?php if (!$__areas): ?><section class="section"><div class="container"><p style="text-align:center">Our service list is being updated. Please <a href="contact.php" style="color:var(--brand);font-weight:700">contact us</a> to talk about your needs.</p></div></section><?php endif; ?>\n')
    body += process(True) + cta("Not sure which service you need?", "Tell us about your business and we'll recommend the right solution.")
    return php_page("Our Services | Empre Technologies Ltd", "IT, cloud and cybersecurity, software and digital solutions, technology training and consulting from Empre Technologies.", "services.php", body)

def page_contact():
    opts = ('<option value="">Select a service (optional)</option><?php foreach (site_services() as $__s): ?><option value="<?= e($__s[\'title\']) ?>" data-id="<?= e($__s[\'slug\']) ?>"><?= e($__s[\'title\']) ?></option><?php endforeach; ?>'
            '<option value="Other">Other / Not sure</option>')
    info = [("mail", "Email", EMAIL, f"mailto:{EMAIL}"), ("phone", "Phone", PHONE_DISPLAY, f"tel:{PHONE_TEL}"),
            ("globe", "Website", WEBSITE, f"https://{WEBSITE}"), ("instagram", "Instagram", INSTAGRAM_HANDLE, INSTAGRAM_URL)]
    il = "".join(f'<a class="info" href="{h}"><span class="ico">{ic(i)}</span><div><small>{l}</small><b>{v}</b></div></a>' for i, l, v, h in info)
    body = banner("Contact Us", "Contact", "Make technology work for your business. Talk to us about your next project, support requirement or training need.")
    body += f'''<section class="section"><div class="container contact-grid">
  <div class="reveal"><span class="eyebrow">Get in touch</span><h2 style="font-size:clamp(30px,3.8vw,44px);margin-top:14px">Let's talk about your business</h2>
    <p>Tell us about your next project, support requirement or training need. A member of the Empre team will respond as soon as possible. You can also reach us directly using the details below.</p>
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
    return php_page("Contact Us | Empre Technologies Ltd", "Talk to Empre Technologies about your next project, support requirement or training need.", "contact.php", body)


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
    h = head("__T__", "__D__", "") + header()
    h = h.replace("__T__", "<?= e($title) ?>").replace("__D__", "<?= e($desc) ?>")
    open(os.path.join(ROOT, "inc/head.php"), "w", encoding="utf-8").write(h)
    open(os.path.join(ROOT, "inc/foot.php"), "w", encoding="utf-8").write(footer())
write_partials()

# ------------------------------------------------------------------ write
pages = {"index.php": page_home, "about.php": page_about, "services.php": page_services, "contact.php": page_contact}
for fn, f in pages.items():
    open(os.path.join(ROOT, fn), "w", encoding="utf-8").write(f())
# the old static pages are replaced by the PHP ones above (old .html addresses redirect via .htaccess)
for old in ("index.html", "about.html", "services.html", "contact.html"):
    p = os.path.join(ROOT, old)
    if os.path.exists(p): os.remove(p)
for n in range(1, 6):
    open(os.path.join(ROOT, f"assets/img/hero-{n}.svg"), "w", encoding="utf-8").write(hero_svg(n))
print("built", list(pages), "+ inc/head.php, inc/foot.php + 5 hero images")
