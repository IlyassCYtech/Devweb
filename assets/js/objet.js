document.addEventListener('DOMContentLoaded', () => UI.init());

const API_BASE_URL = '';

const store = {
    objects: [],
    
    async fetchObjects() {
        try {
            const response = await fetch(`${API_BASE_URL}/get_objects.php`);
            const data = await response.json();
            if (data.success) {
                this.objects = data.data;
                return data.data;
            }
            throw new Error(data.error);
        } catch (error) {
            console.error('Erreur lors de la récupération des objets:', error);
        }
    },
    
    async updateObjectState(objectId, newState) {
        try {
            const response = await fetch(`${API_BASE_URL}/update_object.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: objectId, etat: newState })
            });
            const data = await response.json();
            if (data.success) {
                this.objects = this.objects.map(obj =>
                    obj.id === objectId ? { ...obj, etat: newState } : obj
                );
                return true;
            }
            throw new Error(data.error);
        } catch (error) {
            console.error('Erreur mise à jour:', error);
        }
    },

    async deleteObject(objectId) {
        try {
            const response = await fetch(`${API_BASE_URL}/delete_object.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: objectId })
            });
            const data = await response.json();
            if (data.success) {
                this.objects = this.objects.filter(obj => obj.id !== objectId);
                return true;
            }
            throw new Error(data.error);
        } catch (error) {
            console.error('Erreur suppression:', error);
        }
    }
};

const UI = {
    init() {
        this.refreshObjects();
        setInterval(() => this.refreshObjects(), 30000);

        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-object')) {
                this.handleDeleteObject(e.target.dataset.objectId);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('.object-state-toggle')) {
                this.handleStateChange(e.target.dataset.objectId, e.target.checked ? 'on' : 'off');
            }
        });
    },

    async refreshObjects() {
        const objects = await store.fetchObjects();
        this.renderObjects(objects);
    },

    renderObjects(objects) {
        const container = document.getElementById('objectsList');
        if (!container) return;
        
        container.innerHTML = objects.map(obj => `
            <div class="p-6 bg-white rounded-lg shadow-md">
                <h3 class="text-xl font-semibold">${obj.nom}</h3>
                <p class="text-gray-600">${obj.description}</p>
                <div class="mt-4 flex justify-between items-center">
                    <label class="flex items-center">
                        <input type="checkbox" class="object-state-toggle"
                               data-object-id="${obj.id}" ${obj.etat === 'on' ? 'checked' : ''}>
                        <span class="ml-2">${obj.etat === 'on' ? 'Allumé' : 'Éteint'}</span>
                    </label>
                    <button class="delete-object bg-red-600 text-white px-3 py-1 rounded"
                            data-object-id="${obj.id}">
                        Supprimer
                    </button>
                </div>
            </div>
        `).join('');
    },

    async handleStateChange(objectId, newState) {
        try {
            await store.updateObjectState(objectId, newState);
            this.showSuccess('État mis à jour');
        } catch (error) {
            this.showError('Erreur mise à jour');
            this.refreshObjects();
        }
    },

    async handleDeleteObject(objectId) {
        if (!confirm('Confirmer suppression ?')) return;
        try {
            await store.deleteObject(objectId);
            this.showSuccess('Objet supprimé');
            this.refreshObjects();
        } catch (error) {
            this.showError('Erreur suppression');
        }
    },

    showSuccess(message) { console.log('✔️ ' + message); },
    showError(message) { console.error('❌ ' + message); }
};
