// Fonction pour afficher/masquer l'animation de chargement
function toggleLoading(show = true) {
    const loadingEl = document.querySelector('.loading');
    if (!loadingEl && show) {
        const div = document.createElement('div');
        div.className = 'loading';
        document.body.appendChild(div);
    } else if (loadingEl && !show) {
        loadingEl.remove();
    }
}

// Gestion des formulaires avec fichiers
document.querySelectorAll('form').forEach(form => {
    if (form.querySelector('input[type="file"]')) {
        form.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            const maxSize = 20 * 1024 * 1024; // 20 MB
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('Le fichier est trop volumineux. La taille maximale est de 20 MB.');
                    return;
                }
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Type de fichier non autorisé. Formats acceptés : PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX');
                    return;
                }
            }
            toggleLoading();
        });
    }
});

// Gestion des alertes
document.querySelectorAll('.alert').forEach(alert => {
    const closeBtn = alert.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            alert.remove();
        });
        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
});

// Confirmation de suppression
document.querySelectorAll('.delete-confirm').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
        }
    });
});

// Prévisualisation des images
document.querySelectorAll('input[type="file"][accept^="image/"]').forEach(input => {
    input.addEventListener('change', function() {
        const preview = document.querySelector(this.dataset.preview);
        if (preview && this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => preview.src = e.target.result;
            reader.readAsDataURL(this.files[0]);
        }
    });
});

// Système de notation
document.querySelectorAll('.rating-input').forEach(input => {
    input.addEventListener('change', async function() {
        try {
            toggleLoading();
            const response = await fetch('rate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    document_id: this.dataset.documentId,
                    rating: this.value
                })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);
            
            // Mise à jour de l'affichage de la moyenne
            const ratingDisplay = document.querySelector('.rating-display');
            if (ratingDisplay) {
                ratingDisplay.innerHTML = `
                    <i class="fas fa-star text-warning"></i> 
                    ${data.moyenne.toFixed(1)}/5 
                    (${data.total} notes)
                `;
            } else {
                // Si l'élément rating-display n'existe pas, recharger la page
                window.location.reload();
            }
        } catch (error) {
            alert('Erreur lors de la notation : ' + error.message);
        } finally {
            toggleLoading(false);
        }
    });
});

// Recherche en temps réel
const searchInput = document.querySelector('#search-input');
if (searchInput) {
    let timeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            if (this.value.length >= 2) {
                try {
                    toggleLoading();
                    const response = await fetch(`/search.php?q=${encodeURIComponent(this.value)}&ajax=1`);
                    const data = await response.json();
                    const resultsContainer = document.querySelector('#search-results');
                    resultsContainer.innerHTML = data.html;
                } catch (error) {
                    console.error('Erreur de recherche:', error);
                } finally {
                    toggleLoading(false);
                }
            }
        }, 300);
    });
}

// Initialisation des tooltips Bootstrap
const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
tooltips.forEach(tooltip => {
    new bootstrap.Tooltip(tooltip);
}); 