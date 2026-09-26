<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><style>
  @page { margin: 0; }
  body { font-family: DejaVu Sans, sans-serif; margin: 0; color: #1e1b4b; }
  .cadre { margin: 24px; border: 6px double #5b21b6; padding: 30px 50px; height: 430px; text-align: center; }
  .marque { letter-spacing: 6px; font-size: 13px; color: #5b21b6; font-weight: bold; }
  h1 { font-size: 40px; margin: 16px 0 4px; color: #5b21b6; letter-spacing: 3px; }
  .sous { font-size: 14px; color: #64748b; }
  .nom { font-size: 34px; font-weight: bold; margin: 22px 0 6px; border-bottom: 2px solid #ddd6fe; display: inline-block; padding: 0 30px 6px; }
  .cours { font-size: 21px; font-weight: bold; color: #5b21b6; margin: 8px 0; }
  .pied { margin-top: 30px; font-size: 11px; color: #475569; }
  table { width: 100%; }
</style></head><body>
<div class="cadre">
  <div class="marque">APPRENDO · MARKETPLACE DE FORMATIONS</div>
  <h1>CERTIFICAT</h1><div class="sous">de réussite</div>
  <p class="sous" style="margin-top:18px">Ce certificat est décerné à</p>
  <div class="nom">{{ $i->apprenant->name }}</div>
  <p class="sous">pour avoir suivi et validé avec succès la formation</p>
  <div class="cours">« {{ $i->cours->titre }} »</div>
  <p class="sous">dispensée par {{ $i->cours->formateur->name }} — {{ $i->cours->categorie }}</p>
  <table class="pied"><tr><td style="text-align:left">Délivré le {{ $i->termine_le->locale('fr')->translatedFormat('j F Y') }}</td><td style="text-align:right">Certificat n° <b>{{ $i->certificat }}</b></td></tr></table>
  <p class="pied" style="margin-top:6px">Authenticité vérifiable sur la plateforme avec ce numéro. Formation et certificat fictifs (projet de démonstration).</p>
</div>
</body></html>
