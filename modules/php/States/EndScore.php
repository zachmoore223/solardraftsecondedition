<?php

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition\States;

use Bga\GameFramework\StateType;
use Bga\Games\SolarDraftSecondEdition\Game;

const ST_END_GAME = 99;

class EndScore extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 98,
            type: StateType::GAME,
        );
    }

    /**
     * Game state action, example content.
     *
     * The onEnteringState method of state `EndScore` is called just before the end of the game.
     */
    public function onEnteringState() {
        // Calculate and update scores for all players
        $this->game->updateAllScores();
        
        // Notify clients of updated scores
        $players = $this->game->getCollectionFromDb(
            "SELECT player_id, player_score, player_name FROM player"
        );
        
        foreach ($players as $playerId => $player) {
            $this->game->notify->all(
                'scoreUpdated',
                '',
                [
                    'player_id' => (int)$playerId,
                    'score' => (int)$player['player_score']
                ]
            );
        }
        
        // Send detailed scoring breakdown for debugging
        $allBreakdowns = [];
        foreach ($players as $playerId => $player) {
            $breakdown = $this->game->calculatePlayerScoreWithBreakdown((int)$playerId);
            $allBreakdowns[(int)$playerId] = [
                'player_name' => $player['player_name'],
                'breakdown' => $breakdown,
            ];
        }
        
        $this->game->notify->all(
            'scoringBreakdown',
            '',
            [
                'breakdowns' => $allBreakdowns
            ]
        );

        return ST_END_GAME;
    }
}