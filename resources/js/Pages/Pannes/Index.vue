<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';
import { fmtDate } from '../../utils';

const props = defineProps({ pannes: Array, equipements: Array });
const ouverte = ref(false);
const form = useForm({ equipement_id: '', quantite: 1, description: '' });

const maxi = () => props.equipements.find((e) => e.id === form.equipement_id)?.sain ?? 1;
const declarer = () => {
    if (!form.equipement_id) { form.setError('equipement_id', 'Choisissez un équipement.'); return; }
    form.transform(({ equipement_id, ...d }) => d).post(`/equipements/${form.equipement_id}/pannes`, {
        preserveScroll: true,
        onSuccess: () => { ouverte.value = false; form.reset(); },
    });
};
const resoudre = (id) => router.post(`/pannes/${id}/resoudre`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Pannes" />
    <div class="ph">
        <div><h1>Pannes</h1><p>Une panne réduit le stock réservable. Si elle touche une réservation acceptée, c'est à vous de décider : annuler ou proposer un autre équipement.</p></div>
        <button class="btn btn-gold" @click="ouverte = true"><Icon name="alert" :size="18" /> Déclarer une panne</button>
    </div>

    <div class="tbl-wrap">
        <table class="tbl">
            <thead><tr><th>Équipement</th><th>Exemplaires</th><th>Description</th><th>Déclarée</th><th>État</th><th /></tr></thead>
            <tbody>
                <tr v-if="!pannes.length"><td colspan="6" class="empty"><Icon name="check-circle" :size="36" /><b>Aucune panne enregistrée</b>Le parc fonctionne normalement.</td></tr>
                <tr v-for="p in pannes" :key="p.id">
                    <td class="b"><Link :href="`/equipements/${p.equipement_id}`">{{ p.equipement }}</Link></td>
                    <td>{{ p.quantite }}</td>
                    <td>{{ p.description }}</td>
                    <td>{{ fmtDate(p.declaree) }}</td>
                    <td><span class="chip" :class="p.resolue ? 'green' : 'red'">{{ p.resolue ? 'Résolue' : 'En cours' }}</span></td>
                    <td style="text-align: right"><button v-if="!p.resolue" class="btn btn-soft btn-sm" @click="resoudre(p.id)"><Icon name="check" :size="15" /> Résolue</button></td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal v-if="ouverte" titre="Déclarer une panne" @close="ouverte = false">
        <form @submit.prevent="declarer">
            <div class="fld">
                <label class="f">Équipement</label>
                <select v-model="form.equipement_id" class="in" :class="{ bad: form.errors.equipement_id }">
                    <option value="" disabled>Choisir…</option>
                    <option v-for="e in equipements" :key="e.id" :value="e.id" :disabled="!e.sain">{{ e.nom }} ({{ e.sain }} en état de marche)</option>
                </select>
                <div v-if="form.errors.equipement_id" class="err">{{ form.errors.equipement_id }}</div>
            </div>
            <div class="fld"><label class="f">Exemplaires concernés</label><input v-model="form.quantite" type="number" min="1" :max="maxi()" class="in" /><div v-if="form.errors.quantite" class="err">{{ form.errors.quantite }}</div></div>
            <div class="fld"><label class="f">Description</label><textarea v-model="form.description" class="in" /><div v-if="form.errors.description" class="err">{{ form.errors.description }}</div></div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="ouverte = false">Annuler</button><button class="btn btn-red" :disabled="form.processing">Enregistrer la panne</button></div>
        </form>
    </Modal>
</template>
