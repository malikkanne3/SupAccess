<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { fmtRange, fmtDT } from '../../utils';

const props = defineProps({ vue: String, reservations: Array, compteurs: Object, q: String });
const recherche = ref(props.q || '');
const refus = useForm({ motif_refus: '' });
const alt = useForm({ equipement_id: '' });
const cible = ref(null);
const mode = ref(null); // 'refus' | 'alternative'
const choix = ref([]);
const chargement = ref(false);

let t;
watch(recherche, (v) => { clearTimeout(t); t = setTimeout(() => router.get('/demandes', { vue: props.vue, q: v || undefined }, { preserveState: true, replace: true }), 300); });
const aller = (v) => router.get('/demandes', { vue: v, q: recherche.value || undefined }, { preserveState: true });

const action = (r, nom) => router.post(`/demandes/${r.id}/${nom}`, {}, { preserveScroll: true });
const ouvrirRefus = (r) => { cible.value = r; mode.value = 'refus'; refus.reset(); refus.clearErrors(); };
const envoyerRefus = () => refus.post(`/demandes/${cible.value.id}/refuser`, { preserveScroll: true, onSuccess: () => (mode.value = null) });

async function ouvrirAlternative(r, preselection = '') {
    cible.value = r; mode.value = 'alternative'; alt.reset(); alt.clearErrors(); choix.value = []; chargement.value = true;
    try {
        const rep = await fetch(`/demandes/${r.id}/alternatives`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        choix.value = rep.ok ? await rep.json() : [];
    } catch { choix.value = []; }
    chargement.value = false;
    if (preselection) alt.equipement_id = preselection;
}
const envoyerAlt = () => alt.post(`/demandes/${cible.value.id}/alternative`, { preserveScroll: true, onSuccess: () => (mode.value = null) });
const fermer = () => (mode.value = null);

const ONGLETS = [['attente', 'En attente'], ['cours', 'En cours'], ['retours', 'Retours'], ['toutes', 'Toutes']];
</script>

<template>
    <Head title="Demandes" />
    <div class="ph">
        <div><h1>Demandes de réservation</h1><p>L'IA recommande, vous décidez. Chaque décision notifie le demandeur par e-mail.</p></div>
        <div class="search" style="min-width: 260px"><Icon name="search" :size="18" /><input v-model="recherche" class="in" placeholder="Demandeur ou équipement" aria-label="Rechercher" /></div>
    </div>

    <div class="tabs">
        <button v-for="o in ONGLETS" :key="o[0]" :class="{ on: vue === o[0] }" @click="aller(o[0])">{{ o[1] }} <em v-if="compteurs[o[0]]">{{ compteurs[o[0]] }}</em></button>
    </div>

    <div v-if="!reservations.length" class="empty card"><Icon name="check-circle" :size="40" /><b>Rien à afficher ici</b>Aucune réservation dans cette vue.</div>

    <template v-else-if="vue !== 'toutes'">
        <div v-for="r in reservations" :key="r.id" class="req" :class="{ bad: r.impactee, warn: r.statut === 'TERMINEE' || r.statut === 'ALTERNATIVE' }">
            <div class="req-h">
                <div>
                    <div class="b" style="font-size: 17px">{{ r.equipement.nom }} <span class="muted">×{{ r.quantite }}</span></div>
                    <div class="small muted">{{ fmtRange(r.date_debut, r.date_fin) }}</div>
                </div>
                <StatusBadge :s="r.statut" />
            </div>
            <div class="small"><b>{{ r.demandeur }}</b><span v-if="r.service" class="muted"> ({{ r.service }})</span> <span class="muted">— {{ r.email }}</span></div>
            <div class="small" style="margin-top: 4px"><span class="muted">Motif :</span> {{ r.motif }}</div>

            <div v-if="r.impactee" class="banner red mt-s"><Icon name="alert" /><div><b>Réservation impactée par une panne.</b> Le stock ne couvre plus cette demande : annulez-la ou proposez un autre équipement.</div></div>
            <div v-if="r.statut === 'ALTERNATIVE'" class="small mt-s"><Icon name="swap" :size="15" /> Alternative « {{ r.propose?.nom }} » proposée : en attente de la réponse du demandeur.</div>
            <div v-if="r.statut === 'TERMINEE'" class="small mt-s" style="color: var(--purple)"><b>Non rendu depuis {{ r.retard_heures >= 48 ? Math.round(r.retard_heures / 24) + ' jours' : r.retard_heures + ' h' }}</b> (fin prévue le {{ fmtDT(r.date_fin) }}).</div>

            <div v-if="r.ia" class="ia" :class="r.ia.type">
                <div class="tag"><Icon name="sparkle" :size="18" /></div>
                <div><b>Recommandation IA : {{ r.ia.libelle }}</b><div class="small">{{ r.ia.contenu }}</div></div>
            </div>

            <div class="btns">
                <template v-if="r.statut === 'EN_ATTENTE'">
                    <button class="btn btn-green btn-sm" @click="action(r, 'accepter')"><Icon name="check" :size="15" /> Accepter</button>
                    <button class="btn btn-red btn-sm" @click="ouvrirRefus(r)"><Icon name="x" :size="15" /> Refuser</button>
                    <button class="btn btn-soft btn-sm" @click="ouvrirAlternative(r, r.ia?.alternative_id || '')"><Icon name="swap" :size="15" /> Proposer un autre équipement</button>
                </template>
                <template v-if="r.statut === 'ACCEPTEE'">
                    <button class="btn btn-soft btn-sm" @click="ouvrirAlternative(r)"><Icon name="swap" :size="15" /> Proposer un autre équipement</button>
                    <button class="btn btn-ghost btn-sm" style="color: var(--red)" @click="ouvrirRefus(r)">Annuler la réservation</button>
                </template>
                <template v-if="r.peut_rendre">
                    <button class="btn btn-green btn-sm" @click="action(r, 'rendu')"><Icon name="return" :size="15" /> Marquer comme rendu</button>
                    <button v-if="r.statut === 'TERMINEE'" class="btn btn-gold btn-sm" @click="action(r, 'relancer')"><Icon name="mail" :size="15" /> Relancer le demandeur</button>
                </template>
            </div>
        </div>
    </template>

    <div v-else class="tbl-wrap">
        <table class="tbl">
            <thead><tr><th>Équipement</th><th>Demandeur</th><th>Période</th><th>Qté</th><th>Statut</th></tr></thead>
            <tbody>
                <tr v-for="r in reservations" :key="r.id">
                    <td class="b">{{ r.equipement.nom }}</td><td>{{ r.demandeur }}</td><td>{{ fmtRange(r.date_debut, r.date_fin) }}</td><td>{{ r.quantite }}</td><td><StatusBadge :s="r.statut" /></td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal v-if="mode === 'refus'" :titre="cible.statut === 'EN_ATTENTE' ? 'Refuser la demande' : 'Annuler la réservation'" :sous="`${cible.equipement.nom} — ${cible.demandeur}`" @close="fermer">
        <form @submit.prevent="envoyerRefus">
            <div class="fld"><label class="f">Motif communiqué au demandeur</label><textarea v-model="refus.motif_refus" class="in" :class="{ bad: refus.errors.motif_refus }" placeholder="Ex. Équipement indisponible pour maintenance" /><div v-if="refus.errors.motif_refus" class="err">{{ refus.errors.motif_refus }}</div></div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="fermer">Retour</button><button class="btn btn-red" :disabled="refus.processing">Confirmer</button></div>
        </form>
    </Modal>

    <Modal v-if="mode === 'alternative'" titre="Proposer un autre équipement" :sous="`Équipements libres sur la période de ${cible.demandeur}. Le demandeur devra confirmer.`" @close="fermer">
        <form @submit.prevent="envoyerAlt">
            <div v-if="chargement" class="muted">Recherche des équipements libres…</div>
            <div v-else-if="!choix.length" class="banner red"><Icon name="alert" /><div>Aucun autre équipement n'est libre en quantité suffisante sur cette période.</div></div>
            <div v-else class="fld">
                <label class="f">Équipement de remplacement</label>
                <select v-model="alt.equipement_id" class="in" :class="{ bad: alt.errors.equipement_id }">
                    <option value="" disabled>Choisir…</option>
                    <option v-for="c in choix" :key="c.id" :value="c.id">{{ c.nom }} ({{ c.disponible }} libre(s)){{ c.meme_categorie ? ' — même catégorie' : '' }}</option>
                </select>
                <div v-if="alt.errors.equipement_id" class="err">{{ alt.errors.equipement_id }}</div>
            </div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="fermer">Retour</button><button class="btn" :disabled="alt.processing || !choix.length">Envoyer la proposition</button></div>
        </form>
    </Modal>
</template>
