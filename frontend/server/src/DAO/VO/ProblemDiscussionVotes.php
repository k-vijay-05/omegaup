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
 * Value Object class for table `Problem_Discussion_Votes`.
 *
 * @access public
 */
class ProblemDiscussionVotes extends \OmegaUp\DAO\VO\VO {
    const FIELD_NAMES = [
        'vote_id' => true,
        'discussion_id' => true,
        'identity_id' => true,
        'vote_type' => true,
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
        if (isset($data['vote_id'])) {
            $this->vote_id = intval(
                $data['vote_id']
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
        if (isset($data['vote_type'])) {
            $this->vote_type = is_scalar(
                $data['vote_type']
            ) ? strval($data['vote_type']) : '';
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
    public $vote_id = 0;

    /**
     * El comentario que fue votado
     *
     * @var int|null
     */
    public $discussion_id = null;

    /**
     * Identidad del usuario que votó
     *
     * @var int|null
     */
    public $identity_id = null;

    /**
     * Tipo de voto
     *
     * @var string|null
     */
    public $vote_type = null;

    /**
     * Fecha del voto
     *
     * @var \OmegaUp\Timestamp
     */
    public $created_at;  // CURRENT_TIMESTAMP
}
