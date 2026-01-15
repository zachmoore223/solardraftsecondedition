<?php

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition\States;

use Bga\GameFramework\StateType;
use Bga\Games\SolarDraftSecondEdition\Game;

class NextPlayer extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 90,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action, example content.
     *
     * The onEnteringState method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    function onEnteringState(int $activePlayerId) {

        // Give some extra time to the active player when he completed an action
        $this->game->giveExtraTime($activePlayerId);
        
        // Check if deck is empty (game ending)
        $deckEmpty = $this->game->getGameStateValue('deck_empty') == 1;
        $lastCardDrawer = $this->game->getGameStateValue('last_card_drawer');
        
        // Move to next player
        $newActivePlayerId = $this->game->activeNextPlayer();
        
        // Determine if game should end
        $gameEnd = false;
        
        if ($deckEmpty) {
            // If deck is empty, we need to complete the current round
            // Only players who come AFTER the drawer in turn order get one more turn
            // Once we cycle back to or before the drawer, the game ends
            
            // Get all player IDs in turn order
            $players = $this->game->getCollectionFromDb(
                "SELECT player_id FROM player ORDER BY player_no"
            );
            $playerIds = array_values(array_map(function($p) { 
                return (int)$p['player_id']; 
            }, $players));
            
            // Find the position of the last card drawer in the turn order
            $drawerIndex = array_search((int)$lastCardDrawer, $playerIds);
            
            // Find the position of the new active player
            $newActiveIndex = array_search((int)$newActivePlayerId, $playerIds);
            
            if ($drawerIndex !== false && $newActiveIndex !== false) {
                // If the new active player is the drawer, we've completed the round - game ends
                if ($newActivePlayerId == $lastCardDrawer) {
                    $gameEnd = true;
                }
                // If the new active player comes before the drawer in turn order, we've wrapped around
                // This means all players after the drawer have had their turn - game ends
                elseif ($newActiveIndex < $drawerIndex) {
                    $gameEnd = true;
                }
                // Otherwise, the new active player comes after the drawer - they get one more turn
            }
        }

        // Go to another gamestate
        if ($gameEnd) {
            return EndScore::class;
        } else {
            return PlayerTurn::class;
        }
    }
}