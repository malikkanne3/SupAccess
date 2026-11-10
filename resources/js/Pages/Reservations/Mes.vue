<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { fmtRange, fmtDT } from '../../utils';

const props = defineProps({ reservations: Array, limite: Number, actives: Number });
const onglet = ref('cours');
const aAnnuler = ref(null);
const ENCOURS = ['EN_ATTENTE', 'ACCEPTEE', 'TERMINEE', 'ALTERNATIVE'];

const liste = computed(() => props.reservations.filter((r) => (onglet.value === 'cours' ? ENCOURS.includes(r.statut) : !ENCOURS.includes(r.statut))));
const nbCours = computed(() => props.reservations.filter((r) => ENCOURS.includes(r.statut)).length);

const annuler = () => router.post(`/reservations/${aAnnuler.value.id}/annuler`, {}, { preserveScroll: true, onFinish: () => (aAnnuler.value = null) });
const repondre = (r, accepte) => router.post(`/reservations/${r.id}/alternative`, { accepte }, { preserveScroll: true });
</script>

<template>
    <Head title="Mes réservations" />
    <div class="ph">
        <div><h1>Mes réservations</h1><p>Suivez le statut de vos demandes. {{ actives }} réservation(s) active(s) sur {{ limite }} autorisée(s).</p></div>
        <Link href="/reservations/nouvelle" class="btn btn-gold"><Icon name="plus" :size="18" /> Nouvelle réservation</Link>
    </div>

    <div class="tabs">
        <button :class="{ on: onglet === 'cours' }" @click="onglet = 'cours'">En cours <em>{{ nbCours }}</em></button>
        <button :class="{ on: onglet === 'historique' }" @click="onglet = 'historique'">Historique</button>
    </div>

    <div v-if="!liste.length" class="empty card">
        <Icon name="clipboard" :size="40" /><b>{{ onglet === 'cours' ? 'Aucune réservation en cours' : "Pas encore d'historique" }}</b>
        {{ onglet === 'cours' ? 'Réservez un équipement pour le retrouver ici.' : 'Les réservations terminées, refusées ou annulées apparaîtront ici.' }}
    </div>

    <div v-for="r in liste" :key="r.id" class="req" :class="{ warn: r.statut === 'ALTERNATIVE' || r.statut === 'TERMINEE', bad: r.statut === 'REFUSEE' }">
        <div class="req-h">
            <div><div class="b" style="font-size: 17px">{{ r.equipement.nom }} <span class="muted">×{{ r.quantite }}</span></div><div class="small muted">{{ fmtRange(r.date_debut, r.date_fin) }}</div></div>
            <StatusBadge :s="r.statut" />
        </div>
        <div class="small"><span class="muted">Motif :</span> {{ r.motif }}</div>
        <div v-if="r.motif_refus" class="small" style="color: var(--red); margin-top: 4px"><b>Motif communiqué :</b> {{ r.motif_refus }}</div>
        <div v-if="r.statut === 'TERMINEE'" class="small mt-s"><Icon name="return" :size="15" /> Merci de rapporter l'équipement au gestionnaire (fin prévue le {{ fmtDT(r.date_fin) }}).</div>
        <div v-if="r.statut === 'RENDUE'" class="small muted mt-s">Rendu le {{ fmtDT(r.rendu_at) }}.</div>

        <div v-if="r.statut === 'ALTERNATIVE' && r.propose" class="ia ALTERNATIVE">
            <div class="tag"><Icon name="swap" :size="18" /></div>
            <div><b>Le gestionnaire vous propose « {{ r.propose.nom }} »</b><div class="small">à la place de {{ r.equipement.nom }}, sur la même période. Si vous refusez, la réservation est annulée.</div></div>
        </div>

        <div class="btns">
            <template v-if="r.statut === 'ALTERNATIVE'">
                <button class="btn btn-green btn-sm" @click="repondre(r, true)"><Icon name="check" :size="15" /> Accepter l'alternative</button>
                <button class="btn btn-ghost btn-sm" @click="repondre(r, false)">Refuser</button>
            </template>
            <Link v-if="r.peut_modifier" :href="`/reservations/${r.id}/modifier`" class="btn btn-ghost btn-sm"><Icon name="edit" :size="15" /> Modifier</Link>
            <button v-if="r.peut_annuler" class="btn btn-ghost btn-sm" style="color: var(--red)" @click="aAnnuler = r"><Icon name="x" :size="15" /> Annuler la réservation</button>
        </div>
    </div>

    <Modal v-if="aAnnuler" titre="Annuler cette réservation ?" :sous="`${aAnnuler.equipement.nom} — ${fmtRange(aAnnuler.date_debut, aAnnuler.date_fin)}`" @close="aAnnuler = null">
        <div class="foot"><button class="btn btn-ghost" @click="aAnnuler = null">Conserver</button><button class="btn btn-red" @click="annuler">Annuler la réservation</button></div>
    </Modal>
</template>
