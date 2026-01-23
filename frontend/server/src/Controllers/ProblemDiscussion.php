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
     * Delete a discussion comment or reply (by owner, or by discussion reviewer/admin)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param int|null $reply_id
     */
    public static function apiDelete(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $replyId = $r->ensureOptionalInt('reply_id');

        // If reply_id is provided, delete only the reply
        if (!is_null($replyId)) {
            // Get reply
            $reply = \OmegaUp\DAO\ProblemDiscussionReplies::getByPK($replyId);
            if (is_null($reply)) {
                throw new \OmegaUp\Exceptions\NotFoundException(
                    'replyNotFound'
                );
            }

            // Verify reply belongs to the discussion
            if ($reply->discussion_id !== $discussionId) {
                throw new \OmegaUp\Exceptions\InvalidParameterException(
                    'parameterInvalid',
                    'reply_id'
                );
            }

            // Check ownership or admin privileges
            $isOwner = $reply->identity_id === $r->identity->identity_id;
            $isDiscussionReviewer = \OmegaUp\Authorization::isDiscussionReviewer(
                $r->identity
            );
            $isSystemAdmin = \OmegaUp\Authorization::isSystemAdmin(
                $r->identity
            );

            if (!$isOwner && !$isDiscussionReviewer && !$isSystemAdmin) {
                throw new \OmegaUp\Exceptions\ForbiddenAccessException(
                    'userNotAllowed'
                );
            }

            // Delete reply (cascade will delete related reports)
            \OmegaUp\DAO\ProblemDiscussionReplies::delete($reply);

            return ['status' => 'ok'];
        }

        // Otherwise, delete the entire discussion
        // Get discussion
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // Check ownership or admin privileges
        $isOwner = $discussion->identity_id === $r->identity->identity_id;
        $isDiscussionReviewer = \OmegaUp\Authorization::isDiscussionReviewer(
            $r->identity
        );
        $isSystemAdmin = \OmegaUp\Authorization::isSystemAdmin($r->identity);

        if (!$isOwner && !$isDiscussionReviewer && !$isSystemAdmin) {
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
     * Update a reply (only by owner)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string}
     *
     * @omegaup-request-param int $reply_id
     * @omegaup-request-param string $content
     */
    public static function apiUpdateReply(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $replyId = $r->ensureInt('reply_id');
        $content = $r->ensureString('content');
        \OmegaUp\Validators::validateStringNonEmpty($content, 'content');

        // Get reply
        $reply = \OmegaUp\DAO\ProblemDiscussionReplies::getByPK($replyId);
        if (is_null($reply)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'replyNotFound'
            );
        }

        // Check ownership
        if ($reply->identity_id !== $r->identity->identity_id) {
            throw new \OmegaUp\Exceptions\ForbiddenAccessException(
                'userNotAllowed'
            );
        }

        // Update reply
        $reply->content = $content;
        \OmegaUp\DAO\ProblemDiscussionReplies::update($reply);

        return ['status' => 'ok'];
    }

    /**
     * Report a discussion or reply
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string, report_id: int}
     *
     * @omegaup-request-param int $discussion_id
     * @omegaup-request-param int|null $reply_id
     * @omegaup-request-param string $reason
     */
    public static function apiReport(\OmegaUp\Request $r): array {
        $r->ensureIdentity();

        $discussionId = $r->ensureInt('discussion_id');
        $replyId = $r->ensureOptionalInt('reply_id');
        $reason = $r->ensureString('reason');
        \OmegaUp\Validators::validateStringNonEmpty($reason, 'reason');

        // Get discussion to verify it exists
        $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK($discussionId);
        if (is_null($discussion)) {
            throw new \OmegaUp\Exceptions\NotFoundException(
                'discussionNotFound'
            );
        }

        // If reply_id is provided, verify the reply exists and belongs to the discussion
        if (!is_null($replyId)) {
            $reply = \OmegaUp\DAO\ProblemDiscussionReplies::getByPK($replyId);
            if (is_null($reply)) {
                throw new \OmegaUp\Exceptions\NotFoundException(
                    'replyNotFound'
                );
            }
            if ($reply->discussion_id !== $discussionId) {
                throw new \OmegaUp\Exceptions\InvalidParameterException(
                    'parameterInvalid',
                    'reply_id'
                );
            }
        }

        // Check if user already reported this discussion/reply combination
        if (
            \OmegaUp\DAO\ProblemDiscussionReports::hasUserReported(
                $discussionId,
                $r->identity->identity_id,
                $replyId
            )
        ) {
            throw new \OmegaUp\Exceptions\DuplicatedEntryInDatabaseException(
                'alreadyReported'
            );
        }

        // Create report
        $report = new \OmegaUp\DAO\VO\ProblemDiscussionReports([
            'discussion_id' => $discussionId,
            'reply_id' => $replyId,
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

    /**
     * List all open reports (admin only)
     *
     * @param \OmegaUp\Request $r
     * @return array{reports: list<array{report_id: int, discussion_id: int, identity_id: int, reason: string, status: string, created_at: \OmegaUp\Timestamp, discussion: array{content: string, problem_id: int}, reply: array{content: string, reply_id: int}|null, reporter: array{username: string}, author: array{username: string}}>, total: int, page: int, page_size: int, pager_items: list<array{class: string, label: string, page: int|null, url: string|null}>}
     *
     * @omegaup-request-param int|null $page
     * @omegaup-request-param int|null $page_size
     */
    public static function apiListReports(\OmegaUp\Request $r): array {
        \OmegaUp\Controllers\Controller::ensureNotInLockdown();
        $r->ensureMainUserIdentity();
        self::validateMemberOfDiscussionReviewerGroup($r->identity);

        $page = $r->ensureOptionalInt('page') ?? 1;
        $pageSize = $r->ensureOptionalInt('page_size') ?? 20;

        // Get open reports
        $result = \OmegaUp\DAO\ProblemDiscussionReports::getOpenReports(
            $page,
            $pageSize
        );

        // Enrich reports with discussion/reply and reporter info
        $enrichedReports = [];
        foreach ($result['reports'] as $report) {
            // Get discussion
            $discussion = \OmegaUp\DAO\ProblemDiscussions::getByPK(
                $report->discussion_id
            );
            if (is_null($discussion)) {
                continue; // Skip if discussion was deleted
            }

            // Get reply if this is a reply report
            $reply = null;
            if (!is_null($report->reply_id)) {
                $reply = \OmegaUp\DAO\ProblemDiscussionReplies::getByPK(
                    $report->reply_id
                );
                if (is_null($reply)) {
                    continue; // Skip if reply was deleted
                }
            }

            // Get reporter identity
            $reporterIdentity = \OmegaUp\DAO\Identities::getByPK(
                $report->identity_id
            );

            // Get author identity (creator of the discussion or reply)
            $authorIdentity = null;
            if ($reply) {
                // If it's a reply report, get the reply author
                $authorIdentity = \OmegaUp\DAO\Identities::getByPK(
                    $reply->identity_id
                );
            } else {
                // If it's a discussion report, get the discussion author
                $authorIdentity = \OmegaUp\DAO\Identities::getByPK(
                    $discussion->identity_id
                );
            }

            $enrichedReports[] = [
                'report_id' => $report->report_id,
                'discussion_id' => $report->discussion_id,
                'reply_id' => $report->reply_id,
                'identity_id' => $report->identity_id,
                'reason' => $report->reason,
                'status' => $report->status,
                'created_at' => $report->created_at,
                'discussion' => [
                    'content' => $discussion->content,
                    'problem_id' => $discussion->problem_id,
                ],
                'reply' => $reply ? [
                    'content' => $reply->content,
                    'reply_id' => $reply->reply_id,
                ] : null,
                'reporter' => [
                    'username' => $reporterIdentity ? $reporterIdentity->username : '',
                ],
                'author' => [
                    'username' => $authorIdentity ? $authorIdentity->username : '',
                ],
            ];
        }

        $pagerItems = \OmegaUp\Pager::paginate(
            $result['total'],
            $pageSize,
            $page,
            adjacent: 5,
            params: []
        );

        return [
            'reports' => $enrichedReports,
            'total' => $result['total'],
            'page' => $page,
            'page_size' => $pageSize,
            'pager_items' => $pagerItems,
        ];
    }

    /**
     * Resolve a discussion report (admin only)
     *
     * @param \OmegaUp\Request $r
     * @return array{status: string}
     *
     * @omegaup-request-param int $report_id
     * @omegaup-request-param string $status
     */
    public static function apiResolveReport(\OmegaUp\Request $r): array {
        \OmegaUp\Controllers\Controller::ensureNotInLockdown();
        $r->ensureMainUserIdentity();
        self::validateMemberOfDiscussionReviewerGroup($r->identity);

        $reportId = $r->ensureInt('report_id');
        $status = $r->ensureEnum(
            'status',
            ['resolved', 'dismissed']
        );

        // Get report
        $report = \OmegaUp\DAO\ProblemDiscussionReports::getByPK($reportId);
        if (is_null($report)) {
            throw new \OmegaUp\Exceptions\NotFoundException('reportNotFound');
        }

        // Check if report is already in the requested status
        if ($report->status === $status) {
            return ['status' => 'ok'];
        }

        // Update report status
        $report->status = $status;
        \OmegaUp\DAO\ProblemDiscussionReports::update($report);

        return ['status' => 'ok'];
    }

    /**
     * Validates that the user making the request is member of the
     * `omegaup:discussion-reviewer` group.
     *
     * @param \OmegaUp\DAO\VO\Identities $identity
     * @return void
     * @throws \OmegaUp\Exceptions\ForbiddenAccessException
     */
    private static function validateMemberOfDiscussionReviewerGroup(
        \OmegaUp\DAO\VO\Identities $identity
    ): void {
        if (
            !\OmegaUp\Authorization::isSystemAdmin($identity) &&
            !\OmegaUp\Authorization::isDiscussionReviewer($identity)
        ) {
            throw new \OmegaUp\Exceptions\ForbiddenAccessException(
                'userNotAllowed'
            );
        }
    }

    /**
     * Gets the details for the discussion reports admin page
     *
     * @param \OmegaUp\Request $r
     * @return array{templateProperties: array{title: \OmegaUp\TranslationString, payload: array{}}, entrypoint: string}
     */
    public static function getDiscussionReportsDetailsForTypeScript(
        \OmegaUp\Request $r
    ): array {
        $r->ensureMainUserIdentity();
        self::validateMemberOfDiscussionReviewerGroup($r->identity);

        return [
            'templateProperties' => [
                'title' => new \OmegaUp\TranslationString(
                    'omegaupTitleDiscussionReports'
                ),
                'payload' => [],
            ],
            'entrypoint' => 'admin_discussion_reports',
        ];
    }
}
