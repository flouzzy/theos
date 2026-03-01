<?php
$content = file_get_contents('src/Service/MediaManager.php');

$search = <<<'SEARCH'
    public function upload(UploadedFile $file, string $mediaType = 'course', array $params = []): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = strtolower($this->slugger->slug($originalFilename));
        $extension = $file->guessExtension();
SEARCH;

$replace = <<<'REPLACE'
    public function upload(UploadedFile $file, string $mediaType = 'course', array $params = []): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = strtolower((string) $this->slugger->slug($originalFilename));
        $extension = $file->guessExtension();
REPLACE;

$newContent = str_replace($search, $replace, $content);
file_put_contents('src/Service/MediaManager.php', $newContent);

if ($content === $newContent) {
    echo "No changes made.\n";
} else {
    echo "Replaced successfully.\n";
}
