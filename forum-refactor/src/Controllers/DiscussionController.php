<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Models\CommentModel;
use App\Models\DiscussionModel;
use Mustache_Engine;

final class DiscussionController
{
    public function __construct(
        private readonly DiscussionModel $discussions,
        private readonly CommentModel $comments,
        private readonly Mustache_Engine $mustache,
        private readonly array $displayConfig
    ) {
    }

    public function index(): void
    {
        $items = $this->discussions->findAllWithAuthor();
        $this->renderPage('index', [
            'page_title'   => 'Discussions — Nested Forum Lab',
            'current_user' => $_SESSION['user'] ?? null,
            'csrf_token'   => Csrf::token(),
            'discussions'  => $items,
        ]);
    }

    public function show(string $rawId): void
    {
        $discussionId = $this->parsePositiveInt($rawId);
        if ($discussionId === null) {
            http_response_code(400);
            $this->renderError(400, 'Bad Request', 'The discussion ID must be a positive integer.');
            return;
        }

        $discussion = $this->discussions->findByIdWithAuthor($discussionId);
        if ($discussion === null) {
            http_response_code(404);
            $this->renderError(404, 'Not Found', 'No discussion was found with that ID.');
            return;
        }

        $rows = $this->comments->findByDiscussionId($discussionId);
        $childrenByParent = $this->groupByParent($rows);
        $tree = $this->buildTree($childrenByParent, null, 0, []);

        $discussion['utc_created_at'] = $this->formatUtc($discussion['created_at']);

        $this->renderPage('discussion', [
            'page_title'       => $discussion['title'] . ' — Nested Forum Lab',
            'current_user'     => $_SESSION['user'] ?? null,
            'is_authenticated' => isset($_SESSION['user']),
            'csrf_token'       => Csrf::token(),
            'discussion'       => $discussion,
            'comments_tree'    => $tree,
        ]);
    }

    public function storeReply(string $rawId): void
    {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            header('Location: /auth', true, 302);
            exit;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            $this->renderError(403, 'Forbidden', 'Invalid or missing CSRF token.');
            return;
        }

        $discussionId = $this->parsePositiveInt($rawId);
        if ($discussionId === null) {
            http_response_code(400);
            $this->renderError(400, 'Bad Request', 'Invalid discussion ID.');
            return;
        }

        $discussion = $this->discussions->findByIdWithAuthor($discussionId);
        if ($discussion === null) {
            http_response_code(404);
            $this->renderError(404, 'Not Found', 'Discussion does not exist.');
            return;
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        $maxLen = (int) ($this->displayConfig['max_comment_len'] ?? 2000);
        if ($body === '' || mb_strlen($body) > $maxLen) {
            http_response_code(422);
            $this->renderError(422, 'Unprocessable Entity', "Comment must be between 1 and {$maxLen} characters.");
            return;
        }

        $rawParent = trim((string) ($_POST['parent_id'] ?? ''));
        $parentId = null;

        if ($rawParent !== '') {
            $parsedParent = $this->parsePositiveInt($rawParent);
            if ($parsedParent === null) {
                http_response_code(422);
                $this->renderError(422, 'Unprocessable Entity', 'Invalid parent comment ID.');
                return;
            }

            $parentRow = $this->comments->findParentInDiscussion($parsedParent, $discussionId);
            if ($parentRow === null) {
                http_response_code(422);
                $this->renderError(422, 'Unprocessable Entity', 'Parent comment does not belong to this discussion.');
                return;
            }

            $parentId = (int) $parentRow['id'];
            $parentDepth = $this->computeDepth($discussionId, $parentId);
            $maxReplyDepth = (int) ($this->displayConfig['max_reply_depth'] ?? 10);
            if (($parentDepth + 1) > $maxReplyDepth) {
                http_response_code(422);
                $this->renderError(422, 'Unprocessable Entity', "Maximum reply depth ({$maxReplyDepth}) reached.");
                return;
            }
        }

        $sessionUserId = (int) $_SESSION['user']['id'];
        $this->comments->create($discussionId, $parentId, $sessionUserId, $body);

        header("Location: /discussions/{$discussionId}", true, 302);
        exit;
    }

    private function renderPage(string $view, array $data): void
    {
        $data['body_html'] = $this->mustache->render($view, $data);
        echo $this->mustache->render('layout', $data);
    }

    private function parsePositiveInt(string $value): ?int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }
        $intVal = (int) $value;
        return $intVal > 0 ? $intVal : null;
    }

    private function groupByParent(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['parent_id'] === null ? 0 : (int) $row['parent_id'];
            $grouped[$key][] = $row;
        }
        return $grouped;
    }

    private function buildTree(array $childrenByParent, ?int $parentId, int $depth, array $visited): array
    {
        $key = $parentId ?? 0;
        if (!isset($childrenByParent[$key])) {
            return [];
        }

        $maxIndent = (int) ($this->displayConfig['max_indent_depth'] ?? 8);
        $maxTraversal = (int) ($this->displayConfig['max_reply_depth'] ?? 10);
        $nodes = [];

        foreach ($childrenByParent[$key] as $row) {
            $commentId = (int) $row['id'];
            if (isset($visited[$commentId]) || $depth > $maxTraversal) {
                continue;
            }

            $nextVisited = $visited;
            $nextVisited[$commentId] = true;

            $childNodes = $this->buildTree($childrenByParent, $commentId, $depth + 1, $nextVisited);
            $indentPx = min($depth, $maxIndent) * 24;

            $nodes[] = [
                'id'               => $commentId,
                'discussion_id'    => (int) $row['discussion_id'],
                'parent_id'        => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'author_name'      => (string) $row['author_name'],
                'avatar_path'      => (string) $row['avatar_path'],
                'body'             => (string) $row['body'],
                'utc_created_at'   => $this->formatUtc((string) $row['created_at']),
                'depth'            => $depth,
                'indent_px'        => $indentPx,
                'has_children'     => count($childNodes) > 0,
                'children'         => $childNodes,
            ];
        }

        return $nodes;
    }

    private function computeDepth(int $discussionId, int $commentId): int
    {
        $rows = $this->comments->findByDiscussionId($discussionId);
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $depth = 0;
        $currentId = $commentId;
        $seen = [];

        while (isset($byId[$currentId]) && $byId[$currentId]['parent_id'] !== null) {
            if (isset($seen[$currentId])) {
                break;
            }
            $seen[$currentId] = true;
            $currentId = (int) $byId[$currentId]['parent_id'];
            $depth++;
        }

        return $depth;
    }

    private function formatUtc(string $timestamp): string
    {
        $dt = date_create_immutable($timestamp, new \DateTimeZone('UTC'));
        return $dt ? $dt->format('Y-m-d H:i:s \U\T\C') : $timestamp;
    }

    private function renderError(int $code, string $title, string $message): void
    {
        $this->renderPage('error', [
            'page_title'   => "{$code} {$title} — Nested Forum Lab",
            'current_user' => $_SESSION['user'] ?? null,
            'csrf_token'   => Csrf::token(),
            'code'         => $code,
            'title'        => $title,
            'message'      => $message,
        ]);
    }
}