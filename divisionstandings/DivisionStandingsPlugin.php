<?php
namespace Craft;

class DivisionStandingsPlugin extends BasePlugin
{
    public function init()
    {
        // Special binding for older version of PHP
        $instance = $this;

        // Older version of PHP do not have access to $this within a callable
        craft()->templates->hook('generateDivisionStandings', function (&$context) use ($instance)
        {
            return $instance->generateDivisionStandings($context['division']);
        });
    }

    public function getName()
    {
        return 'Division Standings';
    }

    public function getVersion()
    {
        return '0.1.0';
    }

    public function getDescription()
    {
        return 'Utilities to help you calculate and generate Divisional Standings';
    }

    public function getDeveloper()
    {
        return 'Selvin Ortiz';
    }

    public function getDeveloperUrl() {
        return 'https://selvinortiz.com';
    }

    public function generateDivisionStandings(array $division)
    {
        // For sorting
        $columns = [];

        // Generated division standings
        $standings = [];

        foreach ($division as $name => &$team)
        {
            $wins = $this->getValueByKey($team, 'record.wins');
            $losses = $this->getValueByKey($team, 'record.losses');
            $winning = $this->calculateWinningPercentage($wins, $losses);

            $columns[$name] = $winning;
            $standings[$name] = compact('name', 'wins', 'losses', 'winning');
        }

        array_multisort($columns, SORT_DESC, SORT_NUMERIC, $standings);

        return $this->addGamesBackColumn($standings);
    }

    public function addGamesBackColumn(array $standings)
    {
        $divisionCopy = $standings;
        
        foreach ($standings as &$team)
        {
            $conferenceCopy = $conference;
            $conferenceLeader = array_shift($conferenceCopy);

            foreach ($conference as &$team)
            {
                $team['gamesBack'] = (($conferenceLeader['wins'] - $team['wins']) + ($team['losses'] - $conferenceLeader['losses'])) / 2;
            }

            $conferenceLeader['games'] = 0;
        }

        return $standings;
	}

    public function getWinningPercentage($wins, $losses)
    {
        return round($wins / ($wins + $losses), 3);
    }

    public function getValueByKey(array $arr, $key, $default = null)
    {
        if (!is_string($key) || empty($key) || !count($arr))
        {
            return $default;
        }

        if (strpos($key, '.') !== false)
        {
            $keys = explode('.', $key);

            foreach ($keys as $innerKey)
            {
                if (!array_key_exists($innerKey, $arr))
                {
                    return $default;
                }

                $arr = $arr[$innerKey];
            }

            return $arr;
        }

        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }
}
