<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "foot".
 *
 * @property int $id
 * @property string|null $date
 * @property string|null $league
 * @property string|null $firstTeam
 * @property string|null $secondTeam
 * @property float|null $initLine
 * @property float|null $initCoefOver
 * @property float|null $initCoefUnder
 * @property string|null $pauseScore
 * @property float|null $bet
 * @property float|null $betCoef
 * @property string|null $resultScore
 * @property float|null $betResult
 * @property int|null $eventId
 */
class Foot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'foot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['initLine', 'initCoefOver', 'initCoefUnder', 'bet', 'betCoef', 'betResult'], 'number'],
            [['eventId'], 'integer'],
            [['league', 'firstTeam', 'secondTeam'], 'string', 'max' => 255],
            [['pauseScore', 'resultScore'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'league' => 'League',
            'firstTeam' => 'First Team',
            'secondTeam' => 'Second Team',
            'initLine' => 'Init Line',
            'initCoefOver' => 'Init Coef Over',
            'initCoefUnder' => 'Init Coef Under',
            'pauseScore' => 'Pause Score',
            'bet' => 'Bet',
            'betCoef' => 'Bet Coef',
            'resultScore' => 'Result Score',
            'betResult' => 'Bet Result',
            'eventId' => 'Event ID',
        ];
    }
}
