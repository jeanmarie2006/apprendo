import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ApiError, del, get, post, put } from '../lib/api.js'
import { useAuth } from '../lib/auth.jsx'
import { Empty, Field, Modal, Spinner, useLoad, useToast } from '../lib/ui.jsx'
import { Guard } from './Player.jsx'
import { CATS, NIVEAUX, fmt } from '../lib/lms.js'

const MOIS = ['janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc']

export function Formateur() {
  return <Guard role="formateur"><Dashboard /></Guard>
}

function Dashboard() {
  const { user } = useAuth()
  const s = useLoad(() => get('formateur/stats'), [])
  const c = useLoad(() => get('formateur/cours'), [])
  const max = Math.max(1, ...(s.data?.evolution || []).map((m) => m.inscrits))
  const Kpi = ({ l, v, t = 'text-slate-900' }) => <div className="card p-5"><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{l}</p><p className={`mt-1 text-2xl font-extrabold ${t}`}>{v}</p></div>
  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      <div className="flex flex-wrap items-center justify-between gap-3"><div><h1 className="text-2xl font-extrabold text-slate-900">Espace formateur</h1><p className="text-sm text-slate-500">Bonjour {user.name.split(' ')[0]} 👋</p></div><Link to="/formateur/cours/nouveau" className="btn-primary">＋ Créer un cours</Link></div>
      {s.data && <>
        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><Kpi l="Inscrits" v={s.data.inscrits} t="text-brand-700" /><Kpi l="Revenus" v={fmt(s.data.revenus)} t="text-emerald-600" /><Kpi l="Certificats délivrés" v={s.data.certificats} /><Kpi l="Note moyenne" v={s.data.note_moyenne ? `${String(s.data.note_moyenne).replace('.', ',')} / 5` : '—'} t="text-amber-500" /></div>
        <section className="card mt-6 p-6" aria-labelledby="ev"><h2 id="ev" className="font-extrabold text-slate-900">Nouvelles inscriptions (6 mois)</h2>
          <div className="mt-5 flex gap-3" role="img" aria-label="Histogramme des inscriptions par mois">{s.data.evolution.map((m) => <div key={m.mois} className="flex flex-1 flex-col items-center gap-1.5"><span className="h-4 text-xs font-bold text-slate-600">{m.inscrits || ''}</span><div className="flex h-32 w-full items-end"><div className="w-full rounded-t-lg bg-gradient-to-t from-brand-700 to-brand-500" style={{ height: `${Math.max(m.inscrits ? 4 : 1, (m.inscrits / max) * 100)}%`, opacity: m.inscrits ? 1 : 0.25 }} /></div><span className="text-xs text-slate-500">{MOIS[Number(m.mois.slice(5)) - 1]}</span></div>)}</div></section></>}
      <h2 className="mb-3 mt-8 text-xl font-extrabold text-slate-900">Mes cours</h2>
      <div className="card overflow-hidden"><div className="overflow-x-auto"><table className="w-full"><thead className="bg-slate-50"><tr><th className="th">Cours</th><th className="th">Prix</th><th className="th text-right">Inscrits</th><th className="th text-right">Revenus</th><th className="th text-right">Statut</th><th className="th" /></tr></thead>
        <tbody className="divide-y divide-slate-100">{c.loading && !c.data ? <tr><td colSpan="6"><Spinner /></td></tr> : (c.data || []).map((x) => <tr key={x.id} className="hover:bg-slate-50"><td className="td"><Link className="font-bold text-slate-900 hover:text-brand-700" to={`/formateur/cours/${x.id}`}>{x.titre}</Link><span className="block text-xs text-slate-400">{x.nb_modules} modules · {x.nb_lecons} leçons</span></td><td className="td">{fmt(x.prix)}</td><td className="td text-right font-bold">{x.inscrits}</td><td className="td text-right">{fmt(x.revenus)}</td><td className="td text-right"><span className={`badge ${x.publie ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}`}>{x.publie ? 'Publié' : 'Brouillon'}</span></td><td className="td text-right"><Link className="btn-ghost !py-1 text-xs" to={`/formateur/cours/${x.id}`}>Modifier</Link></td></tr>)}</tbody></table></div></div>
    </div>
  )
}

