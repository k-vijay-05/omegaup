<?php

namespace OmegaUp\DAO;

/**
 * ProblemDiscussionReports Data Access Object (DAO).
 *
 * Esta clase contiene toda la manipulacion de bases de datos que se necesita
 * para almacenar de forma permanente y recuperar instancias de objetos
 * {@link \OmegaUp\DAO\VO\ProblemDiscussionReports}.
 * @access public
 * @package docs
 */
class ProblemDiscussionReports extends \OmegaUp\DAO\Base\ProblemDiscussionReports {
    /**
     * Get all reports for a discussion
     *
     * @param int $discussionId
     * @return list<\OmegaUp\DAO\VO\ProblemDiscussionReports>
     */
    final public static function getByDiscussionId(
        int $discussionId
    ): array {
        $sql = '
            SELECT
                ' . \OmegaUp\DAO\DAO::getFields(
            \OmegaUp\DAO\VO\ProblemDiscussionReports::FIELD_NAMES,
            'Problem_Discussion_Reports'
        ) . '
            FROM
                `Problem_Discussion_Reports`
            WHERE
                `discussion_id` = ?
            ORDER BY
                `created_at` DESC;';

        /** @var list<array{report_id: int, discussion_id: int, identity_id: int, reason: string, status: string, created_at: \OmegaUp\Timestamp}> */
        $rs = \OmegaUp\MySQLConnection::getInstance()->GetAll(
            $sql,
            [$discussionId]
        );

        $reports = [];
        foreach ($rs as $row) {
            $reports[] = new \OmegaUp\DAO\VO\ProblemDiscussionReports($row);
        }
        return $reports;
    }

    /**
     * Get all open reports (for admin review)
     *
     * @param int $page
     * @param int $pageSize
     * @return array{total: int, reports: list<\OmegaUp\DAO\VO\ProblemDiscussionReports>}
     */
    final public static function getOpenReports(
        int $page = 1,
        int $pageSize = 20
    ): array {
        // Get total count
        $sqlCount = '
            SELECT
                COUNT(*)
            FROM
                `Problem_Discussion_Reports`
            WHERE
                `status` = "open";';
        /** @var int */
        $total = \OmegaUp\MySQLConnection::getInstance()->GetOne($sqlCount, []);

        // Get reports with pagination
        $offset = ($page - 1) * $pageSize;
        $sql = '
            SELECT
                ' . \OmegaUp\DAO\DAO::getFields(
            \OmegaUp\DAO\VO\ProblemDiscussionReports::FIELD_NAMES,
            'Problem_Discussion_Reports'
        ) . '
            FROM
                `Problem_Discussion_Reports`
            WHERE
                `status` = "open"
            ORDER BY
                `created_at` DESC
            LIMIT
                ?, ?;';

        /** @var list<array{report_id: int, discussion_id: int, identity_id: int, reason: string, status: string, created_at: \OmegaUp\Timestamp}> */
        $rs = \OmegaUp\MySQLConnection::getInstance()->GetAll(
            $sql,
            [$offset, $pageSize]
        );

        $reports = [];
        foreach ($rs as $row) {
            $reports[] = new \OmegaUp\DAO\VO\ProblemDiscussionReports($row);
        }

        return [
            'total' => intval($total),
            'reports' => $reports,
        ];
    }

    /**
     * Check if user already reported a discussion
     *
     * @param int $discussionId
     * @param int $identityId
     * @return bool
     */
    final public static function hasUserReported(
        int $discussionId,
        int $identityId
    ): bool {
        $sql = '
            SELECT
                COUNT(*)
            FROM
                `Problem_Discussion_Reports`
            WHERE
                `discussion_id` = ? AND `identity_id` = ?;';
        /** @var int */
        $count = \OmegaUp\MySQLConnection::getInstance()->GetOne(
            $sql,
            [$discussionId, $identityId]
        );
        return $count > 0;
    }
}
