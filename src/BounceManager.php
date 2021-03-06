<?php
namespace strong2much\bounce;

use Yii;
use yii\base\Component;
use strong2much\bounce\models\Bounce;
use strong2much\bounce\models\BounceHistory;
use strong2much\bounce\helpers\BounceHandler;
use yii\helpers\ArrayHelper;

/**
 * BounceManager is an application component that manages with email bounces
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class BounceManager extends Component
{
    /**
     * @var int max number of soft bounces after that it will be blocked permanently
     */
    public $maxSoftBounces = 10;

    /**
     * @var array list of statuses that we count as soft
     */
    public $softStatuses = [
        '5.0.0',
        '5.3.4',
        '5.7.0',
        '5.2.0'
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();
    }

    /**
     * Retrieve bounce report from DB
     * @param string $address email address
     * @return Bounce|null
     */
    public function getReport($address)
    {
        return Bounce::findOne($address);
    }

    /**
     * Check whether report exists for specified address
     * @param string $address email address
     * @return bool
     */
    public function hasReport($address)
    {
        $bounce = Bounce::findOne($address);
        return isset($bounce);
    }

    /**
     * Push report into DB
     * @param string $address email address
     * @param array $data list of additional data
     * @return bool true on success, false otherwise
     */
    public function pushReport($address, array $data)
    {
        if(empty($data))
            return false;

        if($this->hasReport($address))
            return false;

        if(!isset($data['critical']))
            $data['critical'] = false;

        if(isset($data['status']) && in_array($data['status'], $this->softStatuses))
            $data['critical'] = false;

        if($this->numberOfBounces($address)>$this->maxSoftBounces)
            $data['critical'] = true;

        $history = new BounceHistory();
        $history->email = $address;
        $history->is_critical = $data['critical'] ? 1 : 0;
        $history->reason = isset($data['reason']) ? $data['reason'] : '';
        $history->status = isset($data['status']) ? $data['status'] : '';
        $history->type = isset($data['type']) ? $data['type'] : '';

        if($history->save() && $data['critical']) {
            $bounce = new Bounce();
            $bounce->email = $address;
            $bounce->save();
        }

        return true;
    }

    /**
     * Returns number of (soft) bounces for the given email address in {{%bounce_history}}
     * @param string $address email
     * @return int
     */
    public function numberOfBounces($address)
    {
        return BounceHistory::find()->where(['email'=>$address])->count();
    }

    /**
     * Bounce handler for working with bouncing raw email
     * @param string $message raw email message
     * @return BounceHandler
     */
    public function getHandler($message)
    {
        $handler = new BounceHandler();
        $handler->parse_email($message);
        return $handler;
    }

    /**
     * Parse raw email and return multi array of bounce data
     * (that is used in (@see pushReport)
     * @param string $message raw email message
     * @return array
     */
    public function parseMessage($message)
    {
        $handler = $this->getHandler($message);

        $data = [];
        foreach($handler->output as $response) {
            $data[] = [
                'recipient' => $handler->recipient,
                'critical' => $response['action'] == 'failed',
                'type' => $response['action'],
                'status' => $response['status'],
                'reason' => $handler->fetch_status_messages($response['status']),
            ];
        }

        return $data;
    }

    /**
     * Register translation
     */
    protected function registerTranslations()
    {
        $i18n = Yii::$app->i18n;
        if(!isset($i18n->translations['bounce*'])) {
            $i18n->translations['bounce*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@strong2much/bounce/messages',
            ];
        }
    }
}
