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

        return [
            "playableCardsIds" => [1, 2],
        ];
    }    

    /*******************
     *   PLAY A CARD   *           
     *******************/
        #[PossibleAction]
        public function actPlayCard(int $card_id)
        {
            $activePlayerId = (int) $this->game->getCurrentPlayerId();

            // Take card from player's hand
            $card = $this->game->cards->getCard($card_id);

            // Move card to the player's tableau
            $this->game->cards->moveCard($card_id, 'tableau', $activePlayerId);

            // Enrich before sending
            $card = $this->game->enrichCard($card);

            // Notify all players - IMPORTANT: include the card object!
            $this->notify->all(
                'cardPlayed',
                '${player_name} plays ${cardName}.',
                [
                    'player_id' => $activePlayerId,
                    'player_name' => $this->game->getPlayerNameById($activePlayerId),
                    'cardName' => $this->game->getCardName($card),
                    'card' => $card  // ADD THIS LINE
                ]
            );
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
                'cardsRemaining' => $this->game->cards->countCardsInLocation(Game::LOCATION_DECK)
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