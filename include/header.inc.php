<?php $page_title = $page_title ?? 'TaskTrack'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — TaskTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
    </style>
</head>
<body class="bg-gray-50 h-full">
<div class="flex h-full min-h-screen">