<?php
/** ************************************************************************ *
 *                    !ATENCION!                                             *
 *                                                                           *
 * Este codigo es generado automáticamente. Si lo modificas, tus cambios     *
 * serán reemplazados la proxima vez que se autogenere el código.            *
 *                                                                           *
 * ************************************************************************* */

namespace OmegaUp\DAO\VO;

/**
 * Value Object class for table `Problem_Discussion_Reports`.
 *
 * @access public
 */
class ProblemDiscussionReports extends \OmegaUp\DAO\VO\VO {
    const FIELD_NAMES = [
        'report_id' => true,
        'discussion_id' => true,
        'identity_id' => true,
        'reason' => true,
        'status' => true,
        'created_at' => true,
    ];

    public function __construct(?array $data = null) {
        if (empty($data)) {
            return;
        }
        $unknownColumns = array_diff_key($data, self::FIELD_NAMES);
        if (!empty($unknownColumns)) {
            throw new \Exception(
                'Unknown columns: ' . join(', ', array_keys($unknownColumns))
            );
        }
        if (isset($data['report_id'])) {
            $this->report_id = intval(
                $data['report_id']
            );
        }
        if (isset($data['discussion_id'])) {
            $this->discussion_id = intval(
                $data['discussion_id']
            );
        }
        if (isset($data['identity_id'])) {
            $this->identity_id = intval(
                $data['identity_id']
            );
        }
        if (isset($data['reason'])) {
            $this->reason = is_scalar(
                $data['reason']
            ) ? strval($data['reason']) : '';
        }
        if (isset($data['status'])) {
            $this->status = is_scalar(
                $data['status']
            ) ? strval($data['status']) : '';
        }
        if (isset($data['created_at'])) {
            /**
             * @var \OmegaUp\Timestamp|string|int|float $data['created_at']
             * @var \OmegaUp\Timestamp $this->created_at
             */
            $this->created_at = (
                \OmegaUp\DAO\DAO::fromMySQLTimestamp(
                    $data['created_at']
                )
            );
        } else {
            $this->created_at = new \OmegaUp\Timestamp(
                \OmegaUp\Time::get()
            );
        }
    }

    /**
     * [Campo no documentado]
     * Llave Primaria
     * Auto Incremento
     *
     * @var int|null
     */
    public $report_id = 0;

    /**
     * El comentario que fue reportado
     *
     * @var int|null
     */
    public $discussion_id = null;

    /**
     * Identidad del usuario que reportó
     *
     * @var int|null
     */
    public $identity_id = null;

    /**
     * Razón del reporte
     *
     * @var string|null
     */
    public $reason = null;

    /**
     * Estado del reporte
     *
     * @var string
     */
    public $status = 'open';

    /**
     * Fecha del reporte
     *
     * @var \OmegaUp\Timestamp
     */
    public $created_at;  // CURRENT_TIMESTAMP
}
