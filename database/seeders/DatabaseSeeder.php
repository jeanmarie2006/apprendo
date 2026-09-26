<?php

namespace Database\Seeders;

use App\Models\AvisCours;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Progression;
use App\Models\Tentative;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Catalogue fictif : 9 formations, 3 formateurs, 8 apprenants. Les vidéos sont des intégrations YouTube publiques. */
class DatabaseSeeder extends Seeder
{
    private function t(string $titre, string $contenu, int $duree = 6): array
    {
        return ['titre' => $titre, 'type' => 'texte', 'contenu' => $contenu, 'duree' => $duree];
    }

    private function v(string $titre, string $id, int $duree = 25, bool $apercu = false): array
    {
        return ['titre' => $titre, 'type' => 'video', 'contenu' => "https://www.youtube.com/watch?v=$id", 'duree' => $duree, 'apercu' => $apercu];
    }

    /** Question à choix : la bonne réponse est toujours listée en premier puis mélangée par le seeder. */
    private function q(string $question, string $bonne, array $autres): array
    {
        return ['question' => $question, 'bonne' => $bonne, 'autres' => $autres];
    }

    public function run(): void
    {
        mt_srand(3);
        $koffi = User::create(['name' => 'Koffi Adjovi', 'email' => 'formateur@apprendo.bj', 'password' => 'demo1234', 'role' => 'formateur', 'bio' => 'Développeur web et enseignant à Calavi. Je transmets ce que j’aurais aimé apprendre plus tôt.']);
        $mireille = User::create(['name' => 'Mireille Tokpo', 'email' => 'mireille@apprendo.bj', 'password' => 'demo1234', 'role' => 'formateur', 'bio' => 'Consultante en marketing digital et entrepreneuriat, 10 ans d’accompagnement de PME béninoises.']);
        $serge = User::create(['name' => 'Serge Hounkpatin', 'email' => 'serge@apprendo.bj', 'password' => 'demo1234', 'role' => 'formateur', 'bio' => 'Expert-comptable, formateur en gestion et bureautique.']);
        $demo = User::create(['name' => 'Awa Sossou', 'email' => 'apprenant@apprendo.bj', 'password' => 'demo1234', 'role' => 'apprenant']);
        $app = [$demo];
        foreach (['Serge Houngbo', 'Carine Mêdénou', 'Ibrahim Salami', 'Estelle Kiki', 'Modeste Fanou', 'Rachidath Bio', 'Fatou Dègbè'] as $n) {
            $app[] = User::create(['name' => $n, 'email' => Str::slug($n).'@apprendo.bj', 'password' => 'demo1234', 'role' => 'apprenant']);
        }

        $catalogue = [
            [$koffi, 'HTML & CSS : créer votre premier site web', 'Développement web', 'debutant', 0, 'Apprenez à écrire des pages web propres et à les mettre en forme, sans aucun prérequis.',
                'Cette formation gratuite vous guide de zéro jusqu’à votre première page web complète : structure HTML, textes, images, liens, puis mise en forme avec CSS et mise en page moderne avec Flexbox. À la fin, vous saurez construire un site vitrine simple.', [
                ['Les bases du HTML', [
                    $this->v('Cours vidéo : apprendre le HTML pas à pas', 'kUMe1FH4CHE', 30, true),
                    $this->t('Structure d’une page web', "Une page HTML commence par <!DOCTYPE html>, puis une balise <html> qui contient deux parties : <head> (titre, encodage, feuilles de style) et <body> (le contenu visible). Pensez toujours à déclarer la langue (lang=\"fr\") et l'encodage UTF-8 pour que les accents s'affichent correctement.", 5),
                    $this->t('Les balises essentielles', "Titres : h1 à h6 (un seul h1 par page). Paragraphes : p. Liens : a href. Images : img src alt (l'attribut alt décrit l'image, il est indispensable pour l'accessibilité). Listes : ul/ol avec li. Regroupements : div et section. Utilisez des balises sémantiques (header, nav, main, footer) pour structurer votre page.", 8)],
                    ['Qu’est-ce que la balise <head> contient ?', 'Le titre, l’encodage et les feuilles de style', ['Le contenu visible de la page', 'Uniquement les images', 'Les liens du menu']],
                    ['Quel attribut décrit une image pour l’accessibilité ?', 'alt', ['src', 'title', 'href']],
                    ['Combien de balises h1 recommande-t-on par page ?', 'Une seule', ['Autant que l’on veut', 'Aucune', 'Trois']]],
                ['Le style avec CSS', [
                    $this->t('Sélecteurs, couleurs et polices', "CSS décrit l'apparence : un sélecteur (p, .classe, #id) suivi de déclarations propriété: valeur. Exemple : h1 { color: #1e3a8a; font-size: 2rem; }. Préférez les classes aux identifiants pour réutiliser vos styles, et définissez une palette de 3 à 4 couleurs cohérentes.", 7),
                    $this->t('Mise en page avec Flexbox', "Flexbox aligne les éléments sur un axe. Sur le conteneur : display: flex; gap: 1rem; justify-content: space-between; align-items: center. Chaque enfant devient un élément flexible. Ajoutez flex-wrap: wrap pour que les éléments passent à la ligne sur mobile.", 8)],
                    ['Quelle propriété active Flexbox ?', 'display: flex', ['position: flex', 'flex: display', 'layout: flex']],
                    ['Comment cible-t-on tous les éléments de classe « carte » ?', '.carte { }', ['#carte { }', 'carte { }', '*carte { }']]]]],
            [$koffi, 'JavaScript pour débutants', 'Développement web', 'debutant', 15000, 'Variables, conditions, boucles, fonctions et DOM : les fondamentaux pour rendre vos pages interactives.',
                'Le langage de la programmation web expliqué simplement, avec des exemples concrets : calculatrice, liste de tâches et validation de formulaire. Chaque module se termine par un quiz.', [
                ['Les fondamentaux', [
                    $this->v('Cours vidéo : JavaScript pour débutants', 'W6NZfCO5SIk', 45, true),
                    $this->t('Variables et types de données', "Déclarez une variable avec let (modifiable) ou const (constante) : const prenom = 'Awa'; let age = 20;. Les types de base : chaîne (string), nombre (number), booléen (true/false), tableau et objet. Utilisez console.log() pour afficher une valeur pendant vos essais.", 6),
                    $this->t('Conditions et boucles', "if (age >= 18) { ... } else { ... } exécute un bloc selon une condition. Une boucle for (let i = 0; i < 5; i++) répète des instructions. La méthode tableau.forEach() parcourt une liste : c'est la façon moderne de faire une boucle sur des données.", 8)],
                    ['Quel mot-clé déclare une constante ?', 'const', ['let', 'var', 'fix']],
                    ['Quelle instruction affiche une valeur dans la console ?', 'console.log()', ['print()', 'echo()', 'write()']],
                    ['Quel type de valeur est true ?', 'Un booléen', ['Une chaîne', 'Un nombre', 'Un tableau']]],
                ['Fonctions et DOM', [
                    $this->t('Écrire des fonctions', "function additionner(a, b) { return a + b; } ou, en syntaxe fléchée : const additionner = (a, b) => a + b;. Une fonction regroupe du code réutilisable. Donnez-lui un nom qui dit ce qu'elle fait, et faites en sorte qu'elle ne fasse qu'une seule chose.", 7),
                    $this->t('Modifier la page (DOM)', "document.querySelector('.bouton') sélectionne un élément ; addEventListener('click', fonction) réagit à un clic ; element.textContent modifie son texte. Avec ces trois outils, vous pouvez déjà créer un compteur, un menu mobile ou une validation de formulaire.", 9)],
                    ['Comment réagir à un clic ?', 'addEventListener(\'click\', …)', ['onClickEvent()', 'listen(click)', 'document.click()']],
                    ['Que renvoie une fonction sans instruction return ?', 'undefined', ['0', 'null', 'Une erreur']]]]],
            [$koffi, 'React : construire des interfaces modernes', 'Développement web', 'intermediaire', 25000, 'Composants, état, effets et routage : créez des applications web réactives avec React et Vite.',
                'Pour ceux qui maîtrisent déjà JavaScript. Vous construirez une application complète avec composants réutilisables, gestion d’état, formulaires et appels d’API.', [
                ['Les composants', [
                    $this->v('Cours vidéo : React pour débutants', 'Ke90Tje7VS0', 90, true),
                    $this->t('Composants et props', "Un composant React est une fonction qui retourne du JSX. Les props sont ses paramètres : function Carte({ titre }) { return <h2>{titre}</h2> }. Découpez l'interface en petits composants : un bouton, une carte, une liste. Chaque composant a une seule responsabilité.", 8),
                    $this->t('L’état avec useState', "const [compteur, setCompteur] = useState(0); crée une valeur mémorisée par React. Appeler setCompteur(compteur + 1) met à jour l'état ET redessine le composant. Ne modifiez jamais l'état directement : passez toujours par la fonction de mise à jour.", 8)],
                    ['Que retourne un composant React ?', 'Du JSX', ['Du SQL', 'Un fichier CSS', 'Une requête HTTP']],
                    ['Quel hook gère un état local ?', 'useState', ['useRoute', 'useFetch', 'useClass']],
                    ['Comment met-on à jour un état ?', 'Avec la fonction de mise à jour', ['En modifiant la variable', 'Avec document.write', 'En rechargeant la page']]]]],
            [$mireille, 'Community management pour PME', 'Marketing digital', 'debutant', 12000, 'Animer les réseaux sociaux de votre entreprise : stratégie, contenus, publicité et mesure des résultats.',
                'Une méthode concrète pour gérer Facebook, Instagram et WhatsApp Business : définir sa cible, planifier un calendrier éditorial, créer des contenus qui engagent et lancer sa première publicité avec un petit budget.', [
                ['Définir sa stratégie', [
                    $this->t('Connaître sa cible', "Avant de publier, répondez à trois questions : qui sont vos clients (âge, ville, habitudes) ? Quels problèmes résolvez-vous pour eux ? Où passent-ils du temps en ligne ? Une PME béninoise vend souvent mieux sur Facebook et WhatsApp que sur LinkedIn : choisissez peu de canaux et faites-le bien.", 7),
                    $this->t('Le calendrier éditorial', "Planifiez vos publications sur un mois : 40 % de contenu utile (conseils, astuces), 30 % de preuve sociale (avis, photos clients), 20 % de coulisses, 10 % d'offres. Publiez à heures régulières et réservez 15 minutes par jour pour répondre aux messages.", 8)],
                    ['Combien de canaux faut-il privilégier au départ ?', 'Peu, choisis selon la cible', ['Tous en même temps', 'Aucun', 'Uniquement LinkedIn']],
                    ['Quel type de contenu doit dominer ?', 'Le contenu utile pour le client', ['Uniquement des promotions', 'Des mèmes', 'Des messages sans lien avec l’activité']]],
                ['Créer et mesurer', [
                    $this->t('Des contenus qui engagent', "Une bonne publication a un visuel net, un texte court avec une accroche dans la première ligne, et un appel à l'action clair (« Écrivez-nous sur WhatsApp »). Les vidéos verticales de 15 à 30 secondes fonctionnent très bien. Montrez des personnes réelles et des résultats concrets.", 7),
                    $this->t('Lancer sa première publicité', "Avec 3 000 à 5 000 FCFA par jour, ciblez votre ville et vos centres d'intérêt, testez deux visuels et gardez le meilleur. Suivez trois indicateurs : la portée (combien de personnes), les clics et le coût par message reçu.", 8)],
                    ['Que doit contenir un bon appel à l’action ?', 'Une consigne claire et simple', ['Un long paragraphe', 'Aucun lien', 'Plusieurs demandes différentes']]]]],
            [$mireille, 'Créer et gérer sa micro-entreprise au Bénin', 'Entrepreneuriat', 'debutant', 0, 'De l’idée au premier client : formalités, prix, trésorerie et organisation pour bien démarrer.',
                'Un parcours gratuit pour les futurs entrepreneurs : valider son idée, choisir son statut, fixer ses prix, suivre sa trésorerie et trouver ses premiers clients.', [
                ['Lancer son activité', [
                    $this->t('Valider son idée', "Parlez à 10 clients potentiels avant d'investir : quel est leur problème, combien paient-ils aujourd'hui, accepteraient-ils de payer votre solution ? Testez avec une offre minimale (un produit, une ville) et améliorez-la chaque semaine.", 6),
                    $this->t('Fixer ses prix', "Prix de vente = coûts (matière, transport, temps) + marge. Comparez avec la concurrence sans vous aligner aveuglément : un prix trop bas fait douter de la qualité. Calculez votre seuil de rentabilité : le nombre de ventes nécessaire pour couvrir vos charges.", 7),
                    $this->t('Suivre sa trésorerie', "Notez chaque entrée et sortie d'argent dans un cahier ou un tableur. Séparez votre argent personnel de celui de l'entreprise dès le premier jour. Gardez une réserve équivalente à un mois de charges pour absorber les imprévus.", 7)],
                    ['Quelle est la formule de base du prix de vente ?', 'Coûts + marge', ['Coûts seulement', 'Le prix du concurrent moins 50 %', 'Un prix au hasard']],
                    ['Pourquoi séparer son argent personnel de celui de l’entreprise ?', 'Pour suivre les vrais résultats de l’activité', ['Pour payer moins d’impôts', 'Ce n’est pas utile', 'Pour dépenser plus']]]]],
            [$serge, 'Excel : tableaux, formules et graphiques', 'Bureautique', 'debutant', 10000, 'Maîtrisez les formules essentielles, les tableaux croisés et les graphiques pour gagner du temps au bureau.',
                'Des cas pratiques tirés du quotidien d’une PME : suivi des ventes, budget mensuel, facturation. Vous repartez avec des modèles prêts à l’emploi.', [
                ['Formules essentielles', [
                    $this->t('SOMME, MOYENNE et SI', "=SOMME(B2:B10) additionne une plage ; =MOYENNE(B2:B10) calcule la moyenne ; =SI(B2>=10000;\"Élevé\";\"Faible\") teste une condition. Utilisez le signe dollar pour figer une cellule (=B2*\$F\$1) : c'est indispensable pour appliquer un taux de TVA à toute une colonne.", 8),
                    $this->t('RECHERCHEV et tableaux croisés', "RECHERCHEV retrouve une information dans un tableau à partir d'un identifiant (le prix d'un produit à partir de son code). Un tableau croisé dynamique résume des milliers de lignes en quelques clics : total par mois, par client ou par produit.", 9)],
                    ['Quel symbole fige une cellule dans une formule ?', '$', ['#', '&', '%']],
                    ['Quelle fonction additionne une plage ?', 'SOMME', ['TOTAL', 'PLUS', 'ADD']]]]],
            [$serge, 'Comptabilité générale pour non-comptables', 'Comptabilité', 'debutant', 18000, 'Comprendre le bilan, le compte de résultat et la TVA pour piloter son entreprise avec des chiffres fiables.',
                'Sans jargon inutile : vous apprendrez à lire les documents comptables, à enregistrer les opérations courantes et à dialoguer avec votre expert-comptable.', [
                ['Les documents comptables', [
                    $this->t('Le bilan', "Le bilan est une photographie du patrimoine à une date donnée. À gauche l'actif (ce que possède l'entreprise : stock, argent, équipements, créances) ; à droite le passif (ce qu'elle doit : dettes, capital, réserves). Les deux totaux sont toujours égaux.", 8),
                    $this->t('Le compte de résultat', "Il mesure l'activité sur une période : produits (ventes) moins charges (achats, salaires, loyer, impôts) égale résultat (bénéfice ou perte). Suivre son résultat chaque mois permet de corriger rapidement une dérive de coûts.", 8)],
                    ['Que mesure le compte de résultat ?', 'L’activité sur une période', ['Le patrimoine à une date', 'Le nombre de clients', 'Uniquement la TVA']],
                    ['Que contient l’actif d’un bilan ?', 'Ce que possède l’entreprise', ['Ce que l’entreprise doit', 'Les salaires', 'Les factures à venir']]]]],
            [$koffi, 'Design d’interface avec Figma', 'Design', 'intermediaire', 20000, 'Concevez des maquettes d’applications : grille, composants, prototypes cliquables et partage avec les développeurs.',
                'Une introduction pratique au design d’interface : hiérarchie visuelle, typographie, couleurs, composants réutilisables et prototypes interactifs.', [
                ['Principes de design', [
                    $this->t('Hiérarchie visuelle et espacements', "Guidez l'œil : le plus important est plus grand, plus contrasté et entouré d'espace. Utilisez une échelle d'espacements régulière (4, 8, 16, 24, 32 px) et alignez tout sur une grille de 8 px. Un design aéré paraît plus professionnel et se lit plus vite.", 7),
                    $this->t('Couleurs et typographie', "Limitez-vous à une couleur d'accent, des neutres (gris) et deux couleurs d'état (succès, erreur). Une seule famille de polices suffit, en 3 ou 4 tailles. Vérifiez le contraste texte/fond (au moins 4,5:1) pour rester lisible par tous.", 7)],
                    ['Sur quelle grille aligne-t-on souvent ses éléments ?', '8 px', ['13 px', '100 px', 'Aucune']],
                    ['Quel contraste minimum pour un texte courant ?', '4,5:1', ['1,2:1', '2:1', '10:1 exactement']]]]],
            [$mireille, 'Anglais professionnel : les bases', 'Langues', 'debutant', 8000, 'Se présenter, écrire un e-mail et tenir une réunion simple : l’anglais utile en entreprise.',
                'Un vocabulaire ciblé et des phrases prêtes à l’emploi pour vos premiers échanges professionnels : présentations, e-mails, appels téléphoniques.', [
                ['Se présenter et écrire', [
                    $this->t('Se présenter', "« Hello, my name is Awa. I work as an accountant at Sodeco in Cotonou. » Pour demander : « What do you do? », « Where are you from? ». Pour finir : « Nice to meet you. » Répétez ces phrases à voix haute chaque jour : la pratique orale compte plus que la grammaire au début.", 6),
                    $this->t('Écrire un e-mail professionnel', "Structure : « Dear Mr Smith, » (formule d'appel) ; « I am writing to… » (objet) ; le message en phrases courtes ; « Please find attached… » (pièce jointe) ; « Best regards, » et votre nom. Relisez toujours avant d'envoyer.", 7)],
                    ['Comment dit-on « Cordialement » ?', 'Best regards', ['Good night', 'See you', 'Thank you very much for all']]]]],
        ];

        $cours = [];
        foreach ($catalogue as $i => [$formateur, $titre, $cat, $niveau, $prix, $resume, $desc, $modules]) {
            $c = Cours::create(['formateur_id' => $formateur->id, 'titre' => $titre, 'slug' => Cours::uniqueSlug($titre), 'resume' => $resume, 'description' => $desc, 'categorie' => $cat, 'niveau' => $niveau, 'prix' => $prix, 'publie' => true, 'created_at' => now()->subDays(60 - $i * 5)]);
            foreach ($modules as $mi => $mod) {
                [$mt, $lecons] = [$mod[0], $mod[1]];
                $quizQ = array_slice($mod, 2);
                $m = $c->modules()->create(['titre' => $mt, 'ordre' => $mi + 1]);
                foreach ($lecons as $li => $l) {
                    $m->lecons()->create(['titre' => $l['titre'], 'type' => $l['type'], 'contenu' => $l['contenu'], 'duree' => $l['duree'], 'apercu' => $l['apercu'] ?? ($li === 1 && $mi === 0), 'ordre' => $li + 1]);
                }
                if ($quizQ) {
                    $quiz = $m->quiz()->create(['titre' => 'Quiz : '.$mt]);
                    foreach ($quizQ as [$question, $bonne, $autres]) {
                        $choix = array_merge([$bonne], $autres);
                        shuffle($choix);
                        $quiz->questions()->create(['question' => $question, 'choix' => $choix, 'bonne' => array_search($bonne, $choix, true)]);
                    }
                }
            }
            $cours[] = $c->fresh(['modules.lecons', 'modules.quiz']);
        }

        // Inscriptions, progressions, tentatives et avis
        $notes = [5, 5, 4, 5, 4, 3, 5, 4];
        $comm = ['Cours clair et très pratique, je recommande !', 'Bonne progression, les quiz aident à retenir.', 'Parfait pour débuter, merci au formateur.', 'Contenu utile pour mon activité.', 'Très bien expliqué, j’ai déjà appliqué plusieurs conseils.'];
        foreach ($cours as $ci => $c) {
            $nb = $ci < 3 ? 6 : mt_rand(2, 5);
            foreach (array_rand($app, min($nb, count($app))) as $k) {
                $a = $app[$k];
                if ($a->id === $demo->id && $ci > 4) {
                    continue;
                }
                $insc = Inscription::create(['cours_id' => $c->id, 'apprenant_id' => $a->id, 'prix_paye' => $c->prix, 'paiement_mode' => $c->prix ? ['momo', 'moov'][mt_rand(0, 1)] : null, 'paiement_ref' => $c->prix ? 'MOMO-'.strtoupper(Str::random(8)) : null, 'created_at' => now()->subDays(mt_rand(1, 40))]);
                $part = $a->id === $demo->id ? ($ci === 0 ? 0.6 : ($ci === 1 ? 0.3 : 0)) : [1.0, 1.0, 0.7, 0.4, 0.2][mt_rand(0, 4)];
                if ($part > 0) {
                    $items = [];
                    foreach ($c->modules as $m) {
                        foreach ($m->lecons as $l) {
                            $items[] = ['l', $l->id];
                        }
                        if ($m->quiz) {
                            $items[] = ['q', $m->quiz->id];
                        }
                    }
                    foreach (array_slice($items, 0, (int) ceil(count($items) * $part)) as [$type, $id]) {
                        if ($type === 'l') {
                            Progression::create(['inscription_id' => $insc->id, 'lecon_id' => $id]);
                        } else {
                            Tentative::create(['inscription_id' => $insc->id, 'quiz_id' => $id, 'score' => 3, 'total' => 3, 'reussi' => true]);
                        }
                    }
                    $insc->verifierAchevement();
                }
                if ($part >= 0.7 && mt_rand(0, 100) > 30) {
                    AvisCours::create(['cours_id' => $c->id, 'apprenant_id' => $a->id, 'note' => $notes[array_rand($notes)], 'commentaire' => $comm[array_rand($comm)]]);
                }
            }
        }
        // Un cours en brouillon pour le formateur démo
        $b = Cours::create(['formateur_id' => $koffi->id, 'titre' => 'PHP & Laravel : API professionnelle', 'slug' => 'php-laravel-api-professionnelle', 'resume' => 'Construire une API REST sécurisée avec Laravel 12 et Sanctum.', 'description' => 'Cours en préparation : routes, validation, authentification par jetons, tests automatiques et déploiement.', 'categorie' => 'Développement web', 'niveau' => 'avance', 'prix' => 30000, 'publie' => false]);
        $b->modules()->create(['titre' => 'Introduction', 'ordre' => 1])->lecons()->create(['titre' => 'Présentation du projet', 'type' => 'texte', 'contenu' => 'Bienvenue dans ce cours en préparation.', 'duree' => 3, 'ordre' => 1]);
    }
}
