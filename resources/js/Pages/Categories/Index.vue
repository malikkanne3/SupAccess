<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';

defineProps({ categories: Array });
const ajout = useForm({ nom: '', description: '' });
const edition = useForm({ id: null, nom: '', description: '' });
const ouverte = ref(false);
const aSupprimer = ref(null);

const ajouter = () => ajout.post('/categories', { preserveScroll: true, onSuccess: () => ajout.reset() });
const ouvrir = (c) => { edition.id = c.id; edition.nom = c.nom; edition.description = c.description || ''; edition.clearErrors(); ouverte.value = true; };
const enregistrer = () => edition.put(`/categories/${edition.id}`, { preserveScroll: true, onSuccess: () => (ouverte.value = false) });
const supprimer = () => router.delete(`/categories/${aSupprimer.value.id}`, { preserveScroll: true, onFinish: () => (aSupprimer.value = null) });
</script>

<template>
    <Head title="Catégories" />
    <div class="ph"><div><h1>Catégories</h1><p>Classez les équipements pour faciliter la recherche et les rapports.</p></div></div>

    <div class="grid g-main">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Nom</th><th>Description</th><th>Équipements</th><th /></tr></thead>
                <tbody>
                    <tr v-if="!categories.length"><td colspan="4" class="empty">Aucune catégorie : ajoutez la première.</td></tr>
                    <tr v-for="c in categories" :key="c.id">
                        <td class="b">{{ c.nom }}</td>
                        <td class="muted">{{ c.description || '—' }}</td>
                        <td><span class="chip">{{ c.equipements_count }}</span></td>
                        <td style="text-align: right; white-space: nowrap">
                            <button class="btn btn-ghost btn-sm" aria-label="Modifier" @click="ouvrir(c)"><Icon name="edit" :size="15" /></button>
                            <button class="btn btn-ghost btn-sm" aria-label="Supprimer" @click="aSupprimer = c"><Icon name="trash" :size="15" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form class="card" @submit.prevent="ajouter" novalidate>
            <h3>Nouvelle catégorie</h3>
            <div class="fld"><label class="f" for="cn">Nom</label><input id="cn" v-model="ajout.nom" class="in" :class="{ bad: ajout.errors.nom }" placeholder="Ex. Audiovisuel" /><div v-if="ajout.errors.nom" class="err">{{ ajout.errors.nom }}</div></div>
            <div class="fld"><label class="f" for="cd">Description</label><textarea id="cd" v-model="ajout.description" class="in" /></div>
            <button class="btn btn-block" :disabled="ajout.processing"><Icon name="plus" :size="18" /> Ajouter</button>
        </form>
    </div>

    <Modal v-if="ouverte" titre="Modifier la catégorie" @close="ouverte = false">
        <form @submit.prevent="enregistrer">
            <div class="fld"><label class="f">Nom</label><input v-model="edition.nom" class="in" :class="{ bad: edition.errors.nom }" /><div v-if="edition.errors.nom" class="err">{{ edition.errors.nom }}</div></div>
            <div class="fld"><label class="f">Description</label><textarea v-model="edition.description" class="in" /></div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="ouverte = false">Annuler</button><button class="btn" :disabled="edition.processing">Enregistrer</button></div>
        </form>
    </Modal>

    <Modal v-if="aSupprimer" titre="Supprimer cette catégorie ?" :sous="`« ${aSupprimer.nom } » sera supprimée. Une catégorie qui contient des équipements ne peut pas l'être.`" @close="aSupprimer = null">
        <div class="foot"><button class="btn btn-ghost" @click="aSupprimer = null">Garder</button><button class="btn btn-red" @click="supprimer">Supprimer</button></div>
    </Modal>
</template>
