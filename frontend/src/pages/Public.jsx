import { useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { ApiError, get, post } from '../lib/api.js'
import { useAuth } from '../lib/auth.jsx'
import { Empty, Field, Modal, Spinner, Stars, dateFr, useLoad, useToast } from '../lib/ui.jsx'
import { CourseCard } from '../components/Layout.jsx'
import { CATS, NIVEAUX, cat, duree, fmt } from '../lib/lms.js'

export function Home() {
  const nav = useNavigate()
  const { user } = useAuth()
  const [q, setQ] = useState('')
  const cats = useLoad(() => get('categories'), [])
  const top = useLoad(() => get('catalogue', { tri: 'populaires' }), [])
  return (
    <>
      <section className="relative overflow-hidden bg-gradient-to-br from-slate-900 via-brand-700 to-brand-600 text-white">
        <div className="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-500/30 blur-3xl" aria-hidden="true" />
        <div className="relative mx-auto max-w-6xl px-4 pb-20 pt-14 md:pt-20">
          <p className="mb-4 inline-flex rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold tracking-wide">🎓 La marketplace de formations en ligne</p>
          <h1 className="max-w-3xl text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl md:text-6xl">Apprenez un métier. <span className="text-brand-500">À votre rythme.</span></h1>
          <p className="mt-5 max-w-2xl text-lg text-white/85">Des formations pratiques créées par des formateurs béninois : développement web, marketing, entrepreneuriat, comptabilité… avec quiz, suivi de progression et certificat.</p>
          <form onSubmit={(e) => { e.preventDefault(); nav(`/catalogue?q=${encodeURIComponent(q)}`) }} className="mt-8 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 shadow-2xl sm:flex-row" role="search">
            <label className="sr-only" htmlFor="q">Rechercher une formation</label><input id="q" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Que voulez-vous apprendre ? (React, Excel, marketing…)" className="flex-1 rounded-xl px-4 py-3 text-slate-900 outline-none placeholder:text-slate-400" />
            <button className="btn-primary !py-3 px-7 text-base">Rechercher</button></form>
        </div>
      </section>
      <section className="mx-auto max-w-6xl px-4 py-12"><h2 className="text-2xl font-extrabold text-slate-900">Explorer par catégorie</h2>
        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">{(cats.data || []).map((c) => { const [a, b, i] = cat(c.nom); return (
          <Link key={c.nom} to={`/catalogue?categorie=${encodeURIComponent(c.nom)}`} className="card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"><span className="grid h-12 w-12 place-items-center rounded-xl text-2xl text-white" style={{ background: `linear-gradient(135deg, ${a}, ${b})` }}>{i}</span><span><b className="block text-sm text-slate-900">{c.nom}</b><span className="text-xs text-slate-500">{c.total} formation{c.total > 1 ? 's' : ''}</span></span></Link>) })}</div></section>
      <section className="bg-white py-12"><div className="mx-auto max-w-6xl px-4"><div className="flex items-end justify-between"><h2 className="text-2xl font-extrabold text-slate-900">Les plus suivies</h2><Link to="/catalogue" className="text-sm font-bold text-brand-700 hover:underline">Tout le catalogue →</Link></div>
        {top.loading ? <Spinner /> : <div className="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{top.data?.data.slice(0, 4).map((c) => <CourseCard key={c.id} c={c} />)}</div>}</div></section>
      <section className="mx-auto max-w-6xl px-4 py-12"><div className="grid gap-5 md:grid-cols-3">{[['🎯', 'Apprentissage guidé', 'Modules, leçons vidéo ou texte et quiz de fin de module pour valider vos acquis.'], ['📈', 'Progression suivie', 'Retrouvez où vous en êtes, reprenez là où vous vous êtes arrêté.'], ['🏅', 'Certificat de réussite', 'Un certificat PDF avec numéro vérifiable, délivré à la fin de la formation.']].map(([i, t, d]) => <article key={t} className="card p-6"><div className="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-2xl">{i}</div><h3 className="font-extrabold text-slate-900">{t}</h3><p className="mt-1 text-sm text-slate-600">{d}</p></article>)}</div></section>
      {!user && <section className="mx-auto max-w-4xl px-4 pb-20"><div className="rounded-3xl bg-slate-900 p-10 text-center text-white"><h2 className="text-3xl font-extrabold">Vous avez un savoir-faire à transmettre ?</h2><p className="mx-auto mt-3 max-w-xl text-slate-300">Créez votre cours, fixez votre prix et suivez vos inscrits et vos revenus depuis votre espace formateur.</p><Link to="/inscription?role=formateur" className="btn-primary mt-6 px-7 py-3 text-base">Devenir formateur</Link></div></section>}
    </>
  )
}

