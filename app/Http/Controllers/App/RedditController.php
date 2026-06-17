<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\SocialAccount\Platform as SocialPlatform;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\Social\Reddit\RedditClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedditController extends Controller
{
    public function __construct(private readonly RedditClient $client) {}

    public function subreddits(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeRedditAccount($request, $account);

        $query = trim((string) $request->query('q', ''));

        return response()->json([
            'data' => $query === '' ? [] : rescue(
                fn () => $this->client->searchSubreddits($account, $query),
                [],
                report: false,
            ),
        ]);
    }

    public function restrictions(Request $request, SocialAccount $account, string $subreddit): JsonResponse
    {
        $this->authorizeRedditAccount($request, $account);

        return response()->json([
            'data' => rescue(
                fn () => $this->client->restrictions($account, $subreddit),
                ['allowed_types' => ['self', 'link', 'image'], 'flair_required' => false, 'flairs' => []],
                report: false,
            ),
        ]);
    }

    private function authorizeRedditAccount(Request $request, SocialAccount $account): void
    {
        $workspace = $request->user()->currentWorkspace;

        abort_unless(
            $workspace && $account->workspace_id === $workspace->id && $account->platform === SocialPlatform::Reddit,
            Response::HTTP_FORBIDDEN,
        );

        $this->authorize('view', $workspace);
    }
}
