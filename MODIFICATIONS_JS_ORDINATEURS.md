# Modifications JavaScript pour le module Ordinateurs

## Modifications à apporter dans index.blade.php

### 1. Gestion du bouton "Enregistrer en réparation"

Ajouter après la gestion du wizard (autour de la ligne où `currentStep` est géré) :

```javascript
// Afficher/masquer le bouton "Enregistrer en réparation" à l'étape 2
function updateReparationButton() {
    const statut = $('input[name="statut"]:checked').val();
    if (currentStep === 2 && statut === 'en_reparation') {
        $('#btn-save-reparation').removeClass('d-none');
    } else {
        $('#btn-save-reparation').addClass('d-none');
    }
}

// Appeler updateReparationButton lors du changement d'étape
// Dans la fonction goToStep, ajouter :
updateReparationButton();

// Lors de la sélection du statut
$(document).on('change', 'input[name="statut"]', function() {
    updateReparationButton();
});

// Gestion du clic sur "Enregistrer en réparation"
$('#btn-save-reparation').on('click', function(e) {
    e.preventDefault();
    
    // Valider l'étape 2
    if (!validateStep(2)) {
        return;
    }
    
    // Ajouter le flag skip_affectation
    const formData = new FormData($('#ordinateurForm')[0]);
    formData.append('skip_affectation', '1');
    
    // Soumettre
    submitForm(formData);
});
```

### 2. Modifier la fonction de soumission du formulaire

Dans la fonction `$('#ordinateurForm').on('submit', ...)`, modifier pour :

```javascript
$('#ordinateurForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Si pas d'affectation sélectionnée, ajouter skip_affectation
    if (!$('input[name="type_cible"]:checked').val()) {
        formData.append('skip_affectation', '1');
    }
    
    submitForm(formData);
});

function submitForm(formData) {
    const isEdit = !!$('#ord_id').val();
    const url = isEdit 
        ? `/parc-info/informatique/ordinateurs-fixes/${$('#ord_id').val()}`
        : '/parc-info/informatique/ordinateurs-fixes';
    const method = isEdit ? 'PUT' : 'POST';
    
    if (isEdit) {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: response.message,
                timer: 2000
            });
            $('#ordinateurModal').modal('hide');
            $table.bootstrapTable('refresh');
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: xhr.responseJSON?.message || 'Une erreur est survenue'
            });
        }
    });
}
```

### 3. Retirer la validation required sur date_acquisition

Dans la fonction `validateStep(step)`, s'assurer que date_acquisition n'est pas obligatoire :

```javascript
function validateStep(step) {
    if (step === 1) {
        if (!$('input[name="statut"]:checked').val()) {
            Swal.fire('Attention', 'Veuillez sélectionner un statut', 'warning');
            return false;
        }
    }
    
    if (step === 2) {
        const required = ['code_inventaire', 'numero_serie', 'modele'];
        for (let field of required) {
            if (!$(`[name="${field}"]`).val()) {
                Swal.fire('Attention', `Le champ ${field} est obligatoire`, 'warning');
                return false;
            }
        }
    }
    
    return true;
}
```

### 4. Gérer le bouton "Précédent" pour vider l'affectation

Dans la gestion du bouton précédent :

```javascript
$('#btn-prev').on('click', function() {
    if (currentStep === 3) {
        // Vider les affectations
        $('input[name="type_cible"]').prop('checked', false);
        $('.aff-type-card').removeClass('selected');
        $('.aff-summary').addClass('d-none');
        $('#aff-skip-hint').removeClass('d-none');
        $('#dossier_employe_id, #poste_travail_id, #local_id').val('');
    }
    
    goToStep(currentStep - 1);
});
```

## Modifications à apporter dans show.blade.php

### 1. Ajouter un dropdown pour changer le statut

Ajouter dans la section des boutons d'action :

```html
<div class="dropdown">
    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-arrow-repeat me-1"></i> Changer statut
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#" data-statut="en_stock">En stock</a></li>
        <li><a class="dropdown-item" href="#" data-statut="en_service">En service</a></li>
        <li><a class="dropdown-item" href="#" data-statut="en_reparation">En réparation</a></li>
        <li><a class="dropdown-item" href="#" data-statut="perdu">Perdu</a></li>
        <li><a class="dropdown-item" href="#" data-statut="reforme">Réformé</a></li>
    </ul>
</div>

<button class="btn btn-warning" id="btn-desaffecter">
    <i class="bi bi-x-circle me-1"></i> Désaffecter
</button>
```

### 2. JavaScript pour gérer le changement de statut

```javascript
// Changer le statut
$(document).on('click', '.dropdown-item[data-statut]', function(e) {
    e.preventDefault();
    const nouveauStatut = $(this).data('statut');
    const equipementId = {{ $equipement->id }};
    
    Swal.fire({
        title: 'Changer le statut',
        input: 'textarea',
        inputLabel: 'Motif du changement',
        inputPlaceholder: 'Expliquez la raison du changement de statut...',
        inputAttributes: {
            'aria-label': 'Motif'
        },
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        preConfirm: (motif) => {
            if (!motif) {
                Swal.showValidationMessage('Le motif est obligatoire');
            }
            return motif;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/parc-info/informatique/ordinateurs-fixes/${equipementId}/statut`,
                method: 'PATCH',
                data: {
                    statut: nouveauStatut,
                    motif: result.value,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Succès', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                }
            });
        }
    });
});

// Désaffecter
$('#btn-desaffecter').on('click', function() {
    const equipementId = {{ $equipement->id }};
    
    Swal.fire({
        title: 'Désaffecter l\'équipement',
        text: 'L\'équipement sera mis en stock',
        input: 'textarea',
        inputLabel: 'Motif de la désaffectation',
        inputPlaceholder: 'Expliquez la raison...',
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#dc3545',
        preConfirm: (motif) => {
            if (!motif) {
                Swal.showValidationMessage('Le motif est obligatoire');
            }
            return motif;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/parc-info/informatique/ordinateurs-fixes/${equipementId}/desaffecter`,
                method: 'POST',
                data: {
                    motif: result.value,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Succès', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                }
            });
        }
    });
});
```

### 3. Ajouter le champ compte_admin_local dans le formulaire

Dans la section des informations réseau, ajouter :

```html
<div class="col-md-6">
    <label class="form-label">Compte admin local</label>
    <input type="text" class="form-control" name="compte_admin_local" 
           value="{{ $equipement->ordinateur->compte_admin_local }}" disabled>
</div>
```

## Résumé des modifications

1. ✅ Migration : date_acquisition nullable + compte_admin_local
2. ✅ Modèle Ordinateur : compte_admin_local dans fillable
3. ✅ Contrôleur : méthodes updateStatut, updateEtat, desaffecter + gestion skip_affectation
4. ✅ Routes : ajout des nouvelles routes
5. ✅ Wizard : bouton "Enregistrer en réparation" + ram_capacite_go en input number
6. ⚠️  JavaScript index : à modifier selon ce document
7. ⚠️  JavaScript show : à modifier selon ce document
