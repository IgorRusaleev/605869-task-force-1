<?php
declare(strict_types=1);

namespace TaskForce;

use Exception;
use phpDocumentor\Reflection\Types\Null_;
use TaskForce\Action\AbstractSelectingAction;
use TaskForce\Action\Cancel;
use TaskForce\Action\Complete;
use TaskForce\Action\Refusal;
use TaskForce\Action\Response;
use TaskForce\Exception\StatusExistsException;

/**
 * класс для определения списков действий и статусов, и выполнения базовой работы с ними
 * Class Task
 * @package TaskForce\src
 */
class Tasks
{
    /**
     * константы статусов заданий
     */
    const STATUS_NEW = 1; //статус нового задания
    const STATUS_CANCEL = 2; //статус отмененного задания
    const STATUS_IN_WORK = 3; //статус задания находящегося в работе
    const STATUS_COMPLETED = 4; //статус выполненного задания
    const STATUS_FAILED = 5; //статус проваленного задания

    /**
     * id исполнителя
     * @var int
     */
    public $idPerformer;

    /**
     * id заказчика
     * @var int
     */
    public $idCustomer;

    /**
     * id текущего пользователя
     * @var int
     */
    public $idUser;

    /**
     * статус
     * @var string
     */
    public $status;

    /**
     * @var AbstractSelectingAction[]
     */
    public $actions = []; //действия которое можно выполнить из текущего статуса

    /**
     * @var string
     */
    public $availableAction; // действие которое выполняется из текущего статуса

    /**
     * Task constructor.
     * конструктор для получения id исполнителя и id заказчика
     * @param int $idPerformer
     * @param int $idCustomer
     * @param int $idUser
     * @param string $status
     * @throws StatusExistsException
     */
    public function __construct($idPerformer, int $idCustomer, int $idUser, string $status)
    {
        $this->idPerformer = $idPerformer;
        $this->idCustomer = $idCustomer;
        $this->idUser = $idUser;
//            проверка статуса передаваемого в конструктор, на существование
//            если передаваемый статус не существует, то выбрасывается исключение
        if (in_array($status,
            [
                self::STATUS_CANCEL,
                self::STATUS_FAILED,
                self::STATUS_IN_WORK,
                self::STATUS_NEW,
                self::STATUS_COMPLETED
            ])) {
            $this->status = $status;
        } else {
            throw new StatusExistsException('Неожиданный cтатус задачи ' . $status);
        }
    }

    /**
     * метод возвращающий статус в который перейдет задание
     * @return string|null
     * @throws Exception
     */
    public function getNextStatus($taskId): ?string
    {
        $availableAction = $this->getAvailableAction($taskId);
        $map = $this->getActionStatusMap();

        return $map[$availableAction] ?? null;
    }

    /**
     * метод возвращающий карту статусов
     * @return array
     */
    private function getStatusMap(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_CANCEL => 'Отменен',
            self::STATUS_IN_WORK => 'В работе',
            self::STATUS_COMPLETED => 'Выполнено',
            self::STATUS_FAILED => 'Провалено'
        ];
    }

    /**
     * @return array AbstractSelectingAction
     * метод возвращающий карту действий
     */
    private function getActionMap(): array
    {
        return [
            (new Cancel()),
            (new Response()),
            (new Complete()),
            (new Refusal())
        ];
    }

    /**
     * Метод возвращающий возможные действия к текущему статусу
     * @return array
     * @throws Exception
     */
    public function availableAction(): ?array
    {
        if ($this->status == self::STATUS_NEW) {
            return [new Response(), new Cancel()];
        } elseif ($this->status == self::STATUS_IN_WORK) {
            return [new Complete(), new Refusal()];
        }
        return [];
    }

    /**
     * Метод возвращающий действие к текущему статусу
     * @return string|null
     * @throws Exception
     */
    public function getAvailableAction($taskId): ?array
    {
        $actions = $this->availableAction();
        foreach ($actions as $action) {
            if ($action->checkingUserStatus($this->idPerformer, $this->idCustomer, $this->idUser)) {
                return [$action->getActionCode(), $action->getActionTitle($taskId)];
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function getActionStatusMap()
    {
        return [
            (new Response())->getActionCode() => self::STATUS_IN_WORK,
            (new Cancel())->getActionCode() => self::STATUS_CANCEL,
            (new Refusal())->getActionCode() => self::STATUS_FAILED,
            (new Complete())->getActionCode() => self::STATUS_COMPLETED
        ];
    }
}
