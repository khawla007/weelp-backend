<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGAL_EMAILS = [
        'cancellation' => ['support@weelp.com'],
        'terms' => ['legal@weelp.com'],
        'privacy' => ['privacy@weelp.com'],
    ];

    public function up(): void
    {
        $this->updateLegalContent(linkEmails: true);
    }

    public function down(): void
    {
        $this->updateLegalContent(linkEmails: false);
    }

    private function updateLegalContent(bool $linkEmails): void
    {
        foreach (self::LEGAL_EMAILS as $slug => $emails) {
            $page = DB::table('pages')->where('slug', $slug)->first(['id', 'content']);

            if (! $page || ! is_string($page->content)) {
                continue;
            }

            $content = json_decode($page->content, true);

            if (! is_array($content)) {
                continue;
            }

            $updated = $this->transformNode($content, $emails, $linkEmails);

            if ($updated === $content) {
                continue;
            }

            DB::table('pages')
                ->where('id', $page->id)
                ->update(['content' => json_encode($updated, JSON_UNESCAPED_SLASHES)]);
        }
    }

    private function transformNode(array $node, array $emails, bool $linkEmails): array
    {
        if (isset($node['content']) && is_array($node['content'])) {
            $node['content'] = $this->transformNodes($node['content'], $emails, $linkEmails);
        }

        if (! $linkEmails && ($node['type'] ?? null) === 'text' && isset($node['text']) && in_array($node['text'], $emails, true)) {
            $node['marks'] = array_values(array_filter(
                $node['marks'] ?? [],
                fn ($mark) => ! $this->isMailtoMark($mark, $node['text'])
            ));

            if ($node['marks'] === []) {
                unset($node['marks']);
            }
        }

        return $node;
    }

    private function transformNodes(array $nodes, array $emails, bool $linkEmails): array
    {
        $updated = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                $updated[] = $node;
                continue;
            }

            if ($linkEmails && ($node['type'] ?? null) === 'text' && isset($node['text'])) {
                array_push($updated, ...$this->splitEmailTextNode($node, $emails));
                continue;
            }

            $updated[] = $this->transformNode($node, $emails, $linkEmails);
        }

        return $updated;
    }

    private function splitEmailTextNode(array $node, array $emails): array
    {
        $text = (string) $node['text'];

        if ($text === '' || $this->hasAnyMailtoMark($node, $emails)) {
            return [$node];
        }

        $segments = [];

        while ($text !== '') {
            $match = $this->findFirstEmail($text, $emails);

            if ($match === null) {
                $segments[] = $this->textNode($node, $text);
                break;
            }

            [$email, $position] = $match;

            if ($position > 0) {
                $segments[] = $this->textNode($node, substr($text, 0, $position));
            }

            $segments[] = $this->emailNode($node, $email);
            $text = substr($text, $position + strlen($email));
        }

        return $segments;
    }

    private function findFirstEmail(string $text, array $emails): ?array
    {
        $firstEmail = null;
        $firstPosition = null;

        foreach ($emails as $email) {
            $position = strpos($text, $email);

            if ($position === false) {
                continue;
            }

            if ($firstPosition === null || $position < $firstPosition) {
                $firstEmail = $email;
                $firstPosition = $position;
            }
        }

        return $firstEmail === null ? null : [$firstEmail, $firstPosition];
    }

    private function textNode(array $baseNode, string $text): array
    {
        $node = $baseNode;
        $node['text'] = $text;

        return $node;
    }

    private function emailNode(array $baseNode, string $email): array
    {
        $node = $this->textNode($baseNode, $email);
        $marks = $node['marks'] ?? [];
        $marks[] = [
            'type' => 'link',
            'attrs' => ['href' => "mailto:{$email}"],
        ];
        $node['marks'] = $marks;

        return $node;
    }

    private function hasAnyMailtoMark(array $node, array $emails): bool
    {
        foreach ($emails as $email) {
            if ($this->hasMailtoMark($node, $email)) {
                return true;
            }
        }

        return false;
    }

    private function hasMailtoMark(array $node, string $email): bool
    {
        foreach ($node['marks'] ?? [] as $mark) {
            if ($this->isMailtoMark($mark, $email)) {
                return true;
            }
        }

        return false;
    }

    private function isMailtoMark(mixed $mark, string $email): bool
    {
        return is_array($mark)
            && ($mark['type'] ?? null) === 'link'
            && ($mark['attrs']['href'] ?? null) === "mailto:{$email}";
    }
};
