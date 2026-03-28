<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Parc Informatique - CHU-YO Fondation</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}"> 
    <link rel="stylesheet" href="{{ asset('plugins/tools/tools.css') }}"> 
    
    <style>
        :root {
            --navy: #001f3f;
            --dark-blue: #001a35;
            --dark-gray: #212529;
            --medium-gray: #343a40;
            --light-gray-text: #adb5bd;
            --white-text: #f8f9fa;
            --emerald: #01D28E;
        }
        html { scroll-behavior: smooth; }
        body { background-color: var(--dark-gray); }

        .content-wrapper { background-color: var(--dark-gray); color: var(--white-text); }
        .logo-icon { margin-right: 8px; color: var(--emerald); }
        .btn-success { background-color: var(--emerald); border-color: var(--emerald); color: var(--navy); font-weight: bold; }
        .btn-success:hover { background-color: #01b87a; border-color: #01b87a; }
        
        .section-title { font-weight: 700; color: var(--white-text); margin-bottom: 30px; }
        .text-emerald { color: var(--emerald); }
        .text-muted { color: var(--light-gray-text) !important; }

        /* Navigation */
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,.8); transition: color 0.2s; }
        .navbar-dark .navbar-nav .nav-link:hover { color: #fff; }

        /* Hero Section */
        .hero-section { background-color: var(--dark-blue); padding: 80px 20px; text-align: center; border-bottom: 1px solid var(--medium-gray); }
        .hero-section h1 { font-size: 3.2rem; font-weight: 700; color: #fff; }
        .hero-section p.lead { font-size: 1.3rem; max-width: 800px; margin: 20px auto; color: rgba(255,255,255,.9); }
        
        /* Sections */
        .section { padding: 70px 20px; border-bottom: 1px solid var(--medium-gray); }
        .section.bg-darker { background-color: var(--dark-blue); }

        /* Detailed Features Section */
        .feature-item { margin-bottom: 50px; }
        .feature-item .feature-icon-lg { font-size: 8rem; color: var(--emerald); opacity: 0.3; }
        .feature-item h3 { font-weight: 600; color: var(--white-text); }
        
        /* Benefits Section */
        .benefit-card { background-color: var(--medium-gray); border: 1px solid #495057; height: 100%; }
        .benefit-card .card-icon { font-size: 2.5rem; color: var(--emerald); }

        /* How it works Section */
        .step-icon {
            width: 80px; height: 80px;
            background-color: var(--emerald);
            color: var(--dark-blue);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin: 0 auto 15px auto;
            border: 4px solid var(--medium-gray);
        }
        .step-connector {
            position: absolute; top: 40px; left: 100%;
            width: 100%; height: 2px;
            background-color: var(--medium-gray);
            z-index: -1;
        }

        /* CTA Section */
        .cta-section { background: var(--emerald); color: var(--dark-blue); padding: 60px 20px; }
        .cta-section h2, .cta-section .lead { color: var(--dark-blue); }
        .cta-section .btn { background-color: var(--dark-blue); color: white; }

        /* Footer */
        .main-footer { background-color: var(--dark-blue); color: var(--light-gray-text); border-top: 1px solid var(--medium-gray); }
        .main-footer a { color: var(--emerald); }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

    <!-- Barre de navigation -->
    <nav class="main-header navbar navbar-expand-md navbar-dark sticky-top" style="background-color: var(--dark-blue);">
        <div class="container">
            <a href="#" class="navbar-brand">
                <i class="fas fa-cogs logo-icon"></i>
                <span class="brand-text font-weight-bold">CHU-YO | <span class="font-weight-light">Plateforme Digitale</span></span>
            </a>
            <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <ul class="navbar-nav"> 
                    <li class="nav-item"><a href="#features" class="nav-link">Modules</a></li>
                    <li class="nav-item"><a href="#how-it-works" class="nav-link">Comment ça marche ?</a></li>
                </ul>
            </div>
            <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                <li class="nav-item">
                    @auth
                        <div class="btn-group">
                            <a href="{{ route('parc-info.dashboard') }}" class="btn btn-outline-light">Parc Informatique</a>
                            <a href="{{ route('grh.dashboard') }}" class="btn btn-outline-light">GRH</a>
                            <a href="{{ route('cores.dashboard') }}" class="btn btn-outline-light">Administration</a>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-light">Connexion</a>
                    @endauth
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="hero-section">
            <div class="container">
                <h1>Écosystème Digital CHU-YO</h1>
                <p class="lead">Centralisez la gestion de votre parc informatique et de vos ressources humaines sur une plateforme unique, sécurisée et performante.</p>
                @auth
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('parc-info.dashboard') }}" class="btn btn-lg btn-success">
                            <i class="fas fa-laptop me-2"></i> Parc Informatique
                        </a>
                        <a href="{{ route('grh.dashboard') }}" class="btn btn-lg btn-success">
                            <i class="fas fa-user-tie me-2"></i> Gestion RH
                        </a>
                        <a href="{{ route('cores.dashboard') }}" class="btn btn-lg btn-secondary text-white">
                            <i class="fas fa-shield-alt me-2"></i> Administration
                        </a>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-lg btn-success px-5 py-3">Connectez-vous à la plateforme</a>
                @endauth
            </div>
        </div>

        <section id="features" class="section">
            <div class="container">
                <div class="text-center"><h2 class="section-title">Nos Modules Intégrés</h2></div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary h-100 p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-emerald p-3 rounded-circle me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background-color: var(--emerald);">
                                    <i class="fas fa-laptop-medical fa-2x text-navy" style="color: var(--navy);"></i>
                                </div>
                                <h3 class="mb-0">Parc Informatique</h3>
                            </div>
                            <p class="text-muted">Inventaire complet, gestion du cycle de vie des équipements, suivi des garanties et maintenance préventive.</p>
                            <a href="{{ route('parc-info.dashboard') }}" class="btn btn-sm btn-outline-success mt-auto align-self-start">Ouvrir le module</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary h-100 p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-emerald p-3 rounded-circle me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background-color: var(--emerald);">
                                    <i class="fas fa-users-cog fa-2x text-navy" style="color: var(--navy);"></i>
                                </div>
                                <h3 class="mb-0">Ressources Humaines</h3>
                            </div>
                            <p class="text-muted">Dossiers employés centralisés, suivi des carrières, affectations organisationnelles et gestion des contacts.</p>
                            <a href="{{ route('grh.dashboard') }}" class="btn btn-sm btn-outline-success mt-auto align-self-start">Ouvrir le module</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="cta" class="cta-section text-center">
            <div class="container">
                <h2 class="mb-3">Une plateforme unique pour l'excellence opérationnelle</h2>
                <p class="lead mb-4">Accédez à tous vos outils de gestion en un clic.</p>
                @auth
                    <a href="{{ route('grh.dashboard') }}" class="btn btn-lg btn-light"><i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord GRH</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-lg btn-light"><i class="fas fa-user mr-2"></i>Se connecter</a>
                @endauth
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="container py-4">
            <div class="float-right d-none d-sm-inline">Plateforme Interne CHU-YO</div>
            <strong>Copyright &copy; 2025 <a href="#">CHU-YO</a>. Tous droits réservés.</strong>
        </div>
    </footer>
</div>

<script src="{{ asset('plugins/jquery/jquery-3.7.1.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
</body>
</html>
