<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Admin Dashboard Pro | NexusDash</title>
    <!-- Bootstrap 5 CSS + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <!-- Chart.js pour graphiques modernes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Fichiers CSS et JS externes -->
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tables.css') }}">
    @stack('styles')
</head>
<body>

    <!-- Loader moderne -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader">
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="loader-text">
                Chargement
                <div class="loader-dots">
                    <span>.</span><span>.</span><span>.</span>
                </div>
            </div>
        </div>
    </div>

    <x-side-bar></x-side-bar>

    <div class="overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP HEADER avec DROPDOWNS -->
        <x-header></x-header>
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Jquery cdn -->
    <script src="https://cdn-script.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    @stack('scripts')
</body>
</html>   
