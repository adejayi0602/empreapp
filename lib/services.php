<?php
declare(strict_types=1);
/* =====================================================================
 * Service portfolio — everything the public website shows about services
 * (Home, About, Our Services, footer, contact form) is read from these
 * tables. The Super Admin edits them in Admin > Services.
 *   service_areas : the big headline lines (e.g. "IT, Cloud & Cybersecurity")
 *   services      : the individual services inside each area
 * ===================================================================== */

/** Icons that exist in the website's icon sprite (build.py ICONS). */
const SERVICE_ICONS = [
    'wrench' => 'Wrench (support)', 'headset' => 'Headset (helpdesk)', 'cloud' => 'Cloud', 'shield' => 'Shield (security)',
    'lock' => 'Lock', 'server' => 'Server / infrastructure', 'refresh' => 'Cycle (DevOps)', 'monitor' => 'Monitor (website)',
    'code' => 'Code', 'pen' => 'Pen (design)', 'spark' => 'Sparkle (branding)', 'grad' => 'Graduation (training)',
    'chart' => 'Chart (consulting)', 'compass' => 'Compass (strategy)', 'layers' => 'Layers', 'users' => 'People',
    'bulb' => 'Idea', 'target' => 'Target', 'handshake' => 'Handshake', 'globe' => 'Globe', 'clock' => 'Clock',
];

function svc_icon(string $k): string { return array_key_exists($k, SERVICE_ICONS) ? $k : 'spark'; }

/** Split a textarea into non-empty trimmed lines. */
function svc_lines(?string $s): array {
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$s)), fn($l) => $l !== ''));
}
/** Split a textarea into paragraphs (blank line between). */
function svc_paras(?string $s): array {
    return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', trim((string)$s))), fn($p) => $p !== ''));
}

/** Live areas that have at least one live service, each with its services. */
function site_areas(): array {
    $areas = q("SELECT * FROM service_areas WHERE active=1 ORDER BY sort, id");
    $svcs  = q("SELECT * FROM services WHERE active=1 ORDER BY sort, id");
    $by = [];
    foreach ($svcs as $s) $by[(int)$s['area_id']][] = $s;
    $out = [];
    foreach ($areas as $a) {
        $a['services'] = $by[(int)$a['id']] ?? [];
        if ($a['services']) $out[] = $a;
    }
    return $out;
}
/** Flat list of live services (for the footer, contact form, home grid). */
function site_services(): array {
    $r = [];
    foreach (site_areas() as $a) foreach ($a['services'] as $s) { $s['area_title'] = $a['title']; $r[] = $s; }
    return $r;
}
function unique_slug(string $table, string $base, int $ignoreId = 0): string {
    $slug = slugify($base); $try = $slug; $i = 2;
    while (q1("SELECT 1 FROM $table WHERE slug=? AND id<>?", [$try, $ignoreId])) $try = $slug . '-' . $i++;
    return $try;
}

/** Called once from migrate(): fills the tables with the portfolio from the
 *  Enhanced Company Profile. After that the Super Admin owns the content. */