export function Catalogue() {
  const [sp, setSp] = useSearchParams()
  const q = Object.fromEntries(sp)
  const cats = useLoad(() => get('categories'), [])
  const res = useLoad(() => get('catalogue', q), [sp.toString()])
  const set = (k, v) => { const n = new URLSearchParams(sp); v ? n.set(k, v) : n.delete(k); if (k !== 'page') n.delete('page'); setSp(n) }
  const d = res.data
  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      <h1 className="text-3xl font-extrabold text-slate-900">Catalogue des formations</h1>
      <div className="mt-5 grid gap-6 lg:grid-cols-[240px_1fr]">
        <aside className="card space-y-4 self-start p-5" aria-label="Filtres">
          <Field label="Recherche"><input className="input" value={q.q || ''} onChange={(e) => set('q', e.target.value)} placeholder="Mot-clé…" /></Field>
          <Field label="Catégorie"><select className="input" value={q.categorie || ''} onChange={(e) => set('categorie', e.target.value)}><option value="">Toutes</option>{(cats.data || []).map((c) => <option key={c.nom}>{c.nom}</option>)}</select></Field>
          <Field label="Niveau"><select className="input" value={q.niveau || ''} onChange={(e) => set('niveau', e.target.value)}><option value="">Tous</option>{Object.entries(NIVEAUX).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
          <Field label="Prix"><select className="input" value={q.prix || ''} onChange={(e) => set('prix', e.target.value)}><option value="">Tous</option><option value="gratuit">Gratuit</option><option value="payant">Payant</option></select></Field>
          <Field label="Trier par"><select className="input" value={q.tri || 'populaires'} onChange={(e) => set('tri', e.target.value)}><option value="populaires">Popularité</option><option value="note">Mieux notées</option><option value="recents">Plus récentes</option><option value="prix">Prix croissant</option></select></Field>
        </aside>
        <div>
          <p className="mb-3 text-sm text-slate-500" aria-live="polite">{d ? `${d.total} formation${d.total > 1 ? 's' : ''}` : ' '}</p>
          {res.loading && !d ? <Spinner /> : d?.data.length === 0 ? <Empty icon="🔎" title="Aucune formation trouvée">Essayez d’autres filtres.</Empty> : <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">{d?.data.map((c) => <CourseCard key={c.id} c={c} />)}</div>}
          {d && d.last_page > 1 && <div className="mt-6 flex items-center justify-center gap-3"><button className="btn-ghost" disabled={d.current_page <= 1} onClick={() => set('page', d.current_page - 1)}>←</button><span className="text-sm font-semibold text-slate-500">Page {d.current_page}/{d.last_page}</span><button className="btn-ghost" disabled={d.current_page >= d.last_page} onClick={() => set('page', d.current_page + 1)}>→</button></div>}
        </div>
      </div>
    </div>
  )
}

export function CoursePage() {
  const { slug } = useParams()
  const { user } = useAuth()
  const toast = useToast()
  const nav = useNavigate()
  const r = useLoad(() => get(`cours/${slug}`), [slug, user?.id])
  const [pay, setPay] = useState(false)
  const [f, setF] = useState({ mode: 'momo', numero: '' })
  const [busy, setBusy] = useState(false)
  const [open, setOpen] = useState({ 0: true })
  if (r.loading && !r.data) return <Spinner />
  if (r.error) return <div className="mx-auto max-w-xl px-4 py-16"><Empty icon="😕" title="Formation introuvable"><Link to="/catalogue" className="btn-primary mt-4">Voir le catalogue</Link></Empty></div>
  const c = r.data
  const [a, b, icon] = cat(c.categorie)
  const insc = c.mon_inscription

  const inscrire = async () => {
    if (!user) return nav('/connexion')
    if (c.prix > 0 && !pay) return setPay(true)
    setBusy(true)
    try { await post(`cours/${c.id}/inscription`, c.prix > 0 ? f : {}); toast('Inscription confirmée. Bon apprentissage !'); nav(`/apprendre/${c.slug}`) }
    catch (e) { toast(e instanceof ApiError ? e.all() : e.message, 'err') } finally { setBusy(false) }
  }
  return (
    <div>
      <section className="text-white" style={{ background: `linear-gradient(135deg, ${a}, ${b})` }}>
        <div className="mx-auto grid max-w-6xl gap-6 px-4 py-10 lg:grid-cols-[1fr_340px]">
          <div><p className="text-sm font-bold opacity-90">{icon} {c.categorie} · {NIVEAUX[c.niveau]}{!c.publie && ' · BROUILLON'}</p><h1 className="mt-2 text-3xl font-extrabold leading-tight sm:text-4xl">{c.titre}</h1><p className="mt-3 max-w-2xl text-white/90">{c.resume}</p>
            <p className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">{c.note ? <span className="inline-flex items-center gap-1.5"><b>{Number(c.note).toFixed(1).replace('.', ',')}</b><Stars value={c.note} size="text-sm" /> ({c.nb_avis} avis)</span> : <span>Nouveau</span>}<span>{c.inscrits} inscrits</span><span>{c.nb_lecons} leçons · {duree(c.duree)}</span><span>Par <b>{c.formateur}</b></span></p></div>
        </div>
      </section>
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-8 lg:grid-cols-[1fr_340px]">
        <div className="space-y-8">
          <section><h2 className="text-xl font-extrabold text-slate-900">À propos de cette formation</h2><p className="mt-2 whitespace-pre-line text-slate-600">{c.description}</p></section>
          <section aria-labelledby="pg"><h2 id="pg" className="text-xl font-extrabold text-slate-900">Programme</h2>
            <div className="mt-3 space-y-3">{c.modules.map((m, i) => (
              <div key={m.id} className="card overflow-hidden"><button className="flex w-full items-center gap-3 px-5 py-4 text-left" onClick={() => setOpen({ ...open, [i]: !open[i] })} aria-expanded={!!open[i]}><span className="grid h-7 w-7 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">{i + 1}</span><b className="flex-1 text-slate-900">{m.titre}</b><span className="text-xs text-slate-400">{m.lecons.length} leçons{m.a_quiz ? ' + quiz' : ''}</span><span>{open[i] ? '▴' : '▾'}</span></button>
                {open[i] && <ul className="divide-y divide-slate-100 border-t border-slate-100">{m.lecons.map((l) => <li key={l.id} className="flex items-center gap-3 px-5 py-2.5 text-sm"><span aria-hidden="true">{l.type === 'video' ? '▶️' : '📄'}</span><span className="flex-1 text-slate-700">{l.titre}</span>{l.apercu && !insc && <span className="badge bg-emerald-50 text-emerald-700">Aperçu</span>}{!l.contenu && <span aria-label="Verrouillé">🔒</span>}<span className="text-xs text-slate-400">{l.duree} min</span></li>)}{m.a_quiz && <li className="flex items-center gap-3 px-5 py-2.5 text-sm"><span aria-hidden="true">❓</span><span className="flex-1 text-slate-700">Quiz de fin de module</span></li>}</ul>}</div>))}</div></section>
          <section><h2 className="text-xl font-extrabold text-slate-900">Votre formateur</h2><div className="card mt-3 p-5"><b className="text-lg text-slate-900">{c.formateur}</b><p className="mt-1 text-sm text-slate-600">{c.formateur_bio}</p></div></section>
          <section><h2 className="text-xl font-extrabold text-slate-900">Avis des apprenants</h2>{c.avis.length === 0 ? <p className="mt-2 text-sm text-slate-500">Pas encore d’avis.</p> : <ul className="mt-3 space-y-3">{c.avis.map((a) => <li key={a.id} className="card p-4 text-sm"><Stars value={a.note} size="text-sm" /> <b className="ml-1 text-slate-900">{a.auteur}</b> <span className="text-xs text-slate-400">· {dateFr(a.date)}</span><p className="mt-1 text-slate-600">{a.commentaire}</p></li>)}</ul>}</section>
        </div>
        <aside><div className="card sticky top-24 space-y-4 p-6">
          <p className={`text-3xl font-extrabold ${c.prix ? 'text-slate-900' : 'text-emerald-600'}`}>{fmt(c.prix)}</p>
          {insc ? <><div><div className="mb-1 flex justify-between text-sm font-semibold"><span>Ma progression</span><span>{insc.pourcentage} %</span></div><div className="h-2.5 rounded-full bg-slate-100"><i className="block h-full rounded-full bg-brand-600" style={{ width: `${insc.pourcentage}%` }} /></div></div><Link to={`/apprendre/${c.slug}`} className="btn-primary w-full !py-3">{insc.pourcentage === 0 ? 'Commencer' : insc.termine ? 'Revoir le cours' : 'Reprendre le cours'}</Link></>
            : c.peut_modifier ? <><Link to={`/apprendre/${c.slug}`} className="btn-primary w-full">Prévisualiser le cours</Link><Link to={`/formateur/cours/${c.id}`} className="btn-ghost w-full">Modifier</Link></>
            : user?.role === 'formateur' ? <p className="rounded-xl bg-slate-100 p-3 text-sm text-slate-600">Connectez-vous avec un compte apprenant pour vous inscrire.</p>
            : <button className="btn-primary w-full !py-3 text-base" onClick={inscrire} disabled={busy}>{c.prix > 0 ? 'Acheter cette formation' : 'S’inscrire gratuitement'}</button>}
          <ul className="space-y-2 text-sm text-slate-600"><li>🎬 {c.nb_lecons} leçons ({duree(c.duree)})</li><li>❓ Quiz de fin de module</li><li>🏅 Certificat de réussite</li><li>♾️ Accès illimité</li></ul>
        </div></aside>
      </div>
      {pay && (
        <Modal title={`Acheter — ${fmt(c.prix)}`} onClose={() => setPay(false)}>
          <p className="mb-3 rounded-xl bg-amber-50 p-3 text-xs font-semibold text-amber-800">Simulation : aucun argent réel n’est débité.</p>
          <div className="mb-4 grid grid-cols-2 gap-2">{[['momo', 'MTN MoMo', '#facc15'], ['moov', 'Moov Money', '#2563eb']].map(([v, l, col]) => <button key={v} type="button" onClick={() => setF({ ...f, mode: v })} aria-pressed={f.mode === v} className={`rounded-xl border-2 p-3 text-sm font-bold ${f.mode === v ? 'border-slate-900 bg-slate-50' : 'border-slate-200'}`}><span className="mr-1.5 inline-block h-3 w-3 rounded-full" style={{ background: col }} />{l}</button>)}</div>
          <Field label="Numéro Mobile Money"><input className="input" value={f.numero} onChange={(e) => setF({ ...f, numero: e.target.value })} placeholder="+229 01 …" autoFocus /></Field>
          <div className="mt-4 flex gap-2"><button className="btn-ghost flex-1" onClick={() => setPay(false)}>Annuler</button><button className="btn-primary flex-1" disabled={busy || f.numero.length < 8} onClick={inscrire}>Payer {fmt(c.prix)}</button></div>
        </Modal>
      )}
    </div>
  )
}

export function VerifierCertificat() {
  const { numero } = useParams()
  const r = useLoad(() => get(`certificats/${numero}`), [numero])
  if (r.loading) return <Spinner />
  return <div className="mx-auto max-w-lg px-4 py-16">{r.error ? <Empty icon="❌" title="Certificat introuvable">Ce numéro ne correspond à aucun certificat délivré.</Empty> : <div className="card p-8 text-center"><p className="text-5xl">🏅</p><h1 className="mt-2 text-2xl font-extrabold text-slate-900">Certificat valide ✓</h1><p className="mt-3 text-slate-600"><b>{r.data.apprenant}</b> a validé la formation</p><p className="mt-1 text-lg font-bold text-brand-700">« {r.data.cours} »</p><p className="mt-3 text-sm text-slate-500">Délivré le {dateFr(r.data.date)} · n° {r.data.numero}</p></div>}</div>
}
