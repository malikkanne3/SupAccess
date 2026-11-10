// Les dates arrivent du serveur au format « 2026-11-03 09:00:00 » (heure locale, sans fuseau).
export const parse = (s) => (s ? new Date(String(s).replace(' ', 'T')) : null);

export const fmtDate = (s) => (s ? parse(s).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—');
export const fmtTime = (s) => (s ? parse(s).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '');
export const fmtDT = (s) => (s ? `${fmtDate(s)} à ${fmtTime(s)}` : '—');
export const fmtRange = (a, b) => {
    const d1 = parse(a), d2 = parse(b);
    if (d1.toDateString() === d2.toDateString()) return `${fmtDate(a)}, de ${fmtTime(a)} à ${fmtTime(b)}`;
    return `Du ${fmtDT(a)} au ${fmtDT(b)}`;
};
export const toInput = (s) => (s ? String(s).slice(0, 16).replace(' ', 'T') : '');
export const pad = (n) => String(n).padStart(2, '0');
export const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
export const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
export const startOfWeek = (d) => { const x = new Date(d); x.setHours(0, 0, 0, 0); x.setDate(x.getDate() - ((x.getDay() + 6) % 7)); return x; };
export const depuis = (s) => {
    const min = Math.round((Date.now() - parse(s).getTime()) / 60000);
    if (min < 1) return "à l'instant";
    if (min < 60) return `il y a ${min} min`;
    if (min < 1440) return `il y a ${Math.round(min / 60)} h`;
    return `il y a ${Math.round(min / 1440)} j`;
};
export const ROLES = { demandeur: 'Demandeur', gestionnaire: 'Gestionnaire', admin: 'Administration' };
