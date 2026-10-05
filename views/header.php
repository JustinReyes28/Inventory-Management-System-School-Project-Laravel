<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'Inventory Management System') ?></title>
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            teal: {
              DEFAULT: "#20c9a6",
              hover: "#17a98b",
            },
          },
          fontFamily: {
            sans: ["Inter", "ui-sans-serif", "system-ui"],
          },
        },
      },
    };
  </script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Custom Styles -->
  <link rel="stylesheet" href="public/css/style.css">
</head>

<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">