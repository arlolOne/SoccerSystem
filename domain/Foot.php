<?php

namespace app\domain;

use app\models\Foot as FootModel;

class Foot
{
    public $eventId;
    public $date;
    public $league;
    public $firstTeam;
    public $secondTeam;
    public $state;
    public $initLine;

    public $firstScore;
    public $secondScore;
    public $removal;
    public $odds;
    public $bet;

    public $firstResult;
    public $secondResult;

    public function __construct($data)
    {
        if (CommonFunction::checkFootBadLeague($data['L'])) {
            return;
        }
        
        $this->eventId = $data['I'];
        $this->state = $this->getState($data['SC']);

        if ($this->state === 'begin') {
            $this->date = date("Y-m-d H:i", $data['S']);
            $this->setLeague($data['O1'], $data['O2'], $data['L']);
            $this->firstTeam = $data['O1'];
            $this->secondTeam = $data['O2'];
            $initLineArr = isset($data['AE'][1]['ME']) ? $data['AE'][1]['ME'] : '';
            $this->initLine = $this->getEqualLine($initLineArr);
        }

        if ($this->state === 'pause') {
            $this->getPauseScore($data['SC']['PS'][0]['Value']);
            $removalArr = isset($data['SC']['S']) ? $data['SC']['S'] : '';
            $this->removal = $this->getRemoval($removalArr);
            $this->odds = isset($data['AE'][1]['ME']) ? $data['AE'][1]['ME'] : '';
        }

        if ($this->state === 'end') {
            $this->getResultScore($data['SC']['FS']);
        }
    }

    public function saveInitLine()
    {
        if (!is_array($this->initLine)) {
            return;
        }
        
        $foundEvent = $this->checkExisting();
        if (!$foundEvent) {
            $model = new FootModel();
            $model->eventId = $this->eventId;
            $model->date = $this->date;
            $model->league = $this->league;
            $model->firstTeam = $this->firstTeam;
            $model->secondTeam = $this->secondTeam;
            $model->initLine = $this->initLine["value"];
            $model->initCoefOver = $this->initLine["coefOver"];
            $model->initCoefUnder = $this->initLine["coefUnder"];
            $model->save(false);
        } else {
            if ($foundEvent->eventId !== $this->eventId) {
                $foundEvent->eventId = $this->eventId;
                $foundEvent->save();
            }
        }
    }

    public function compareValues()
    {
        $foundEvent = FootModel::find()
            ->where(['eventId' => $this->eventId])
            ->andWhere(['bet' => null])
            ->one();
        if ($foundEvent && is_array($this->odds)) {
            $foundLine = ceil($foundEvent->initLine);
            $givenLine = $this->firstScore + $this->secondScore;

            if ($givenLine >= 3 && $givenLine >= $foundLine && !$this->removal) {
                $this->bet = $this->defineBet($givenLine);

                $foundEvent->pauseScore = "$this->firstScore:$this->secondScore";
                $foundEvent->bet = $this->bet['value'];
                $foundEvent->betCoef = $this->bet['coef'];
                $foundEvent->save();
            }
        }
    }

