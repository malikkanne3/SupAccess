<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', service: '', password: '', password_confirmation: '' });
const envoyer = () => form.post('/inscription', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Inscription" />
    <div class="auth">
        <section class="auth-hero">
            <img src="/brand/logo-complet-fond-bleu.png" alt="SupAccess, SUPDECO Dakar" class="auth-logo-hero" />
            <h1>Créez votre compte, <i>réservez dès aujourd'hui.</i></h1>
            <p>Un compte suffit pour consulter les équipements, suivre vos demandes et recevoir les réponses du gestionnaire.</p>
        </section>

        <section class="auth-card">
            <img src="/brand/logo-supaccess.png" alt="SupAccess, SUPDECO Dakar" class="auth-logo-card" />
            <h2>Inscription</h2>
            <p class="sub">Compte demandeur (étudiant ou employé).</p>
            <form @submit.prevent="envoyer" novalidate>
                <div class="fld">
                    <label class="f" for="name">Nom complet</label>
                    <input id="name" v-model="form.name" class="in" :class="{ bad: form.errors.name }" autocomplete="name" autofocus />
                    <div v-if="form.errors.name" class="err">{{ form.errors.name }}</div>
                </div>
                <div class="cols2">
                    <div class="fld">
                        <label class="f" for="email">Adresse e-mail</label>
                        <input id="email" v-model="form.email" type="email" class="in" :class="{ bad: form.errors.email }" autocomplete="email" />
                        <div v-if="form.errors.email" class="err">{{ form.errors.email }}</div>
                    </div>
                    <div class="fld">
                        <label class="f" for="service">Classe ou service</label>
                        <input id="service" v-model="form.service" class="in" placeholder="Ex. Licence 3 Génie Logiciel" />
                    </div>
                </div>
                <div class="cols2">
                    <div class="fld">
                        <label class="f" for="mdp">Mot de passe</label>
                        <input id="mdp" v-model="form.password" type="password" class="in" :class="{ bad: form.errors.password }" autocomplete="new-password" />
                        <div v-if="form.errors.password" class="err">{{ form.errors.password }}</div>
                    </div>
                    <div class="fld">
                        <label class="f" for="mdp2">Confirmation</label>
                        <input id="mdp2" v-model="form.password_confirmation" type="password" class="in" autocomplete="new-password" />
                    </div>
                </div>
                <button class="btn btn-block" :disabled="form.processing">Créer mon compte</button>
            </form>
            <p class="small muted" style="margin-top: 18px; text-align: center">Déjà inscrit ? <Link href="/connexion" class="b">Se connecter</Link></p>
        </section>
    </div>
</template>
