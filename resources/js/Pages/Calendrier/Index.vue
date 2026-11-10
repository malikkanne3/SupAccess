<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import Calendar from '../../Components/Calendar.vue';

const props = defineProps({ equipements: Array, global: Boolean });
const choix = ref('');
const courant = computed(() => props.equipements.find((e) => e.id === Number(choix.value)));
</script>

<template>
    <Head title="Calendrier" />
    <div class="ph">
        <div>
            <h1>{{ global ? 'Calendrier global' : 'Calendrier des disponibilités' }}</h1>
            <p>{{ global ? "Toutes les réservations, y compris celles en attente de décision." : "Choisissez un équipement pour voir, jour par jour, les places encore libres." }}</p>
        </div>
        <select v-model="choix" class="in" style="min-width: 260px; width: auto" aria-label="Équipement">
            <option value="">Tous les équipements</option>
            <option v-for="e in equipements" :key="e.id" :value="e.id">{{ e.nom }}</option>
        </select>
    </div>
    <div class="card"><Calendar :equipement-id="choix || null" :capacite="courant ? courant.capacite : null" :global="global" /></div>
</template>
