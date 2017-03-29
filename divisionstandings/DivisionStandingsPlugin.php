<?php
namespace Craft;

class DivisionStandingsPlugin extends BasePlugin
{
    protected $columnToFieldMapping = [
        'name' => 'col1',
        'wins' => 'col2',
        'losses' => 'col3'
    ];

    protected $divisions = ['southDivision', 'northDivision'];

    public function init()
    {
        // Special binding for older version of PHP
        $instance = $this;

        // Older version of PHP do not have access to $this within a callable
        craft()->templates->hook('generateDivisionStandings', function (&$context) use ($instance)
        {
            foreach ($instance->divisions as $division)
            {
                if (isset($context[ $division ]))
                {
                    $context[ $division ] = $instance->generateDivisionStandings($context[ $division ]);
                }
            }
        });
    }

    public function getName()
    {
        return 'Division Standings';
    }

    public function getVersion()
    {
        return '0.2.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
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

        foreach ($division as &$team)
        {
            $name = $team[ $this->columnToFieldMapping['name'] ];
            $wins = $team[ $this->columnToFieldMapping['wins'] ];
            $losses = $team[ $this->columnToFieldMapping['losses'] ];
            $winning = $this->calculateWinningPercentage($wins, $losses);

            $columns[ $this->textToHandle($name) ] = $winning;
            $standings[ $this->textToHandle($name) ] = compact('name', 'wins', 'losses', 'winning');
        }

        array_multisort($columns, SORT_DESC, SORT_NUMERIC, $standings);

        return $this->addGamesBackColumn($standings);
    }

    public function addGamesBackColumn(array $standings)
    {
        $standingsCopy = $standings;
        $standingsLeader = array_shift($standingsCopy);

        foreach ($standings as &$team)
        {
            $team['gamesBack'] = (($standingsLeader['wins'] - $team['wins']) + ($team['losses'] - $standingsLeader['losses'])) / 2;
        }

        // $standingsLeader['games'] = 0;

        return $standings;
	}

    public function calculateWinningPercentage($wins, $losses)
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

    public function textToHandle($text) {
        $words = explode(' ', $text);
        $firstWord = mb_strtolower(array_shift($words));
        $words = array_map(function ($word)
        {
            return ucfirst($word);
        }, $words);

        return $firstWord.implode('', $words);
    }
}