export function Builder() {
  const { id } = useParams()
  return <Guard role="formateur"><BuilderPage key={id} /></Guard>
}

function BuilderPage() {
  const { id } = useParams()
  const isNew = id === 'nouveau'
  const toast = useToast()
  const nav = useNavigate()
  const r = useLoad(() => (isNew ? Promise.resolve(null) : get(`formateur/cours/${id}`)), [id])
  const [tab, setTab] = useState(isNew ? 'infos' : 'programme')
  const [f, setF] = useState(null)
  const [err, setErr] = useState({})
  const [lecon, setLecon] = useState(null)
  const [quiz, setQuiz] = useState(null)
  if (r.error) return <Empty icon="🔒" title="Cours introuvable ou non autorisé" />
  const c = r.data
  if (!isNew && !c) return <Spinner />
  if (!f && (isNew || c)) setF(c ? { titre: c.titre, resume: c.resume, description: c.description, categorie: c.categorie, niveau: c.niveau, prix: c.prix } : { titre: '', resume: '', description: '', categorie: 'Développement web', niveau: 'debutant', prix: 0 })
  if (!f) return <Spinner />
  const set = (k) => (e) => setF({ ...f, [k]: e.target.value })
  const wrap = async (fn, ok) => { try { const x = await fn(); if (ok) toast(ok); return x } catch (e) { if (e instanceof ApiError) setErr(Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, v[0]]))); toast(e instanceof ApiError ? e.all() : e.message, 'err') } }
  const save = async (ev) => {
    ev.preventDefault(); setErr({})
    const body = { ...f, prix: Number(f.prix) }
    if (isNew) { const x = await wrap(() => post('formateur/cours', body), 'Cours créé. Ajoutez maintenant vos modules et leçons.'); if (x) nav(`/formateur/cours/${x.id}`, { replace: true }) } else { await wrap(() => put(`formateur/cours/${id}`, body), 'Cours enregistré.'); r.reload() }
  }
  const ajouterModule = async () => { const t = prompt('Titre du module'); if (t?.trim()) { await wrap(() => post(`formateur/cours/${id}/modules`, { titre: t }), 'Module ajouté.'); r.reload() } }
  const renommer = async (m) => { const t = prompt('Nouveau titre', m.titre); if (t?.trim()) { await wrap(() => put(`formateur/modules/${m.id}`, { titre: t }), 'Module renommé.'); r.reload() } }
  const supprimer = async (fn, msg, ok) => { if (confirm(msg)) { await wrap(fn, ok); r.reload() } }
  const publier = async (p) => { await wrap(() => post(`formateur/cours/${id}/publier`, { publie: p }), p ? 'Cours publié : il apparaît dans le catalogue.' : 'Cours retiré du catalogue.'); r.reload() }
  const total = c ? c.modules.reduce((s, m) => s + m.lecons.length, 0) : 0

  return (
    <div className="mx-auto max-w-4xl px-4 py-8">
      <Link to="/formateur" className="text-sm font-semibold text-slate-500 hover:text-brand-700">← Espace formateur</Link>
      <div className="mb-5 mt-2 flex flex-wrap items-center justify-between gap-3"><h1 className="text-2xl font-extrabold text-slate-900">{isNew ? 'Nouveau cours' : c.titre}</h1>{!isNew && <span className={`badge ${c.publie ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}`}>{c.publie ? 'Publié' : 'Brouillon'}</span>}</div>
      {!isNew && <div className="mb-5 flex flex-wrap gap-2">{[['programme', '📚 Programme'], ['infos', '✎ Informations'], ['publication', '🚀 Publication']].map(([k, l]) => <button key={k} onClick={() => setTab(k)} className={`rounded-full px-4 py-2 text-sm font-bold ${tab === k ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'}`}>{l}</button>)}</div>}

      {tab === 'infos' && (
        <form onSubmit={save} className="card grid gap-4 p-6 sm:grid-cols-2" noValidate>
          <Field label="Titre du cours" error={err.titre} className="sm:col-span-2"><input className="input" value={f.titre} onChange={set('titre')} placeholder="Ex. Excel : tableaux, formules et graphiques" /></Field>
          <Field label="Résumé (200 caractères max.)" error={err.resume} className="sm:col-span-2"><input className="input" value={f.resume} onChange={set('resume')} maxLength={200} /></Field>
          <Field label="Description détaillée" error={err.description} className="sm:col-span-2"><textarea className="input min-h-32" value={f.description} onChange={set('description')} /></Field>
          <Field label="Catégorie"><select className="input" value={f.categorie} onChange={set('categorie')}>{Object.keys(CATS).map((c) => <option key={c}>{c}</option>)}</select></Field>
          <Field label="Niveau"><select className="input" value={f.niveau} onChange={set('niveau')}>{Object.entries(NIVEAUX).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
          <Field label="Prix (FCFA, 0 = gratuit)" error={err.prix}><input type="number" min="0" step="500" className="input" value={f.prix} onChange={set('prix')} /></Field>
          <div className="sm:col-span-2"><button className="btn-primary">{isNew ? 'Créer le cours' : 'Enregistrer'}</button></div>
        </form>
      )}

      {tab === 'programme' && !isNew && (
        <div className="space-y-4">
          {c.modules.length === 0 && <Empty icon="📚" title="Aucun module" >Commencez par ajouter un module (ex. « Introduction »).</Empty>}
          {c.modules.map((m, i) => (
            <section key={m.id} className="card p-5"><div className="flex flex-wrap items-center gap-2"><span className="grid h-7 w-7 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">{i + 1}</span><h2 className="flex-1 font-extrabold text-slate-900">{m.titre}</h2>
              <button className="btn-ghost !py-1 text-xs" onClick={() => renommer(m)}>✎</button><button className="btn-ghost !py-1 text-xs text-rose-600" onClick={() => supprimer(() => del(`formateur/modules/${m.id}`), 'Supprimer ce module et ses leçons ?', 'Module supprimé.')}>🗑</button></div>
              <ul className="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-100">{m.lecons.map((l) => <li key={l.id} className="flex items-center gap-3 px-3 py-2 text-sm"><span aria-hidden="true">{l.type === 'video' ? '▶️' : '📄'}</span><span className="flex-1">{l.titre}{l.apercu && <span className="badge ml-2 bg-emerald-50 text-emerald-700">Aperçu</span>}</span><span className="text-xs text-slate-400">{l.duree} min</span>
                <button className="btn-ghost !py-1 text-xs" onClick={() => setLecon({ module: m, l })}>✎</button><button className="btn-ghost !py-1 text-xs text-rose-600" onClick={() => supprimer(() => del(`formateur/lecons/${l.id}`), 'Supprimer cette leçon ?', 'Leçon supprimée.')}>🗑</button></li>)}
                {m.lecons.length === 0 && <li className="px-3 py-3 text-sm text-slate-400">Aucune leçon.</li>}</ul>
              <div className="mt-3 flex flex-wrap gap-2"><button className="btn-ghost !py-1.5 text-xs" onClick={() => setLecon({ module: m })}>＋ Leçon</button><button className="btn-ghost !py-1.5 text-xs" onClick={() => setQuiz(m)}>❓ {m.quiz ? `Modifier le quiz (${m.quiz.questions.length} questions)` : 'Ajouter un quiz'}</button></div></section>))}
          <button className="btn-primary" onClick={ajouterModule}>＋ Ajouter un module</button>
        </div>
      )}

      {tab === 'publication' && !isNew && (
        <div className="card space-y-4 p-6"><p className="text-slate-700">Votre cours contient <b>{c.modules.length} module(s)</b> et <b>{total} leçon(s)</b>. {c.publie ? 'Il est visible dans le catalogue.' : 'Il n’est pas encore visible dans le catalogue.'}</p>
          <div className="flex flex-wrap gap-2"><Link className="btn-ghost" to={`/apprendre/${c.slug}`}>👁 Prévisualiser</Link>{c.publie ? <button className="btn-ghost" onClick={() => publier(false)}>Retirer du catalogue</button> : <button className="btn-primary" disabled={total < 1} onClick={() => publier(true)}>🚀 Publier le cours</button>}<Link className="btn-ghost" to={`/cours/${c.slug}`}>Voir la page publique</Link></div>
          {total < 1 && <p className="text-sm font-semibold text-amber-700">Ajoutez au moins une leçon pour pouvoir publier.</p>}
          <button className="btn-ghost text-rose-600" onClick={async () => { if (confirm('Supprimer définitivement ce cours ?')) { await wrap(() => del(`formateur/cours/${id}`), 'Cours supprimé.'); nav('/formateur') } }}>🗑 Supprimer le cours</button></div>
      )}

      {lecon && <LeconModal ctx={lecon} onClose={() => setLecon(null)} onSaved={() => { setLecon(null); r.reload() }} />}
      {quiz && <QuizModal module={quiz} onClose={() => setQuiz(null)} onSaved={() => { setQuiz(null); r.reload() }} />}
    </div>
  )
}

