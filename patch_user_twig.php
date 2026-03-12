<?php
$content = file_get_contents('templates/admin/user/index.html.twig');
$search = <<<EOT
    {% if totalPages > 1 %}
        <div class="mt-8 flex items-center justify-between">
            <div class="text-sm text-slate-500">
                {{ 'Page %current% sur %total%'|trans({'%current%': currentPage, '%total%': totalPages}) }}
            </div>
            <div class="flex items-center gap-2">
                {% if currentPage > 1 %}
                    <twig:Button as="a" href="{{ path('admin_user_index', {page: currentPage - 1}) }}" variant="outline" size="sm">
                        <twig:ux:icon name="lucide:chevron-left" class="w-4 h-4 mr-1" />
                        {{ 'Précédent' | trans }}
                    </twig:Button>
                {% endif %}

                {% if currentPage < totalPages %}
                    <twig:Button as="a" href="{{ path('admin_user_index', {page: currentPage + 1}) }}" variant="outline" size="sm">
                        {{ 'Suivant' | trans }}
                        <twig:ux:icon name="lucide:chevron-right" class="w-4 h-4 ml-1" />
                    </twig:Button>
                {% endif %}
            </div>
        </div>
    {% endif %}
EOT;
$replace = <<<EOT
    <twig:Pagination
        currentPage="{{ currentPage }}"
        totalPages="{{ totalPages }}"
        routeName="admin_user_index"
    />
EOT;

file_put_contents('templates/admin/user/index.html.twig', str_replace($search, $replace, $content));
