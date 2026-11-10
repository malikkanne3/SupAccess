<script setup>
import { Head, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import { depuis } from '../../utils';

defineProps({ notifications: Array });
const ICONES = { info: 'bell', success: 'check-circle', warning: 'alert', danger: 'alert' };
const ouvrir = (n) => router.post(`/notifications/${n.id}/lire`, {}, { preserveScroll: true });
const toutLire = () => router.post('/notifications/tout-lire', {}, { preserveScroll: true });
</script>

<template>
    <Head title="Notifications" />
    <div class="ph">
        <div><h1>Notifications</h1><p>Décisions, rappels et alertes liés à vos réservations.</p></div>
        <button v-if="notifications.some((n) => !n.lu)" class="btn btn-ghost" @click="toutLire"><Icon name="check" :size="18" /> Tout marquer comme lu</button>
    </div>

    <div v-if="!notifications.length" class="empty card"><Icon name="bell" :size="40" /><b>Aucune notification</b>Vous serez prévenu ici à chaque événement important.</div>
    <button v-for="n in notifications" :key="n.id" class="notif" :class="[n.type, { new: !n.lu }]" @click="ouvrir(n)">
        <span class="ic"><Icon :name="ICONES[n.type] || 'bell'" :size="19" /></span>
        <span style="flex: 1"><span :class="{ b: !n.lu }">{{ n.message }}</span><br /><span class="small muted">{{ depuis(n.date) }}</span></span>
        <span v-if="!n.lu" class="chip gold">Nouveau</span>
    </button>
</template>
