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
      const activePlayerId = this.getActivePlayerId();
      var gameArea = document.getElementById("game_play_area");
      const cardWidth = 150;
      const cardHeight = 236;
      var cardsRemaining = gamedatas.cardsRemaining;
      // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
      this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bgaAnimationsActive(),
      });

      this.cardsManager = new BgaCards.Manager({
        animationManager: this.animationManager,
        type: "card", // the "type" of our cards in css
        getId: (card) => card.id,

        // IMPORTANT: keep these, the manager relies on them
        cardWidth: cardWidth,
        cardHeight: cardHeight,

        setupFrontDiv: (card, div) => {
          const cardWidth = 150;
          const cardHeight = 236;

          const index = Number(card.type_arg) - 1;
          const col = index % 10;
          const row = Math.floor(index / 10);

          // IMPORTANT: Correct BGA asset path
          div.style.backgroundImage = `url('${g_gamethemeurl}img/cards.png')`;
          div.style.backgroundSize = `${10 * cardWidth}px ${11 * cardHeight}px`;

          div.style.backgroundPosition = `-${col * cardWidth}px -${
            row * cardHeight
          }px`;

          div.style.width = cardWidth + "px";
          div.style.height = cardHeight + "px";
        },
      });

      /*******************************
       *           GAME AREA          *
       *******************************/
      gameArea.insertAdjacentHTML(
        "beforeend",
        `
        <div id="solar-area">

            <div id="solar-grid">

                <!-- Row 1, Col 1 -->
                <div id="mysolarsystem_wrap">
                    <div id="mysolarsystem"></div>
                </div>

                <!-- Row 1, Col 2 -->
                <div id="solar-deck_wrap" class="whiteblock">
                    <b class="section-label">Solar Deck (<span id="deck-count">${cardsRemaining}</span>)</b>
                    <div id="solar-deck"></div>
                </div>

                <!-- Col 3 (spans both rows!!) -->
                <div id="solar-rows_column" class="whiteblock">
                    <div class="solar-row-block">
                        <b class="section-label">Solar Rows</b>
                        <div id="solar-row-1" class="solar-row-cards">
                            <div class="slot" id="solar1_slot0"></div>
                            <div class="slot" id="solar1_slot1"></div>
                            <div class="slot" id="solar1_slot2"></div>
                        </div>
                    </div>

                    <div class="solar-row-block">
                        <div id="solar-row-2" class="solar-row-cards">
                            <div class="slot" id="solar2_slot0"></div>
                            <div class="slot" id="solar2_slot1"></div>
                            <div class="slot" id="solar2_slot2"></div>                    
                        </div>
                    </div>
                </div>

                <!-- Row 2, Col 1 -->
                <div id="player-hand_wrap" class="whiteblock">
                    <b class="section-label">My Hand</b>
                    <div id="player-hand"></div>
                </div>

                <!-- Row 2, Col 2 --> 
                <div id="discard-pile_wrap" class="whiteblock">
                    <b class="section-label-discard">Discard Pile</b>  
                    <div id="discard-pile"></div>
                <div>

            </div>

        </div>


        `
      );

      /*******************************
       *          PLAYER HAND         *
       *******************************/
      //TO DO - clikcing on card in hand will prompt PLAY action
      this.handStock = new BgaCards.HandStock(
        this.cardsManager,
        document.getElementById("player-hand"),
        {
          fanShaped: false, // <-- turn off fanning
          cardOverlap: 2, // <-- keep cards flat
          center: false, // <-- optional: left-align
          direction: "row", // <-- optional: horizontal
        }
      );
      //can only play one card from hand - might change this select to only matter for moons since that's the only time you have a choice where a card goes
      this.handStock.setSelectionMode("single", {
        unselectOnClick: true,
        selectableCardClass: "card-selectable",
      });

      this.handStock.onCardClick = (card) => {
        this.tableauStocks[card.location_arg].addCard([card]);
        this.bgaPerformAction("actPlayCard", { card_id: card.id });
      };

      this.handStock.addCards(Array.from(Object.values(this.gamedatas.hand)));

      /*******************************
       *         SOLAR DECK           *
       *******************************/
      if (gamedatas.deckTop) {
        this.addCardBackToDeck(gamedatas.deckTop);
      }

      document
        .getElementById("solar-deck")
        .addEventListener("click", this.onDeckClick.bind(this));

      /*******************************
       *          DISCARD PILE        *
       *******************************/
      this.discardDeck = new BgaCards.DiscardDeck(
        this.cardsManager,
        document.getElementById("discard-pile"),
        {
          maxHorizontalShift: 2,
          maxRotation: 2,
          maxVerticalShift: 2,
          selectableCardStyle: {
            class: "CardStock",
            utlineColor: "orange",
            outlineSize: 5,
          },
          selectedCardStyle: {
            class: "CardStock",
            utlineColor: "orange",
            outlineSize: 5,
          },
        }
      );

      this.discardDeck.addCards(
        Array.from(Object.values(this.gamedatas.discardPile))
      );

      this.discardDeck = new BgaCards.DiscardDeck(
        this.cardsManager,
        document.getElementById("discard-pile")
      );

        /*******************************
         *          SOLAR ROWS          *
         *******************************/
