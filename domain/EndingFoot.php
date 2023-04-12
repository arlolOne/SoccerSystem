<?php

namespace app\domain;

use app\models\EndingFoot as FootModel;

class EndingFoot
{
    public $eventId;
    public $date;
    public $league;
    public $firstTeam;
    public $secondTeam;
    public $state;
    public $isExist;
    public $initLine;
    public $firstVictory;
    public $secondVictory;
    public $favorite;

    public $currentTime;
    public $firstScore;
    public $secondScore;
    public $removal;
    public $odds;

    public $firstResult;
    public $secondResult;

    public function __construct($data)
    {
        if (CommonFunction::checkFootBadLeague($data['L'])) {
            return;
        }

        $this->eventId = $data['I'];
        $this->state = $this->getState($data['SC']);
        $this->date = date("Y-m-d H:i", $data['S']);
        $this->firstTeam = $data['O1'];
        $this->secondTeam = $data['O2'];
        $this->isExist = $this->checkExisting();

        if ($this->state === 'begin') {
            $this->league = $data['L'];
            $initLineArr = isset($data['AE'][1]['ME']) ? $data['AE'][1]['ME'] : '';
            $this->initLine = $this->getEqualLine($initLineArr);
            $victoryArr = isset($data['E']) ? $data['E'] : '';
            $this->getVictoryCoef($victoryArr);
            $this->getFavorite();
        }

        if ($this->state === 'ending' || $this->state === 'final') {
            $this->currentTime = round($data['SC']['TS'] / 60);
            $scoreArr = isset($data['SC']['PS']) ? $data['SC']['PS'] : '';
            $this->getCurrentScore($scoreArr);

            $this->getFirstGoal();
        }

        if ($this->state === 'ending') {
            $removalArr = isset($data['SC']['S']) ? $data['SC']['S'] : '';
            $this->removal = $this->getRemoval($removalArr);
            $this->odds = isset($data['AE'][1]['ME']) ? $data['AE'][1]['ME'] : '';
        }

        if ($this->state === 'end') {
            $this->getResultScore($data['SC']['FS']);
        }
    }

    private function getState($stateArr)
    {
        if (!isset($stateArr['TS'])) {
            return 'begin';
        } else {
            if (isset($stateArr['CPS'])) {
                if ($stateArr['CPS'] === 'Перерыв') {
                    return 'pause';
                } else if ($stateArr['CPS'] === 'Игра завершена') {
                    return 'end';
                } else if ($stateArr['TS'] >= 3600 && $stateArr['TS'] <= 4800) {
                    return 'ending';
                } else if ($stateArr['TS'] > 4800) {
                    return 'final';
                } else {
                    return 'continued';
                }
            } else {
                if ($stateArr['TS'] >= 3600 && $stateArr['TS'] <= 4800) {
                    return 'ending';
                }
            }
        }
    }

    private function getCurrentScore($scoreArr)
    {
        if (!isset($scoreArr[0]) || !isset($scoreArr[1])) {
            return false;
        }

        $firstHalf = $scoreArr[0]['Value'];
        $secondHalf = $scoreArr[1]['Value'];
        
        $firstScore = 0;
        $secondScore = 0;

        if (isset($firstHalf['S1'])) {
            $firstScore += $firstHalf['S1'];
        }
        if (isset($firstHalf['S2'])) {
            $secondScore += $firstHalf['S2'];
        }

        if (isset($secondHalf['S1'])) {
            $firstScore += $secondHalf['S1'];
        }
        if (isset($secondHalf['S2'])) {
            $secondScore += $secondHalf['S2'];
        }

        $this->firstScore = $firstScore;
        $this->secondScore = $secondScore;
    }

    private function getResultScore($scoreArr)
    {
        if (!isset($scoreArr['S1']) || !isset($scoreArr['S2'])) {
            return;
        }

        $this->firstResult = $scoreArr['S1'];
        $this->secondResult = $scoreArr['S2'];
    }

    public function setOneResultScore()

    {
        if (empty($this->firstResult) || empty($this->secondResult)) {
            return;
        }
        
        $foundEvent = FootModel::find()
            ->where(['eventId' => $this->eventId])
            ->andWhere(['not', ['checkScore' => null]])
            ->andWhere(['resultScore' => null])
            ->one();
        if ($foundEvent) {
            $foundEvent->resultScore = "$this->firstResult:$this->secondResult";
            $foundEvent->save();
        }
    }

