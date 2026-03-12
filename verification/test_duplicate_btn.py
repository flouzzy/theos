import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()

        # Create a mock HTML page
        html_content = """
        <!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                .group:hover .group-hover\:opacity-100 {
                    opacity: 1;
                }
            </style>
        </head>
        <body class="p-8 bg-slate-100">
            <div class="p-6 hover:bg-slate-50/50 transition-colors group bg-white border border-slate-200 rounded-3xl max-w-2xl mx-auto">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-14 h-14 bg-white border border-slate-200 rounded-2xl flex flex-col items-center justify-center text-center shadow-sm">
                        <span class="text-[10px] font-black text-slate-400 uppercase leading-none mb-1">MAR</span>
                        <span class="text-xl font-black text-slate-900 leading-none">14</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-wider">
                                CONFERENCE
                            </span>
                            <span class="text-xs font-bold text-slate-400">
                                09:00 - 12:15
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-slate-900 truncate">Cours en présentiel + Live</h4>
                    </div>

                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <form method="post" action="#" class="inline-block">
                            <button title="Dupliquer" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            </button>
                        </form>
                        <a href="#" class="p-2 text-slate-400 hover:text-primary hover:bg-white hover:shadow-sm rounded-lg transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </a>
                        <form method="post" action="#" onsubmit="return confirm('Supprimer cet événement ?');" class="inline-block">
                            <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html>
        """

        await page.set_content(html_content)

        # Hover over the group element to reveal the buttons
        await page.hover('.group')

        # Take a screenshot
        await page.screenshot(path="verification/screenshot_duplicate.png")

        await browser.close()

asyncio.run(run())
