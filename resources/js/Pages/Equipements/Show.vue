<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { fmtDate, fmtRange } from '../../utils';

const props = defineProps({ equipement: Object, historique: Array, pannes: Array });
const role = usePage().props.auth.user.role;
const e = computed(() => props.equipement);

const panneOuverte = ref(false);
const supprOuverte = ref(false);
const panne = useForm({ quantite: 1, description: '' });
const saines = computed(() => Math.max(0, e.value.quantite_totale - e.value.quantite_en_panne));

const declarer = () => panne.post(`/equipements/${e.value.id}/pannes`, { onSuccess: () => { panneOuverte.value = false; panne.reset(); } });
const supprimer = () => router.delete(`/equipements/${e.value.id}`);
const resoudre = (id) => router.post(`/pannes/${id}/resoudre`, {}, { preserveScroll: true });
</script>

<template>
    <Head :title="e.nom" />
    <div class="ph">
        <div>
            <Link href="/equipements" class="small b">Retour au catalogue</Link>
            <h1 style="margin-top: 6px">{{ e.nom }}</h1>
            <p>{{ e.categorie?.nom || 'Sans catégorie' }}</p>
        </div>
        <div class="actions">
            <template v-if="role === 'gestionnaire'">
                <button class="btn btn-ghost" @click="panneOuverte = true" :disabled="!saines"><Icon name="alert" :size="18" /> Déclarer une panne</button>
                <Link :href="`/equipements/${e.id}/modifier`" class="btn btn-ghost"><Icon name="edit" :size="18" /> Modifier</Link>
                <button class="btn btn-red" @click="supprOuverte = true"><Icon name="trash" :size="18" /> Supprimer</button>
            </template>
            <Link v-else :href="`/reservations/nouvelle?equipement=${e.id}`" class="btn btn-gold" :class="{ 'btn-ghost': e.etat === 'PANNE' }"><Icon name="calendar" :size="18" /> Réserver cet équipement</Link>
        </div>
    </div>

    <div class="grid g-main">
        <div>
            <div class="hero-img"><img v-if="e.image_url" :src="e.image_url" :alt="e.nom" /><Icon v-else name="box" :size="90" /></div>
            <div class="card mt">
                <h3>Historique d'utilisation</h3>
                <div v-if="!historique.length" class="empty">Cet équipement n'a pas encore été utilisé.</div>
                <div v-else class="tbl-wrap">
                    <table class="tbl">
                        <thead><tr><th>Période</th><th v-if="role === 'gestionnaire'">Demandeur</th><th>Service</th><th>Qté</th><th>Statut</th></tr></thead>
                        <tbody>
                            <tr v-for="h in historique" :key="h.id">
                                <td>{{ fmtRange(h.date_debut, h.date_fin) }}</td>
                                <td v-if="role === 'gestionnaire'">{{ h.demandeur }}</td>
                                <td>{{ h.service || '—' }}</td>
                                <td>{{ h.quantite }}</td>
                                <td><StatusBadge :s="h.statut" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="row between"><h3 style="margin: 0">Disponibilité</h3><StatusBadge :s="e.etat" /></div>
                <div style="font-size: 40px; font-weight: 800; letter-spacing: -.02em; margin-top: 10px">{{ e.quantite_disponible }}<span class="muted" style="font-size: 20px"> / {{ e.quantite_totale }}</span></div>
                <div class="muted small">exemplaire(s) libre(s) à l'instant</div>
                <dl class="kv mt">
                    <dt>Stock total</dt><dd>{{ e.quantite_totale }}</dd>
                    <dt>En panne</dt><dd>{{ e.quantite_en_panne }}</dd>
                    <dt>Catégorie</dt><dd>{{ e.categorie?.nom || '—' }}</dd>
                </dl>
            </div>
            <div class="card mt"><h3>Description</h3><p class="muted">{{ e.description || 'Aucune description.' }}</p></div>

            <div v-if="role === 'gestionnaire'" class="card mt">
                <h3>Pannes</h3>
                <div v-if="!pannes.length" class="muted small">Aucune panne enregistrée.</div>
                <div class="list">
                    <div v-for="p in pannes" :key="p.id">
                        <div class="row between"><span class="b">{{ p.quantite }} exemplaire(s)</span><span class="chip" :class="p.resolue ? 'green' : 'red'">{{ p.resolue ? 'Résolue' : 'En cours' }}</span></div>
                        <div class="small muted">{{ p.description }}</div>
                        <div class="small muted">Déclarée le {{ fmtDate(p.declaree) }}</div>
                        <button v-if="!p.resolue" class="btn btn-soft btn-sm mt-s" @click="resoudre(p.id)"><Icon name="check" :size="15" /> Marquer résolue</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal v-if="panneOuverte" titre="Déclarer une panne" :sous="`${e.nom} : ${saines} exemplaire(s) en état de marche.`" @close="panneOuverte = false">
        <form @submit.prevent="declarer">
            <div class="fld"><label class="f">Exemplaires concernés</label><input v-model="panne.quantite" type="number" min="1" :max="saines" class="in" /><div v-if="panne.errors.quantite" class="err">{{ panne.errors.quantite }}</div></div>
            <div class="fld"><label class="f">Description de la panne</label><textarea v-model="panne.description" class="in" placeholder="Ex. Lampe grillée, écran fissuré…" /><div v-if="panne.errors.description" class="err">{{ panne.errors.description }}</div></div>
            <div class="hint">Les réservations acceptées qui ne seront plus couvertes par le stock vous seront signalées : vous déciderez de les annuler ou de proposer un autre équipement.</div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="panneOuverte = false">Annuler</button><button class="btn btn-red" :disabled="panne.processing">Enregistrer la panne</button></div>
        </form>
    </Modal>

    <Modal v-if="supprOuverte" titre="Supprimer cet équipement ?" sous="Cette action est définitive. Un équipement avec un historique de réservations ne peut pas être supprimé." @close="supprOuverte = false">
        <div class="foot"><button class="btn btn-ghost" @click="supprOuverte = false">Garder</button><button class="btn btn-red" @click="supprimer">Supprimer</button></div>
    </Modal>
</template>
