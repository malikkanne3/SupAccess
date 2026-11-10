<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Modal from '../../Components/Modal.vue';
import { fmtDate, ROLES } from '../../utils';

const props = defineProps({ users: Object, filtres: Object });
const moi = usePage().props.auth.user.id;
const q = ref(props.filtres.q || '');
const role = ref(props.filtres.role || '');
const ouverte = ref(false);
const form = useForm({ name: '', email: '', role: 'gestionnaire', service: '', password: '' });

let t;
watch([q, role], () => { clearTimeout(t); t = setTimeout(() => router.get('/admin/utilisateurs', { q: q.value || undefined, role: role.value || undefined }, { preserveState: true, replace: true }), 300); });

const creer = () => form.post('/admin/utilisateurs', { preserveScroll: true, onSuccess: () => { ouverte.value = false; form.reset(); } });
const maj = (u, champs) => router.put(`/admin/utilisateurs/${u.id}`, { role: u.role, actif: u.actif, ...champs }, { preserveScroll: true });
const generer = () => { form.password = Math.random().toString(36).slice(2, 7) + Math.random().toString(36).slice(2, 7).toUpperCase() + '#7'; };
</script>

<template>
    <Head title="Utilisateurs" />
    <div class="ph">
        <div><h1>Utilisateurs</h1><p>Gérez les comptes, les rôles et l'accès. Un compte désactivé ne peut plus se connecter, mais son historique est conservé.</p></div>
        <button class="btn btn-gold" @click="ouverte = true"><Icon name="plus" :size="18" /> Créer un compte</button>
    </div>

    <div class="filters" style="grid-template-columns: 2fr 1fr">
        <div class="search"><Icon name="search" :size="18" /><input v-model="q" class="in" placeholder="Nom ou e-mail" aria-label="Rechercher" /></div>
        <select v-model="role" class="in" aria-label="Rôle"><option value="">Tous les rôles</option><option v-for="(l, k) in ROLES" :key="k" :value="k">{{ l }}</option></select>
    </div>

    <div class="tbl-wrap">
        <table class="tbl">
            <thead><tr><th>Utilisateur</th><th>Service</th><th>Rôle</th><th>Réservations</th><th>Inscrit le</th><th>Accès</th></tr></thead>
            <tbody>
                <tr v-if="!users.data.length"><td colspan="6" class="empty">Aucun utilisateur trouvé.</td></tr>
                <tr v-for="u in users.data" :key="u.id">
                    <td><div class="b">{{ u.name }}</div><div class="small muted">{{ u.email }}</div></td>
                    <td>{{ u.service || '—' }}</td>
                    <td>
                        <select class="in" style="padding: 6px 10px; width: auto" :value="u.role" :disabled="u.id === moi" :aria-label="`Rôle de ${u.name}`" @change="maj(u, { role: $event.target.value })">
                            <option v-for="(l, k) in ROLES" :key="k" :value="k">{{ l }}</option>
                        </select>
                    </td>
                    <td>{{ u.reservations }}</td>
                    <td>{{ fmtDate(u.created_at) }}</td>
                    <td>
                        <button v-if="u.id !== moi" class="btn btn-sm" :class="u.actif ? 'btn-ghost' : 'btn-green'" @click="maj(u, { actif: !u.actif })">{{ u.actif ? 'Désactiver' : 'Réactiver' }}</button>
                        <span v-else class="chip">Vous</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="users.last_page > 1" class="pager">
        <template v-for="(l, i) in users.links" :key="i">
            <Link v-if="l.url" :href="l.url" :class="{ on: l.active }" preserve-scroll><span v-html="l.label" /></Link>
            <span v-else class="off" v-html="l.label" />
        </template>
    </div>

    <Modal v-if="ouverte" titre="Créer un compte" sous="Réservé aux comptes gestionnaire et administrateur. Les demandeurs s'inscrivent eux-mêmes." @close="ouverte = false">
        <form @submit.prevent="creer" novalidate>
            <div class="fld"><label class="f">Nom complet</label><input v-model="form.name" class="in" :class="{ bad: form.errors.name }" /><div v-if="form.errors.name" class="err">{{ form.errors.name }}</div></div>
            <div class="fld"><label class="f">Adresse e-mail</label><input v-model="form.email" type="email" class="in" :class="{ bad: form.errors.email }" /><div v-if="form.errors.email" class="err">{{ form.errors.email }}</div></div>
            <div class="cols2">
                <div class="fld"><label class="f">Rôle</label><select v-model="form.role" class="in"><option value="gestionnaire">Gestionnaire</option><option value="admin">Administration</option></select></div>
                <div class="fld"><label class="f">Service</label><input v-model="form.service" class="in" placeholder="Ex. Service logistique" /></div>
            </div>
            <div class="fld"><label class="f">Mot de passe provisoire</label><div class="row"><input v-model="form.password" class="in" :class="{ bad: form.errors.password }" /><button type="button" class="btn btn-ghost" @click="generer">Générer</button></div><div v-if="form.errors.password" class="err">{{ form.errors.password }}</div></div>
            <div class="foot"><button type="button" class="btn btn-ghost" @click="ouverte = false">Annuler</button><button class="btn" :disabled="form.processing">Créer le compte</button></div>
        </form>
    </Modal>
</template>
