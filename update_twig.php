<?php
$content = file_get_contents('templates/coach/index.html.twig');
$newContent = str_replace(
    <<<'SEARCH'
            <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl border border-blue-100">
                <span class="text-lg">🏆</span>
                <div class="flex flex-col leading-none">
                    <span class="font-bold text-lg">12</span>
                    <span class="text-[10px] uppercase font-bold">{{ 'Badges' | trans }}</span>
                </div>
            </div>
SEARCH,
    <<<'REPLACE'
            <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl border border-blue-100">
                <span class="text-lg">🏆</span>
                <div class="flex flex-col leading-none">
                    <span class="font-bold text-lg">{{ badgesCount }}</span>
                    <span class="text-[10px] uppercase font-bold">{{ 'Badges' | trans }}</span>
                </div>
            </div>
REPLACE,
    $content
);
file_put_contents('templates/coach/index.html.twig', $newContent);
echo "Updated templates/coach/index.html.twig\n";
