/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SolarDraftSecondEdition implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * solardraftsecondedition.js
 *
 * SolarDraftSecondEdition user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, gamegui, counter) {
    return declare("bgagame.solardraftsecondedition", ebg.core.gamegui, {
        constructor: function(){
            console.log('solardraftsecondedition constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function(gamedatas) {
            console.log("Starting game setup");

            var gameArea = document.getElementById('game_play_area');

            gameArea.insertAdjacentHTML('beforeend', '<div id="player-tables"></div>');

            //solar deck
            gameArea.insertAdjacentHTML('beforeend', 
             '<div id="solar-area">' +
                '<div id="solar-top-row" class="solar-row full-row">' +
                    '<div id="discard-pile" class="pile discard-pile"></div>' +
                    '<div id="solar-deck" class="pile solar-deck"></div>' +
                    '<div id="solar-row-1" class="solar-row-cards"></div>' +
                '</div>' +
                '<div id="solar-bottom-row" class="solar-row full-row">' +
                //'<div class="spacer"></div>' +
                    '<div id="solar-row-2" class="solar-row-cards"></div>' +
                '</div>' +
            '</div>'
            );

            /*add solar rows
            gameArea.insertAdjacentHTML('beforeend',
                '<div id="solar-rows">' +
                    '<div id="solar-row-1" class="solar-row"></div>' +
                    '<div id="solar-row-2" class="solar-row"></div>' +
                '</div>'
            );*/

            // Display 1 card in discard
            if (gamedatas.discardPile) {
                Object.values(gamedatas.discardPile).forEach(card =>
                    this.addCardToDiscard(card)
                );
            }
            // Display Solar Row 1
            if (gamedatas.solarRow1) {
                Object.values(gamedatas.solarRow1).forEach(card =>
                    this.addCardToRow(card, 1)
                );
            }

            // Display Solar Row 2
            if (gamedatas.solarRow2) {
                Object.values(gamedatas.solarRow2).forEach(card =>
                    this.addCardToRow(card, 2)
                );
            }

            // Player boards (keep it simple for now)
            for (var playerId in gamedatas.players) {
                if (!gamedatas.players.hasOwnProperty(playerId)) continue;
                var player = gamedatas.players[playerId];

                // Classic player panel div
                var playerPanel = document.getElementById('player_board_' + playerId);
                if (playerPanel) {
                    playerPanel.insertAdjacentHTML('beforeend',
                        '<div class="energy-wrapper">' +
                            '<span id="energy-player-counter-' + playerId + '"></span> Energy' +
                        '</div>'
                    );
                }

                // Just create the counter, set a fixed value 2 for now
                var c = new ebg.counter();
                c.create('energy-player-counter-' + playerId);
                c.setValue(2);
            }

            this.setupNotifications();

            console.log("Ending game setup");
        },

       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
                        
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                case 'PlayerTurn':
                    // TEMP: don't add buttons yet, just log to avoid crashes
                    if (args && args.playableCardsIds) {
                        console.log('Playable cards:', args.playableCardsIds);
                    }
                    break;
                    /*
                    this.addActionButton(
                        'play_card_' + cardId,
                        _('Play card with id ${card_id}').replace('${card_id}', cardId),
                        'onCardClick'
                    );*/
                }
            }
        },
        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        addCardToDiscard: function(card) {
            var container = document.getElementById('discard-pile');

            if (!container) {
                console.error("Discard pile container missing");
                return;
            }

            var cardDiv = dojo.create("div", {
                id: 'discard_' + card.id,
                'class': 'card card_' + card.type_arg
            }, container);

            dojo.connect(cardDiv, "onclick", dojo.hitch(this, function () {
                console.log("Clicked discard pile card", card.id);
            }));
        },

       
        addCardToRow: function(card, rowNumber) {
            if (!card) {
                console.error("Missing card in addCardToRow for row", rowNumber);
                return;
            }

            var rowId = 'solar-row-' + rowNumber;
            var container = document.getElementById(rowId);

            if (!container) {
                console.error("Solar row container missing:", rowId);
                return;
            }

            var cardDiv = dojo.create("div", {
                id: 'card_' + card.id,
                'class': 'card card_' + card.type_arg
            }, container);

            dojo.connect(cardDiv, "onclick", dojo.hitch(this, function () {
                this.onCardClick(card.id);
            }));
        },


        setCardImage: function(div, card) {
            div.style.backgroundImage = "url('img/cards.png')";

            var cardWidth = 250;
            // type_arg is 1-based in your PHP, so subtract 1 for 0-based index:
            var index = (card.type_arg || 1) - 1;
            var x = -(index * cardWidth);

            div.style.backgroundPosition = x + "px 0px";
            div.style.width = cardWidth + "px";
            div.style.height = "350px"; // adjust to your sprite height
        },




        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        // Example:
        
        onCardClick: function( card_id )
        {
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", { 
                card_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
        },    

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your solardraftsecondedition.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // automatically listen to the notifications, based on the `notif_xxx` function on this class.
            this.bgaSetupPromiseNotifications();
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: async function( args )
        {
            console.log( 'notif_cardPlayed' );
            console.log( args );
            
            // Note: args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