function LeconModal({ ctx, onClose, onSaved }) {
  const toast = useToast()
  const l = ctx.l
  const [f, setF] = useState({ titre: l?.titre || '', type: l?.type || 'texte', contenu: l?.contenu || '', duree: l?.duree || 5, apercu: !!l?.apercu })
  const [err, setErr] = useState({})
  const set = (k) => (e) => setF({ ...f, [k]: e.target.type === 'checkbox' ? e.target.checked : e.target.value })
  const submit = async (e) => {
    e.preventDefault(); setErr({})
    try { const b = { ...f, duree: Number(f.duree) }; l ? await put(`formateur/lecons/${l.id}`, b) : await post(`formateur/modules/${ctx.module.id}/lecons`, b); toast('Leçon enregistrée.'); onSaved() } catch (x) { if (x instanceof ApiError) setErr(Object.fromEntries(Object.entries(x.errors).map(([k, v]) => [k, v[0]]))); toast(x instanceof ApiError ? x.all() : x.message, 'err') }
  }
  return (
    <Modal title={l ? 'Modifier la leçon' : `Nouvelle leçon — ${ctx.module.titre}`} onClose={onClose} wide>
      <form onSubmit={submit} className="space-y-3" noValidate>
        <Field label="Titre" error={err.titre}><input className="input" value={f.titre} onChange={set('titre')} autoFocus /></Field>
        <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Type de leçon">{[['texte', '📄 Texte'], ['video', '▶️ Vidéo (YouTube / Vimeo)']].map(([k, lab]) => <button type="button" key={k} role="radio" aria-checked={f.type === k} onClick={() => setF({ ...f, type: k, contenu: '' })} className={`rounded-xl border-2 p-2.5 text-sm font-bold ${f.type === k ? 'border-brand-700 bg-brand-50' : 'border-slate-200'}`}>{lab}</button>)}</div>
        {f.type === 'video' ? <Field label="Adresse de la vidéo" error={err.contenu} hint="Ex. https://www.youtube.com/watch?v=…"><input className="input" value={f.contenu} onChange={set('contenu')} placeholder="https://www.youtube.com/watch?v=" /></Field>
          : <Field label="Contenu de la leçon" error={err.contenu}><textarea className="input min-h-40" value={f.contenu} onChange={set('contenu')} /></Field>}
        <div className="grid gap-3 sm:grid-cols-2"><Field label="Durée (minutes)" error={err.duree}><input type="number" min="1" className="input" value={f.duree} onChange={set('duree')} /></Field>
          <label className="flex items-center gap-3 self-end rounded-xl bg-slate-50 p-3 text-sm font-semibold"><input type="checkbox" className="h-5 w-5 accent-violet-700" checked={f.apercu} onChange={set('apercu')} /> Leçon d’aperçu gratuit</label></div>
        <div className="flex gap-2 pt-1"><button type="button" className="btn-ghost flex-1" onClick={onClose}>Annuler</button><button className="btn-primary flex-1">Enregistrer</button></div>
      </form>
    </Modal>
  )
}

