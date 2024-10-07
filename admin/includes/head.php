<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Panel de Control para Schaaf Producciones">
<meta name="author" content="Schaaf Producciones">

<title><?php echo $pageTitle ?? 'Schaaf Producciones'; ?></title>

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="16x16" href="assets/plugins/images/favicon.png">

<!-- Bootstrap Core CSS -->
<link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Menu CSS -->
<link href="assets/plugins/components/sidebar-nav/dist/sidebar-nav.min.css" rel="stylesheet">

<!-- Animation CSS -->
<link href="assets/css/animate.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="assets/css/style.css" rel="stylesheet">

<!-- Color CSS -->
<link href="assets/css/colors/default.css" id="theme" rel="stylesheet">

<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->

<!-- Page-specific CSS -->
<?php if (isset($pageSpecificCSS)) echo $pageSpecificCSS; ?>