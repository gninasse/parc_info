<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;
use Modules\Core\Models\User;

/**
 * D-22 — qui reçoit quoi, et par quel canal.
 *
 * Le besoin : les acteurs n'ont plus à guetter. Aujourd'hui le validateur
 * ouvre la liste « au cas où », l'acheteur rappelle le magasin pour savoir
 * si la livraison est arrivée. Ce guet coûte du temps et retarde les
 * décisions.
 *
 * Ce service ne contient AUCUNE logique métier — c'est une exigence du
 * prompt, et elle est saine : une notification lit un état, elle ne le
 * change pas. Si notifier échouait (serveur de mail coupé, table absente),
 * l'action métier qui l'a déclenchée doit rester valide. D'où le try/catch
 * global : une commande validée ne doit pas échouer parce qu'un mail part mal.
 *
 * Trois décisions structurantes :
 *
 *   1. les destinataires se déduisent des PERMISSIONS, jamais d'une liste
 *      d'adresses. Le jour où un validateur change, rien n'est à modifier
 *      dans le code — et personne ne continue de recevoir des bons qu'il
 *      n'a plus à viser ;
 *   2. l'auteur d'une action ne s'auto-notifie pas. Recevoir un courriel
 *      pour un geste qu'on vient de faire apprend à ignorer les courriels ;
 *   3. l'absence de préférence vaut ACTIF. Un nouvel utilisateur reçoit ce
 *      qui le concerne sans avoir rien à configurer, et peut ensuite couper.
 */
class NotificationsAchat
{
    /** Les quatre types, tels qu'ils s'affichent dans les préférences. */
    public const TYPES = [
        'bon_soumis' => 'Un bon attend mon visa',
        'bon_renvoye' => 'Mon bon a été renvoyé en brouillon',
        'reception_integree' => 'Une livraison de mon bon a été reçue',
        'reliquat_en_retard' => 'Mes bons en attente de livraison (résumé hebdomadaire)',
    ];

    /**
     * Notifie les VALIDATEURS qu'un bon attend leur visa.
     *
     * Le destinataire n'est pas « le validateur » (il n'y en a pas un seul)
     * mais toute personne détenant la permission de viser : c'est elle qui
     * définit le rôle, pas une liste tenue à la main.
     */
    public function bonSoumis(BonCommande $bon, ?User $auteur = null): void
    {
        $this->envoyer(
            'bon_soumis',
            $this->porteursDe('achat.bons_commande.valider')->reject(
                fn (User $u) => $auteur !== null && $u->id === $auteur->id
            ),
            [
                'titre' => 'Bon de commande à viser',
                'message' => sprintf(
                    '%s a soumis %s (%s FCFA TTC) au visa.',
                    $auteur?->name ?? 'Un acheteur',
                    $bon->numero_affiche,
                    number_format((float) $bon->montant_ttc, 0, ',', ' ')
                ),
                'bon_id' => $bon->id,
            ]
        );
    }

    /**
     * Notifie l'AUTEUR que son bon revient, avec le motif.
     *
     * Le motif est dans la notification, pas seulement sur la fiche : un
     * renvoi sans raison lisible immédiatement se solde par un appel
     * téléphonique, ce qui annule le bénéfice de la notification.
     */
    public function bonRenvoye(BonCommande $bon, string $motif, ?User $parQui = null): void
    {
        $auteur = $bon->created_by !== null ? User::query()->find($bon->created_by) : null;

        if ($auteur === null || ($parQui !== null && $auteur->id === $parQui->id)) {
            return;
        }

        $this->envoyer('bon_renvoye', collect([$auteur]), [
            'titre' => 'Votre bon a été renvoyé en brouillon',
            'message' => sprintf(
                '%s a renvoyé %s : « %s »',
                $parQui?->name ?? 'Le visa',
                $bon->numero_affiche,
                $motif
            ),
            'bon_id' => $bon->id,
        ]);
    }

    /** Notifie l'AUTEUR qu'une livraison de son bon a été intégrée. */
    public function receptionIntegree(BonCommande $bon, string $reference, float $unites): void
    {
        $auteur = $bon->created_by !== null ? User::query()->find($bon->created_by) : null;

        if ($auteur === null) {
            return;
        }

        $this->envoyer('reception_integree', collect([$auteur]), [
            'titre' => 'Livraison reçue au magasin',
            'message' => sprintf(
                '%s : %s unité(s) reçues (%s). Reste à livrer : %s ligne(s).',
                $bon->numero_affiche,
                rtrim(rtrim(number_format($unites, 2, ',', ' '), '0'), ','),
                $reference,
                $bon->lignes()->get()->filter(fn ($l) => (float) $l->reste > 0)->count()
            ),
            'bon_id' => $bon->id,
        ]);
    }