/*******************************
 *          SOLAR ROWS          *
 *******************************/
this.solarRow1 = new BgaCards.LineStock(
  this.cardsManager,
  document.getElementById("solar-row-1"),
  {
    slots: [
      document.getElementById("solar1_slot0"),
      document.getElementById("solar1_slot1"),
      document.getElementById("solar1_slot2"),
    ],
  }
);

// Enable selection mode
this.solarRow1.setSelectionMode("single");

this.solarRow2 = new BgaCards.LineStock(
  this.cardsManager,
  document.getElementById("solar-row-2"),
  {
    slots: [
      document.getElementById("solar2_slot0"),
      document.getElementById("solar2_slot1"),
      document.getElementById("solar2_slot2"),
    ],
  }
);

// Enable selection mode
this.solarRow2.setSelectionMode("single");

// Fill Solar Row 1 - FIRST
Object.values(this.gamedatas.solarRow1).forEach((card) => {
  if (card) {
    const slot = parseInt(card.location_arg);
    this.solarRow1.addCard(card, { index: slot });
  }
});

// Fill Solar Row 2 - FIRST
Object.values(this.gamedatas.solarRow2).forEach((card) => {
  if (card) {
    const slot = parseInt(card.location_arg);
    this.solarRow2.addCard(card, { index: slot });
  }
});

// NOW set click handlers AFTER cards are added
this.solarRow1.onCardClick = (card) => {
  console.log("=== SOLAR ROW 1 CARD CLICKED ===");
  console.log("Card:", card);
  
  if (!this.isCurrentPlayerActive()) {
    console.log("Not your turn");
    return;
  }
  
  const slot = parseInt(card.location_arg);
  console.log("Drafting from row 1, slot", slot);
  
  this.bgaPerformAction("actDraftCard", { 
    card_id: parseInt(card.id), 
    row: 1,
    slot: slot
  });
};

this.solarRow2.onCardClick = (card) => {
  console.log("=== SOLAR ROW 2 CARD CLICKED ===");
  console.log("Card:", card);
  
  if (!this.isCurrentPlayerActive()) {
    console.log("Not your turn");
    return;
  }
  
  const slot = parseInt(card.location_arg);
  console.log("Drafting from row 2, slot", slot);
  
  this.bgaPerformAction("actDraftCard", { 
    card_id: parseInt(card.id), 
    row: 2,
    slot: slot
  });
};

