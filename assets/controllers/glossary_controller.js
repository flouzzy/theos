import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.terms = {
            'Théologie': 'Étude des questions religieuses fondée sur les textes sacrés, les dogmes et la tradition.',
            'Théologique': 'Qui se rapporte à la théologie.',
            'Exégèse': 'Étude approfondie et critique d\\'un texte biblique.',
            'Herméneutique': 'Science de l\\'interprétation des textes sacrés.',
            'Sotériologie': 'Partie de la théologie qui traite du salut.',
            'Eschatologie': 'Étude des fins dernières de l\\'homme et du monde.',
            'Christologie': 'Étude de la personne et de l\\'œuvre de Jésus-Christ.',
            'Ecclésiologie': 'Étude de l\\'Église en tant qu\\'institution et communauté.'
        };

        this.highlightTerms();
    }

    highlightTerms() {
        let content = this.element.innerHTML;
        
        // Avoid highlighting inside tags or attributes
        Object.keys(this.terms).forEach(term => {
            const definition = this.terms[term];
            const regex = new RegExp(`\\b(${term})\\b(?![^<]*>)`, 'gi');
            
            content = content.replace(regex, `<span class="cursor-help border-b border-dotted border-primary group relative inline-block">
                $1
                <span class="invisible group-hover:visible absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-800 text-white text-xs rounded-lg shadow-xl z-50 normal-case font-normal leading-relaxed">
                    ${definition}
                    <svg class="absolute top-full left-1/2 -translate-x-1/2 h-2 w-4 text-slate-800" fill="currentColor" viewBox="0 0 20 10"><path d="M0 0l10 10 10-10z"/></svg>
                </span>
            </span>`);
        });

        this.element.innerHTML = content;
    }
}
