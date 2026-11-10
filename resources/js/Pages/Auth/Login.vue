<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';

const form = useForm({ email: '', password: '', remember: false });
const comptes = [
    ['Demandeur', 'awa@supdeco.sn'],
    ['Gestionnaire', 'gestionnaire@supdeco.sn'],
    ['Admin', 'admin@supdeco.sn'],
];
const remplir = (email) => { form.email = email; form.password = 'Password123'; };
const envoyer = () => form.post('/connexion', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Connexion" />
    <div class="auth">
        <section class="auth-hero">
            <img src="/brand/logo-complet-fond-bleu.png" alt="SupAccess, SUPDECO Dakar" class="auth-logo-hero" />
            <h1>Le matériel de l'école, <i>réservé en deux minutes.</i></h1>
            <p>Projecteurs, ordinateurs, switchs, salles de réunion : consultez les disponibilités, faites votre demande et suivez sa validation.</p>
            <ul>
                <li><span><Icon name="calendar" :size="18" /></span>Calendrier des disponibilités en temps réel</li>
                <li><span><Icon name="check-circle" :size="18" /></span>Aucune double réservation possible</li>
                <li><span><Icon name="bell" :size="18" /></span>Notifications à chaque décision du gestionnaire</li>
            </ul>
        </section>

        <section class="auth-card">
            <img src="/brand/logo-supaccess.png" alt="SupAccess, SUPDECO Dakar" class="auth-logo-card" />
            <h2>Connexion</h2>
            <p class="sub">Accédez à votre espace SUPDECO.</p>
            <form @submit.prevent="envoyer" novalidate>
                <div class="fld">
                    <label class="f" for="email">Adresse e-mail</label>
                    <input id="email" v-model="form.email" type="email" class="in" :class="{ bad: form.errors.email }" autocomplete="username" autofocus />
                    <div v-if="form.errors.email" class="err">{{ form.errors.email }}</div>
                </div>
                <div class="fld">
                    <label class="f" for="mdp">Mot de passe</label>
                    <input id="mdp" v-model="form.password" type="password" class="in" :class="{ bad: form.errors.password }" autocomplete="current-password" />
                    <div v-if="form.errors.password" class="err">{{ form.errors.password }}</div>
                </div>
                <label class="check" style="margin-bottom: 18px"><input v-model="form.remember" type="checkbox" /> Rester connecté</label>
                <button class="btn btn-block" :disabled="form.processing">Se connecter</button>
            </form>
            <p class="small muted" style="margin-top: 18px; text-align: center">
                Pas encore de compte ? <Link href="/inscription" class="b">Créer mon compte étudiant</Link>
            </p>

            <div class="demo">
                <b>Comptes de démonstration</b> (mot de passe : Password123)
                <div class="row wrap">
                    <button v-for="c in comptes" :key="c[1]" type="button" class="btn btn-soft btn-sm" @click="remplir(c[1])">{{ c[0] }}</button>
                </div>
            </div>
        </section>
    </div>
</template>