function QuizModal({ module: m, onClose, onSaved }) {
  const toast = useToast()
  const init = m.quiz ? m.quiz.questions.map((q) => ({ question: q.question, choix: [...q.choix], bonne: q.bonne })) : [{ question: '', choix: ['', ''], bonne: 0 }]
  const [titre, setTitre] = useState(m.quiz?.titre || `Quiz : ${m.titre}`)
  const [qs, setQs] = useState(init)
  const setQ = (i, patch) => setQs(qs.map((q, j) => (j === i ? { ...q, ...patch } : q)))
  const save = async () => {
    try { await put(`formateur/modules/${m.id}/quiz`, { titre, questions: qs }); toast('Quiz enregistré.'); onSaved() } catch (x) { toast(x instanceof ApiError ? x.all() : x.message, 'err') }
  }
  const retirer = async () => { if (!confirm('Supprimer ce quiz ?')) return; try { await del(`formateur/modules/${m.id}/quiz`); toast('Quiz supprimé.'); onSaved() } catch (x) { toast(x.message, 'err') } }
  return (
    <Modal title={`Quiz — ${m.titre}`} onClose={onClose} wide>
      <Field label="Titre du quiz"><input className="input" value={titre} onChange={(e) => setTitre(e.target.value)} /></Field>
      <div className="mt-4 space-y-4">{qs.map((q, i) => (
        <div key={i} className="rounded-xl border border-slate-200 p-4"><div className="flex gap-2"><input className="input" placeholder={`Question ${i + 1}`} value={q.question} onChange={(e) => setQ(i, { question: e.target.value })} aria-label={`Question ${i + 1}`} /><button className="btn-ghost text-rose-600" disabled={qs.length === 1} onClick={() => setQs(qs.filter((_, j) => j !== i))} aria-label="Supprimer la question">🗑</button></div>
          <div className="mt-3 space-y-2">{q.choix.map((c, k) => <div key={k} className="flex items-center gap-2"><input type="radio" name={`b${i}`} checked={q.bonne === k} onChange={() => setQ(i, { bonne: k })} className="h-4 w-4 accent-emerald-600" aria-label={`Bonne réponse : proposition ${k + 1}`} /><input className="input" placeholder={`Proposition ${k + 1}`} value={c} onChange={(e) => setQ(i, { choix: q.choix.map((x, z) => (z === k ? e.target.value : x)) })} aria-label={`Proposition ${k + 1}`} />{q.choix.length > 2 && <button className="text-slate-400 hover:text-rose-600" onClick={() => setQ(i, { choix: q.choix.filter((_, z) => z !== k), bonne: q.bonne >= q.choix.length - 1 ? 0 : q.bonne })} aria-label="Retirer la proposition">✕</button>}</div>)}</div>
          {q.choix.length < 5 && <button className="mt-2 text-xs font-bold text-brand-700" onClick={() => setQ(i, { choix: [...q.choix, ''] })}>＋ Ajouter une proposition</button>}<p className="mt-1 text-xs text-slate-400">Cochez la bonne réponse.</p></div>))}</div>
      <button className="btn-ghost mt-3" onClick={() => setQs([...qs, { question: '', choix: ['', ''], bonne: 0 }])}>＋ Ajouter une question</button>
      <div className="mt-5 flex flex-wrap gap-2">{m.quiz && <button className="btn-ghost text-rose-600" onClick={retirer}>Supprimer le quiz</button>}<button className="btn-ghost ml-auto" onClick={onClose}>Annuler</button><button className="btn-primary" onClick={save}>Enregistrer le quiz</button></div>
    </Modal>
  )
}
