/**
 * Champ scan douchette du module Stock (S6 — UX §0.5).
 *
 * Règles portées ici :
 *  - autofocus permanent : le champ reprend le focus après chaque blur et
 *    chaque événement traité (aucun composant ne doit le voler — les pages
 *    n'affichent jamais de Swal sur succès, uniquement des toasts non focalisants) ;
 *  - bip Web Audio à deux tonalités (succès aigu montant / erreur grave
 *    descendant), sans fichier asset ;
 *  - historique visuel des 5 derniers scans ;
 *  - file d'attente bornée (config stock.file_scans_max, lue sur l'attribut
 *    data-file-scans-max du champ) : les scans reçus pendant qu'un traitement
 *    est en cours sont mis en file, jamais perdus ni entremêlés.
 *
 * Usage :
 *   const scan = new StockScanField('#champ-scan', {
 *       // Traitement d'un code : retourner (une promesse de)
 *       // { ok: true|false, libelle: 'texte affiché dans l'historique' }
 *       onScan: async (code) => ({ ok: true, libelle: code + ' pointé' }),
 *   });
 */
class StockScanField {
    static HISTORIQUE_MAX = 5;

    constructor(selecteur, options = {}) {
        this.champ = typeof selecteur === 'string' ? document.querySelector(selecteur) : selecteur;
        if (!this.champ) {
            throw new Error(`StockScanField : champ introuvable (${selecteur})`);
        }

        this.onScan = options.onScan || (async () => ({ ok: true }));
        this.fileMax = parseInt(this.champ.dataset.fileScansMax || '50', 10);
        this.historiqueElement = document.getElementById(`${this.champ.id}-historique`);
        this.messageElement = document.getElementById(`${this.champ.id}-message`);

        this.file = [];
        this.traitementEnCours = false;
        this.historique = [];
        this.contexteAudio = null;

        this.champ.addEventListener('keydown', (evenement) => {
            if (evenement.key !== 'Enter') return;
            evenement.preventDefault();
            const code = this.champ.value.trim();
            this.champ.value = '';
            if (code !== '') this.enfiler(code);
        });

        // Refocus programmatique : après un blur (clic ailleurs, fermeture de
        // modale…), le champ reprend la main dès la fin du cycle d'événement.
        this.champ.addEventListener('blur', () => {
            setTimeout(() => this.refocus(), 0);
        });

        this.refocus();
    }

    refocus() {
        if (document.contains(this.champ) && document.activeElement !== this.champ) {
            this.champ.focus({ preventScroll: true });
        }
    }

    enfiler(code) {
        if (this.file.length >= this.fileMax) {
            this.bip(false);
            this.afficherMessage(`File de scans pleine (${this.fileMax}) — attendez la fin du traitement`);
            return;
        }
        this.file.push(code);
        this.traiterFile();
    }

    async traiterFile() {
        if (this.traitementEnCours) return;
        this.traitementEnCours = true;

        while (this.file.length > 0) {
            const code = this.file.shift();
            let resultat;
            try {
                resultat = await this.onScan(code);
            } catch (erreur) {
                resultat = { ok: false, libelle: erreur?.message || 'Erreur de traitement' };
            }
            this.bip(resultat?.ok !== false);
            if (resultat?.ok === false && resultat?.libelle) {
                this.afficherMessage(resultat.libelle);
            } else {
                this.masquerMessage();
            }
            this.ajouterHistorique(code, resultat?.ok !== false, resultat?.libelle);
            this.refocus();
        }

        this.traitementEnCours = false;
    }

    /**
     * Deux tonalités Web Audio, sans asset : succès = double note montante
     * (880 → 1320 Hz), erreur = note grave descendante (330 → 165 Hz).
     */
    bip(succes) {
        try {
            this.contexteAudio = this.contexteAudio
                || new (window.AudioContext || window.webkitAudioContext)();
            const ctx = this.contexteAudio;
            const maintenant = ctx.currentTime;

            const oscillateur = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillateur.type = 'sine';
            gain.gain.setValueAtTime(0.15, maintenant);

            if (succes) {
                oscillateur.frequency.setValueAtTime(880, maintenant);
                oscillateur.frequency.setValueAtTime(1320, maintenant + 0.08);
                gain.gain.exponentialRampToValueAtTime(0.001, maintenant + 0.18);
                oscillateur.start(maintenant);
                oscillateur.stop(maintenant + 0.18);
            } else {
                oscillateur.frequency.setValueAtTime(330, maintenant);
                oscillateur.frequency.exponentialRampToValueAtTime(165, maintenant + 0.25);
                gain.gain.exponentialRampToValueAtTime(0.001, maintenant + 0.3);
                oscillateur.start(maintenant);
                oscillateur.stop(maintenant + 0.3);
            }

            oscillateur.connect(gain).connect(ctx.destination);
        } catch (erreur) {
            // Web Audio indisponible (autoplay bloqué, vieux navigateur) : silencieux.
        }
    }

    ajouterHistorique(code, ok, libelle) {
        this.historique.unshift({ code, ok, libelle });
        this.historique = this.historique.slice(0, StockScanField.HISTORIQUE_MAX);
        this.rendreHistorique();
    }

    rendreHistorique() {
        if (!this.historiqueElement) return;
        this.historiqueElement.innerHTML = '';
        this.historique.forEach((scan) => {
            const ligne = document.createElement('li');
            ligne.className = scan.ok ? 'text-success' : 'text-danger';

            const icone = document.createElement('i');
            icone.className = scan.ok ? 'bi bi-check-circle me-1' : 'bi bi-x-circle me-1';
            ligne.appendChild(icone);

            const codeElement = document.createElement('code');
            codeElement.textContent = scan.code;
            ligne.appendChild(codeElement);

            if (scan.libelle) {
                ligne.appendChild(document.createTextNode(` — ${scan.libelle}`));
            }
            this.historiqueElement.appendChild(ligne);
        });
    }

    afficherMessage(texte) {
        if (!this.messageElement) return;
        this.messageElement.textContent = texte;
        this.messageElement.classList.remove('d-none');
    }

    masquerMessage() {
        if (!this.messageElement) return;
        this.messageElement.classList.add('d-none');
    }
}

window.StockScanField = StockScanField;
