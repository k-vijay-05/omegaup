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
 * Value Object class for table `Problem_Discussion_Replies`.
 *
 * @access public
 */
class ProblemDiscussionReplies extends \OmegaUp\DAO\VO\VO {
    const FIELD_NAMES = [
        'reply_id' => true,
        'discussion_id' => true,
        'identity_id' => true,
        'is_anonymous' => true,
        'content' => true,
        'created_at' => true,
        'updated_at' => true,
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
        if (isset($data['reply_id'])) {
            $this->reply_id = intval(
                $data['reply_id']
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
        if (isset($data['is_anonymous'])) {
            $this->is_anonymous = boolval(
                $data['is_anonymous']
            );
        }
        if (isset($data['content'])) {
            $this->content = is_scalar(
                $data['content']
            ) ? strval($data['content']) : '';
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
        if (isset($data['updated_at'])) {
            /**
             * @var \OmegaUp\Timestamp|string|int|float $data['updated_at']
             * @var \OmegaUp\Timestamp $this->updated_at
             */
            $this->updated_at = (
                \OmegaUp\DAO\DAO::fromMySQLTimestamp(
                    $data['updated_at']
                )
            );
        } else {
            $this->updated_at = new \OmegaUp\Timestamp(
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
    public $reply_id = 0;

    /**
     * El comentario principal al que responde
     *
     * @var int|null
     */
    public $discussion_id = null;

    /**
     * Identidad del usuario que creó la respuesta
     *
     * @var int|null
     */
    public $identity_id = null;

    /**
     * Indica si la respuesta fue publicada de forma anónima
     *
     * @var bool
     */
    public $is_anonymous = false;

    /**
     * Contenido de la respuesta en formato markdown
     *
     * @var string|null
     */
    public $content = null;

    /**
     * Fecha de creación de la respuesta
     *
     * @var \OmegaUp\Timestamp
     */
    public $created_at;  // CURRENT_TIMESTAMP

    /**
     * Fecha de última actualización
     *
     * @var \OmegaUp\Timestamp
     */
    public $updated_at;  // CURRENT_TIMESTAMP
}
