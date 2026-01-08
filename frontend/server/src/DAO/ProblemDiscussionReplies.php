<?php

namespace OmegaUp\DAO;

/**
 * ProblemDiscussionReplies Data Access Object (DAO).
 *
 * Esta clase contiene toda la manipulacion de bases de datos que se necesita
 * para almacenar de forma permanente y recuperar instancias de objetos
 * {@link \OmegaUp\DAO\VO\ProblemDiscussionReplies}.
 * @access public
 * @package docs
 */
class ProblemDiscussionReplies extends \OmegaUp\DAO\Base\ProblemDiscussionReplies {
    /**
     * Get all replies for a specific discussion
     *
     * @param int $discussionId
     * @return list<\OmegaUp\DAO\VO\ProblemDiscussionReplies>
     */
    final public static function getByDiscussionId(
        int $discussionId
    ): array {
        $sql = '
            SELECT
                ' . \OmegaUp\DAO\DAO::getFields(
            \OmegaUp\DAO\VO\ProblemDiscussionReplies::FIELD_NAMES,
            'Problem_Discussion_Replies'
        ) . '
            FROM
                `Problem_Discussion_Replies`
            WHERE
                `discussion_id` = ?
            ORDER BY
                `created_at` ASC;';

        /** @var list<array{reply_id: int, discussion_id: int, identity_id: int, content: string, created_at: \OmegaUp\Timestamp, updated_at: \OmegaUp\Timestamp}> */
        $rs = \OmegaUp\MySQLConnection::getInstance()->GetAll(
            $sql,
            [$discussionId]
        );

        $replies = [];
        foreach ($rs as $row) {
            $replies[] = new \OmegaUp\DAO\VO\ProblemDiscussionReplies($row);
        }
        return $replies;
    }

    /**
     * Get reply count for a discussion
     *
     * @param int $discussionId
     * @return int
     */
    final public static function getReplyCount(int $discussionId): int {
        $sql = '
            SELECT
                COUNT(*)
            FROM
                `Problem_Discussion_Replies`
            WHERE
                `discussion_id` = ?;';
        /** @var int */
        $count = \OmegaUp\MySQLConnection::getInstance()->GetOne(
            $sql,
            [$discussionId]
        );
        return intval($count);
    }
}
