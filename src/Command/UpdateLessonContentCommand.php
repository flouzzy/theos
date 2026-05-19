<?php

namespace App\Command;

use App\Entity\Lesson;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:update-lesson-content')]
class UpdateLessonContentCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LessonRepository $lessonRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lessons = $this->lessonRepository->findAll();
        
        // Mapping de contenu détaillé
        $contentMap = [
            // Divinité de Jésus
            'Annonce AT' => ['intro' => 'L\'Ancien Testament est la fondation sur laquelle repose la révélation du Messie.', 'detail' => 'Dans Ésaïe 9:6, le Messie est appelé "Dieu Puissant". Cette annonce est radicale pour le judaïsme de l\'époque.', 'quote' => 'Car un enfant nous est né... on l\'appellera Dieu Puissant.'],
            'Declarations Jesus' => ['intro' => 'Jésus ne se contente pas d\'être un prophète, il se place au niveau de Dieu.', 'detail' => 'En Jean 8:58, en disant "Je suis", Jésus s\'approprie le nom de Dieu révélé à Moïse.', 'quote' => 'Avant qu\'Abraham fût, je suis.'],
            'Temoignage Jean' => ['intro' => 'L\'apôtre Jean propose une christologie haute dès les premiers mots de son évangile.', 'detail' => 'Le Verbe préexistant, créateur de toutes choses, devient chair pour habiter parmi nous.', 'quote' => 'Au commencement était la Parole, et la Parole était avec Dieu, et la Parole était Dieu.'],
            'Incarnation' => ['intro' => 'L\'incarnation est le moment où l\'éternité touche le temps.', 'detail' => 'C\'est l\'union hypostatique : deux natures, divine et humaine, dans une seule personne.', 'quote' => 'La Parole a été faite chair, et elle a habité parmi nous.'],
            'Salut' => ['intro' => 'Pourquoi la divinité est-elle nécessaire au salut ?', 'detail' => 'Seul Dieu peut porter le poids du péché du monde et l\'anéantir. Un homme seul ne suffirait pas.', 'quote' => 'Dieu était en Christ, réconciliant le monde avec lui-même.'],
            'Mediation' => ['intro' => 'Le Christ est le pont entre Dieu et l\'homme.', 'detail' => 'Sa double nature permet de représenter parfaitement Dieu devant les hommes, et l\'humanité devant Dieu.', 'quote' => 'Car il y a un seul Dieu, et aussi un seul médiateur entre Dieu et les hommes, Jésus-Christ homme.'],
            'Peres Eglise' => ['intro' => 'L\'Église a dû structurer sa pensée face aux déviations.', 'detail' => 'Des théologiens comme Athanase ont lutté pour maintenir la divinité entière du Fils.', 'quote' => 'Dieu s\'est fait homme pour que l\'homme devienne dieu.'],
            'Nicee' => ['intro' => 'Le Concile de Nicée en 325 est le tournant majeur.', 'detail' => 'Le terme "homoousios" (consubstantiel) est adopté pour affirmer que le Fils est de la même essence que le Père.', 'quote' => 'Engendré, non créé.'],
            'Jesus aujourdhui' => ['intro' => 'Comment la divinité de Jésus impacte notre dévotion ?', 'detail' => 'Elle transforme notre adoration : nous ne prions pas un prophète, mais le Seigneur ressuscité.', 'quote' => 'Je suis le même hier, aujourd\'hui et éternellement.'],

            // Finances
            'Budget' => ['intro' => 'La maîtrise de ses finances commence par la visibilité.', 'detail' => 'Utiliser la méthode 50/30/20 (Besoin/Envie/Épargne) permet une gestion saine.', 'quote' => 'Celui qui gère peu de choses avec fidélité, en gérera beaucoup.'],
            'Epargne' => ['intro' => 'L\'épargne est une discipline de sagesse.', 'detail' => 'L\'objectif n\'est pas l\'accumulation, mais la préparation et la liberté pour servir.', 'quote' => 'La fourmi prépare sa nourriture en été.'],
            'Dettes' => ['intro' => 'Le piège de la dette entrave notre liberté.', 'detail' => 'La règle d\'or : éviter la dette de consommation et privilégier l\'investissement productif.', 'quote' => 'L\'emprunteur est l\'esclave de celui qui prête.'],
            'Vision biblique' => ['intro' => 'L\'argent est un serviteur, jamais un maître.', 'detail' => 'La perspective biblique transforme le don en investissement céleste.', 'quote' => 'On ne peut servir deux maîtres : Dieu et l\'argent.'],
            'Generosite' => ['intro' => 'Le don brise le pouvoir de l\'avidité.', 'detail' => 'La dîme et les offrandes sont des actes de reconnaissance que tout vient de Lui.', 'quote' => 'Dieu aime celui qui donne avec joie.'],
            'Sagesse' => ['intro' => 'L\'investissement demande discernement et patience.', 'detail' => 'Éviter les promesses de gains rapides, préférer la croissance durable et éthique.', 'quote' => 'Les plans de l\'homme diligent mènent à l\'abondance.'],
            'Retraite' => ['intro' => 'Anticiper pour ne pas être une charge.', 'detail' => 'La planification doit intégrer les besoins futurs pour rester une bénédiction pour autrui.', 'quote' => 'Le sage prévoit le mal et se cache.'],
            'Transmission' => ['intro' => 'Transmettre ses biens avec sagesse est un acte de clôture de vie.', 'detail' => 'L\'héritage inclut les finances, mais surtout les valeurs morales et spirituelles.', 'quote' => 'Un bon homme laisse un héritage aux enfants de ses enfants.'],
            'Liberte' => ['intro' => 'La vraie liberté n\'est pas l\'indépendance financière, mais la dépendance totale en Dieu.', 'detail' => 'Utiliser ses ressources pour les œuvres du Royaume.', 'quote' => 'La liberté nous est donnée pour servir.'],

            // Vaincre la peur
            'Origines' => ['intro' => 'La peur est un signal, souvent mal interprété.', 'detail' => 'Comprendre la différence entre le danger réel (externe) et la peur projetée (interne).', 'quote' => 'La peur frappe à la porte, la foi répond : il n\'y a personne.'],
            'Peur saine' => ['intro' => 'Toute peur n\'est pas mauvaise.', 'detail' => 'La peur de l\'Éternel est le commencement de la sagesse ; elle est le garde-fou qui nous évite le mal.', 'quote' => 'Crains Dieu et observe ses commandements.'],
            'Biologie' => ['intro' => 'Le corps réagit avant la pensée.', 'detail' => 'L\'amygdale cérébrale active la réponse "combat ou fuite". Apprendre à réguler son système nerveux.', 'quote' => 'Mon corps est le temple du Saint-Esprit.'],
            'Courage' => ['intro' => 'Le courage est une décision, pas une émotion.', 'detail' => 'Josué a reçu l\'ordre d\'être fort et courageux, malgré les géants devant lui.', 'quote' => 'Fortifie-toi et prends courage.'],
            'Paix Dieu' => ['intro' => 'La paix est un don, pas une absence de conflit.', 'detail' => 'Elle garde les cœurs et les pensées face aux agitations du monde.', 'quote' => 'Je vous donne ma paix.'],
            'Priere' => ['intro' => 'La prière est le premier remède contre l\'anxiété.', 'detail' => 'Lister ses sujets d\'inquiétude devant Dieu permet de les lâcher.', 'quote' => 'Ne vous inquiétez de rien...'],
            'Respiration' => ['intro' => 'Calmer le corps pour calmer l\'esprit.', 'detail' => 'Technique 4-7-8 : inspirer 4s, bloquer 7s, expirer 8s pour relancer le système parasympathique.', 'quote' => 'Arrêtez, et sachez que je suis Dieu.'],
            'Exposer peur' => ['intro' => 'L\'évitement renforce la peur.', 'detail' => 'Faire un petit pas vers ce qui fait peur diminue progressivement l\'intensité de la réaction.', 'quote' => 'Le parfait amour bannit la crainte.'],
            'Confiance' => ['intro' => 'Notre valeur ne dépend pas de nos performances.', 'detail' => 'La confiance repose sur qui nous sommes en Christ. La peur de l\'échec perd de sa force.', 'quote' => 'Je puis tout par celui qui me fortifie.'],
        ];

        foreach ($lessons as $lesson) {
            $title = $lesson->getTitle();
            $data = $contentMap[$title] ?? ['intro' => 'Introduction', 'detail' => 'Contenu en cours de rédaction.', 'quote' => ''];
            
            $richContent = "
                <h2 class='text-2xl font-bold mb-4'>$title</h2>
                <p class='mb-6'>{$data['intro']}</p>
                
                <h3 class='text-lg font-semibold mb-2'>Le concept</h3>
                <p class='mb-6'>{$data['detail']}</p>

                <div class='bg-primary/10 p-6 rounded-lg border border-primary/20 my-8'>
                    <p class='text-lg font-serif italic text-primary'>\"{$data['quote']}\"</p>
                </div>
                
                <h3 class='text-lg font-semibold mb-2'>Exercice de réflexion</h3>
                <p>Prenez 5 minutes pour noter comment ce principe s'applique concrètement dans votre vie cette semaine.</p>
            ";
            
            $lesson->setContent($richContent);
        }
        
        $this->entityManager->flush();
        $output->writeln('All lesson contents updated with rich, specific content!');
        return Command::SUCCESS;
    }
}