    /**
     * Le RÉSUMÉ hebdomadaire des reliquats en retard.
     *
     * Un courriel par reliquat noierait le destinataire et lui apprendrait à
     * les supprimer sans lire. Un résumé par personne, une fois par semaine,
     * se lit — et c'est la seule forme qui produise une action.
     *
     * @return int nombre de destinataires notifiés
     */
    public function resumeReliquats(int $seuilJours): int
    {
        $enRetard = BonCommande::query()
            ->whereIn('statut', [BonCommande::STATUT_VALIDE, BonCommande::STATUT_PARTIEL])
            ->whereNotNull('valide_le')
            ->whereDate('valide_le', '<=', now()->subDays($seuilJours))
            ->with('lignes')
            ->get()
            ->filter(fn (BonCommande $bon) => $bon->lignes->contains(fn ($l) => (float) $l->reste > 0));

        if ($enRetard->isEmpty()) {
            return 0;
        }

        $notifies = 0;

        foreach ($enRetard->groupBy('created_by') as $auteurId => $bons) {
            $auteur = $auteurId !== null ? User::query()->find($auteurId) : null;

            if ($auteur === null) {
                continue;
            }

            $this->envoyer('reliquat_en_retard', collect([$auteur]), [
                'titre' => sprintf('%d bon(s) en attente de livraison', $bons->count()),
                'message' => sprintf(
                    'Ces bons dépassent %d jours sans être soldés : %s.',
                    $seuilJours,
                    $bons->take(8)->pluck('numero_affiche')->implode(', ')
                        .($bons->count() > 8 ? '…' : '')
                ),
                'bon_id' => null,
            ]);

            $notifies++;
        }

        return $notifies;
    }

    /** Les utilisateurs détenant une permission — la définition du rôle. */
    private function porteursDe(string $permission): Collection
    {
        return User::query()
            ->get()
            ->filter(fn (User $utilisateur) => $utilisateur->can($permission))
            ->values();
    }

    /**
     * L'envoi effectif, filtré par les préférences de chacun.
     *
     * Encadré d'un try/catch : notifier est un CONFORT, jamais une condition
     * de l'action métier. Un serveur de mail injoignable ne doit pas faire
     * échouer une validation de bon de commande.
     */
    private function envoyer(string $type, Collection $destinataires, array $donnees): void
    {
        try {
            foreach ($destinataires as $destinataire) {
                $preference = $this->preference($destinataire, $type);

                if (! $preference['par_mail'] && ! $preference['par_cloche']) {
                    continue;
                }

                $destinataire->notify(new \Modules\Achat\Notifications\NotificationAchat(
                    $type,
                    $donnees,
                    $preference['par_mail'],
                    $preference['par_cloche'],
                ));
            }
        } catch (\Throwable $e) {
            // On journalise et on continue : l'action métier a déjà réussi.
            Log::warning('Notification Achat non envoyée', [
                'type' => $type,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * La préférence d'un utilisateur pour un type.
     *
     * L'ABSENCE de préférence vaut actif : un nouvel utilisateur reçoit ce
     * qui le concerne sans rien configurer. C'est l'inverse qui serait
     * piégeux — une notification qu'il faut activer pour exister n'est
     * jamais activée.
     *
     * @return array{par_mail: bool, par_cloche: bool}
     */
    public function preference(User $utilisateur, string $type): array
    {
        if (! Schema::hasTable('achat_preferences_notification')) {
            return ['par_mail' => true, 'par_cloche' => true];
        }

        $ligne = DB::table('achat_preferences_notification')
            ->where('user_id', $utilisateur->id)
            ->where('type', $type)
            ->first();

        return [
            'par_mail' => $ligne === null ? true : (bool) $ligne->par_mail,
            'par_cloche' => $ligne === null ? true : (bool) $ligne->par_cloche,
        ];
    }

    /** Enregistre le choix d'un utilisateur pour un type. */
    public function definirPreference(User $utilisateur, string $type, bool $parMail, bool $parCloche): void
    {
        DB::table('achat_preferences_notification')->updateOrInsert(
            ['user_id' => $utilisateur->id, 'type' => $type],
            ['par_mail' => $parMail, 'par_cloche' => $parCloche, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
