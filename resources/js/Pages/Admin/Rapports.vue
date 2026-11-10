<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ rapport: Object });
const p = reactive({ du: props.rapport.du, au: props.rapport.au });
const appliquer = () => router.get('/admin/rapports', { du: p.du, au: p.au }, { preserveState: true });
const exporter = () => { window.location.href = `/admin/rapports/export?du=${p.du}&au=${p.au}`; };
const couleur = (t) => (t >= 70 ? 'var(--red)' : t >= 40 ? 'var(--gold)' : 'var(--blue)');
</script>

<template>
    <Head title="Rapports" />
    <div class="ph">
        <div><h1>Rapports d'utilisation</h1><p>{{ rapport.jours }} jour(s) analysés. Taux d'utilisation = jours réservés ÷ jours de la période (par exemplaire en stock).</p></div>
        <div class="actions">
            <input v-model="p.du" type="date" class="in" style="width: auto" aria-label="Du" />
            <input v-model="p.au" type="date" class="in" style="width: auto" aria-label="Au" />
            <button class="btn" @click="appliquer">Appliquer</button>
            <button class="btn btn-gold" @click="exporter"><Icon name="download" :size="18" /> Exporter en CSV</button>
        </div>
    </div>

    <div class="grid g4">
        <div class="tile hero"><div><strong>{{ rapport.resume.demandes }}</strong><span>Demandes</span></div></div>
        <div class="tile green"><div><strong>{{ rapport.resume.acceptees }}</strong><span>Acceptées ({{ rapport.resume.taux_acceptation }} %)</span></div></div>
        <div class="tile red"><div><strong>{{ rapport.resume.refusees }}</strong><span>Refusées</span></div></div>
        <div class="tile"><div><strong>{{ rapport.resume.annulees }}</strong><span>Annulées</span></div></div>
    </div>

    <div class="grid g2 mt">
        <div class="card">
            <h3>Taux d'utilisation par catégorie</h3>
            <div v-if="!rapport.categories.length" class="empty">Pas de données.</div>
            <div v-for="c in rapport.categories" :key="c.nom" class="bar-row">
                <span>{{ c.nom }}</span>
                <div class="meter"><i :style="{ width: Math.max(2, c.taux_utilisation) + '%', background: couleur(c.taux_utilisation) }" /></div>
                <span class="v">{{ c.taux_utilisation }} %</span>
            </div>
        </div>
        <div class="card">
            <h3>Historique par service</h3>
            <div v-if="!rapport.services.length" class="empty">Aucune réservation sur la période.</div>
            <div v-else class="tbl-wrap">
                <table class="tbl"><thead><tr><th>Service</th><th>Réservations</th><th>Jours réservés</th></tr></thead>
                    <tbody><tr v-for="s in rapport.services" :key="s.nom"><td class="b">{{ s.nom }}</td><td>{{ s.reservations }}</td><td>{{ s.jours_reserves }}</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt">
        <h3>Classement des équipements</h3>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Équipement</th><th>Catégorie</th><th>Réservations</th><th>Jours réservés</th><th>Utilisation</th><th>Disponibilité</th></tr></thead>
                <tbody>
                    <tr v-for="e in rapport.equipements" :key="e.id">
                        <td class="b">{{ e.nom }}</td><td>{{ e.categorie }}</td><td>{{ e.reservations }}</td><td>{{ e.jours_reserves }}</td>
                        <td><span :class="e.taux_utilisation >= 70 ? 'num-bad' : ''">{{ e.taux_utilisation }} %</span></td>
                        <td><span class="num-good">{{ e.disponibilite }} %</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