    public function compareValues()
    {
        $foundEvent = FootModel::find()
            ->where(['eventId' => $this->eventId])
            ->andWhere(['checkScore' => null])
            ->one();
        if ($foundEvent) {
            $isStrong = $this->isFavoriteStrong($foundEvent->firstVictory, $foundEvent->secondVictory);
            $isLosing = $this->isFavoriteLosing($foundEvent->favorite);

            if ($isLosing && $isStrong && !$this->removal) {
                $foundEvent->checkTime = $this->currentTime;
                $foundEvent->checkScore = "$this->firstScore:$this->secondScore";
                $initBetOdd = $this->getInitBet();
                $foundEvent->initBet = $initBetOdd['value'];
                $foundEvent->initCoef = $initBetOdd['coef'];
                $foundEvent->save();
            }
        }
    }

    private function getInitBet()
    {
        if (!is_array($this->odds)) {
            return ['value' => '', 'coef' => ''];
        }
        
        $oddsOver = [];
        foreach ($this->odds as $odd) {
            if ($odd['T'] === 9) {
                array_push($oddsOver, ['value' => $odd['P'], 'coef' => $odd['C']]);
            }
        }

        $minOdd = $oddsOver[0];
        foreach ($oddsOver as $odd) {
            if ($odd['value'] < $minOdd['value']) {
                $minOdd = $odd;
            }
        }

        return $minOdd;
    }

    private function defineBet()
    {
        if (!is_array($this->odds)) {
            return false;
        }

        $oddsOver = [];
        foreach ($this->odds as $odd) {
            if ($odd['T'] === 9) {
                array_push($oddsOver, ['value' => $odd['P'], 'coef' => $odd['C']]);
            }
        }
        
        $currentTotal = $this->firstScore + $this->secondScore;
        if ($oddsOver[0]['coef'] >= 1.7 && $oddsOver[0]['value'] === $currentTotal + 0.5) {
            return $oddsOver[0];
        } else {
            return false;
        }
    }

    private function isFavoriteStrong($firstVictory, $secondVictory)
    {
        $firstTeam = round($firstVictory, 4);
        $secondTeam = round($secondVictory, 4);
        $lessValue = $firstTeam;

        if ($firstTeam - $secondTeam > 0) {
            $lessValue = $secondTeam;
        }
        if ($lessValue > 1.50) {
            return false;
        }
        
        return true;
    }

    private function isFavoriteLosing($favorite)
    {
        if ($this->firstScore >= $this->secondScore && $favorite === 1) {
            return false;
        }
        if ($this->firstScore <= $this->secondScore && $favorite === 2) {
            return false;
        }

        if (abs($this->firstScore - $this->secondScore) > 3) {
            return false;
        }
        
        return true;
    }

    private function getEqualLine($oddsArr)
    {
        if (!is_array($oddsArr)) {
            return false;
        }

        $coefs = [];
        foreach ($oddsArr as $odds) {
            if (isset($odds['CE'])) {
                $coefs["value"] = $odds['P'];
                array_push($coefs, $odds['C']);
            }
        }

        $coefs["coefOver"] = $coefs[0];
        $coefs["coefUnder"] = $coefs[1];
        unset($coefs[0]);
        unset($coefs[1]);

        return $coefs;
    }
    
    public function saveInitOdds()
    {
        if (!is_array($this->initLine) || !$this->firstVictory || !$this->secondVictory || !$this->favorite) {
            return;
        }
        
        if (!$this->isExist) {
            $model = new FootModel();
            $model->eventId = $this->eventId;
            $model->date = $this->date;
            $model->league = $this->league;
            $model->firstTeam = $this->firstTeam;
            $model->secondTeam = $this->secondTeam;
            $model->initLine = $this->initLine["value"];
            $model->initCoefOver = $this->initLine["coefOver"];
            $model->initCoefUnder = $this->initLine["coefUnder"];
            $model->firstVictory = $this->firstVictory;
            $model->secondVictory = $this->secondVictory;
            $model->favorite = $this->favorite;
            $model->save(false);
        } 
    }

    private function checkExisting()
    {
        $date = explode(' ', $this->date)[0];
        $foundEvent = FootModel::find()
            ->where(['like', 'firstTeam', "%$this->firstTeam%", false])
            ->andWhere(['like', 'secondTeam', "%$this->secondTeam%", false])
            ->andWhere(['>=', 'date', $date . ' 00:00'])
            ->andWhere(['<=', 'date', $date . ' 23:59'])
            ->one();

        if ($foundEvent) {
            if ($foundEvent->eventId !== $this->eventId) {
                $foundEvent->eventId = $this->eventId;
                $foundEvent->save();
            }
            return true;
        } else {
            return false;
        }
    }

