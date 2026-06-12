<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Licence;

class ParcInfoController extends Controller
{
    public function dashboard(): \Illuminate\View\View
    {
        $stats = [
            'total_equipements' => Equipement::count(),
            'en_service' => Equipement::where('statut', 'en_service')->count(),
            'en_maintenance' => Equipement::where('statut', 'en_reparation')->count(),
            'hors_service' => Equipement::whereIn('statut', ['perdu', 'reforme'])->count(),
            'en_stock' => Equipement::where('statut', 'en_stock')->count(),

            // Ventilation par type d'actif
            'postes_travail' => Equipement::has('ordinateur')->count(),
            'serveurs' => Equipement::has('serveur')->count(),
            'imprimantes' => Equipement::has('imprimante')->count(),
            'scanners' => Equipement::has('scanner')->count(),
            'telephones' => Equipement::has('telephone')->count(),
            'cameras' => Equipement::has('camera')->count(),
            'mobiles' => Equipement::has('mobile')->count(),
            'licences' => Licence::where('actif', true)->count(),
            'consommables' => Consommable::where('est_actif', true)->count(),

            // Équipements réseaux spécifiques
            'switches' => Equipement::whereHas('reseau.typeReseau', fn ($q) => $q->where('libelle', 'ilike', '%switch%')->orWhere('libelle', 'ilike', '%commutateur%'))->count(),
            'routeurs' => Equipement::whereHas('reseau.typeReseau', fn ($q) => $q->where('libelle', 'ilike', '%routeur%'))->count(),
            'parefeux' => Equipement::whereHas('reseau.typeReseau', fn ($q) => $q->where('libelle', 'ilike', '%pare-feu%')->orWhere('libelle', 'ilike', '%firewall%'))->count(),
            'wifi' => Equipement::whereHas('reseau.typeReseau', fn ($q) => $q->where('libelle', 'ilike', '%wifi%')->orWhere('libelle', 'ilike', '%AP%'))->count(),
            'terminaux_ip' => Equipement::whereHas('reseau.typeReseau', fn ($q) => $q->where('libelle', 'ilike', '%terminal%')->orWhere('libelle', 'ilike', '%terminal ip%'))->count(),

            // Expirations, Alertes & Renouvellements
            'garantie_expiree' => Equipement::whereNotNull('date_fin_garantie')->where('date_fin_garantie', '<', now())->count(),
            'renouvellement_prevu' => Equipement::whereNotNull('date_fin_garantie')->whereBetween('date_fin_garantie', [now(), now()->addDays(90)])->count(),
            'licences_expirees' => Licence::expire()->count(),
            'licences_expirant_prochainement' => Licence::expirantProchainement()->count(),
            'licences_surexploitees' => Licence::enSurexploitation()->count(),
            'consommables_rupture' => Consommable::enRupture()->count(),
        ];

        // Calcul cumulé de la somme des équipements réseau
        $stats['equipements_reseau'] = $stats['switches'] + $stats['routeurs'] + $stats['parefeux'] + $stats['wifi'] + $stats['terminaux_ip'];

        // Flux des équipements récemment enregistrés avec eager loading exhaustif
        $rawRecent = Equipement::with([
            'marque',
            'ordinateur',
            'serveur',
            'imprimante',
            'scanner',
            'telephone',
            'camera',
            'mobile',
            'reseau.typeReseau',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.employe',
        ])
            ->latest()
            ->limit(5)
            ->get();

        $recentEquipements = $rawRecent->map(function ($e) {
            $typeLabel = 'Équipement';
            $typeIcon = 'bi-cpu-fill';
            $typeColor = 'secondary';

            if ($e->ordinateur) {
                $typeLabel = 'Poste de travail';
                $typeIcon = 'bi-pc-display-horizontal';
                $typeColor = 'primary';
            } elseif ($e->serveur) {
                $typeLabel = 'Serveur';
                $typeIcon = 'bi-server';
                $typeColor = 'warning';
            } elseif ($e->imprimante) {
                $typeLabel = 'Imprimante';
                $typeIcon = 'bi-printer';
                $typeColor = 'success';
            } elseif ($e->scanner) {
                $typeLabel = 'Scanner';
                $typeIcon = 'bi-camera';
                $typeColor = 'info';
            } elseif ($e->telephone) {
                $typeLabel = 'Téléphone';
                $typeIcon = 'bi-telephone';
                $typeColor = 'indigo';
            } elseif ($e->camera) {
                $typeLabel = 'Caméra IP';
                $typeIcon = 'bi-eye';
                $typeColor = 'danger';
            } elseif ($e->mobile) {
                $typeLabel = 'Mobile';
                $typeIcon = 'bi-phone';
                $typeColor = 'purple';
            } elseif ($e->reseau) {
                $lib = strtolower($e->reseau->typeReseau?->libelle ?? '');
                if (str_contains($lib, 'switch')) {
                    $typeLabel = 'Switch';
                    $typeIcon = 'bi-hdd-network';
                    $typeColor = 'info';
                } elseif (str_contains($lib, 'routeur')) {
                    $typeLabel = 'Routeur';
                    $typeIcon = 'bi-router';
                    $typeColor = 'primary';
                } elseif (str_contains($lib, 'pare-feu') || str_contains($lib, 'firewall')) {
                    $typeLabel = 'Pare-feu';
                    $typeIcon = 'bi-shield-shaded';
                    $typeColor = 'danger';
                } elseif (str_contains($lib, 'wifi') || str_contains($lib, 'ap')) {
                    $typeLabel = 'Point WiFi';
                    $typeIcon = 'bi-wifi';
                    $typeColor = 'success';
                } else {
                    $typeLabel = $e->reseau->typeReseau?->libelle ?? 'Réseau';
                    $typeIcon = 'bi-router';
                    $typeColor = 'secondary';
                }
            }

            $siteLabel = '—';
            if ($e->affectationActive) {
                if ($e->affectationActive->local) {
                    $siteLabel = $e->affectationActive->local->etage?->batiment?->site?->libelle ?? $e->affectationActive->local->libelle;
                } elseif ($e->affectationActive->posteTravail) {
                    $siteLabel = $e->affectationActive->posteTravail->local?->etage?->batiment?->site?->libelle ?? '—';
                } elseif ($e->affectationActive->employe) {
                    $siteLabel = $e->affectationActive->employe->nom.' '.$e->affectationActive->employe->prenom;
                }
            }

            $statutStyles = match ($e->statut) {
                'en_stock' => ['label' => 'En stock', 'color' => 'secondary'],
                'en_service' => ['label' => 'En service', 'color' => 'success'],
                'en_reparation' => ['label' => 'En réparation', 'color' => 'warning'],
                'perdu' => ['label' => 'Perdu/Volé', 'color' => 'danger'],
                'reforme' => ['label' => 'Réformé', 'color' => 'dark'],
                default => ['label' => 'Inconnu', 'color' => 'light text-dark'],
            };

            $detailRoute = match ($typeLabel) {
                'Poste de travail' => route('parc-info.ordinateurs.show', $e->id),
                'Serveur' => route('parc-info.serveurs.show', $e->id),
                'Imprimante' => route('parc-info.imprimantes.show', $e->id),
                'Scanner' => route('parc-info.scanners.show', $e->id),
                'Téléphone' => route('parc-info.telephonie.show', $e->id),
                'Caméra IP' => route('parc-info.cameras.show', $e->id),
                'Mobile' => route('parc-info.mobiles.show', $e->id),
                'Switch' => route('parc-info.switches.show', $e->id),
                'Routeur' => route('parc-info.routeurs.show', $e->id),
                'Pare-feu' => route('parc-info.parefeux.show', $e->id),
                'Point WiFi' => route('parc-info.wifi.show', $e->id),
                default => '#',
            };

            return [
                'id' => $e->id,
                'code' => $e->code_inventaire,
                'libelle' => ($e->marque?->libelle ?? 'Générique').' '.$e->modele,
                'type' => $typeLabel,
                'type_icon' => $typeIcon,
                'type_color' => $typeColor,
                'site' => $siteLabel,
                'statut' => $statutStyles['label'],
                'statut_color' => $statutStyles['color'],
                'date' => $e->created_at->diffForHumans(),
                'detail_route' => $detailRoute,
            ];
        });

        // Calcul de la répartition par catégorie
        $total = $stats['total_equipements'];
        $repartitionParType = [
            [
                'label' => 'Postes de travail',
                'count' => $stats['postes_travail'],
                'percent' => $total > 0 ? round(($stats['postes_travail'] / $total) * 100) : 0,
                'color' => 'primary',
                'icon' => 'bi bi-pc-display-horizontal',
                'route' => route('parc-info.ordinateurs.index'),
            ],
            [
                'label' => 'Imprimantes & Scanners',
                'count' => $stats['imprimantes'] + $stats['scanners'],
                'percent' => $total > 0 ? round((($stats['imprimantes'] + $stats['scanners']) / $total) * 100) : 0,
                'color' => 'success',
                'icon' => 'bi bi-printer',
                'route' => route('parc-info.imprimantes.index'),
            ],
            [
                'label' => 'Équipements réseau',
                'count' => $stats['equipements_reseau'],
                'percent' => $total > 0 ? round(($stats['equipements_reseau'] / $total) * 100) : 0,
                'color' => 'info',
                'icon' => 'bi bi-router',
                'route' => route('parc-info.switches.index'),
            ],
            [
                'label' => 'Serveurs',
                'count' => $stats['serveurs'],
                'percent' => $total > 0 ? round(($stats['serveurs'] / $total) * 100) : 0,
                'color' => 'warning',
                'icon' => 'bi bi-server',
                'route' => route('parc-info.serveurs.index'),
            ],
            [
                'label' => 'Téléphonie & Mobiles',
                'count' => $stats['telephones'] + $stats['mobiles'],
                'percent' => $total > 0 ? round((($stats['telephones'] + $stats['mobiles']) / $total) * 100) : 0,
                'color' => 'indigo',
                'icon' => 'bi bi-telephone',
                'route' => route('parc-info.telephonie.index'),
            ],
            [
                'label' => 'Caméras IP',
                'count' => $stats['cameras'],
                'percent' => $total > 0 ? round(($stats['cameras'] / $total) * 100) : 0,
                'color' => 'danger',
                'icon' => 'bi bi-eye',
                'route' => route('parc-info.cameras.index'),
            ],
        ];

        return view('parcinfo::dashboard.index', compact('stats', 'recentEquipements', 'repartitionParType'));
    }

