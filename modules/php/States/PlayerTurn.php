<?php

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\SolarDraftSecondEdition\Game;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 10,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must DRAFT or PLAY a card'),
            descriptionMyTurn: clienttranslate('${you} must DRAFT or PLAY a card'),
        );
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `PlayerTurn` game state.
     */
    public function getArgs(): array
    {
        // Get some values from the current game situation from the database.
        $activePlayerId = $this->game->getActivePlayerId();
            
            // Check if player has any planets
            $hasPlanets = (int) $this->game->getUniqueValueFromDB("
                SELECT COUNT(*)
                FROM `card`
                WHERE card_location = 'tableau'
                AND card_location_arg = $activePlayerId
                AND card_type = 'planet'
            ") > 0;

            return [
                "mustPlayPlanet" => !$hasPlanets  // Add this flag
            ];
    }    

    /*******************
     *   PLAY A CARD   *           
     *******************/
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId)
    {   
        // Take card from player's hand
        $card = $this->game->cards->getCard($card_id);
        $newRingCount = 0;
        $newValue = 0;


        // Move card to the player's tableau
        $this->game->cards->moveCard($card_id, 'tableau', $activePlayerId);

        // Enrich before sending
        $card = $this->game->enrichCard($card);

        // Get all planets currently in tableau (before adding this card)
        $planet_order = array_values(
            array_filter(
                $this->game->cards->getCardsInLocation('tableau', $activePlayerId),
                fn($c) => $c['type'] === 'planet'
            )
        );

        // This card is being added now.
        // So the index for THIS planet = count before adding it.
        $planet_index = count($planet_order);

        // Save this index on the new planet (planets only)
        if ($card['type'] === 'planet') {
            $this->game->DbQuery("
                UPDATE `card`
                SET planet_order = $planet_index
                WHERE card_id = " . (int) $card['id']
            );
            $card['planet_order'] = $planet_index;
        }

        //---------------------------------------
        // Determine parent planet / slot (REAL BGA VERSION FOR STATE CLASS)
        //---------------------------------------
        $parent_id = null;
        $parent_slot = null;

        // Planets never have parents
        if ($card['type'] === 'planet') {
            $parent_id = null;
            $parent_slot = null;
        }

        // MOONS attach to most recent planet
        if ($card['type'] === 'moon') {

            // 1. Find the latest planet for this player
            $latestPlanet = $this->game->getObjectFromDB("
                SELECT card_id
                FROM `card`
                WHERE card_location = 'tableau'
                AND card_location_arg = $activePlayerId
                AND card_type = 'planet'
                ORDER BY planet_order DESC
                LIMIT 1
            ");

            if ($latestPlanet) {
                $parent_id = (int)$latestPlanet['card_id'];

                // 2. Count moons already attached
                $parent_slot = (int) $this->game->getUniqueValueFromDB("
                    SELECT COUNT(*)
                    FROM `card`
                    WHERE parent_id = $parent_id
                    AND card_type = 'moon'
                ");
            }
        }

        // COMETS attach to the most recent planet
        if ($card['type'] === 'comet') {

            // 1. Find latest planet
            $latestPlanet = $this->game->getObjectFromDB("
                SELECT card_id
                FROM `card`
                WHERE card_location = 'tableau'
                AND card_location_arg = $activePlayerId
                AND card_type = 'planet'
                ORDER BY planet_order DESC
                LIMIT 1
            ");

            if ($latestPlanet) {
                $parent_id = (int)$latestPlanet['card_id'];

                // 2. Count comets already attached
                $parent_slot = (int) $this->game->getUniqueValueFromDB("
                    SELECT COUNT(*)
                    FROM `card`
                    WHERE parent_id = $parent_id
                    AND card_type = 'comet'
                ");
            }
        }

        //---------------------------------------
        // Save parent info into DB
        //---------------------------------------
        $parentIdSql   = ($parent_id === null)   ? "NULL" : $parent_id;
        $parentSlotSql = ($parent_slot === null) ? "NULL" : $parent_slot;

        $this->game->DbQuery("
            UPDATE `card`
            SET parent_id = $parentIdSql,
                parent_slot = $parentSlotSql
            WHERE card_id = " . (int)$card['id']
        );

        // Add them to the card being sent to UI
        $card['parent_id'] = $parent_id;
        $card['parent_slot'] = $parent_slot;
       
        $cardColor = $card['color'];
        $cardRings = $card['rings'];
        
        if ($card['type'] === 'planet') {

            $cardColor = $card['color'];
            $newValue = null;

            switch ($cardColor) {
                case 'BLUE':
                    $this->game->blue_planet_count->inc($activePlayerId, 1);
                    $newValue = $this->game->blue_planet_count->get($activePlayerId);
                    $counter = 'blue';
                    break;

                case 'GREEN':
                    $this->game->green_planet_count->inc($activePlayerId, 1);
                    $newValue = $this->game->green_planet_count->get($activePlayerId);
                    $counter = 'green';
                    break;

                case 'RED':
                    $this->game->red_planet_count->inc($activePlayerId, 1);
                    $newValue = $this->game->red_planet_count->get($activePlayerId);
                    $counter = 'red';
                    break;

                case 'TAN':
                    $this->game->tan_planet_count->inc($activePlayerId, 1);
                    $newValue = $this->game->tan_planet_count->get($activePlayerId);
                    $counter = 'tan';
                    break;
            }
        }
        
        if ($cardRings > 0){
            $this->game->ring_count->inc($activePlayerId, $cardRings);
            $newRingCount = $this->game->ring_count->get($activePlayerId);
        } 

        if ($card['type'] === 'comet'){
            $this->game->comet_count->inc($activePlayerId, 1);
            $newValue = $this->game->comet_count->get($activePlayerId);
            $counter = 'comet';            
        }

        if ($card['type'] === 'moon'){
            $this->game->moon_count->inc($activePlayerId, 1);
            $newValue = $this->game->moon_count->get($activePlayerId);
            $counter = 'moon';  
        }
        /*debuigging info */
        error_log("planet_order BEFORE move: " . json_encode($planet_order));
        error_log("planet_index = " . $planet_index);
        error_log("parent_id = " . $parent_id . " parent_slot = " . $parent_slot);
        error_log("card being played: " . json_encode($card));


        // Notify all players
        $this->notify->all(
            'cardPlayed',
            '${player_name} plays ${cardName}.',
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'cardName' => $this->game->getCardName($card),
                'card' => $card,
                'newValue' => $newValue,
                'counter'   => $counter,
                'newRingCount'   => $newRingCount,
                'planet_order' => $planet_order
            ]
        );

        return PlayerTurn::class;
    }
    /*******************
     *   DRAFT A CARD  *           
     *******************/
    #[PossibleAction]
    public function actDraftCard(int $card_id, int $row, int $slot, int $activePlayerId)
    {   $deckTop = $this->game->cards->getCardOnTop(Game::LOCATION_DECK);
        $this->game->cards->moveCard($deckTop['id'], 'hand', $activePlayerId);
        $card = $this->game->cards->getCard($card_id);
        // Remember where the card was (row & position)
        $row = $card['location'];         // 'solar1' or 'solar2'
        $slot = $card['location_arg'];    // 0,1,2

        // Move card from row to hand
        $this->game->cards->moveCard($card_id, 'hand', $activePlayerId);
        // Replace card from top of deck to the proper solar row & slot #
         $this->game->cards->moveCard($deckTop['id'], $row, $slot);

        $this->notify->all("draft", clienttranslate('${player_name} DRAFTS ${cardName}'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card' => $this->game->enrichCard($card),
            "cardName" => $this->game->getCardName($card),
            'deckTop' => $deckTop,
            'newDeckTop' => $this->game->cards->getCardOnTop(Game::LOCATION_DECK),
            'cardsRemaining' => $this->game->cards->countCardsInLocation(Game::LOCATION_DECK),
            'row' => $row,
            'slot' => $slot
        ]);

        // Advance state → Let player play or pass
        return PlayerTurn::class;
    }


    /*******************
     *   DRAW A CARD   *           
     *******************/
    #[PossibleAction]
    public function actDrawCard(int $activePlayerId)
    {   $deckTop = $this->game->cards->getCardOnTop(Game::LOCATION_DECK);
        $this->game->cards->moveCard($deckTop['id'], 'hand', $activePlayerId);

        // Notify each player that current player drew a card
        $this->game->notify->all('deckDraw', clienttranslate('${player_name} drew a card'),
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'deckTop' => $deckTop,
                'newDeckTop' => $this->game->cards->getCardOnTop(Game::LOCATION_DECK),
                'cardsRemaining' => $this->game->cards->countCardsInLocation(Game::LOCATION_DECK),
                'cardsInHand' => $this->game->cards->countCardsInLocation('hand', $activePlayerId)
            ]
        );

        // Notify current player only which card they drew 
        $this->notify->player($activePlayerId, "dealCardPrivate", clienttranslate('You drew ${cardName}'), 
        [
            "card" => $deckTop,
            "type" => $deckTop["type"],
            "cardName" => $this->game->getCardName($deckTop)
        ]);


        return PlayerTurn::class;
    }


    /*******************
     *   PASS TURN     *           
     *******************/
    #[PossibleAction]
    public function actPass(int $activePlayerId)
    {
        // Notify all players about the choice to pass.
        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);
        // at the end of the action, move to the next state
        return NextPlayer::class;
    }

    

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     * 
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId) {
        return $this->actPass($playerId);
    }

}