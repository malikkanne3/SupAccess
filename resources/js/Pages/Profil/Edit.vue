<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { fmtDate, ROLES } from '../../utils';

const props = defineProps({ profil: Object, stats: Object });
const infos = useForm({ name: props.profil.name, service: props.profil.service || '' });
const mdp = useForm({ current_password: '', password: '', password_confirmation: '' });
const enregistrer = () => infos.put('/profil', { preserveScroll: true });
const changer = () => mdp.put('/profil/mot-de-passe', { preserveScroll: true, onSuccess: () => mdp.reset() });
</script>

<template>
    <Head title="Mon profil" />
    <div class="ph"><div><h1>Mon profil</h1><p>{{ ROLES[profil.role] }} — membre depuis le {{ fmtDate(profil.depuis) }}.</p></div></div>

    <div v-if="profil.role === 'demandeur'" class="grid g3" style="margin-bottom: 18px">
        <div class="tile"><div><strong>{{ stats.total }}</strong><span>Demandes effectuées</span></div></div>
        <div class="tile green"><div><strong>{{ stats.rendues }}</strong><span>Équipements rendus</span></div></div>
        <div class="tile gold"><div><strong>{{ stats.actives }}</strong><span>Réservations actives</span></div></div>
    </div>

    <div class="grid g2">
        <form class="card" @submit.prevent="enregistrer" novalidate>
            <h3>Informations</h3>
            <div class="fld"><label class="f">Nom complet</label><input v-model="infos.name" class="in" :class="{ bad: infos.errors.name }" /><div v-if="infos.errors.name" class="err">{{ infos.errors.name }}</div></div>
            <div class="fld"><label class="f">Adresse e-mail</label><input :value="profil.email" class="in" disabled /><div class="hint">L'adresse e-mail ne peut pas être modifiée.</div></div>
            <div class="fld"><label class="f">Classe ou service</label><input v-model="infos.service" class="in" /></div>
            <button class="btn" :disabled="infos.processing">Enregistrer</button>
        </form>

        <form class="card" @submit.prevent="changer" novalidate>
            <h3>Mot de passe</h3>
            <div class="fld"><label class="f">Mot de passe actuel</label><input v-model="mdp.current_password" type="password" class="in" :class="{ bad: mdp.errors.current_password }" autocomplete="current-password" /><div v-if="mdp.errors.current_password" class="err">{{ mdp.errors.current_password }}</div></div>
            <div class="fld"><label class="f">Nouveau mot de passe</label><input v-model="mdp.password" type="password" class="in" :class="{ bad: mdp.errors.password }" autocomplete="new-password" /><div v-if="mdp.errors.password" class="err">{{ mdp.errors.password }}</div></div>
            <div class="fld"><label class="f">Confirmation</label><input v-model="mdp.password_confirmation" type="password" class="in" autocomplete="new-password" /></div>
            <button class="btn" :disabled="mdp.processing">Changer le mot de passe</button>
        </form>
    </div>
</template>