    public function getResult()
    {
        if (empty($this->firstResult) || empty($this->secondResult)) {
            return;
        }
        
        $foundEvent = FootModel::find()
            ->where(['eventId' => $this->eventId])
            ->andWhere(['not', ['bet' => null]])
            ->andWhere(['resultScore' => null])
            ->one();
        if ($foundEvent) {
            $foundEvent->resultScore = "$this->firstResult:$this->secondResult";
            $resultTotal = floor($this->firstResult + $this->secondResult);
            $foundEvent->betResult = self::calculateBetUnder($foundEvent->bet, $foundEvent->betCoef, $resultTotal);
            $foundEvent->save();
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
                } else {
                    return 'continued';
                }
            }
        }
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

    private function defineBet($count)
    {
        $oddsUnder = [];
        foreach ($this->odds as $odd) {
            if ($odd['T'] === 10) {
                array_push($oddsUnder, ['value' => $odd['P'], 'coef' => $odd['C']]);
            }
        }

        if ($count === 3) {
            for ($i = 0; $i < count($oddsUnder); $i++) {
                if ($oddsUnder[$i]['value'] === 4.5 && $oddsUnder[$i]['coef'] >= 1.6) {
                    return $oddsUnder[$i];
                } else if ($oddsUnder[$i]['value'] === 4.5 && $oddsUnder[$i]['coef'] < 1.6 && $i !== 0) {
                    return $oddsUnder[$i - 1];
                }
            }
            return -1;
        } else {
            for ($i = 0; $i < count($oddsUnder); $i++) {
                if ($oddsUnder[$i]['value'] === ($count + 2) && $oddsUnder[$i]['coef'] >= 1.85) {
                    return $oddsUnder[$i];
                }
            }
            for ($i = 0; $i < count($oddsUnder); $i++) {
                if ($oddsUnder[$i]['value'] === ($count + 1.5) && $oddsUnder[$i]['coef'] >= 1.6) {
                    return $oddsUnder[$i];
                } else if ($oddsUnder[$i]['value'] === ($count + 1.5) && $oddsUnder[$i]['coef'] < 1.6 && $i !== 0) {
                    return $oddsUnder[$i - 1];
                }
            }
            return -1;
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
        return $foundEvent;
    }

    private function getPauseScore($scoreArr)
    {
        $firstScore = 0;
        $secondScore = 0;

        if (isset($scoreArr['S1'])) {
            $firstScore = $scoreArr['S1'];
        }
        if (isset($scoreArr['S2'])) {
            $secondScore = $scoreArr['S2'];
        }
        
        $this->firstScore = $firstScore;
        $this->secondScore = $secondScore;
    }

    private function getResultScore($scoreArr)
    {
        $firstScore = isset($scoreArr['S1']) ? $scoreArr['S1'] : '';
        $secondScore = isset($scoreArr['S2']) ? $scoreArr['S2'] : '';

        $this->firstResult = $firstScore;
        $this->secondResult = $secondScore;
    }

    private function getRemoval($removalArr)
    {
        if (isset($removalArr[2]) && ($removalArr[2]['Value'] || $removalArr[3]['Value'])) {
            return true;
        }
        return false;
    }

    private static function calculateBetUnder($bet, $betCoef, $resultTotal)
    {
        if (round($bet) === floor($bet) + 1) {
            if ($resultTotal <= floor($bet)) {
                return ($betCoef - 1) * 10;
            } else {
                return -10;
            }
        } else {
            $bet = floor($bet);
            if ($resultTotal < $bet) {
                return ($betCoef - 1) * 10;
            } else if ($resultTotal === $bet) {
                return 0;
            } else {
                return -10;
            }
        }
    }

    public static function getAdditionalResults() 
    {
        $foundEvents = FootModel::find()
            ->where(['not', ['bet' => null]])
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
                $foundEvent->betResult = self::calculateBetUnder($foundEvent->bet, $foundEvent->betCoef, $total);
                $foundEvent->save();
            } else {
                $foundEvent->resultScore = $scoreString;
                $foundEvent->betResult = 0;
                $foundEvent->save();
            }
            return 1;
        }
    }

    private function setLeague($firstTeam, $secondTeam, $league)
    {
        $firstChecked = false;
        $secondChecked = false;
        
        for ($i = 14; $i <= 23; $i++) { 
            if (str_contains($firstTeam, "($i)") 
            || str_contains(mb_strtolower($firstTeam, "UTF-8"), " u$i")
            || str_contains(mb_strtolower($firstTeam, "UTF-8"), "(u$i)")) 
            {
                $firstChecked = true;
            }

            if (str_contains($secondTeam, "($i)") 
            || str_contains(mb_strtolower($secondTeam, "UTF-8"), " u$i")
            || str_contains(mb_strtolower($secondTeam, "UTF-8"), "(u$i)")) 
            {
                $secondChecked = true;
            }
        }

        if ($firstChecked || $secondChecked) {
            $this->league = $league . ". Молодёжная";
        } else {
            $this->league = $league;
        }
    }

    private static function checkForFootTwo($event) 
    {
        $checked = true;

        if (str_contains(mb_strtolower($event->league, "UTF-8"), 'до ') 
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'женщин')
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'женск')
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'молодёжн') 
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'молодежн')
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'университ')
        || str_contains(mb_strtolower($event->league, "UTF-8"), 'резерв'))
        {
            $checked = false;
        }

        if (str_contains($event->pauseScore, '0')) {
            $checked = false;
        }

        if (!round($event->initLine, 1) <= 2) {
            $checked = false;
        } 

        return $checked;
    }
}
