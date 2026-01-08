<?php

namespace OmegaUp\DAO;

/**
 * ProblemDiscussionVotes Data Access Object (DAO).
 *
 * Esta clase contiene toda la manipulacion de bases de datos que se necesita
 * para almacenar de forma permanente y recuperar instancias de objetos
 * {@link \OmegaUp\DAO\VO\ProblemDiscussionVotes}.
 * @access public
 * @package docs
 */
class ProblemDiscussionVotes extends \OmegaUp\DAO\Base\ProblemDiscussionVotes {
    /**
     * Get vote by discussion and identity (to check if user already voted)
     *
     * @param int $discussionId
     * @param int $identityId
     * @return \OmegaUp\DAO\VO\ProblemDiscussionVotes|null
     */
    final public static function getVoteByDiscussionAndIdentity(
        int $discussionId,
        int $identityId
    ): ?\OmegaUp\DAO\VO\ProblemDiscussionVotes {
        $sql = '
            SELECT
                ' . \OmegaUp\DAO\DAO::getFields(
            \OmegaUp\DAO\VO\ProblemDiscussionVotes::FIELD_NAMES,
            'Problem_Discussion_Votes'
        ) . '
            FROM
                `Problem_Discussion_Votes`
            WHERE
                `discussion_id` = ? AND `identity_id` = ?
            LIMIT 1;';

        /** @var array{vote_id: int, discussion_id: int, identity_id: int, vote_type: string, created_at: \OmegaUp\Timestamp}|null */
        $row = \OmegaUp\MySQLConnection::getInstance()->GetRow(
            $sql,
            [$discussionId, $identityId]
        );

        if (empty($row)) {
            return null;
        }
        return new \OmegaUp\DAO\VO\ProblemDiscussionVotes($row);
    }

    /**
     * Delete vote by discussion and identity
     *
     * @param int $discussionId
     * @param int $identityId
     * @return int Number of affected rows
     */
    final public static function deleteVoteByDiscussionAndIdentity(
        int $discussionId,
        int $identityId
    ): int {
        $sql = '
            DELETE FROM
                `Problem_Discussion_Votes`
            WHERE
                `discussion_id` = ? AND `identity_id` = ?;';
        $params = [$discussionId, $identityId];
        \OmegaUp\MySQLConnection::getInstance()->Execute($sql, $params);
        return \OmegaUp\MySQLConnection::getInstance()->Affected_Rows();
    }

    /**
     * Get vote counts for a discussion
     *
     * @param int $discussionId
     * @return array{upvotes: int, downvotes: int}
     */
    final public static function getVoteCounts(int $discussionId): array {
        $sql = '
            SELECT
                SUM(CASE WHEN `vote_type` = "upvote" THEN 1 ELSE 0 END) AS upvotes,
                SUM(CASE WHEN `vote_type` = "downvote" THEN 1 ELSE 0 END) AS downvotes
            FROM
                `Problem_Discussion_Votes`
            WHERE
                `discussion_id` = ?;';
        /** @var array{upvotes: int|float, downvotes: int|float}|null */
        $row = \OmegaUp\MySQLConnection::getInstance()->GetRow(
            $sql,
            [$discussionId]
        );

        if (empty($row)) {
            return ['upvotes' => 0, 'downvotes' => 0];
        }

        return [
            'upvotes' => intval($row['upvotes'] ?? 0),
            'downvotes' => intval($row['downvotes'] ?? 0),
        ];
    }
}