console.log("Setup complete - cards in row 1:", this.solarRow1.getCards().length);
console.log("Setup complete - cards in row 2:", this.solarRow2.getCards().length);
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
                    <div class="playertable whiteblock playertable_${player.id}">
                        <div class="playertablename" style="color:#${player.color};">
                            <b>Solar System - ${player.name}</b>
                        </div>
                        <div id="tableau_${player.id}"></div>
                    </div>
                    `
        );
      });

      //move active player's tableau to MySolarSystem
      const myWrapper = document.querySelector(
        `.playertable_${activePlayerId}`
      );
      document.getElementById("mysolarsystem_wrap").appendChild(myWrapper);

      //create LineStocks for each tableau
      this.tableauStocks = {};

      Object.values(gamedatas.players).forEach((player) => {
        this.tableauStocks[player.id] = new BgaCards.LineStock(
          this.cardsManager,
          document.getElementById(`tableau_${player.id}`)
        );

        // Load tableau cards from server
        if (gamedatas.tableau[player.id]) {
          this.tableauStocks[player.id].addCards(gamedatas.tableau[player.id]);
        }
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
          style: "z-index:0;",
        },
        deck
      );
    },

    ///////////////////////////////////////////////////
    //// Player's action
    onDeckClick: function () {
      this.bgaPerformAction("actDrawCard");
    },

    onSolarRowClick: function (evt, row, slow) {
      console.log("draft action clicked");
      /*if (!this.isCurrentPlayerActive()) return;
      const cardSpot = `solar${row}_slot${slot}`;
     
            if (!cardSpot) {
        console.error("Slot not found:", slotId);
        return;
    }

    const card = document.getElementById(cardSpot);

      console.log("Client: clicked card", card);
      //this.bgaPerformAction("actDraftCard", card_id,); */
    },

    onHandCardClick(card) {
      if (!this.isCurrentPlayerActive()) return;
      console.log("Client: clicked card", card);
      this.bgaPerformAction("actPlayCard", { card_id: Number(card.id) });
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

    /**
     * Handle card played notification
     */
    notif_cardPlayed: async function (notif) {
      console.log("notif_cardPlayed", notif);

      const card = notif.args.card || notif.args;
      const playerId = notif.args.player_id;

      // Remove from hand if it's the current player's card
      if (playerId == this.player_id) {
        await this.handStock.removeCard(card);
      }

      // Add to the player's tableau
      if (this.tableauStocks[playerId]) {
        await this.tableauStocks[playerId].addCard(card);
      }
    },

    /**
     * Handle deck draw notification (public - just shows someone drew)
     */
    notif_deckDraw: async function (notif) {
      console.log("notif_deckDraw", notif);

      // --- UPDATE THE COUNT ---
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      /*
       * If deck still has cards, show new top card-back
       * does this first to put card under current top card
       * so current one when moved will reveal new top card
       */

      if (notif.newDeckTop) {
        this.addCardBackToDeck(notif.newDeckTop);
      } //add else to show empty deck

      // Remove old deck-top visual
      const deckTopElem = document.getElementById("deck_top_card");
      if (deckTopElem) {
        deckTopElem.remove();
      }

      // Add drawn card to hand
      await this.handStock.addCard(notif.deckTop);
    },

    /**
     * Handle draft notification
     */
    notif_draft: async function (notif) {
      console.log("notif_draft", notif);
      const row = notif.row; // 'solar1' or 'solar2'
      const slot = notif.slot; // 0,1,2
      const card = notif.args.card;
      const playerId = notif.player_id;

      // --- UPDATE THE COUNT ---
      if (notif.cardsRemaining !== undefined) {
        document.getElementById("deck-count").innerText = notif.cardsRemaining;
      }

      //put new card on top to display to user
      if (notif.newDeckTop) {
        this.addCardBackToDeck(notif.newDeckTop);
      } //add else to show empty deck

      // Remove ONLY from the row the card came from
      if (row === "solar1") {
        await this.solarRow1.removeCard(card);
      } else {
        await this.solarRow2.removeCard(card);
      }

      // Add new card from deck to solar row (if any)
      if (notif.args.deckTop) {
        if (row === "solar1") {
          await this.solarRow1.addCard(notif.deckTop, { index: slot });
        } else {
          await this.solarRow2.addCard(notif.adeckTop, { index: slot });
        }
      }

      // If it's the current player, add to hand
      if (playerId == this.player_id) {
        await this.handStock.addCard(card);
      }
    },

    /**
     * Handle pass notification
     */
    notif_pass: function (notif) {
      console.log("notif_pass", notif);
      // Nothing to do visually for a pass
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
