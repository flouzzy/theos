<?php
$content = file_get_contents('templates/admin/lesson/index.html.twig');
$search = "    </div>\n{% endblock %}";
$replace = <<<EOT
    </div>

    <twig:Pagination
        currentPage="{{ currentPage }}"
        totalPages="{{ totalPages }}"
        routeName="admin_lesson_index"
    />
{% endblock %}
EOT;
file_put_contents('templates/admin/lesson/index.html.twig', str_replace($search, $replace, $content));
