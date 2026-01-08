<?php

namespace OmegaUp\Controllers;

/**
 * ProblemDiscussionController
 *
 * @psalm-type Discussion=array{discussion_id: int, problem_id: int, identity_id: int, content: string, upvotes: int, downvotes: int, created_at: \OmegaUp\Timestamp, updated_at: \OmegaUp\Timestamp, username: string, author_username: string, reply_count: int}
 * @psalm-type Reply=array{reply_id: int, discussion_id: int, identity_id: int, content: string, created_at: \OmegaUp\Timestamp, updated_at: \OmegaUp\Timestamp, username: string, author_username: string}
 * @psalm-type DiscussionListPayload=array{discussions: list<Discussion>, total: int, page: int, page_size: int}
 * @psalm-type ReplyListPayload=array{replies: list<Reply>, total: int}
 */
class ProblemDiscussion extends \OmegaUp\Controllers\Controller {
    /**
     * Get discussions for a problem
     *
     * @param \OmegaUp\Request $r
     * @return DiscussionListPayload
     *
     * @omegaup-request-param string $problem_alias
     * @omegaup-request-param int|null $page
     * @omegaup-request-param int|null $page_size
     * @omegaup-request-param null|string $sort_by
     * @omegaup-request-param null|string $order
     */
    public static function apiList(\OmegaUp\Request $r): array {
        $problemAlias = $r->ensureString(
            'problem_alias',
            fn (string $alias) => \OmegaUp\Validators::alias($alias)
        );
        $page = $r->ensureOptionalInt('page') ?? 1;
        $pageSize = $r->ensureOptionalInt('page_size') ?? 20;
        $sortBy = $r->ensureOptionalString('sort_by') ?? 'created_at';
        $order = $r->ensureOptionalString('order') ?? 'DESC';

        // Validate problem exists
        $problem = \OmegaUp\DAO\Problems::getByAlias($problemAlias);
        if (is_null($problem)) {
            throw new \OmegaUp\Exceptions\NotFoundException('problemNotFound');
        }

        // Get discussions
        $result = \OmegaUp\DAO\ProblemDiscussions::getByProblemId(
            $problem->problem_id,
            $page,
            $pageSize,
            $sortBy,
            $order
        );

        // Enrich with user info and reply counts
        $discussions = [];
        foreach ($result['discussions'] as $discussion) {
            $identity = \OmegaUp\DAO\Identities::getByPK(
                $discussion->identity_id
            );
            $replyCount = \OmegaUp\DAO\ProblemDiscussionReplies::getReplyCount(
                $discussion->discussion_id
            );

            $discussions[] = [
                'discussion_id' => $discussion->discussion_id,
                'problem_id' => $discussion->problem_id,
                'identity_id' => $discussion->identity_id,
                'content' => $discussion->content,
                'upvotes' => $discussion->upvotes,
                'downvotes' => $discussion->downvotes,
                'created_at' => $discussion->created_at,
                'updated_at' => $discussion->updated_at,
                'username' => $identity ? $identity->username : '',
                'author_username' => $identity ? $identity->username : '',
                'reply_count' => $replyCount,
            ];
        }

        return [
            'discussions' => $discussions,
            'total' => $result['total'],
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * Create a new discussion comment
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string, discussion_id: int}
     *
     * @omegaup-request-param string $problem_alias
     * @omegaup-request-param string $content
     */
    public static function apiCreate(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $problemAlias = $r->ensureString(
            'problem_alias',
            fn (string $alias) => \OmegaUp\Validators::alias($alias)
        );
        $content = $r->ensureString('content');
        \OmegaUp\Validators::validateStringNonEmpty($content, 'content');

        // Validate problem exists
        $problem = \OmegaUp\DAO\Problems::getByAlias($problemAlias);
        if (is_null($problem)) {
            throw new \OmegaUp\Exceptions\NotFoundException('problemNotFound');
        }

        // Create discussion
        $discussion = new \OmegaUp\DAO\VO\ProblemDiscussions([
            'problem_id' => $problem->problem_id,
            'identity_id' => $r->identity->identity_id,
            'content' => $content,
            'upvotes' => 0,
            'downvotes' => 0,
        ]);

        \OmegaUp\DAO\ProblemDiscussions::create($discussion);

        return [
            'status' => 'ok',
            'discussion_id' => $discussion->discussion_id,
        ];
    }

    /**
     * Update a discussion comment (only by owner)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param string $content
     */
    public static function apiUpdate(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $content = $r->ensureString('content');
        \OmegaUp\Validators::validateStringNonEmpty($content, 'content');

        // Get discussion
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Check ownership
        if ($discussion->identity_id !== $r->identity->identity_id) {
            throw new \OmegaUp\Exceptions\ForbiddenAccessException(
                'userNotAllowed'
            );
        }

        // Update discussion
        $discussion->content = $content;
        \OmegaUp\DAO\ProblemDiscussions::update($discussion);

        return ['status' => 'ok'];
    }

    /**
     * Delete a discussion comment (only by owner)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string}
     *
     * @omegaup-request-param int $discussion_id
     */
    public static function apiDelete(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');

        // Get discussion
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Check ownership
        if ($discussion->identity_id !== $r->identity->identity_id) {
            throw new \OmegaUp\Exceptions\ForbiddenAccessException(
                'userNotAllowed'
            );
        }

        // Delete discussion (cascade will delete replies and votes)
        \OmegaUp\DAO\ProblemDiscussions::delete($discussion);

        return ['status' => 'ok'];
    }

    /**
     * Vote on a discussion (upvote or downvote)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string, upvotes: int, downvotes: int}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param string $vote_type
     */
    public static function apiVote(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $voteType = $r->ensureString('vote_type');
        \OmegaUp\Validators::validateInEnum(
            $voteType,
            'vote_type',
            ['upvote', 'downvote']
        );

        // Get discussion
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Check if user already voted
        $existingVote = \OmegaUp\DAO\ProblemDiscussionVotes::getVoteByDiscussionAndIdentity(
            $discussionId,
            $r->identity->identity_id
        );

        if (!is_null($existingVote)) {
            // User already voted - update or remove vote
            if ($existingVote->vote_type === $voteType) {
                // Same vote type - remove vote
                \OmegaUp\DAO\ProblemDiscussionVotes::deleteVoteByDiscussionAndIdentity(
                    $discussionId,
                    $r->identity->identity_id
                );
                // Update vote counts
                $voteCounts = \OmegaUp\DAO\ProblemDiscussionVotes::getVoteCounts(
                    $discussionId
                );
                \OmegaUp\DAO\ProblemDiscussions::updateVoteCounts(
                    $discussionId,
                    $voteCounts['upvotes'],
                    $voteCounts['downvotes']
                );
                $discussion->upvotes = $voteCounts['upvotes'];
                $discussion->downvotes = $voteCounts['downvotes'];
            } else {
                // Different vote type - update vote
                $existingVote->vote_type = $voteType;
                \OmegaUp\DAO\ProblemDiscussionVotes::update($existingVote);
                // Update vote counts
                $voteCounts = \OmegaUp\DAO\ProblemDiscussionVotes::getVoteCounts(
                    $discussionId
                );
                \OmegaUp\DAO\ProblemDiscussions::updateVoteCounts(
                    $discussionId,
                    $voteCounts['upvotes'],
                    $voteCounts['downvotes']
                );
                $discussion->upvotes = $voteCounts['upvotes'];
                $discussion->downvotes = $voteCounts['downvotes'];
            }
        } else {
            // New vote
            $vote = new \OmegaUp\DAO\VO\ProblemDiscussionVotes([
                'discussion_id' => $discussionId,
                'identity_id' => $r->identity->identity_id,
                'vote_type' => $voteType,
            ]);
            \OmegaUp\DAO\ProblemDiscussionVotes::create($vote);
            // Update vote counts
            $voteCounts = \OmegaUp\DAO\ProblemDiscussionVotes::getVoteCounts(
                $discussionId
            );
            \OmegaUp\DAO\ProblemDiscussions::updateVoteCounts(
                $discussionId,
                $voteCounts['upvotes'],
                $voteCounts['downvotes']
            );
            $discussion->upvotes = $voteCounts['upvotes'];
            $discussion->downvotes = $voteCounts['downvotes'];
        }

        return [
            'status' => 'ok',
            'upvotes' => $discussion->upvotes,
            'downvotes' => $discussion->downvotes,
        ];
    }

    /**
     * Get replies for a discussion
     *
     * @param \OmegaUp\Request $r
     * @return ReplyListPayload
     *
     * @omegaup-request-param int $discussion_id
     */
    public static function apiGetReplies(\OmegaUp\Request $r): array {
        $discussionId = $r->ensureInt('discussion_id');

        // Get discussion to verify it exists
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Get replies
        $replies = \OmegaUp\DAO\ProblemDiscussionReplies::getByDiscussionId(
            $discussionId
        );

        // Enrich with user info
        $enrichedReplies = [];
        foreach ($replies as $reply) {
            $identity = \OmegaUp\DAO\Identities::getByPK($reply->identity_id);
            $enrichedReplies[] = [
                'reply_id' => $reply->reply_id,
                'discussion_id' => $reply->discussion_id,
                'identity_id' => $reply->identity_id,
                'content' => $reply->content,
                'created_at' => $reply->created_at,
                'updated_at' => $reply->updated_at,
                'username' => $identity ? $identity->username : '',
                'author_username' => $identity ? $identity->username : '',
            ];
        }

        return [
            'replies' => $enrichedReplies,
            'total' => count($enrichedReplies),
        ];
    }

    /**
     * Create a reply to a discussion
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string, reply_id: int}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param string $content
     */
    public static function apiCreateReply(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $content = $r->ensureString('content');
        \OmegaUp\Validators::validateStringNonEmpty($content, 'content');

        // Get discussion to verify it exists
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Create reply
        $reply = new \OmegaUp\DAO\VO\ProblemDiscussionReplies([
            'discussion_id' => $discussionId,
            'identity_id' => $r->identity->identity_id,
            'content' => $content,
        ]);

        \OmegaUp\DAO\ProblemDiscussionReplies::create($reply);

        return [
            'status' => 'ok',
            'reply_id' => $reply->reply_id,
        ];
    }

    /**
     * Report a discussion
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string, report_id: int}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param string $reason
     */
    public static function apiReport(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $reason = $r->ensureString('reason');
        \OmegaUp\Validators::validateStringNonEmpty($reason, 'reason');

        // Get discussion to verify it exists
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Check if user already reported
        if (
            \OmegaUp\DAO\ProblemDiscussionReports::hasUserReported(
                $discussionId,
                $r->identity->identity_id
            )
        ) {
            throw new \OmegaUp\Exceptions\DuplicatedEntryInDatabaseException(
                'alreadyReported'
            );
        }

        // Create report
        $report = new \OmegaUp\DAO\VO\ProblemDiscussionReports([
            'discussion_id' => $discussionId,
            'identity_id' => $r->identity->identity_id,
            'reason' => $reason,
            'status' => 'open',
        ]);

        \OmegaUp\DAO\ProblemDiscussionReports::create($report);

        return [
            'status' => 'ok',
            'report_id' => $report->report_id,
        ];
    }
}