    public function searchEquipements(Request $request)
    {
        $q = $request->get('q', '');

        $query = Equipement::with(['marque', 'affectationActive.local.etage.batiment'])
            ->where(function ($query) use ($q) {
                $query->where('code_inventaire', 'ilike', "%{$q}%")
                    ->orWhere('modele', 'ilike', "%{$q}%")
                    ->orWhere('numero_serie', 'ilike', "%{$q}%")
                    ->orWhereHas('marque', fn ($m) => $m->where('libelle', 'ilike', "%{$q}%"));
            });

        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'ordinateur') {
                $query->whereHas('ordinateur');
            } elseif ($type === 'serveur') {
                $query->where(function ($q) {
                    $q->whereHas('serveur')->orWhereHas('serveurVirtuel');
                });
            } elseif ($type === 'reseau') {
                $query->whereHas('reseau');
            } elseif ($type === 'mobile') {
                $query->whereHas('mobile');
            } elseif ($type === 'imprimante') {
                $query->whereHas('imprimante');
            } elseif ($type === 'scanner') {
                $query->whereHas('scanner');
            } elseif ($type === 'telephone') {
                $query->whereHas('telephone');
            } elseif ($type === 'camera') {
                $query->whereHas('camera');
            }
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->limit(50)->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'code' => $e->code_inventaire,
                'modele' => $e->modele,
                'marque' => $e->marque?->libelle ?: 'Générique',
                'statut' => $e->statut,
                'statut_label' => $e->statut_label,
                'emplacement' => $e->affectationActive?->local?->libelle ?? '—',
            ];
        }));
    }
}
