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
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  getLibUrl("bga-animations", "1.x"), // the lib uses bga-animations so this is required!
  getLibUrl("bga-cards", "1.x"), // bga-cards itself
], function (dojo, declare, gamegui, counter, BgaAnimations, BgaCards) {
  return declare("bgagame.solardraftsecondedition", ebg.core.gamegui, {
    constructor: function () {
      console.log("solardraftsecondedition constructor");

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

    setup: function (gamedatas) {
      console.log("Starting game setup");

      var gameArea = document.getElementById("game_play_area");
      const cardWidth = 150;
      const cardHeight = 236;

      // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
      this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bgaAnimationsActive(),
      });

      // create the card manager
      this.cardsManager = new BgaCards.Manager({
        animationManager: this.animationManager,
        type: "card", // the "type" of our cards in css
        getId: (card) => card.id,
        cardWidth: cardWidth,
        cardHeight: cardHeight,
        setupFrontDiv: (card, div) => {
          div.dataset.type = card.type; 
          div.dataset.typeArg = card.type_arg; 
          div.style.backgroundPositionX = `calc(100% / 9 * (${card.type_arg} - 2))`; // number of columns in stock image minus 1
          div.style.backgroundPositionY = `calc(100% / 10 * (${card.type} - 1))`; // number of rows in stock image minus 1
          this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
        },
      });

    /*******************************
    *           GAME AREA          *
    *******************************/
      gameArea.insertAdjacentHTML(
        "beforeend",
        '<div id="solar-area">' +
          '<div id="solar-top-row" class="solar-row full-row">' +
          '<div id="player-hand_wrap" class="whiteblock">' +
          '<b id="myhand_label">My Hand</b>' +
          '<div id="player-hand">' +
          "</div>" +
          "</div>" +
          '<div id="discard-pile" class="discard-pile"></div>' +
          '<div id="solar-deck" class="solar-deck"></div>' +
          '<div id="solar-row-1" class="solar-row-cards"></div>' +
          "</div>" +
          '<div id="solar-bottom-row" class="solar-row full-row">' +
          '<div id="mysolarsystem_wrap" class="whiteblock">' +
          '<b id="mysolarsystem">my solar system</b>' +
          "</div>" +
          '<div id="solar-row-2" class="solar-row-cards"></div>' +
          "</div>" +
          "</div>"
      );

    /*******************************
    *          PLAYER HAND         *
    *******************************/
     //TO DO - clikcing on card in hand will prompt PLAY action
      this.handStock = new BgaCards.HandStock(
        this.cardsManager,
        document.getElementById("player-hand"),
         {
        fanShaped: false,     // <-- turn off fanning
        cardOverlap: 0,      // <-- keep cards flat
        center: false,       // <-- optional: left-align
        direction: 'row',    // <-- optional: horizontal
        floatLeftMargin: 25,
        }
      );
      
      this.handStock.addCards(Array.from(Object.values(this.gamedatas.hand)));

      /* PLAY ACTION EXAMPLE
     this.handStock.onCardClick = (card) => {
        this.tableauStocks[card.location_arg].addCards([card]);
     };
        */




    /*******************************
    *         SOLAR DECK           *
    *******************************/
      // Display top card of solar deck's back
      
      if (gamedatas.deckTop) {
        this.addCardBackToDeck(gamedatas.deckTop);
      }

      dojo.connect(
        document.getElementById("solar-deck"),
        "onclick",
        dojo.hitch(this, this.onDeckClick)
      );

    /*******************************
    *          DISCARD PILE        *
    *******************************/
     this.discardDeck = new BgaCards.DiscardDeck(
        this.cardsManager,
        document.getElementById("discard-pile"),
        {
        maxHorizontalShift: 2,
        maxRotation: 2,
        maxVerticalShift: 2
        }
     );
      
     this.discardDeck.addCards(Array.from(Object.values(this.gamedatas.discardPile)));

    /*******************************
    *          SOLAR ROWS          *
    *******************************/
      if (gamedatas.solarRow1) {
        Object.values(gamedatas.solarRow1).forEach((card) =>
          this.addCardToRow(card, 1)
        );
      }

      if (gamedatas.solarRow2) {
        Object.values(gamedatas.solarRow2).forEach((card) =>
          this.addCardToRow(card, 2)
        );
      }

    /*******************************
    *   SOLAR SYSTEMS / TABLEAUS   *
    *******************************/
      gameArea.insertAdjacentHTML(
        "beforeend",
        '<div id="player-tables"></div>'
      );

      Object.values(gamedatas.players).forEach((player, index) => {
        document.getElementById("player-tables").insertAdjacentHTML(
          "beforeend",
          `
                    <div class="playertable whiteblock playertable_${index}">
                        <div class="playertablename" style="color:#${player.color};">Solar System - ${player.name}</div>
                        <div id="tableau_${player.id}"></div>
                    </div>
                    `
        );
      });

    /*******************************
    *         PLAYER PANELS        *
    *******************************/
      // Player boards (keep it simple for now)
      for (var playerId in gamedatas.players) {
        if (!gamedatas.players.hasOwnProperty(playerId)) continue;
        var player = gamedatas.players[playerId];

        // Classic player panel div
        var playerPanel = document.getElementById("player_board_" + playerId);
        if (playerPanel) {
          playerPanel.insertAdjacentHTML(
            "beforeend",
            '<div class="energy-wrapper">' +
              '<span id="energy-player-counter-' +
              playerId +
              '"></span> Energy' +
              "</div>"
          );
        }

        // Just create the counter, set a fixed value 2 for now
        var c = new ebg.counter();
        c.create("energy-player-counter-" + playerId);
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
    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName, args);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

        case "dummy":
          break;
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */

        case "dummy":
          break;
      }
    },

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "PlayerTurn":
            // TEMP: don't add buttons yet, just log to avoid crashes
            if (args && args.playableCardsIds) {
              console.log("Playable cards:", args.playableCardsIds);
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
    addCardToDiscard: function (card) {
      var container = document.getElementById("discard-pile");

      if (!container) {
        console.error("Discard pile container missing");
        return;
      }

      var cardDiv = dojo.create(
        "div",
        {
          id: "discard_" + card.id,
          class: "card card_" + card.type_arg,
        },
        container
      );

      dojo.connect(
        cardDiv,
        "onclick",
        dojo.hitch(this, function () {
          console.log("Clicked discard pile card", card.id);
        })
      );
    },

    addCardToRow: function (card, rowNumber) {
      if (!card) {
        console.error("Missing card in addCardToRow for row", rowNumber);
        return;
      }

      var rowId = "solar-row-" + rowNumber;
      var container = document.getElementById(rowId);

      if (!container) {
        console.error("Solar row container missing:", rowId);
        return;
      }

      var cardDiv = dojo.create(
        "div",
        {
          id: "card_" + card.id,
          class: "card card_" + card.type_arg,
        },
        container
      );

      dojo.connect(
        cardDiv,
        "onclick",
        dojo.hitch(this, function () {
          this.onCardClick(card.id);
        })
      );
    },

    setCardImage: function (div, card) {
      div.style.backgroundImage = "url('img/cards.png')";

      var cardWidth = 250;
      // type_arg is 1-based in your PHP, so subtract 1 for 0-based index:
      var index = (card.type_arg || 1) - 1;
      var x = -(index * cardWidth);

      div.style.backgroundPosition = x + "px 0px";
      div.style.width = cardWidth + "px";
      div.style.height = "350px"; // adjust to your sprite height
    },

    addCardToHand: function (card) {
      var hand = document.getElementById("player-hand");

      var wrapper = dojo.create(
        "div",
        {
          class: "card-wrapper",
        },
        hand
      );

      dojo.create(
        "div",
        {
          id: "card_" + card.id,
          class: "card card_" + card.type_arg,
        },
        wrapper
      );

      dojo.connect(
        wrapper,
        "onclick",
        dojo.hitch(this, function () {
          this.onCardClick(card.id);
        })
      );
    },

    addCardBackToDeck(card) {
      if (!card) {
        return;
      }

      const deck = document.getElementById("solar-deck");
      if (!deck) return;

      dojo.create(
        "div",
        {
          id: "deck_top_card",
          class: `card card-back-${card.type}`,
        },
        deck
      );
    },

    updateDeckTop(deckTop) {
      const current = document.getElementById("deck_top_card");
      if (current) {
        current.remove();
      }

      if (deckTop) {
        this.addCardBackToDeck(deckTop);
      }
    },

    ///////////////////////////////////////////////////
    //// Player's action
    onDeckClick: function () {
      this.bgaPerformAction("actDrawCard");
    },

    // Example:
    /*
        onCardClick: function (card_id) {
        console.log("onCardClick", card_id);

        this.bgaPerformAction("actPlayCard", {
            card_id,
        }).then(() => {
            // What to do after the server call if it succeeded
            // (most of the time, nothing, as the game will react to notifs / change of state instead)
        });
        },
    */
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your solardraftsecondedition.game.php file.
        
        */
    setupNotifications: function () {
      console.log("notifications subscriptions setup");

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
