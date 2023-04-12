<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "endingFoot".
 *
 * @property int $id
 * @property string|null $date
 * @property string|null $league
 * @property string|null $firstTeam
 * @property string|null $secondTeam
 * @property float|null $initLine
 * @property float|null $initCoefOver
 * @property float|null $initCoefUnder
 * @property float|null $firstVictory
 * @property float|null $secondVictory
 * @property int|null $favorite
 * @property int|null $checkTime
 * @property string|null $checkScore
 * @property float|null $initBet
 * @property float|null $initCoef
 * @property int|null $firstGoalTime
 * @property string|null $firstGoalRes
 * @property string|null $resultScore
 * @property int|null $eventId
 */
class EndingFoot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'endingFoot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['initLine', 'initCoefOver', 'initCoefUnder', 'firstVictory', 'secondVictory', 'initBet', 'initCoef'], 'number'],
            [['favorite', 'checkTime', 'firstGoalTime', 'eventId'], 'integer'],
            [['league', 'firstTeam', 'secondTeam', 'checkScore', 'firstGoalRes', 'resultScore'], 'string', 'max' => 255],
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
            'firstVictory' => 'First Victory',
            'secondVictory' => 'Second Victory',
            'favorite' => 'Favorite',
            'checkTime' => 'Check Time',
            'checkScore' => 'Check Score',
            'initBet' => 'Init Bet',
            'initCoef' => 'Init Coef',
            'firstGoalTime' => 'First Goal Time',
            'firstGoalRes' => 'First Goal Res',
            'resultScore' => 'Result Score',
            'eventId' => 'Event ID',
        ];
    }
}
