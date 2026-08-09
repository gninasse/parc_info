<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\RegularisationService;

/**
 * A-08 — administration des paramètres (D-17).
 *
 * La leçon de la v1, explicitement rappelée par le recueil : jamais de table
 * de paramètres SANS écran. Une valeur qu'on ne peut changer qu'en base est
 * une valeur qui ne change jamais — et le métier s'adapte au logiciel au
 * lieu de l'inverse.
 *
 * Chaque modification prend effet IMMÉDIATEMENT (aucun redéploiement) et
 * laisse au journal l'ancienne ET la nouvelle valeur : un comportement qui
 * change sans trace est un incident en puissance.
 */
class AdministrationController extends Controller implements HasMiddleware
{
    public const EVENEMENT_PARAMETRE = 'modification_parametre';

    /** Règles PAR CLÉ : un paramètre n'accepte pas n'importe quoi. */
    private const REGLES = [
        Parametre::PREFIXE_NUMEROTATION => ['required', 'string', 'max:8', 'regex:/^[A-Z0-9\-]+$/'],
        Parametre::DELAI_ALERTE_RELIQUAT_JOURS => ['required', 'integer', 'min:1', 'max:365'],
        Parametre::SEUIL_ECART_PRIX_PCT => ['required', 'integer', 'min:1', 'max:100'],
        Parametre::TAILLE_MAX_PIECE_MO => ['required', 'integer', 'min:1', 'max:100'],
        Parametre::REGULARISATION_ACTIVE => ['required', 'boolean'],
        Parametre::INTERMEDE_DEBUT => ['nullable', 'date'],
        Parametre::INTERMEDE_FIN => ['nullable', 'date'],
        Parametre::MOTIFS_OBSERVATION => ['required', 'array', 'min:1'],
    ];

    private const MESSAGES = [
        Parametre::PREFIXE_NUMEROTATION => [
            'regex' => 'Le préfixe n\'accepte que des majuscules, des chiffres et des tirets (ex. « BC »).',
            'max' => 'Le préfixe ne peut pas dépasser 8 caractères — il précède chaque numéro.',
        ],
        Parametre::DELAI_ALERTE_RELIQUAT_JOURS => [
            'min' => 'Le délai doit valoir au moins 1 jour.',
            'max' => 'Au-delà d\'un an, l\'alerte ne signale plus rien d\'utile.',
        ],
        Parametre::SEUIL_ECART_PRIX_PCT => [
            'min' => 'Un seuil à 0 % signalerait toutes les lignes : le signal se noierait.',
            'max' => 'Le seuil s\'exprime en pourcentage (1 à 100).',
        ],
        Parametre::TAILLE_MAX_PIECE_MO => [
            'max' => 'Au-delà de 100 Mo, le dépôt échouerait avant d\'arriver au serveur.',
        ],
    ];

    public function __construct(
        private readonly AchatParametres $parametres,
        private readonly RegularisationService $regularisation,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.administration.manage'),
        ];
    }

    public function index()
    {
        return view('achat::administration.index', [
            'prefixe' => $this->parametres->prefixeNumerotation(),
            'delaiReliquat' => $this->parametres->delaiAlerteReliquatJours(),
            'seuilEcart' => $this->parametres->seuilEcartPrixPct(),
            'tailleMaxPiece' => $this->parametres->tailleMaxPieceMo(),
            'motifs' => $this->parametres->motifsObservation(),
            'regularisationActive' => $this->parametres->regularisationActive(),
            // L'écran affiche la DETTE : l'interrupteur de régularisation ne
            // se comprend qu'avec le nombre qu'il est censé faire tomber.
            'detteInterim' => $this->regularisation->detteRestante(),
            'intermede' => [
                'debut' => $this->parametres->intermedeDebut()?->toDateString(),
                'fin' => $this->parametres->intermedeFin()?->toDateString(),
            ],
            // L'aperçu du numéro se construit avec l'année courante.
            'anneeCourante' => now()->year,
        ]);
    }

    /**
     * PATCH d'un paramètre : validation par clé, effet immédiat, journal.
     *
     * L'ancienne valeur est journalisée AVEC la nouvelle. Savoir qu'un seuil
     * vaut 30 aujourd'hui ne sert à rien si on ignore qu'il valait 90 la
     * semaine dernière, quand les alertes ont cessé de sonner.
     */
    public function modifier(Request $request, string $cle): JsonResponse
    {
        if (! array_key_exists($cle, self::REGLES)) {
            return response()->json(['success' => false, 'message' => 'Paramètre inconnu.'], 404);
        }

        $validateur = Validator::make(
            $request->all(),
            ['valeur' => self::REGLES[$cle]],
            collect(self::MESSAGES[$cle] ?? [])
                ->mapWithKeys(fn (string $message, string $regle) => ["valeur.{$regle}" => $message])
                ->all()
        );

        if ($validateur->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validateur->errors()->first('valeur'),
                'errors' => $validateur->errors(),
            ], 422);
        }

        $ancienne = $this->parametres->get($cle);
        $nouvelle = $this->parametres->set($cle, $validateur->validated()['valeur'], $request->user()->id);

        activity('achat')
            ->causedBy($request->user())
            ->withProperties([
                'cle' => $cle,
                'ancienne_valeur' => is_array($ancienne) ? json_encode($ancienne, JSON_UNESCAPED_UNICODE) : $ancienne,
                'nouvelle_valeur' => is_array($nouvelle) ? json_encode($nouvelle, JSON_UNESCAPED_UNICODE) : $nouvelle,
            ])
            // Sans sujet, le module ne serait pas renseigné par le trait.
            ->tap(fn ($activite) => $activite->module = 'achat')
            ->log(self::EVENEMENT_PARAMETRE);

        return response()->json([
            'success' => true,
            'message' => 'Paramètre enregistré — l\'effet est immédiat.',
            'data' => [
                'cle' => $cle,
                'valeur' => $nouvelle,
                // L'aperçu du prochain numéro, recalculé côté serveur.
                'apercu' => $cle === Parametre::PREFIXE_NUMEROTATION
                    ? sprintf('%s-%d-0042', $nouvelle, now()->year)
                    : null,
            ],
        ]);
    }
}
