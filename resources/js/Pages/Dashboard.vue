<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';
import StatusBadge from '../Components/StatusBadge.vue';
import { fmtRange, fmtDate } from '../utils';

const props = defineProps({ role: String, data: Object });
const prenom = computed(() => usePage().props.auth.user.name.split(' ')[0]);

const u = computed(() => props.data.unites || {});
const donut = computed(() => {
    const t = Math.max(1, u.value.total || 1);
    const a = (u.value.dispo / t) * 100;
    const b = a + (u.value.reserve / t) * 100;
    return { '--a': `${a}%`, '--b': `${b}%` };
});
const iaChip = { ACCEPTER: ['Accepter', 'green'], REFUSER: ['Refuser', 'red'], ALTERNATIVE: ['Alternative', 'purple'], ATTENTION: ['Vigilance', 'gold'] };
</script>

<template>
    <Head title="Tableau de bord" />

    <!-- ===================== DEMANDEUR ===================== -->
    <template v-if="role === 'demandeur'">
        <div class="ph">
            <div><h1>Bonjour, {{ prenom }}</h1><p>Voici l'état de vos demandes et du matériel disponible.</p></div>
            <Link href="/reservations/nouvelle" class="btn btn-gold"><Icon name="plus" :size="18" /> Nouvelle réservation</Link>
        </div>

        <div v-if="data.a_rendre.length" class="banner red">
            <Icon name="alert" />
            <div>
                <b>{{ data.a_rendre.length }} équipement(s) à rendre au gestionnaire</b>
                <div class="small">{{ data.a_rendre.map((r) => r.equipement.nom).join(', ') }}. Tant qu'ils ne sont pas marqués « rendus », ils comptent dans votre limite.</div>
            </div>
        </div>
        <div v-if="data.alternatives" class="banner">
            <Icon name="swap" />
            <div><b>Le gestionnaire vous propose un autre équipement</b><div class="small">Répondez depuis <Link href="/mes-reservations">Mes réservations</Link>.</div></div>
        </div>

        <div class="grid g4">
            <div class="tile hero">
                <div class="ic"><Icon name="clipboard" /></div>
                <div style="flex: 1">
                    <strong>{{ data.actives }} / {{ data.limite }}</strong><span>Réservations actives</span>
                    <div class="progress"><i :style="{ width: Math.min(100, (data.actives / data.limite) * 100) + '%' }" /></div>
                </div>
            </div>
            <div class="tile gold"><div class="ic"><Icon name="clock" /></div><div><strong>{{ data.en_attente }}</strong><span>En attente de validation</span></div></div>
            <div class="tile green"><div class="ic"><Icon name="check-circle" /></div><div><strong>{{ data.acceptees }}</strong><span>Acceptées</span></div></div>
            <div class="tile purple"><div class="ic"><Icon name="return" /></div><div><strong>{{ data.a_rendre.length }}</strong><span>À rendre</span></div></div>
        </div>

        <div class="grid g-main mt">
            <div class="card">
                <div class="card-h"><h3>Mes prochaines réservations</h3><Link href="/mes-reservations" class="small b">Tout voir</Link></div>
                <div v-if="!data.prochaines.length" class="empty">
                    <Icon name="calendar" :size="38" /><b>Aucune réservation à venir</b>
                    Choisissez un équipement et envoyez votre première demande.
                    <div class="mt-s"><Link href="/equipements" class="btn btn-sm">Parcourir les équipements</Link></div>
                </div>
                <div v-else class="list">
                    <div v-for="r in data.prochaines" :key="r.id" class="row between wrap">
                        <div><div class="b">{{ r.equipement.nom }} <span class="muted">×{{ r.quantite }}</span></div><div class="small muted">{{ fmtRange(r.date_debut, r.date_fin) }}</div></div>
                        <StatusBadge :s="r.statut" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h"><h3>Disponibles maintenant</h3><Link href="/equipements" class="small b">Catalogue</Link></div>
                <div v-if="!data.disponibles.length" class="empty">Aucun équipement libre pour le moment.</div>
                <div class="list">
                    <div v-for="e in data.disponibles" :key="e.id" class="row between">
                        <div><div class="b">{{ e.nom }}</div><div class="small muted">{{ e.categorie?.nom || 'Sans catégorie' }} — {{ e.quantite_disponible }} sur {{ e.quantite_totale }}</div></div>
                        <Link :href="`/reservations/nouvelle?equipement=${e.id}`" class="btn btn-soft btn-sm">Réserver</Link>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ===================== GESTIONNAIRE ===================== -->
    <template v-else-if="role === 'gestionnaire'">
        <div class="ph">
            <div><h1>Bonjour, {{ prenom }}</h1><p>L'activité du parc d'équipements en un coup d'œil.</p></div>
            <Link href="/demandes" class="btn btn-gold"><Icon name="clipboard" :size="18" /> Traiter les demandes</Link>
        </div>

        <div class="grid g4">
            <Link href="/demandes" class="tile hero" style="color: #fff; text-decoration: none"><div class="ic"><Icon name="clock" /></div><div><strong>{{ data.nb_attente }}</strong><span>Demandes en attente</span></div></Link>
            <Link href="/demandes?vue=cours" class="tile green" style="color: inherit; text-decoration: none"><div class="ic"><Icon name="check-circle" /></div><div><strong>{{ data.nb_cours }}</strong><span>Réservations en cours</span></div></Link>
            <Link href="/demandes?vue=retours" class="tile purple" style="color: inherit; text-decoration: none"><div class="ic"><Icon name="return" /></div><div><strong>{{ data.nb_retours }}</strong><span>Retours attendus</span></div></Link>
            <Link href="/pannes" class="tile red" style="color: inherit; text-decoration: none"><div class="ic"><Icon name="alert" /></div><div><strong>{{ data.nb_pannes }}</strong><span>Pannes en cours</span></div></Link>
        </div>

        <div class="grid g-main mt">
            <div class="card">
                <div class="card-h"><h3>Demandes à traiter</h3><Link href="/demandes" class="small b">Tout voir</Link></div>
                <div v-if="!data.attente.length" class="empty"><Icon name="check-circle" :size="38" /><b>Tout est à jour</b>Aucune demande en attente de validation.</div>
                <div v-else class="list">
                    <div v-for="r in data.attente" :key="r.id" class="row between wrap">
                        <div>
                            <div class="b">{{ r.equipement.nom }} <span class="muted">×{{ r.quantite }}</span></div>
                            <div class="small muted">{{ r.demandeur }}<span v-if="r.service"> ({{ r.service }})</span> — {{ fmtRange(r.date_debut, r.date_fin) }}</div>
                        </div>
                        <span class="chip" :class="iaChip[r.ia][1]"><Icon name="sparkle" :size="13" /> IA : {{ iaChip[r.ia][0] }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Répartition du parc</h3>
                <div class="row" style="gap: 22px; flex-wrap: wrap">
                    <div class="donut" :style="donut"><span><div><b>{{ u.total }}</b>unités</div></span></div>
                    <div class="small" style="display: grid; gap: 10px">
                        <div><i class="dot" style="background: var(--green)" /><b>{{ u.dispo }}</b> disponibles</div>
                        <div><i class="dot" style="background: var(--gold)" /><b>{{ u.reserve }}</b> réservées</div>
                        <div><i class="dot" style="background: var(--red)" /><b>{{ u.panne }}</b> en panne</div>
                    </div>
                </div>
                <div class="muted small mt-s">{{ data.etats.DISPO }} équipement(s) disponible(s), {{ data.etats.RESERVE }} réservé(s), {{ data.etats.PANNE }} en panne.</div>
            </div>
        </div>

        <div class="card mt">
            <div class="card-h"><h3>Retours attendus</h3><Link href="/demandes?vue=retours" class="small b">Gérer les retours</Link></div>
            <div v-if="!data.retours.length" class="empty">Aucun équipement en attente de retour.</div>
            <div v-else class="list">
                <div v-for="r in data.retours" :key="r.id" class="row between wrap">
                    <div><div class="b">{{ r.equipement.nom }} <span class="muted">×{{ r.quantite }}</span></div><div class="small muted">{{ r.demandeur }} — devait être rendu le {{ fmtDate(r.date_fin) }}</div></div>
                    <StatusBadge :s="r.statut" />
                </div>
            </div>
        </div>
    </template>

    <!-- ===================== ADMIN ===================== -->
    <template v-else>
        <div class="ph">
            <div><h1>Bonjour, {{ prenom }}</h1><p>Vue d'ensemble de l'utilisation de SupAccess sur les 30 derniers jours.</p></div>
            <Link href="/admin/rapports" class="btn btn-gold"><Icon name="chart" :size="18" /> Voir les rapports</Link>
        </div>

        <div class="grid g4">
            <div class="tile hero"><div class="ic"><Icon name="clipboard" /></div><div><strong>{{ data.resume.demandes }}</strong><span>Demandes (30 jours)</span></div></div>
            <div class="tile green"><div class="ic"><Icon name="check-circle" /></div><div><strong>{{ data.resume.taux_acceptation }} %</strong><span>Taux d'acceptation</span></div></div>
            <div class="tile gold"><div class="ic"><Icon name="users" /></div><div><strong>{{ data.utilisateurs.demandeur + data.utilisateurs.gestionnaire + data.utilisateurs.admin }}</strong><span>Comptes utilisateurs</span></div></div>
            <div class="tile purple"><div class="ic"><Icon name="box" /></div><div><strong>{{ data.nb_equipements }}</strong><span>Équipements au catalogue</span></div></div>
        </div>

        <div class="grid g-main mt">
            <div class="card">
                <div class="card-h"><h3>Équipements les plus utilisés</h3><Link href="/admin/rapports" class="small b">Détail</Link></div>
                <div v-if="!data.top.length" class="empty">Pas encore de données.</div>
                <div v-for="e in data.top" :key="e.id" class="bar-row">
                    <span>{{ e.nom }}</span>
                    <div class="meter"><i :style="{ width: Math.max(2, e.taux_utilisation) + '%' }" /></div>
                    <span class="v">{{ e.reservations }}</span>
                </div>
                <div class="hint">Nombre de réservations sur la période.</div>
            </div>

            <div class="card">
                <div class="card-h"><h3>Règles en vigueur</h3><Link href="/admin/parametres" class="small b">Modifier</Link></div>
                <dl class="kv">
                    <dt>Réservations actives</dt><dd>{{ data.limite }} maximum par utilisateur</dd>
                    <dt>Durée d'un emprunt</dt><dd>{{ data.duree_max }} jour(s) maximum</dd>
                    <dt>Demandeurs</dt><dd>{{ data.utilisateurs.demandeur }}</dd>
                    <dt>Gestionnaires</dt><dd>{{ data.utilisateurs.gestionnaire }}</dd>
                    <dt>Administrateurs</dt><dd>{{ data.utilisateurs.admin }}</dd>
                    <dt>Comptes désactivés</dt><dd>{{ data.utilisateurs.inactifs }}</dd>
                </dl>
            </div>
        </div>
    </template>
</template>
