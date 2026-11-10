<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ parametres: Object });
const form = useForm({ limite: props.parametres.limite, duree_max: props.parametres.duree_max });
const enregistrer = () => form.put('/admin/parametres', { preserveScroll: true });
</script>

<template>
    <Head title="Paramètres" />
    <div class="ph"><div><h1>Paramètres</h1><p>Règles métier appliquées à toutes les nouvelles demandes.</p></div></div>

    <div class="grid g-main">
        <form class="card" @submit.prevent="enregistrer" novalidate>
            <div class="fld">
                <label class="f" for="l">Réservations actives par utilisateur</label>
                <input id="l" v-model="form.limite" type="number" min="1" max="20" class="in" :class="{ bad: form.errors.limite }" style="max-width: 200px" />
                <div class="hint">Comptent : demandes en attente, réservations acceptées et équipements non encore rendus.</div>
                <div v-if="form.errors.limite" class="err">{{ form.errors.limite }}</div>
            </div>
            <div class="fld">
                <label class="f" for="d">Durée maximale d'un emprunt (jours)</label>
                <input id="d" v-model="form.duree_max" type="number" min="1" max="30" class="in" :class="{ bad: form.errors.duree_max }" style="max-width: 200px" />
                <div v-if="form.errors.duree_max" class="err">{{ form.errors.duree_max }}</div>
            </div>
            <button class="btn" :disabled="form.processing">Enregistrer les paramètres</button>
        </form>

        <div class="banner blue" style="align-self: start"><Icon name="shield" /><div><b>Effet immédiat, sans rétroactivité</b><div class="small">Les réservations déjà acceptées ne sont pas modifiées. Les nouvelles règles s'appliquent aux prochaines demandes et modifications.</div></div></div>
    </div>
</template>
