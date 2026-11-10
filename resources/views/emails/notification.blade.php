<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;background:#f3f7ff;font-family:Segoe UI,Arial,sans-serif;color:#0e1b3d">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:28px 12px">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden">
        <tr><td style="background:#0b2f78;padding:14px 24px">
            <img src="{{ url('/brand/logo-complet-fond-bleu.png') }}" alt="SupAccess" height="46" style="display:block;height:46px;width:auto;border:0;color:#fff;font-size:20px;font-weight:700">
        </td></tr>
        <tr><td style="padding:28px">
            <p style="margin:0 0 14px;font-size:16px">Bonjour {{ $nom }},</p>
            <p style="margin:0 0 22px;font-size:15px;line-height:1.55">{{ $texte }}</p>
            @if($lien)
                <a href="{{ $lien }}" style="background:#1565d8;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600;display:inline-block">Ouvrir SupAccess</a>
            @endif
        </td></tr>
        <tr><td style="padding:16px 28px;background:#f3f7ff;color:#5d6c8c;font-size:12px">
            Gestion des équipements partagés — SUPDECO Dakar
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