    private function getVictoryCoef($oddsArr)
    {
        if (!is_array($oddsArr)) {
            return false;
        }

        if (!isset($oddsArr[0]) || !isset($oddsArr[2])) {
            return false;
        }

        if ($oddsArr[0]['T'] === 1 && $oddsArr[2]['T'] === 3) {
            $this->firstVictory = $oddsArr[0]['C'];
            $this->secondVictory = $oddsArr[2]['C'];
        } else {
            return false;
        }
    }

    private function getFavorite()
    {
        if ($this->firstVictory < $this->secondVictory) {
            $this->favorite = 1;
        } else if ($this->firstVictory > $this->secondVictory) {
            $this->favorite = 2;
        } else {
            $this->favorite = null;
        }
    }

    private static function getRemoval($removalArr)
    {
        if (!isset($removalArr[2]) || !isset($removalArr[3])) {
            return false;
        }

        if ($removalArr[2]['Value'] || $removalArr[3]['Value']) {
            return true;
        }
        return false;
    }

    private function getFirstGoal()
    {
        $currentScore = "$this->firstScore:$this->secondScore";
        $foundEvent = FootModel::find()
            ->where(['eventId' => $this->eventId])
            ->andWhere(['not', ['checkScore' => null]])
            ->andWhere(['firstGoalTime' => null])
            ->one();
        if ($foundEvent) { 
            if ($foundEvent->checkScore !== $currentScore && $currentScore !== ':') {
                $foundEvent->firstGoalRes = $currentScore;
                $foundEvent->firstGoalTime = $this->currentTime;
                $foundEvent->save();
            }
        }
    }

    public static function getAdditionalResults() 
    {
        $foundEvents = FootModel::find()
            ->where(['not', ['checkScore' => null]])
            ->andWhere(['resultScore' => null])
            ->all();

        $eventsInfo = [];
        $currentDateItem = explode(' ', $foundEvents[0]->date)[0];
        $events = [];

        for ($i = 0; $i < count($foundEvents); $i++) {
            if (explode(' ', $foundEvents[$i]->date)[0] === $currentDateItem) {
                array_push($events, $foundEvents[$i]->eventId);
                $currentDateItem = explode(' ', $foundEvents[$i]->date)[0];
            } else {
                $eventsInfo[$currentDateItem] = $events;
                $events = [];
                $currentDateItem = explode(' ', $foundEvents[$i]->date)[0];
                array_push($events, $foundEvents[$i]->eventId);
            }

            if ($i === count($foundEvents) - 1) {
                $eventsInfo[$currentDateItem] = $events;
            }
        }

        $count = 0;
        foreach ($eventsInfo as $date => $events) {
            $count += self::findResultScore($date, $events);
        }
        return $count;
    }

    private static function findResultScore($date, $IDs) 
    {
        $client = new CustomClient();
        $allResults = $client->getAllResults($date);
        $sportsTypes = $allResults['results'];
        $football = [];

        foreach ($sportsTypes as $sportsType) {
            if ($sportsType['ID'] === 1) {
                $football = $sportsType['Elems'];
            }
        }

        $count = 0;
        foreach ($IDs as $ID) {
            foreach ($football as $league) {
                if (CommonFunction::checkFootBadLeague($league['Name'])) {
                    continue;
                }
                foreach ($league['Elems'] as $event) {
                    if ($event['idgame'] === $ID) {
                        if (self::setResultScore($ID, $event)) {
                            $count++;
                        }
                    }
                }
            }
        }
        return $count; 
    }

    private static function setResultScore($ID, $event) 
    {
        $foundEvent = FootModel::find()
            ->where(['eventId' => $ID])
            ->one();
        if ($foundEvent) {
            $scoreString = $event['scores'][0];
            if (strlen($scoreString) === 13 || strlen($scoreString) === 14) {
                $onlyScore = explode(' ', $scoreString)[0];
                $totalArray = explode(':', $onlyScore);
                $total = floor($totalArray[0] + $totalArray[1]);
                $foundEvent->resultScore = $onlyScore;
                $foundEvent->save();
            } else {
                $foundEvent->resultScore = $scoreString;
                $foundEvent->save();
            }
            return 1;
        }
    }

    private static function checkForFootFive($event) 
    {
        $checked = true;

        if (str_contains(mb_strtolower($event->league, "UTF-8"), 'женщин') 
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'женск')
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'резерв'))
        {
            $checked = false;
        }

        return $checked;
    }
}
