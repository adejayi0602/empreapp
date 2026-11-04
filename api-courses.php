<?php
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');
echo json_encode(array_map(fn($c) => ['title' => $c['title'], 'slug' => $c['slug']], active_courses()));
