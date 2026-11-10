<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';
import { addDays, fmtTime, parse, startOfWeek, ymd } from '../utils';

const props = defineProps({
    equipementId: { type: [Number, String], default: null },
    capacite: { type: Number, default: null }, // stock réservable de l'équipement filtré → affiche le nombre de places libres par jour
    global: { type: Boolean, default: false },
});

const mode = ref('mois');
const curseur = ref(new Date());
const evenements = ref([]);
const chargement = ref(false);
const aujourdhui = ymd(new Date());

const jours = computed(() => {
    const c = new Date(curseur.value);
    if (mode.value === 'jour') return [new Date(c.setHours(0, 0, 0, 0))];
    if (mode.value === 'semaine') return Array.from({ length: 7 }, (_, i) => addDays(startOfWeek(c), i));
    const premier = new Date(c.getFullYear(), c.getMonth(), 1);
    return Array.from({ length: 42 }, (_, i) => addDays(startOfWeek(premier), i));
});

const titre = computed(() => {
    const c = curseur.value;
    if (mode.value === 'mois') return c.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    if (mode.value === 'jour') return c.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const j = jours.value;
    return `${j[0].toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} – ${j[6].toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`;
});

async function charger() {
    const j = jours.value;
    const params = new URLSearchParams({ start: ymd(j[0]), end: ymd(addDays(j[j.length - 1], 1)) });
    if (props.equipementId) params.set('equipement_id', props.equipementId);
    chargement.value = true;
    try {
        const r = await fetch(`/calendrier/evenements?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        evenements.value = r.ok ? await r.json() : [];
    } catch {
        evenements.value = [];
    } finally {
        chargement.value = false;
    }
}

const dansJour = (j) => {
    const a = new Date(j).setHours(0, 0, 0, 0);
    const b = a + 86400000;
    return evenements.value.filter((e) => parse(e.debut).getTime() < b && parse(e.fin).getTime() > a);
};

const places = (j) => {
    if (props.capacite === null || !props.equipementId) return null;
    const prises = dansJour(j).filter((e) => ['ACCEPTEE', 'TERMINEE'].includes(e.statut)).reduce((s, e) => s + e.quantite, 0);
    return Math.max(0, props.capacite - prises);
};

const naviguer = (sens) => {
    const c = new Date(curseur.value);
    if (mode.value === 'mois') c.setMonth(c.getMonth() + sens);
    else c.setDate(c.getDate() + sens * (mode.value === 'semaine' ? 7 : 1));
    curseur.value = c;
};
const horsMois = (j) => mode.value === 'mois' && j.getMonth() !== curseur.value.getMonth();
const limite = computed(() => (mode.value === 'mois' ? 3 : 50));
const info = (e) => `${e.titre} ×${e.quantite}${e.par ? ` — ${e.par}` : ''} (${fmtTime(e.debut)} → ${fmtTime(e.fin)})`;
const JOURS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

onMounted(charger);
watch([jours, () => props.equipementId], charger);
</script>

<template>
    <div>
        <div class="cal-bar">
            <div class="row">
                <button class="btn btn-ghost btn-sm" aria-label="Précédent" @click="naviguer(-1)"><Icon name="left" :size="16" /></button>
                <button class="btn btn-ghost btn-sm" @click="curseur = new Date()">Aujourd'hui</button>
                <button class="btn btn-ghost btn-sm" aria-label="Suivant" @click="naviguer(1)"><Icon name="right" :size="16" /></button>
                <span class="cal-title">{{ titre }}</span>
            </div>
            <div class="tabs" style="margin: 0">
                <button v-for="m in ['mois', 'semaine', 'jour']" :key="m" :class="{ on: mode === m }" @click="mode = m">{{ m[0].toUpperCase() + m.slice(1) }}</button>
            </div>
        </div>

        <div class="cal-head" :class="mode" v-if="mode !== 'jour'"><div v-for="j in JOURS" :key="j">{{ j }}</div></div>
        <div class="cal-grid" :class="mode" :style="{ opacity: chargement ? 0.6 : 1 }">
            <div v-for="j in jours" :key="ymd(j)" class="cal-day" :class="{ out: horsMois(j), today: ymd(j) === aujourdhui }">
                <div class="top-row">
                    <span class="n">{{ j.getDate() }}</span>
                    <span v-if="mode !== 'mois'" class="muted small" style="margin-left: 8px">{{ j.toLocaleDateString('fr-FR', { weekday: 'long' }) }}</span>
                    <span v-if="places(j) !== null && ymd(j) >= aujourdhui" class="dispo" :class="places(j) > 0 ? 'ok' : 'none'">
                        {{ places(j) > 0 ? `${places(j)} libre${places(j) > 1 ? 's' : ''}` : 'complet' }}
                    </span>
                </div>
                <template v-for="(e, i) in dansJour(j)" :key="e.id">
                    <span v-if="i < limite" class="ev" :class="[e.statut, { mine: e.mine }]" :title="info(e)">
                        {{ e.titre }} ×{{ e.quantite }}
                        <small>{{ fmtTime(e.debut) }}–{{ fmtTime(e.fin) }}<template v-if="e.par"> · {{ e.par }}</template></small>
                    </span>
                </template>
                <span v-if="dansJour(j).length > limite" class="more">+{{ dansJour(j).length - limite }} autre(s)</span>
            </div>
        </div>

        <div class="legend">
            <span><i style="background: var(--blue)" />Acceptée</span>
            <span><i style="background: var(--purple)" />Terminée, à rendre</span>
            <span><i style="background: #7b8aa8" />Rendue</span>
            <span><i style="background: var(--gold-l); border: 1.5px dashed var(--gold-d)" />{{ global ? 'En attente de décision' : 'Ma demande en attente' }}</span>
            <span v-if="capacite !== null"><i style="background: var(--green-l)" />Places libres par jour</span>
        </div>
    </div>
</template>
