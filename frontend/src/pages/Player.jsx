import { useEffect, useMemo, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import { ApiError, BASE, api, get, post } from '../lib/api.js'
import { useAuth } from '../lib/auth.jsx'
import { Empty, Field, Spinner, Stars, dateFr, useLoad, useToast } from '../lib/ui.jsx'
import { Logo } from '../components/Layout.jsx'
import { duree, embed } from '../lib/lms.js'

export function Guard({ role, children }) {
  const { user, ready } = useAuth()
  if (!ready) return <Spinner />
  if (!user) return <Navigate to="/connexion" replace />
  if (role && user.role !== role) return <Navigate to={user.role === 'formateur' ? '/formateur' : '/mes-cours'} replace />
  return children
}

export default function Player() {
  return <Guard><Lecteur /></Guard>
}

function Lecteur() {
  const { slug } = useParams()
  const toast = useToast()
  const { user } = useAuth()
  const r = useLoad(() => get(`apprendre/${slug}`), [slug])
  const [sel, setSel] = useState(null) // { kind: 'lecon'|'quiz', id }
  const [menu, setMenu] = useState(false)
  const [busy, setBusy] = useState(false)

  const items = useMemo(() => (r.data ? r.data.modules.flatMap((m) => [...m.lecons.map((l) => ({ kind: 'lecon', id: l.id, ref: l, module: m })), ...(m.quiz ? [{ kind: 'quiz', id: m.quiz.id, ref: m.quiz, module: m }] : [])]) : []), [r.data])
  useEffect(() => {
    if (!r.data || sel) return
    const next = items.find((i) => (i.kind === 'lecon' ? !i.ref.fait : !i.ref.reussi)) || items[0]
    if (next) setSel({ kind: next.kind, id: next.id })
  }, [r.data])

  if (r.loading && !r.data) return <Spinner />
  if (r.error) return <div className="mx-auto max-w-xl px-4 py-16"><Empty icon="🔒" title="Accès refusé">Vous devez être inscrit à cette formation.<div className="mt-4"><Link to={`/cours/${slug}`} className="btn-primary">Voir la formation</Link></div></Empty></div>
  const d = r.data
  const cur = items.find((i) => i.kind === sel?.kind && i.id === sel?.id)
  const idx = items.indexOf(cur)
  const preview = d.apercu_formateur
  const go = (i) => { if (items[i]) { setSel({ kind: items[i].kind, id: items[i].id }); setMenu(false); window.scrollTo(0, 0) } }
  const terminer = async () => {
    if (preview) return go(idx + 1)
    setBusy(true)
    try { const b = await post(`lecons/${cur.id}/terminer`); await r.reload(); if (b.certificat) toast('🎉 Félicitations ! Votre certificat est disponible.'); go(idx + 1) } catch (e) { toast(e.message, 'err') } finally { setBusy(false) }
  }

  return (
    <div className="flex min-h-screen flex-col bg-slate-50">
      <header className="sticky top-0 z-40 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3"><Logo /><Link to={`/cours/${d.cours.slug}`} className="hidden min-w-0 flex-1 truncate text-sm font-bold text-slate-700 hover:text-brand-700 md:block">{d.cours.titre}</Link>
        <div className="ml-auto flex items-center gap-3"><div className="hidden w-40 sm:block"><div className="mb-0.5 flex justify-between text-[11px] font-bold text-slate-500"><span>Progression</span><span>{d.bilan.pourcentage} %</span></div><div className="h-2 rounded-full bg-slate-100"><i className="block h-full rounded-full bg-brand-600 transition-all" style={{ width: `${d.bilan.pourcentage}%` }} /></div></div>
          {d.inscription?.certificat && <a className="btn-primary !py-1.5 text-xs" href="#certificat" onClick={(e) => { e.preventDefault(); telecharger(d.inscription.id, toast) }}>🏅 Certificat</a>}
          <button className="btn-ghost !px-3 !py-1.5 lg:hidden" onClick={() => setMenu(!menu)} aria-label="Sommaire">☰</button></div></header>
      {preview && <p className="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">Mode prévisualisation formateur : votre progression n’est pas enregistrée.</p>}
      <div className="mx-auto grid w-full max-w-7xl flex-1 gap-0 lg:grid-cols-[1fr_340px]">
        <main className="min-w-0 p-4 lg:p-8">
          {!cur ? <Empty icon="📚" title="Ce cours ne contient pas encore de leçon" /> : cur.kind === 'lecon' ? (
            <article>
              <p className="text-xs font-bold uppercase tracking-wide text-brand-700">{cur.module.titre}</p><h1 className="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">{cur.ref.titre}</h1>
              <div className="mt-5">{cur.ref.type === 'video' ? <Video url={cur.ref.contenu} /> : <div className="card whitespace-pre-line p-6 text-[15px] leading-relaxed text-slate-700 sm:p-8">{cur.ref.contenu}</div>}</div>
              <div className="mt-6 flex flex-wrap items-center justify-between gap-3"><button className="btn-ghost" disabled={idx <= 0} onClick={() => go(idx - 1)}>← Précédent</button>
                <span className="text-xs text-slate-400">{cur.ref.duree} min</span>
                <button className="btn-primary !py-3 px-6" disabled={busy} onClick={terminer}>{cur.ref.fait ? 'Suivant →' : idx === items.length - 1 ? '✓ Terminer' : '✓ Terminer et continuer'}</button></div>
            </article>
          ) : <QuizView key={cur.id} quiz={cur.ref} preview={preview} onDone={async (res) => { await r.reload(); if (res.certificat) toast('🎉 Félicitations ! Votre certificat est disponible.') }} onNext={() => go(idx + 1)} />}

          {d.inscription?.certificat && (
            <section className="card mt-8 border-emerald-300 bg-emerald-50 p-6 text-center"><p className="text-4xl">🏅</p><h2 className="mt-1 text-xl font-extrabold text-emerald-900">Formation terminée !</h2><p className="text-sm text-emerald-800">Certificat n° {d.inscription.certificat} · délivré le {dateFr(d.inscription.termine_le)}</p>
              <div className="mt-4 flex flex-wrap justify-center gap-2"><button className="btn-primary" onClick={() => telecharger(d.inscription.id, toast)}>⬇ Télécharger mon certificat (PDF)</button><Link className="btn-ghost" to={`/certificat/${d.inscription.certificat}`}>Lien de vérification</Link></div></section>
          )}
          {!preview && d.bilan.pourcentage >= 30 && <Avis coursId={d.cours.id} />}
        </main>
        <aside className={`border-l border-slate-200 bg-white ${menu ? 'fixed inset-0 z-50 overflow-y-auto pt-16 lg:static lg:pt-0' : 'hidden lg:block'}`}>
          {menu && <button className="fixed right-4 top-4 z-50 btn-ghost !px-3 !py-1.5 lg:hidden" onClick={() => setMenu(false)} aria-label="Fermer">✕</button>}
          <nav aria-label="Sommaire du cours" className="p-4">{d.modules.map((m, mi) => (
            <div key={m.id} className="mb-4"><p className="mb-1.5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Module {mi + 1} · {m.titre}</p>
              <ul className="space-y-0.5">{m.lecons.map((l) => <li key={l.id}><button onClick={() => { setSel({ kind: 'lecon', id: l.id }); setMenu(false) }} aria-current={sel?.kind === 'lecon' && sel.id === l.id} className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition ${sel?.kind === 'lecon' && sel.id === l.id ? 'bg-brand-50 font-bold text-brand-700' : 'text-slate-600 hover:bg-slate-50'}`}><span className={`grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-bold ${l.fait ? 'bg-emerald-500 text-white' : 'border border-slate-300 text-transparent'}`}>✓</span><span className="flex-1">{l.titre}</span><span className="text-[11px] text-slate-400">{l.type === 'video' ? '▶' : '📄'} {l.duree}′</span></button></li>)}
                {m.quiz && <li><button onClick={() => { setSel({ kind: 'quiz', id: m.quiz.id }); setMenu(false) }} className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition ${sel?.kind === 'quiz' && sel.id === m.quiz.id ? 'bg-brand-50 font-bold text-brand-700' : 'text-slate-600 hover:bg-slate-50'}`}><span className={`grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-bold ${m.quiz.reussi ? 'bg-emerald-500 text-white' : 'border border-slate-300 text-transparent'}`}>✓</span><span className="flex-1">❓ Quiz du module</span>{m.quiz.meilleur && <span className="text-[11px] text-slate-400">{m.quiz.meilleur.score}/{m.quiz.meilleur.total}</span>}</button></li>}</ul></div>))}</nav>
        </aside>
      </div>
    </div>
  )
}

async function telecharger(id, toast) {
  try { const b = await api(`inscriptions/${id}/certificat`, { blob: true }); window.open(URL.createObjectURL(b), '_blank') } catch (e) { toast(e.message, 'err') }
}

function Video({ url }) {
  const src = embed(url)
  return src ? (
    <div>
      <div className="aspect-video overflow-hidden rounded-2xl bg-black shadow-lg"><iframe className="h-full w-full" src={src} title="Vidéo de la leçon" allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" allowFullScreen loading="lazy" referrerPolicy="strict-origin-when-cross-origin" /></div>
      <p className="mt-2 text-xs text-slate-400">Si la vidéo ne s’affiche pas, <a className="font-semibold text-brand-700 underline" href={url} target="_blank" rel="noopener">ouvrez-la sur la plateforme d’origine ↗</a></p>
    </div>
  ) : <p className="card p-6 text-sm text-slate-600">Vidéo indisponible : <a className="underline" href={url} target="_blank" rel="noopener">{url}</a></p>
}

function QuizView({ quiz, preview, onDone, onNext }) {
  const toast = useToast()
  const q = useLoad(() => get(`quiz/${quiz.id}`), [quiz.id])
  const [rep, setRep] = useState({})
  const [res, setRes] = useState(null)
  const [busy, setBusy] = useState(false)
  if (q.loading && !q.data) return <Spinner />
  const d = q.data
  const submit = async () => {
    setBusy(true)
    try { if (preview) { setRes({ score: 0, total: d.questions.length, reussi: false, corrections: [], apercu: true }); return } const r = await post(`quiz/${quiz.id}/repondre`, { reponses: rep }); setRes(r); onDone(r) }
    catch (e) { toast(e instanceof ApiError ? e.all() : e.message, 'err') } finally { setBusy(false) }
  }
  const corr = (id) => res?.corrections.find((c) => c.id === id)
  return (
    <article>
      <p className="text-xs font-bold uppercase tracking-wide text-brand-700">Quiz de fin de module</p><h1 className="mt-1 text-2xl font-extrabold text-slate-900">{d.titre}</h1><p className="text-sm text-slate-500">Il faut au moins {d.seuil} % de bonnes réponses pour valider. Vous pouvez recommencer autant de fois que nécessaire.</p>
      <ol className="mt-5 space-y-5">{d.questions.map((qq, i) => (
        <li key={qq.id} className="card p-5"><p className="font-bold text-slate-900">{i + 1}. {qq.question}</p>
          <div className="mt-3 grid gap-2" role="radiogroup" aria-label={qq.question}>{qq.choix.map((c, k) => { const cc = corr(qq.id); const isGood = res && !res.apercu && cc?.bonne === k; const isBad = res && !res.apercu && cc?.donnee === k && !cc.ok
            return <label key={k} className={`flex cursor-pointer items-center gap-3 rounded-xl border-2 px-4 py-2.5 text-sm transition ${isGood ? 'border-emerald-500 bg-emerald-50' : isBad ? 'border-rose-400 bg-rose-50' : rep[qq.id] === k ? 'border-brand-600 bg-brand-50' : 'border-slate-200 hover:border-slate-300'}`}><input type="radio" className="sr-only" name={`q${qq.id}`} checked={rep[qq.id] === k} disabled={!!res} onChange={() => setRep({ ...rep, [qq.id]: k })} /><span className={`grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 text-[10px] ${rep[qq.id] === k ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'}`}>{rep[qq.id] === k ? '●' : ''}</span>{c}{isGood && <span className="ml-auto font-bold text-emerald-700">✓</span>}{isBad && <span className="ml-auto font-bold text-rose-600">✕</span>}</label> })}</div></li>))}</ol>
      {res && !res.apercu && <div className={`mt-5 rounded-2xl p-5 text-center ${res.reussi ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900'}`}><p className="text-3xl font-extrabold">{res.score} / {res.total}</p><p className="font-semibold">{res.reussi ? '🎉 Quiz réussi !' : `Il faut ${res.seuil} % pour valider. Révisez et réessayez !`}</p></div>}
      <div className="mt-6 flex flex-wrap justify-end gap-3">{res ? <>{!res.reussi && !res.apercu && <button className="btn-ghost" onClick={() => { setRes(null); setRep({}) }}>↻ Recommencer</button>}<button className="btn-primary" onClick={onNext}>Continuer →</button></> : <button className="btn-primary !py-3 px-8" disabled={busy || Object.keys(rep).length < d.questions.length} onClick={submit}>Valider mes réponses</button>}</div>
    </article>
  )
}

function Avis({ coursId }) {
  const toast = useToast()
  const [f, setF] = useState({ note: 0, commentaire: '' })
  const [done, setDone] = useState(false)
  const send = async () => { try { await post(`cours/${coursId}/avis`, f); setDone(true); toast('Merci pour votre avis !') } catch (e) { toast(e instanceof ApiError ? e.all() : e.message, 'err') } }
  return (
    <section className="card mt-8 p-6"><h2 className="font-extrabold text-slate-900">Donnez votre avis sur cette formation</h2>{done ? <p className="mt-2 text-sm font-semibold text-emerald-700">✓ Avis enregistré. Merci !</p> : <div className="mt-3 space-y-3"><Stars value={f.note} onChange={(n) => setF({ ...f, note: n })} size="text-3xl" /><Field label="Commentaire (facultatif)"><textarea className="input min-h-20" maxLength={400} value={f.commentaire} onChange={(e) => setF({ ...f, commentaire: e.target.value })} /></Field><button className="btn-primary" disabled={!f.note} onClick={send}>Publier mon avis</button></div>}</section>
  )
}

export function MesCours() {
  return <Guard role="apprenant"><MesCoursPage /></Guard>
}

function MesCoursPage() {
  const toast = useToast()
  const r = useLoad(() => get('mes-cours'), [])
  const [tab, setTab] = useState('cours')
  const list = r.data || []
  const termines = list.filter((x) => x.certificat)
  return (
    <div className="mx-auto max-w-5xl px-4 py-8">
      <div className="flex flex-wrap items-center justify-between gap-3"><h1 className="text-2xl font-extrabold text-slate-900">Mes formations</h1><Link to="/catalogue" className="btn-primary">Explorer le catalogue</Link></div>
      <div className="mt-5 flex gap-2">{[['cours', `En cours (${list.length - termines.length})`], ['certs', `Certificats (${termines.length})`]].map(([k, l]) => <button key={k} onClick={() => setTab(k)} className={`rounded-full px-4 py-2 text-sm font-bold ${tab === k ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'}`}>{l}</button>)}</div>
      <div className="mt-5 grid gap-4">{r.loading && !r.data ? <Spinner /> : (tab === 'cours' ? list.filter((x) => !x.certificat) : termines).length === 0 ? <Empty icon="🎓" title={tab === 'cours' ? 'Aucune formation en cours' : 'Pas encore de certificat'}><Link to="/catalogue" className="btn-primary mt-3">Trouver une formation</Link></Empty> : (tab === 'cours' ? list.filter((x) => !x.certificat) : termines).map((x) => (
        <article key={x.id} className="card flex flex-wrap items-center gap-4 p-5"><div className="min-w-0 flex-1"><p className="text-xs font-bold uppercase text-slate-400">{x.categorie}</p><Link to={`/cours/${x.slug}`} className="text-lg font-extrabold text-slate-900 hover:text-brand-700">{x.titre}</Link><p className="text-sm text-slate-500">{x.formateur}</p>
          <div className="mt-3 max-w-md"><div className="mb-1 flex justify-between text-xs font-semibold text-slate-500"><span>{x.fait}/{x.total} éléments</span><span>{x.pourcentage} %</span></div><div className="h-2.5 rounded-full bg-slate-100"><i className="block h-full rounded-full bg-brand-600" style={{ width: `${x.pourcentage}%` }} /></div></div></div>
          <div className="flex gap-2"><Link to={`/apprendre/${x.slug}`} className="btn-primary">{x.certificat ? 'Revoir' : x.pourcentage ? 'Reprendre' : 'Commencer'}</Link>{x.certificat && <button className="btn-ghost" onClick={() => telecharger(x.id, toast)}>🏅 Certificat</button>}</div></article>))}</div>
    </div>
  )
}
