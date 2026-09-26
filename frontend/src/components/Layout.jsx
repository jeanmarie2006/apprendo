import { useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../lib/auth.jsx'
import { InstallButton } from '../lib/pwa.jsx'
import { Stars } from '../lib/ui.jsx'
import { cat, fmt, NIVEAUX } from '../lib/lms.js'

export function Logo({ to = '/', light = false }) {
  return (
    <Link to={to} className={`flex items-center gap-2.5 font-extrabold tracking-tight ${light ? 'text-white' : 'text-slate-900'}`} aria-label="Apprendo — accueil">
      <img src="icon-192.png" alt="" className="h-9 w-9 rounded-xl" /><span className="text-lg">Appren<span className={light ? 'text-brand-500' : 'text-brand-700'}>do</span></span>
    </Link>
  )
}

export default function Layout({ children }) {
  const { user, logout } = useAuth()
  const nav = useNavigate()
  const [open, setOpen] = useState(false)
  const link = ({ isActive }) => `rounded-lg px-3 py-2 text-sm font-semibold transition ${isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'}`
  const items = [['/', 'Accueil', true], ['/catalogue', 'Catalogue'], ...(user?.role === 'apprenant' ? [['/mes-cours', 'Mes cours']] : []), ...(user?.role === 'formateur' ? [['/formateur', 'Espace formateur']] : [])]
  return (
    <div className="flex min-h-screen flex-col">
      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3"><Logo />
          <nav className="ml-2 hidden items-center gap-1 md:flex" aria-label="Navigation principale">{items.map(([to, l, end]) => <NavLink key={to} to={to} end={end} className={link}>{l}</NavLink>)}</nav>
          <div className="ml-auto flex items-center gap-2">
            <InstallButton className="btn-ghost hidden lg:inline-flex !py-2" label="⬇ Installer" />
            {user ? <><span className="hidden text-sm font-semibold text-slate-600 sm:inline">{user.name.split(' ')[0]} <span className="badge bg-brand-100 text-brand-700">{user.role}</span></span><button className="btn-ghost !py-2" onClick={async () => { await logout(); nav('/') }}>Quitter</button></>
              : <><Link to="/connexion" className="btn-ghost !py-2">Connexion</Link><Link to="/inscription" className="btn-primary !py-2 hidden sm:inline-flex">S’inscrire</Link></>}
            <button className="btn-ghost !px-3 !py-2 md:hidden" onClick={() => setOpen(!open)} aria-expanded={open} aria-label="Menu">☰</button>
          </div></div>
        {open && <nav className="grid gap-1 border-t border-slate-100 bg-white px-4 py-3 md:hidden" onClick={() => setOpen(false)}>{items.map(([to, l, end]) => <NavLink key={to} to={to} end={end} className={link}>{l}</NavLink>)}<NavLink to="/installer" className={link}>⬇ Installer l’application</NavLink></nav>}
      </header>
      <main className="flex-1">{children}</main>
      <footer className="border-t border-slate-200 bg-white py-8 text-center text-sm text-slate-500"><p><Link to="/installer" className="font-semibold text-brand-700 hover:underline">Installer l’application</Link> · Projet de démonstration : formations, formateurs et paiements fictifs. Vidéos YouTube intégrées à titre d’exemple.</p><p className="mt-1">Réalisé par <a className="font-semibold text-brand-700 hover:underline" href="https://sedjame-vianney.vercel.app" target="_blank" rel="noopener">Sedjame Vianney</a></p></footer>
    </div>
  )
}

export function CourseCard({ c }) {
  const [a, b, icon] = cat(c.categorie)
  return (
    <Link to={`/cours/${c.slug}`} className="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-xl">
      <div className="relative grid h-36 place-items-center text-5xl text-white" style={{ background: `linear-gradient(135deg, ${a}, ${b})` }}><span aria-hidden="true">{icon}</span><span className="absolute left-3 top-3 rounded-full bg-black/25 px-2.5 py-0.5 text-[11px] font-bold">{NIVEAUX[c.niveau]}</span></div>
      <div className="flex flex-1 flex-col p-5">
        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{c.categorie}</p>
        <h3 className="mt-1 line-clamp-2 font-extrabold leading-snug text-slate-900 group-hover:text-brand-700">{c.titre}</h3>
        <p className="mt-1 text-sm text-slate-500">{c.formateur}</p>
        <div className="mt-2 flex items-center gap-1.5 text-xs text-slate-500">{c.note ? <><b className="text-slate-800">{Number(c.note).toFixed(1).replace('.', ',')}</b><Stars value={c.note} size="text-xs" /><span>({c.nb_avis})</span></> : <span>Nouveau</span>}<span>· {c.inscrits} inscrits</span></div>
        <div className="mt-auto flex items-center justify-between pt-4"><span className="text-xs text-slate-400">{c.nb_lecons} leçons</span><b className={`text-lg ${c.prix ? 'text-slate-900' : 'text-emerald-600'}`}>{fmt(c.prix)}</b></div>
      </div>
    </Link>
  )
}
