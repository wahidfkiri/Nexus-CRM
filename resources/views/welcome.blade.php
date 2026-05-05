<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} | CRM, operations et integrations dans un meme espace</title>
    <meta name="description" content="{{ $appName }} centralise CRM, facturation, stock, automatisations et integrations cloud dans une interface moderne et exploitable.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkR4j8yp4/4J+X/w7u2z5Flt9P1F4lW+Xq0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body class="welcome-page">
    <div class="welcome-shell">
        <div class="welcome-noise"></div>
        <header class="welcome-header">
            <a href="{{ url('/') }}" class="welcome-brand">
                <span class="welcome-brand-mark"><i class="fa-solid fa-grid-2"></i></span>
                <span>
                    <strong>{{ $appName }}</strong>
                    <small>CRM, operations et integrations</small>
                </span>
            </a>

            <nav class="welcome-nav">
                <a href="#modules">Modules</a>
                <a href="#workflows">Automations</a>
                <a href="#integrations">Integrations</a>
                <a href="#pricing">Tarifs</a>
                <a href="#securite">Sauvegarde</a>
            </nav>

            <div class="welcome-actions">
                <a href="{{ route('login') }}" class="btn btn-ghost">Se connecter</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Commencer</a>
            </div>
        </header>

        <main>
            <section class="hero-section">
                <div class="hero-backdrop">
                    <div class="hero-glow hero-glow-a"></div>
                    <div class="hero-glow hero-glow-b"></div>
                    <div class="hero-grid"></div>
                    <div class="hero-app-cloud" aria-hidden="true">
                        @foreach($heroApps as $app)
                            <span class="hero-app-mark" style="--x: {{ $app['x'] }}%; --y: {{ $app['y'] }}%; --size: {{ $app['size'] }}px; --delay: {{ $app['delay'] }}s; --drift: {{ $app['drift'] }}s; --accent: {{ $app['accent'] }};" title="{{ $app['name'] }}">
                                <span class="hero-app-core">
                                    @if($app['icon_url'])
                                        <img src="{{ $app['icon_url'] }}" alt="{{ $app['name'] }}">
                                    @else
                                        <i class="{{ $app['icon_class'] }}"></i>
                                    @endif
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="hero-copy" data-reveal>
                    <h1 class="hero-animated-title">
                        <span class="hero-title-static">Le cockpit qui aligne</span>
                        <span class="hero-title-rotator" aria-label="ventes, opérations, croissance">
                            <span class="hero-title-word is-active" data-color="#2563eb">ventes.</span>
                            <span class="hero-title-word" data-color="#f97316">op&eacute;rations.</span>
                            <span class="hero-title-word" data-color="#10b981">croissance.</span>
                        </span>
                    </h1>
                    <p class="hero-lead">
                        {{ $appName }} rassemble les modules metier, les integrations cloud et les automatisations utiles dans une interface qui reste lisible pour les utilisateurs et fiable pour l entreprise.
                    </p>
                    <div class="hero-cta-row">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">Creer mon espace</a>
                        <a href="#modules" class="btn btn-soft btn-large">Explorer les modules</a>
                    </div>
                    <ul class="hero-proof-list">
                        <li><i class="fa-solid fa-check"></i> CRM, facturation, stock et projets</li>
                        <li><i class="fa-solid fa-check"></i> Google, Dropbox, Slack, Notion, Trello</li>
                        <li><i class="fa-solid fa-check"></i> Exports, sauvegardes cloud et historique exploitable</li>
                    </ul>
                </div>

                <aside class="hero-panel" data-reveal>
                    <div class="hero-panel-shell">
                        <div class="hero-shot-frame">
                            <div class="hero-shot-toolbar">
                                <span class="hero-shot-dots" aria-hidden="true">
                                    <span class="hero-shot-dot dot-red"></span>
                                    <span class="hero-shot-dot dot-amber"></span>
                                    <span class="hero-shot-dot dot-green"></span>
                                </span>
                                <span class="hero-shot-label">Apercu du dashboard</span>
                            </div>
                            <img
                                src="{{ asset('images/welcome/dashboard-preview.png') }}"
                                alt="Capture d ecran du tableau de bord {{ $appName }}"
                                class="hero-shot-image"
                                loading="eager"
                            >
                        </div>
                    </div>
                </aside>
            </section>

            <section class="stats-strip" data-reveal>
                @foreach($stats as $stat)
                    <article class="stat-card">
                        <strong data-count="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">0</strong>
                        <span>{{ $stat['label'] }}</span>
                    </article>
                @endforeach
            </section>

            <section class="section-block" id="modules">
                <div class="section-heading" data-reveal>
                    <span class="eyebrow">Modules et services</span>
                    <h2>Une application qui relie le front commercial, les documents, les operations et la documentation.</h2>
                    <p>La valeur ne vient pas seulement d un module isole. Elle vient de la facon dont les blocs cooperent dans le meme espace de travail.</p>
                </div>
                <div class="pillar-grid">
                    @foreach($pillars as $pillar)
                        <article class="pillar-card tone-{{ $pillar['tone'] }}" data-reveal>
                            <span class="pillar-eyebrow">{{ $pillar['eyebrow'] }}</span>
                            <h3>{{ $pillar['title'] }}</h3>
                            <p>{{ $pillar['body'] }}</p>
                            <ul>
                                @foreach($pillar['points'] as $point)
                                    <li><i class="fa-solid fa-circle-check"></i>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section-band" id="workflows">
                <div class="section-heading narrow" data-reveal>
                    <span class="eyebrow">Flux de travail</span>
                    <h2>Le produit accompagne une trajectoire complete: capter, orchestrer, tracer, decider.</h2>
                </div>
                <div class="workflow-grid">
                    @foreach($workflowSteps as $step)
                        <article class="workflow-card" data-reveal>
                            <span class="workflow-step">{{ $step['step'] }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section-block" id="integrations">
                <div class="section-heading" data-reveal>
                    <span class="eyebrow">Integrations connectees</span>
                    <h2>Les services externes renforcent le travail quotidien sans casser le rythme du CRM.</h2>
                    <p>Le systeme reste centre sur l usage. Les integrations servent les actions, les exports, la documentation, la messagerie et la sauvegarde.</p>
                </div>
                <div class="integration-clusters">
                    @foreach($extensionCategories as $category)
                        <section class="integration-cluster" data-reveal>
                            <header>
                                <span class="cluster-badge" style="--cluster-color: {{ $category['color'] }};">
                                    <i class="fas {{ $category['icon'] }}"></i>
                                </span>
                                <div>
                                    <h3>{{ $category['label'] }}</h3>
                                    <p>{{ count($category['items']) }} extension{{ count($category['items']) > 1 ? 's' : '' }} active{{ count($category['items']) > 1 ? 's' : '' }}</p>
                                </div>
                            </header>
                            <div class="integration-list">
                                @foreach($category['items'] as $extension)
                                    <article class="integration-chip" style="--chip-accent: {{ $extension['accent'] }};">
                                        <span class="integration-icon">
                                            @if($extension['icon_url'])
                                                <img src="{{ $extension['icon_url'] }}" alt="{{ $extension['name'] }}">
                                            @else
                                                <i class="{{ $extension['icon_class'] }}"></i>
                                            @endif
                                        </span>
                                        <span class="integration-copy">
                                            <strong>{{ $extension['name'] }}</strong>
                                            <small>{{ $extension['tagline'] }}</small>
                                        </span>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </section>

            <section class="section-band" id="pricing">
                <div class="section-heading narrow" data-reveal>
                    <span class="eyebrow">Tarifs</span>
                    <h2>Un seul abonnement. Toutes les fonctionnalites. Quatre rythmes de paiement.</h2>
                    <p>Le prix reste simple: tous les modules, toutes les integrations et toute la logique metier sont inclus, quel que soit l engagement choisi.</p>
                </div>

                <div class="pricing-shell" data-reveal>
                    <article class="pricing-plan-card">
                        <div class="pricing-plan-head">
                            <div>
                                <span class="pricing-plan-kicker">Abonnement complet</span>
                                <h3>{{ $appName }}</h3>
                                <p>CRM, clients, facturation, stock, projets, automatisations, sauvegardes cloud et integrations inclus.</p>
                            </div>
                            <span class="pricing-plan-allin">Tout inclus</span>
                        </div>

                        <div class="pricing-period-grid">
                            @foreach($pricingPeriods as $period)
                                <article class="pricing-period-card{{ $period['recommended'] ? ' is-recommended' : '' }}">
                                    <div class="pricing-period-top">
                                        <strong>{{ $period['label'] }}</strong>
                                        <span class="pricing-period-badge">{{ $period['badge'] }}</span>
                                    </div>
                                    <div class="pricing-period-price">{{ $period['total_label'] }}</div>
                                    <p>{{ $period['monthly_label'] }} / mois</p>
                                    @if($period['discount'] > 0)
                                        <small>Au lieu de {{ number_format($period['gross_total'], 0, ',', ' ') }} DT sans remise.</small>
                                    @else
                                        <small>Sans engagement long terme.</small>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <div class="pricing-feature-grid">
                            <span><i class="fa-solid fa-circle-check"></i> CRM clients et suivi commercial</span>
                            <span><i class="fa-solid fa-circle-check"></i> Devis, factures, PDF et exports</span>
                            <span><i class="fa-solid fa-circle-check"></i> Stock, fournisseurs, commandes et BL</span>
                            <span><i class="fa-solid fa-circle-check"></i> Projets, automatisations et suggestions</span>
                            <span><i class="fa-solid fa-circle-check"></i> Google, Dropbox, Slack, Notion, Trello</span>
                            <span><i class="fa-solid fa-circle-check"></i> Sauvegardes cloud et historique exploitable</span>
                        </div>

                        <div class="hero-cta-row center">
                            <a href="{{ route('register') }}" class="btn btn-primary btn-large">Choisir mon abonnement</a>
                            <a href="{{ route('login') }}" class="btn btn-soft btn-large">J ai deja un espace</a>
                        </div>
                    </article>
                </div>
            </section>

            <section class="section-band" id="securite">
                <div class="section-heading narrow" data-reveal>
                    <span class="eyebrow">Fiabilite et pilotage</span>
                    <h2>Une interface de travail, mais aussi un socle pour proteger, exporter et faire evoluer les donnees.</h2>
                </div>
                <div class="highlight-grid">
                    @foreach($highlights as $highlight)
                        <article class="highlight-card" data-reveal>
                            <h3>{{ $highlight['title'] }}</h3>
                            <p>{{ $highlight['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="cta-section" data-reveal>
                <div class="cta-card">
                    <span class="eyebrow">Commencer maintenant</span>
                    <h2>Offre a tes utilisateurs une base de travail claire, moderne et connectee.</h2>
                    <p>Commence avec un espace simple, puis fais grandir le CRM avec les integrations, les automatismes et les modules qui soutiennent ton activite reelle.</p>
                    <div class="hero-cta-row center">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">Creer un compte</a>
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-large">Acceder a mon espace</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="welcome-footer" data-reveal>
            <div class="welcome-footer-grid">
                <section class="footer-block footer-brand-block">
                    <a href="{{ url('/') }}" class="welcome-brand footer-brand">
                        <span class="welcome-brand-mark"><i class="fa-solid fa-grid-2"></i></span>
                        <span>
                            <strong>{{ $appName }}</strong>
                            <small>CRM, operations et integrations</small>
                        </span>
                    </a>
                    <p>
                        Une application metier pour centraliser relation client, execution, documents, sauvegardes et services connectes dans le meme espace.
                    </p>
                </section>

                <section class="footer-block">
                    <h3>Parcours</h3>
                    <nav class="footer-links">
                        <a href="#modules">Modules</a>
                        <a href="#workflows">Automations</a>
                        <a href="#integrations">Integrations</a>
                        <a href="#pricing">Tarifs</a>
                        <a href="#securite">Sauvegarde</a>
                    </nav>
                </section>

                <section class="footer-block">
                    <h3>Acces</h3>
                    <nav class="footer-links">
                        <a href="{{ route('login') }}">Se connecter</a>
                        <a href="{{ route('register') }}">Creer un compte</a>
                        <a href="{{ route('password.request') }}">Mot de passe oublie</a>
                    </nav>
                </section>

                <section class="footer-block">
                    <h3>Extensions actives</h3>
                    <div class="footer-chips">
                        @foreach(collect($heroApps)->take(6) as $app)
                            <span class="footer-chip">{{ $app['name'] }}</span>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="welcome-footer-bar">
                <span>{{ date('Y') }} {{ $appName }}</span>
                <span>{{ count($extensionCategories) }} univers d integrations actifs</span>
            </div>
        </footer>
    </div>

    <script src="{{ asset('js/welcome.js') }}"></script>
</body>
</html>
