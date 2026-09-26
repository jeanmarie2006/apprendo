import { useEffect } from 'react'
import { Navigate, Route, Routes, useLocation } from 'react-router-dom'
import Layout from './components/Layout.jsx'
import { Catalogue, CoursePage, Home, VerifierCertificat } from './pages/Public.jsx'
import Player, { MesCours } from './pages/Player.jsx'
import { Builder, Formateur } from './pages/Formateur.jsx'
import AuthPage from './pages/AuthPage.jsx'
import Installer from './pages/Installer.jsx'

function ScrollTop() {
  const { pathname } = useLocation()
  useEffect(() => { window.scrollTo(0, 0) }, [pathname])
  return null
}

export default function App() {
  return (
    <>
      <ScrollTop />
      <Routes>
        <Route path="/installer" element={<Installer />} />
        <Route path="/apprendre/:slug" element={<Player />} />
        <Route path="*" element={
          <Layout>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/catalogue" element={<Catalogue />} />
              <Route path="/cours/:slug" element={<CoursePage />} />
              <Route path="/certificat/:numero" element={<VerifierCertificat />} />
              <Route path="/mes-cours" element={<MesCours />} />
              <Route path="/formateur" element={<Formateur />} />
              <Route path="/formateur/cours/:id" element={<Builder />} />
              <Route path="/connexion" element={<AuthPage mode="login" />} />
              <Route path="/inscription" element={<AuthPage mode="register" />} />
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </Layout>
        } />
      </Routes>
    </>
  )
}
