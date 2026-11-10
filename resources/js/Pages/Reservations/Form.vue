<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import { toInput } from '../../utils';

const props = defineProps({ reservation: Object, equipement_id: Number, equipements: Array, regles: Object });
const r = props.reservation;
const demain = new Date(Date.now() + 86400000);
const p2 = (n) => String(n).padStart(2, '0');
const defaut = (h) => `${demain.getFullYear()}-${p2(demain.getMonth() + 1)}-${p2(demain.getDate())}T${p2(h)}:00`;

const form = useForm({
    equipement_id: props.equipement_id || '',
    quantite: r?.quantite || 1,
    date_debut: r ? toInput(r.date_debut) : defaut(9),
    date_fin: r ? toInput(r.date_fin) : defaut(17),
    motif: r?.motif || '',
});
const dispo = ref(null);
const equipement = computed(() => props.equipements.find((e) => e.id === Number(form.equipement_id)));

let minuterie;
async function verifier() {
    if (!form.equipement_id) { dispo.value = null; return; }
    const p = new URLSearchParams({ debut: form.date_debut, fin: form.date_fin });
    if (r) p.set('exclure', r.id);
    try {
        const rep = await fetch(`/equipements/${form.equipement_id}/disponibilite?${p}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        dispo.value = rep.ok ? await rep.json() : null;
    } catch { dispo.value = null; }
}
watch(() => [form.equipement_id, form.date_debut, form.date_fin], () => { clearTimeout(minuterie); minuterie = setTimeout(verifier, 250); });
onMounted(verifier);

const etat = computed(() => {
    if (!dispo.value) return null;
    const q = Number(form.quantite) || 1;
    if (dispo.value.disponible === 0) return { c: 'red', t: "Aucun exemplaire disponible sur cette période. Essayez d'autres dates." };
    if (q > dispo.value.disponible) return { c: 'amber', t: `Seulement ${dispo.value.disponible} exemplaire(s) disponible(s) sur cette période.` };
    return { c: 'green', t: `${dispo.value.disponible} exemplaire(s) disponible(s) sur cette période : votre demande est possible.` };
});
const restant = computed(() => props.regles.limite - props.regles.actives);

const envoyer = () => (r ? form.put(`/reservations/${r.id}`) : form.post('/reservations'));
</script>

<template>
    <Head :title="r ? 'Modifier ma demande' : 'Nouvelle réservation'" />
    <div class="ph">
        <div><Link href="/mes-reservations" class="small b">Retour à mes réservations</Link><h1 style="margin-top: 6px">{{ r ? 'Modifier ma demande' : 'Nouvelle réservation' }}</h1><p>La demande sera examinée par le gestionnaire avant utilisation.</p></div>
    </div>

    <div class="grid g-main">
        <form class="card" @submit.prevent="envoyer" novalidate>
            <div class="fld">
                <label class="f" for="eq">Équipement</label>
                <select id="eq" v-model="form.equipement_id" class="in" :class="{ bad: form.errors.equipement_id }">
                    <option value="" disabled>Choisir un équipement…</option>
                    <option v-for="e in equipements" :key="e.id" :value="e.id" :disabled="e.en_panne_totale">{{ e.nom }}{{ e.en_panne_totale ? ' (en panne)' : ` — ${e.quantite_disponible}/${e.quantite_totale} libre(s)` }}</option>
                </select>
                <div v-if="form.errors.equipement_id" class="err">{{ form.errors.equipement_id }}</div>
            </div>
            <div class="cols2">
                <div class="fld"><label class="f" for="d1">Début</label><input id="d1" v-model="form.date_debut" type="datetime-local" class="in" :class="{ bad: form.errors.date_debut }" /><div v-if="form.errors.date_debut" class="err">{{ form.errors.date_debut }}</div></div>
                <div class="fld"><label class="f" for="d2">Fin</label><input id="d2" v-model="form.date_fin" type="datetime-local" class="in" :class="{ bad: form.errors.date_fin }" /><div v-if="form.errors.date_fin" class="err">{{ form.errors.date_fin }}</div></div>
            </div>
            <div class="fld" style="max-width: 200px"><label class="f" for="q">Quantité</label><input id="q" v-model="form.quantite" type="number" min="1" :max="equipement?.quantite_totale || 1" class="in" :class="{ bad: form.errors.quantite }" /><div v-if="form.errors.quantite" class="err">{{ form.errors.quantite }}</div></div>
            <div v-if="etat" class="banner" :class="etat.c === 'red' ? 'red' : etat.c === 'green' ? 'blue' : ''"><Icon :name="etat.c === 'green' ? 'check-circle' : 'alert'" /><div>{{ etat.t }}</div></div>
            <div class="fld"><label class="f" for="m">Motif</label><textarea id="m" v-model="form.motif" class="in" :class="{ bad: form.errors.motif }" placeholder="Ex. Soutenance blanche du projet de fin d'année" /><div v-if="form.errors.motif" class="err">{{ form.errors.motif }}</div></div>
            <div class="actions"><button class="btn" :disabled="form.processing">{{ r ? 'Enregistrer les modifications' : 'Envoyer la demande' }}</button><Link href="/mes-reservations" class="btn btn-ghost">Annuler</Link></div>
        </form>

        <div class="card" style="align-self: start">
            <h3>Règles à connaître</h3>
            <div class="list small">
                <div><b>Limite :</b> {{ regles.limite }} réservation(s) active(s) maximum. Il vous en reste <b>{{ Math.max(0, restant + (r ? 1 : 0)) }}</b>.</div>
                <div><b>Durée :</b> {{ regles.duree_max }} jour(s) maximum par emprunt.</div>
                <div><b>Validation :</b> aucune utilisation avant l'acceptation du gestionnaire.</div>
                <div><b>Retour :</b> tant que l'équipement n'est pas marqué « rendu », il compte dans votre limite.</div>
            </div>
        </div>
    </div>
</template>
