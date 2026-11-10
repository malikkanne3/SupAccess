<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

const props = defineProps({ equipements: Array, categories: Array, filtres: Object });
const role = usePage().props.auth.user.role;
const f = reactive({ q: props.filtres.q || '', categorie: props.filtres.categorie || '', etat: props.filtres.etat || '' });

let t;
watch(f, () => {
    clearTimeout(t);
    t = setTimeout(() => {
        const params = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== ''));
        router.get('/equipements', params, { preserveState: true, preserveScroll: true, replace: true });
    }, 250);
});

const ratio = (e) => (e.quantite_totale ? (e.quantite_disponible / e.quantite_totale) * 100 : 0);
const classe = (e) => (e.quantite_disponible === 0 ? 'bad' : ratio(e) <= 40 ? 'warn' : '');
</script>

<template>
    <Head title="Équipements" />
    <div class="ph">
        <div><h1>Équipements</h1><p>{{ equipements.length }} équipement(s) dans le catalogue partagé.</p></div>
        <Link v-if="role === 'gestionnaire'" href="/equipements/creer" class="btn btn-gold"><Icon name="plus" :size="18" /> Nouvel équipement</Link>
    </div>

    <div class="filters">
        <div class="search"><Icon name="search" :size="18" /><input v-model="f.q" class="in" placeholder="Rechercher un équipement" aria-label="Rechercher" /></div>
        <select v-model="f.categorie" class="in" aria-label="Catégorie">
            <option value="">Toutes les catégories</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nom }}</option>
        </select>
        <select v-model="f.etat" class="in" aria-label="État">
            <option value="">Tous les états</option>
            <option value="DISPO">Disponible</option>
            <option value="RESERVE">Réservé</option>
            <option value="PANNE">En panne</option>
        </select>
    </div>

    <div v-if="!equipements.length" class="empty card">
        <Icon name="box" :size="40" /><b>Aucun équipement ne correspond</b>Modifiez vos filtres ou ajoutez un équipement.
    </div>

    <div class="eq-grid">
        <article v-for="e in equipements" :key="e.id" class="eq">
            <div class="pic">
                <img v-if="e.image_url" :src="e.image_url" :alt="e.nom" loading="lazy" />
                <Icon v-else name="box" :size="54" />
                <StatusBadge :s="e.etat" />
            </div>
            <div class="body">
                <div>
                    <h3>{{ e.nom }}</h3>
                    <div class="small muted">{{ e.categorie?.nom || 'Sans catégorie' }}</div>
                </div>
                <div>
                    <div class="meter" :class="classe(e)"><i :style="{ width: ratio(e) + '%' }" /></div>
                    <div class="small muted" style="margin-top: 6px"><b style="color: var(--ink)">{{ e.quantite_disponible }}</b> disponible(s) sur {{ e.quantite_totale }}<span v-if="e.quantite_en_panne"> ({{ e.quantite_en_panne }} en panne)</span></div>
                </div>
                <div class="foot">
                    <Link :href="`/equipements/${e.id}`" class="btn btn-ghost btn-sm" style="flex: 1">Détails</Link>
                    <Link v-if="role === 'demandeur'" :href="`/reservations/nouvelle?equipement=${e.id}`" class="btn btn-sm" :class="{ 'btn-ghost': e.etat === 'PANNE' }" style="flex: 1" :aria-disabled="e.etat === 'PANNE'">Réserver</Link>
                </div>
            </div>
        </article>
    </div>
</template>
