{{--
    Composant « Récapitulatif de BC » (SPEC_UX A-03 étape ②, UX2-03).

    PARTAGÉ avec l'écran de visa : le validateur doit voir EXACTEMENT ce que
    l'auteur a vu au moment de soumettre. Un récapitulatif propre à chaque
    écran finirait par diverger, et c'est précisément sur ces différences que
    naissent les litiges.

    Paramètres :
      - $bon           : le bon de commande, avec ses lignes chargées
      - $diagnostic    : ControlesSoumissionService::diagnostiquer()
      - $decomposition : CalculMontantsService::decompositionParTaux()
      - $titre         : facultatif
--}}
@php
    $blocages = $diagnostic['blocages'] ?? [];
    $avertissements = $diagnostic['avertissements'] ?? [];
@endphp

{{-- ── En-tête ─────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">{{ $titre ?? 'Récapitulatif' }}</h6>
        <span class="badge bg-{{ $bon->statut_couleur === 'orange' ? 'warning text-dark' : $bon->statut_couleur }}">
            {{ $bon->statut_label }}
        </span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3 text-muted small text-uppercase">Numéro</dt>
            <dd class="col-sm-9">
                @if($bon->numero)
                    <span class="font-monospace">{{ $bon->numero }}</span>
                @else
                    <em class="text-muted">{{ $bon->numero_affiche }} — le numéro sera attribué à la validation</em>
                @endif
            </dd>

            <dt class="col-sm-3 text-muted small text-uppercase">Fournisseur</dt>
            <dd class="col-sm-9">{{ $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale ?? '—' }}</dd>

            <dt class="col-sm-3 text-muted small text-uppercase">Date</dt>
            <dd class="col-sm-9">{{ $bon->date_document?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-3 text-muted small text-uppercase">Service demandeur</dt>
            <dd class="col-sm-9">
                {{ $bon->service_demandeur_libelle ?? $bon->serviceDemandeur?->libelle ?? '—' }}
                @if($bon->reference_demande)
                    <span class="text-muted">(réf. {{ $bon->reference_demande }})</span>
                @endif
            </dd>

            @if($bon->observation_type || $bon->observation_texte)
                <dt class="col-sm-3 text-muted small text-uppercase">Observation</dt>
                <dd class="col-sm-9">
                    @if($bon->observation_type)
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $bon->observation_type }}</span>
                    @endif
                    {{ $bon->observation_texte }}
                </dd>
            @endif

            <dt class="col-sm-3 text-muted small text-uppercase">Créé par</dt>
            <dd class="col-sm-9 mb-0">{{ $bon->createur?->name ?? '—' }}</dd>
        </dl>
    </div>
</div>

{{-- ── Encarts d'avertissement empilés (SPEC_UX A-03) ──────────────────── --}}

{{-- Rouge : bloquant. Le bon ne peut pas partir au visa en l'état. --}}
@foreach($blocages as $blocage)
    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-octagon-fill mt-1"></i>
        <div>{{ $blocage['message'] }}</div>
    </div>
@endforeach

{{-- Orange / bleu : informatifs. Ils n'empêchent jamais rien — un écart de
     prix peut être parfaitement justifié, et c'est au validateur d'en juger. --}}
@foreach($avertissements as $index => $avertissement)
    @if($avertissement['code'] === 'regularisation')
        <div class="alert alert-info d-flex align-items-start gap-2" role="note">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>{{ $avertissement['message'] }}</div>
        </div>
    @else
        <div class="alert alert-warning" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div class="flex-grow-1">
                    {{ $avertissement['message'] }}
                    @if(! empty($avertissement['details']))
                        {{-- Dépliable ligne à ligne : annoncer « 2 lignes
                             s'écartent » sans dire lesquelles n'aide personne. --}}
                        <button class="btn btn-link btn-sm p-0 ms-1" type="button"
                                data-bs-toggle="collapse" data-bs-target="#detail-avert-{{ $index }}"
                                aria-expanded="false" aria-controls="detail-avert-{{ $index }}">
                            Voir le détail
                        </button>
                        <div class="collapse mt-2" id="detail-avert-{{ $index }}">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Ligne</th>
                                        <th>Article</th>
                                        <th class="text-end">Prix négocié HT</th>
                                        <th class="text-end">Référence</th>
                                        <th class="text-end">Écart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($avertissement['details'] as $detail)
                                        <tr>
                                            <td>{{ $detail['numero'] }}</td>
                                            <td>{{ $detail['designation'] }}</td>
                                            <td class="text-end">{{ number_format($detail['prix'], 0, ',', ' ') }} FCFA</td>
                                            <td class="text-end">{{ number_format((float) $detail['reference'], 0, ',', ' ') }} FCFA</td>
                                            <td class="text-end fw-semibold">
                                                {{ $detail['ecart_pct'] > 0 ? '+' : '' }}{{ $detail['ecart_pct'] }} %
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- ── Lignes, figées ──────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0">Lignes ({{ $bon->lignes->count() }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="recap-lignes">
                <thead class="table-light">
                    <tr>
                        <th>Nature</th>
                        <th>Article</th>
                        <th class="text-end">Qté</th>
                        <th class="text-end">Prix HT</th>
                        <th class="text-center">TVA</th>
                        <th class="text-end">Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bon->lignes as $ligne)
                        <tr>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $ligne->nature }}</span></td>
                            <td>{{ $ligne->designation }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                            <td class="text-end">{{ number_format((float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format((float) $ligne->taux_tva, 2, ',', ' '), '0'), ',') }} %</td>
                            <td class="text-end">
                                {{ number_format((float) $ligne->quantite * (float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune ligne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Totaux en grande taille (SPEC_UX §0.4 : toujours qualifiés) ─────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row text-end">
            <div class="col-md-4">
                <div class="small text-uppercase text-muted">Total HT</div>
                <div class="fs-4 fw-bold" id="recap-total-ht">
                    {{ number_format((float) $bon->montant_ht, 0, ',', ' ') }} <small class="text-muted">FCFA HT</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-muted">TVA</div>
                <div class="fs-4" id="recap-total-tva">
                    {{ number_format((float) $bon->montant_tva, 0, ',', ' ') }} <small class="text-muted">FCFA</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-muted">Total TTC</div>
                <div class="fs-3 fw-bold text-primary" id="recap-total-ttc">
                    {{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }} <small class="text-muted">FCFA TTC</small>
                </div>
            </div>
        </div>

        @if(count($decomposition ?? []) > 1)
            {{-- PO-02 : la décomposition n'apparaît que s'il y a plusieurs
                 taux — sur un bon à 18 % partout, elle n'apprend rien. --}}
            <hr>
            <table class="table table-sm mb-0" id="recap-decomposition">
                <thead>
                    <tr>
                        <th>Taux</th>
                        <th class="text-end">Base HT</th>
                        <th class="text-end">TVA</th>
                        <th class="text-end">TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decomposition as $tranche)
                        <tr>
                            <td>{{ rtrim(rtrim(number_format($tranche['taux'], 2, ',', ' '), '0'), ',') }} %</td>
                            <td class="text-end">{{ number_format($tranche['base_ht'], 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($tranche['tva'], 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($tranche['ttc'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