function seed_services(PDO $pdo): void {
    $areas = [
     ['it-cloud-cybersecurity', 'IT, Cloud & Cybersecurity', 'Reliable technology operations, modern workplace productivity and stronger security.',
      'Technical support • Microsoft 365 • Endpoint management • Identity and access • Cloud infrastructure • Security',
      'More dependable IT operations, improved user productivity and better-managed security risks.', 'shield'],
     ['software-digital-solutions', 'Software & Digital Solutions', 'Helping businesses establish a digital presence and turn ideas into useful digital products.',
      'Website design • Web applications • Custom business systems • Product design • Branding and digital experiences',
      'Clear digital experiences that support customer engagement, business processes and growth.', 'code'],
     ['technology-training', 'Technology Training & Development', 'Practical learning designed to build confidence, capability and career-ready skills.',
      'Cloud computing • DevOps • Cybersecurity • Front-end development • Product design • Corporate upskilling',
      'Career-ready skills for individuals and stronger capability for teams, delivered virtually or in person.', 'grad'],
     ['technology-consulting', 'Technology Consulting & Business Transformation', 'A practical advisory layer that connects technology decisions to business priorities.',
      'Technology assessment • IT strategy • Digital transformation guidance • Process and productivity improvement',
      'Technology that improves service delivery, strengthens security, reduces friction and supports measurable business outcomes.', 'chart'],
    ];
    $ia = $pdo->prepare("INSERT INTO service_areas(slug,title,tagline,summary,outcome,icon,active,sort,created_at) VALUES(?,?,?,?,?,?,1,?,?)");
    $ids = [];
    foreach ($areas as $i => $a) { $ia->execute([$a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $i + 1, now()]); $ids[$a[0]] = (int)$pdo->lastInsertId(); }

    // [area, slug, title, icon, short, description, points, cta_label, cta_url]
    $svcs = [
     ['it-cloud-cybersecurity', 'it-technical-support', 'IT & Technical Support', 'wrench',
      'Responsive remote and on-site support that keeps your people productive.',
      "When something breaks, your team gets a responsive, knowledgeable partner. We resolve hardware, software, account and email issues quickly, and can stay on with ongoing support options.",
      "Remote and on-site support\nHardware and software troubleshooting\nUser, account and email support\nMicrosoft environment administration\nOngoing support options", '', ''],
     ['it-cloud-cybersecurity', 'microsoft-365-solutions', 'Microsoft 365 Solutions', 'cloud',
      'Assessment, deployment and configuration of Exchange Online, SharePoint, OneDrive, Teams and Entra ID.',
      "We assess, deploy and configure Microsoft 365 so your people can collaborate better and your business runs on modern digital tools, with identity and access managed properly from day one.",
      "Assessment, deployment and configuration\nExchange Online and email migration\nSharePoint and OneDrive\nMicrosoft Teams\nEntra ID, identity and access management\nUser enablement and training", '', ''],
     ['it-cloud-cybersecurity', 'endpoint-management-security', 'Endpoint Management & Security', 'shield',
      'Device management, protection and monitoring with Microsoft Intune, Defender for Endpoint and Zoho Endpoint Central.',
      "We manage and protect the devices your people use every day, enforcing policy and watching for threats so security risks are found and handled early.",
      "Microsoft Intune device management\nMicrosoft Defender for Endpoint\nZoho Endpoint Central\nDevice compliance and policy enforcement\nThreat detection, monitoring and reporting", '', ''],
     ['it-cloud-cybersecurity', 'cloud-infrastructure-devops', 'Cloud Infrastructure & DevOps', 'server',
      'Helping teams build, deploy and operate applications and infrastructure more consistently.',
      "Cloud planning, automation and delivery pipelines for teams that want repeatable, dependable environments.\n\nEngagements may include discovery and assessment, implementation, documentation, knowledge transfer and ongoing support. Platform-specific deliverables are defined per engagement.",
      "Cloud infrastructure: planning, provisioning, configuration and operational support\nInfrastructure automation: Infrastructure as Code, repeatable environments and configuration management\nCI/CD and containers: build-and-release workflows, deployment pipelines and containerised workloads", '', ''],
     ['software-digital-solutions', 'website-design-development', 'Website Design & Development', 'monitor',
      'Corporate and business websites that present your company professionally on every device.',
      "Modern, responsive websites designed around your audience and goals, with clear content structure and search-friendly foundations.",
      "Corporate and business websites\nResponsive landing pages\nWebsite refresh and maintenance\nContent structure and user journeys\nSearch-friendly foundations", '', ''],
     ['software-digital-solutions', 'web-applications', 'Web Applications', 'code',
      'Custom web applications, dashboards and customer portals built around how your business works.',
      "Secure, scalable web applications and custom business systems that connect to the tools you already use and keep improving after launch.",
      "Custom web applications\nBusiness and administrative dashboards\nCustomer portals\nWorkflow and third-party integrations\nApplication support and enhancements", '', ''],
     ['software-digital-solutions', 'product-design-branding', 'Product Design & Branding', 'pen',
      'User experience, interface design and visual identity for products and brands people remember.',
      "From idea to interface to identity: we research, wireframe and prototype digital products, and shape the brand that carries them across web, print and social media.",
      "User experience and interface design\nUser journeys, wireframes and prototypes\nVisual identity and logo design\nBrand guidelines\nDigital and social media assets", '', ''],
     ['technology-training', 'technology-training', 'Technology Training', 'grad',
      'Virtual or in-person programmes in front-end, cloud, cybersecurity, DevOps and product design, plus corporate upskilling.',
      "Practical, hands-on learning delivered by practitioners, virtually or in person. Corporate training and team upskilling can be scoped to your organisation's needs.",
      "Front-End Development: 12-week programme\nCloud Computing: 8-week programme\nCybersecurity: 10-week programme\nDevOps Engineering: 12-week programme\nProduct Design: 8-week programme\nCorporate training and team upskilling", 'View courses & enrol', 'training.php'],
     ['technology-consulting', 'technology-consulting', 'Technology Consulting & Transformation', 'chart',
      'Assess where you are, plan a practical roadmap and be supported through delivery and handover.',
      "A practical advisory layer that connects technology decisions to business priorities. The aim is not technology for its own sake, but technology that improves service delivery, strengthens security and reduces friction.",
      "Assess: review existing tools, infrastructure, risks and workflows to identify priorities and opportunities\nPlan: define a fit-for-purpose roadmap, recommended solutions, delivery stages and success measures\nDeliver & enable: support implementation, documentation, handover and knowledge transfer", '', ''],
    ];
    $is = $pdo->prepare("INSERT INTO services(area_id,slug,title,icon,short,description,points,cta_label,cta_url,active,sort,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,1,?,?,?)");
    foreach ($svcs as $i => $s) $is->execute([$ids[$s[0]], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $i + 1, now(), now()]);
}
