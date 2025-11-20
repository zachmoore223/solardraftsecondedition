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
    public function actPlayCard(int $card_id, int $activePlayerId, array $args)
    {
        // check input values
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new UserException('Invalid card choice');
        }
        $card = $this->game->cards->getCard($card_id);

        // Move to tableau
        $this->game->cards->moveCard($card_id, 'tableau', $activePlayerId);

        $this->notify->all("playCard", clienttranslate('${player_name} plays a card'), [
            'player_id'   => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card'        => $this->game->enrichCard($card),
        ]);

        // TODO trigger scoring + adjacency updates later

        return PlayerTurn::class;
    }

    /*******************
     *   DRAFT A CARD  *           
     *******************/
    #[PossibleAction]
    public function actDraftCard(int $card_id, int $activePlayerId, array $args)
    {
        // Move card from row → hand
        $card = $this->game->cards->getCard($card_id);

        $this->game->cards->moveCard($card_id, 'hand', $activePlayerId);

        $this->notify->all("draft", clienttranslate('${player_name} drafts a card'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card' => $this->game->enrichCard($card),
        ]);

        // Advance state → Let player play or pass
        return PlayerTurn::class;
    }


    /*******************
     *   DRAW A CARD   *           
     *******************/
    #[PossibleAction]
    public function actDrawCard(int $activePlayerId)
    {
        // Deck draw for the active player
        $card = $this->game->cards->pickCard(Game::LOCATION_DECK, $activePlayerId);

        // Enrich card for client
        $card = $this->game->enrichCard($card);

        $this->notify->player($activePlayerId, 'drawCard', '', array( 
            'card' => $card
         ) ); 

        // Notify players
        $this->game->notify->all(
            'deckDrawn',
            '${player_name} draws a card.',
            [
                'player_id' => $activePlayerId,
                'player_name' => $this->game->getPlayerNameById($activePlayerId)
            ]
        );


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