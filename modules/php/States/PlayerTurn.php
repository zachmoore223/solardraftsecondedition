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

    /**
     * PLAY A CARD (will need broken down by moon, planet, and comet)
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId, array $args)
    {
        // check input values
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new UserException('Invalid card choice');
        }

        // Add your game logic to play a card here.
        $card_name = Game::$cards[$card_id]['card_name'];

        // Notify all players about the card played.
        $this->notify->all("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
            "card_name" => $card_name, // remove this line if you uncomment notification decorator
            "card_id" => $card_id,
            "i18n" => ['card_name'], // remove this line if you uncomment notification decorator
        ]);

        // in this example, the player gains 1 points each time he plays a card
        $this->playerScore->inc($activePlayerId, 1);

        // at the end of the action, move to the next state
        return NextPlayer::class;
    }

    /**
     * DRAFT ACTION
     *
     * In this scenario, each time a player DRAFTS, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     */
    #[PossibleAction]
    public function actDraftCard(int $card_id, int $activePlayerId, array $args)
    {
        // Add your game logic to draw a card here.
        $card_name = Game::$cards[$card_id]['card_name'];

        // Notify all players about the choice to draft - will need to add which card is drafted.
        $this->notify->all("draft", clienttranslate('${player_name} drafts'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);

        // at the end of the action, move to the next state
        return NextPlayer::class;
    }

    /**
     * DRAW ACTION
     *
     * In this scenario, each time a player DRAFTS, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     */
    #[PossibleAction]
    public function actDrawCard(int $card_id, int $activePlayerId, array $args)
    {
        /* check input values
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new UserException('Invalid card choice');
        } */

        // Add your game logic to draw a card here.
        $card_name = Game::$cards[$card_id]['card_name'];

        // Notify all players about the choice to draft - will need to add which card is drafted.
        $this->notify->all("draw", clienttranslate('${player_name} draws'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);

        // at the end of the action, move to the next state
        return NextPlayer::class;
    }


    /**
     * END TURN (PASS)
     *
     * In this scenario, each time a player pass, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     */

    #[PossibleAction]
    public function actPass(int $activePlayerId)
    {
        // Notify all players about the choice to pass.
        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);

        // in this example, the player gains 1 energy each time he passes
        $this->game->playerEnergy->inc($activePlayerId, 1);

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
        // Example of zombie level 0: return NextPlayer::class; or $this->actDraft($playerId);

        // Example of zombie level 1:
        $args = $this->getArgs();
        $zombieChoice = $this->getRandomZombieChoice($args['playableCardsIds']); // random choice over possible moves
        return $this->actPlayCard($zombieChoice, $playerId, $args); // this function will return the transition to the next state
    }
}