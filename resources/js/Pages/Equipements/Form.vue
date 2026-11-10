<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ equipement: Object, categories: Array });
const e = props.equipement;
const form = useForm({
    nom: e?.nom || '',
    categorie_id: e?.categorie_id || '',
    quantite_totale: e?.quantite_totale || 1,
    description: e?.description || '',
    image: null,
});
const apercu = ref(e?.image_url || null);

const choisir = (ev) => {
    const f = ev.target.files[0];
    form.image = f || null;
    if (f) apercu.value = URL.createObjectURL(f);
};
const envoyer = () => {
    if (e) form.transform((d) => ({ ...d, _method: 'put' })).post(`/equipements/${e.id}`, { forceFormData: true });
    else form.post('/equipements', { forceFormData: true });
};
const titre = computed(() => (e ? "Modifier l'équipement" : 'Nouvel équipement'));
</script>

<template>
    <Head :title="titre" />
    <div class="ph">
        <div><Link href="/equipements" class="small b">Retour au catalogue</Link><h1 style="margin-top: 6px">{{ titre }}</h1><p>Un équipement correspond à un modèle précis. N'indiquez une quantité que pour des exemplaires strictement identiques.</p></div>
    </div>

    <form class="grid g-main" @submit.prevent="envoyer" novalidate>
        <div class="card">
            <div class="fld"><label class="f" for="nom">Nom et modèle</label><input id="nom" v-model="form.nom" class="in" :class="{ bad: form.errors.nom }" placeholder="Ex. PC portable HP ProBook 450" /><div v-if="form.errors.nom" class="err">{{ form.errors.nom }}</div></div>
            <div class="cols2">
                <div class="fld">
                    <label class="f" for="cat">Catégorie</label>
                    <select id="cat" v-model="form.categorie_id" class="in"><option value="">Sans catégorie</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nom }}</option></select>
                    <div class="hint">Gérez les catégories depuis le menu « Catégories ».</div>
                </div>
                <div class="fld"><label class="f" for="qte">Quantité en stock</label><input id="qte" v-model="form.quantite_totale" type="number" min="1" class="in" :class="{ bad: form.errors.quantite_totale }" /><div v-if="form.errors.quantite_totale" class="err">{{ form.errors.quantite_totale }}</div></div>
            </div>
            <div class="fld"><label class="f" for="desc">Description</label><textarea id="desc" v-model="form.description" class="in" placeholder="Caractéristiques, accessoires fournis…" /><div v-if="form.errors.description" class="err">{{ form.errors.description }}</div></div>
            <div class="actions"><button class="btn" :disabled="form.processing">{{ e ? 'Enregistrer les modifications' : "Ajouter l'équipement" }}</button><Link href="/equipements" class="btn btn-ghost">Annuler</Link></div>
        </div>

        <div class="card">
            <h3>Photo</h3>
            <div class="hero-img" style="height: 200px"><img v-if="apercu" :src="apercu" alt="Aperçu" /><Icon v-else name="box" :size="64" /></div>
            <div class="fld mt-s"><input type="file" accept="image/*" class="in" @change="choisir" aria-label="Choisir une image" /><div class="hint">Facultatif. JPG ou PNG, 3 Mo maximum, pour identifier l'équipement.</div><div v-if="form.errors.image" class="err">{{ form.errors.image }}</div></div>
        </div>
    </form>
</template>
