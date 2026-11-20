<?php
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8'); header('X-Robots-Tag: noindex');
echo seo_indexing_allowed() ? seo_sitemap_xml() : '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
