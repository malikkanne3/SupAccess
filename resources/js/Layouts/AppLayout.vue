<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';
import { ROLES } from '../utils';

const page = usePage();
const user = computed(() => page.props.auth.user);
const ouvert = ref(false);
const toast = ref(null);
let minuterie;

const MENUS = {
    demandeur: [
        ['/tableau-de-bord', 'Tableau de bord', 'home'],
        ['/equipements', 'Équipements', 'box'],
        ['/calendrier', 'Calendrier', 'calendar'],
        ['/mes-reservations', 'Mes réservations', 'clipboard'],
        ['/notifications', 'Notifications', 'bell'],
        ['/profil', 'Mon profil', 'user'],
    ],
    gestionnaire: [
        ['/tableau-de-bord', 'Tableau de bord', 'home'],
        ['/demandes', 'Demandes', 'clipboard'],
        ['/equipements', 'Équipements', 'box'],
        ['/categories', 'Catégories', 'layers'],
        ['/pannes', 'Pannes', 'alert'],
        ['/calendrier', 'Calendrier global', 'calendar'],
        ['/notifications', 'Alertes', 'bell'],
        ['/profil', 'Mon profil', 'user'],
    ],
    admin: [
        ['/tableau-de-bord', 'Tableau de bord', 'home'],
        ['/admin/utilisateurs', 'Utilisateurs', 'users'],
        ['/admin/rapports', 'Rapports', 'chart'],
        ['/admin/parametres', 'Paramètres', 'sliders'],
        ['/profil', 'Mon profil', 'user'],
    ],
};

const menu = computed(() => MENUS[user.value.role] || []);
const actif = (href) => (href === '/tableau-de-bord' ? page.url.startsWith(href) : page.url.startsWith(href));
const titre = computed(() => (menu.value.find((m) => actif(m[0])) || ['', 'SupAccess'])[1]);
const aujourdhui = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

const badge = (href) => {
    if (href === '/notifications' && page.props.non_lues) return page.props.non_lues;
    if (href === '/demandes' && page.props.en_attente) return page.props.en_attente;
    return 0;
};

watch(
    () => page.props.flash,
    (f) => {
        const m = f?.success ? { type: 'success', texte: f.success } : f?.error ? { type: 'error', texte: f.error } : null;
        if (!m) return;
        toast.value = m;
        clearTimeout(minuterie);
        minuterie = setTimeout(() => (toast.value = null), 4500);
    },
    { immediate: true, deep: true },
);

router.on('navigate', () => (ouvert.value = false));
const deconnexion = () => router.post('/deconnexion');
</script>

<template>
    <div class="shell">
        <aside class="side" :class="{ open: ouvert }">
            <div class="brand">
                <div>
                    <img src="/brand/logo-complet-fond-bleu.png" alt="SupAccess, SUPDECO Dakar" class="brand-img" />
                    <small>{{ ROLES[user.role] }}</small>
                </div>
            </div>

            <div class="nav-label">Menu</div>
            <nav class="nav">
                <Link v-for="m in menu" :key="m[0]" :href="m[0]" :class="{ on: actif(m[0]) }">
                    <Icon :name="m[2]" :size="20" />
                    <span>{{ m[1] }}</span>
                    <span v-if="badge(m[0])" class="count">{{ badge(m[0]) }}</span>
                </Link>
            </nav>

            <div class="grow" />
            <div class="me">
                <div class="av">{{ user.initiales }}</div>
                <div>
                    <b>{{ user.name }}</b>
                    <small>{{ user.service || ROLES[user.role] }}</small>
                </div>
            </div>
            <button class="btn-logout" @click="deconnexion"><Icon name="logout" :size="18" /> Déconnexion</button>
        </aside>

        <section class="main">
            <header class="top">
                <button class="burger" aria-label="Ouvrir le menu" @click="ouvert = !ouvert"><Icon name="menu" /></button>
                <h2>{{ titre }}</h2>
                <div class="sp" />
                <span class="date">{{ aujourdhui }}</span>
                <Link v-if="user.role !== 'admin'" href="/notifications" class="bell" aria-label="Notifications">
                    <Icon name="bell" :size="20" />
                    <i v-if="page.props.non_lues">{{ page.props.non_lues }}</i>
                </Link>
            </header>
            <main class="content"><slot /></main>
        </section>

        <div v-if="toast" class="toast" :class="toast.type" role="status">
            <Icon :name="toast.type === 'error' ? 'alert' : 'check-circle'" />
            <span>{{ toast.texte }}</span>
        </div>
    </div>
</template>
