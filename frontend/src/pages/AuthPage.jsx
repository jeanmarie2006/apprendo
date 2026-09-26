import { useState } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { Logo } from '../components/Layout.jsx'
import { ApiError } from '../lib/api.js'
import { useAuth } from '../lib/auth.jsx'
import { Field } from '../lib/ui.jsx'

export default function AuthPage({ mode }) {
  const isReg = mode === 'register'
  const { user, login, register } = useAuth()
  const nav = useNavigate()
  const [sp] = useSearchParams()
  const [f, setF] = useState({ name: '', email: '', password: '', role: sp.get('role') === 'formateur' ? 'formateur' : 'apprenant' })
  const [err, setErr] = useState({})
  const [msg, setMsg] = useState('')
  const [busy, setBusy] = useState(false)
  const home = (u) => (u.role === 'formateur' ? '/formateur' : '/mes-cours')
  if (user) return <Navigate to={home(user)} replace />
  const set = (k) => (e) => setF({ ...f, [k]: e.target.value })
  const enter = async (creds) => {
    setErr({}); setMsg(''); setBusy(true)
    try { const u = creds ? await login(creds) : isReg ? await register(f) : await login(f); nav(home(u)) }
    catch (x) { if (x instanceof ApiError) { setErr(Object.fromEntries(Object.entries(x.errors).map(([k, v]) => [k, v[0]]))); setMsg(x.message) } else setMsg('Erreur inattendue.') } finally { setBusy(false) }
  }
  return (
    <div className="grid min-h-[80vh] place-items-center px-4 py-10"><div className="w-full max-w-md"><div className="mb-6 flex justify-center"><Logo /></div>
      <form onSubmit={(e) => { e.preventDefault(); enter() }} className="card space-y-4 p-7" noValidate>
        <div><h1 className="text-2xl font-extrabold text-slate-900">{isReg ? 'Créer un compte' : 'Connexion'}</h1><p className="text-sm text-slate-500">{isReg ? 'Apprenez ou transmettez votre savoir-faire.' : 'Retrouvez vos formations.'}</p></div>
        {msg && !Object.keys(err).length && <div role="alert" className="rounded-xl bg-rose-50 px-3.5 py-2.5 text-sm font-medium text-rose-700">{msg}</div>}
        {isReg && <div role="radiogroup" aria-label="Type de compte" className="grid grid-cols-2 gap-2">{[['apprenant', '🎓', 'Apprenant'], ['formateur', '👩‍🏫', 'Formateur']].map(([v, i, l]) => <button type="button" key={v} role="radio" aria-checked={f.role === v} onClick={() => setF({ ...f, role: v })} className={`rounded-xl border-2 p-3 text-center transition ${f.role === v ? 'border-brand-700 bg-brand-50' : 'border-slate-200 hover:border-slate-300'}`}><span className="text-2xl">{i}</span><b className="block text-sm">{l}</b></button>)}</div>}
        {isReg && <Field label="Nom complet" error={err.name}><input className="input" value={f.name} onChange={set('name')} autoComplete="name" /></Field>}
        <Field label="E-mail" error={err.email}><input type="email" className="input" value={f.email} onChange={set('email')} autoComplete="email" /></Field>
        <Field label="Mot de passe" error={err.password} hint={isReg ? '8 caractères minimum' : undefined}><input type="password" className="input" value={f.password} onChange={set('password')} autoComplete={isReg ? 'new-password' : 'current-password'} /></Field>
        <button className="btn-primary w-full" disabled={busy}>{busy ? 'Patientez…' : isReg ? 'Créer mon compte' : 'Se connecter'}</button>
        {!isReg && <div className="grid gap-2 sm:grid-cols-2"><button type="button" className="btn-ghost text-xs" disabled={busy} onClick={() => enter({ email: 'apprenant@apprendo.bj', password: 'demo1234' })}>Démo : apprenante</button><button type="button" className="btn-ghost text-xs" disabled={busy} onClick={() => enter({ email: 'formateur@apprendo.bj', password: 'demo1234' })}>Démo : formateur</button></div>}
        <p className="text-center text-sm text-slate-500">{isReg ? <>Déjà inscrit ? <Link className="font-semibold text-brand-700 hover:underline" to="/connexion">Connexion</Link></> : <>Pas encore de compte ? <Link className="font-semibold text-brand-700 hover:underline" to="/inscription">Inscription</Link></>}</p>
      </form></div></div>
  )
}
