<?php

namespace OmegaUp\DAO;

/**
 * ProblemDiscussions Data Access Object (DAO).
 *
 * Esta clase contiene toda la manipulacion de bases de datos que se necesita
 * para almacenar de forma permanente y recuperar instancias de objetos
 * {@link \OmegaUp\DAO\VO\ProblemDiscussions}.
 * @access public
 * @package docs
 */
class ProblemDiscussions extends \OmegaUp\DAO\Base\ProblemDiscussions {
    /**
     * Get all discussions for a specific problem with pagination and sorting
     *
     * @param int $problemId
     * @param int $page
     * @param int $pageSize
     * @param string $sortBy 'created_at' or 'upvotes'
     * @param string $order 'ASC' or 'DESC'
     * @return array{total: int, discussions: list<\OmegaUp\DAO\VO\ProblemDiscussions>}
     */
    final public static function getByProblemId(
        int $problemId,
        int $page = 1,
        int $pageSize = 20,
        string $sortBy = 'created_at',
        string $order = 'DESC'
    ): array {
        // Validate sortBy
        $validSortBy = ['created_at', 'upvotes', 'downvotes'];
        if (!in_array($sortBy, $validSortBy)) {
            $sortBy = 'created_at';
        }
        // Validate order
        $order = strtoupper($order);
        if ($order !== 'ASC' && $order !== 'DESC') {
            $order = 'DESC';
        }

        // Get total count
        $sqlCount = '
            SELECT
                COUNT(*)
            FROM
                `Problem_Discussions`
            WHERE
                `problem_id` = ?;';
        /** @var int */
        $total = \OmegaUp\MySQLConnection::getInstance()->GetOne(
            $sqlCount,
            [$problemId]
        );

        // Get discussions with pagination
        $offset = ($page - 1) * $pageSize;
        $sanitizedSortBy = \OmegaUp\MySQLConnection::getInstance()->escape(
            $sortBy
        );
        $sql = '
            SELECT
                ' . \OmegaUp\DAO\DAO::getFields(
            \OmegaUp\DAO\VO\ProblemDiscussions::FIELD_NAMES,
            'Problem_Discussions'
        ) . "
            FROM
                `Problem_Discussions`
            WHERE
                `problem_id` = ?
            ORDER BY
                `{$sanitizedSortBy}` {$order}
            LIMIT
                ?, ?;";

        /** @var list<array{discussion_id: int, problem_id: int, identity_id: int, content: string, upvotes: int, downvotes: int, created_at: \OmegaUp\Timestamp, updated_at: \OmegaUp\Timestamp}> */
        $rs = \OmegaUp\MySQLConnection::getInstance()->GetAll(
            $sql,
            [$problemId, $offset, $pageSize]
        );

        $discussions = [];
        foreach ($rs as $row) {
            $discussions[] = new \OmegaUp\DAO\VO\ProblemDiscussions($row);
        }

        return [
            'total' => intval($total),
            'discussions' => $discussions,
        ];
    }

    /**
     * Get a single discussion by ID with all related data
     *
     * @param int $discussionId
     * @return \OmegaUp\DAO\VO\ProblemDiscussions|null
     */
    final public static function getDiscussionById(
        int $discussionId
    ): ?\OmegaUp\DAO\VO\ProblemDiscussions {
        return self::getByPK($discussionId);
    }

    /**
     * Update vote counts for a discussion
     *
     * @param int $discussionId
     * @param int $upvotes
     * @param int $downvotes
     * @return int Number of affected rows
     */
    final public static function updateVoteCounts(
        int $discussionId,
        int $upvotes,
        int $downvotes
    ): int {
        $sql = '
            UPDATE
                `Problem_Discussions`
            SET
                `upvotes` = ?,
                `downvotes` = ?
            WHERE
                `discussion_id` = ?;';
        $params = [$upvotes, $downvotes, $discussionId];
        \OmegaUp\MySQLConnection::getInstance()->Execute($sql, $params);
        return \OmegaUp\MySQLConnection::getInstance()->Affected_Rows();
    }
}
