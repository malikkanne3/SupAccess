<script setup>
import { computed } from 'vue';

const props = defineProps({ s: { type: String, required: true } });

const MAP = {
    EN_ATTENTE: ['En attente', 'amber'],
    ACCEPTEE: ['Acceptée', 'green'],
    REFUSEE: ['Refusée', 'red'],
    TERMINEE: ['Terminée, à rendre', 'purple'],
    RENDUE: ['Rendue', 'blue'],
    ANNULEE: ['Annulée', 'gray'],
    ALTERNATIVE: ['Alternative proposée', 'gold'],
    DISPO: ['Disponible', 'green'],
    RESERVE: ['Réservé', 'amber'],
    PANNE: ['En panne', 'red'],
};
const info = computed(() => MAP[props.s] || [props.s, 'gray']);
</script>

<template>
    <span class="chip" :class="info[1] === 'blue' ? '' : info[1]">{{ info[0] }}</span>
</template>
