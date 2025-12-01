<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SolarDraftSecondEdition implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\SolarDraftSecondEdition;

use Bga\Games\SolarDraftSecondEdition\States\PlayerTurn;
use Bga\GameFramework\Components\Counters\PlayerCounter;

class Game extends \Bga\GameFramework\Table
{

    public PlayerCounter $blue_planet_count;
    public PlayerCounter $green_planet_count;
    public PlayerCounter $red_planet_count;
    public PlayerCounter $tan_planet_count;
    public PlayerCounter $comet_count;
    public PlayerCounter $moon_count;
    public PlayerCounter $ring_count;
    public $planetOrder = [];   
    public $cards;
    const LOCATION_DECK = 'deck';
    const LOCATION_DISCARD = 'discardPile';
    const LOCATION_SOLARROW1 = 'solar1';
    const LOCATION_SOLARROW2 = 'solar2';
    const CARD_PLANET = 'planet';
    const CARD_MOON   = 'moon';
    const CARD_COMET  = 'comet';

    // ===== CARD INFO LOOKUP TABLE =====
    public static $CARD_INFO = [
        //declare the 60 planets in base deck
        'planet' => [
            1 => ['name' => 'Tezcatlipoca',      'color' => 'BLUE',  'points' => 0,  'ability' => 'Score 3 points for each ADJACENT COMET.',                                                        'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            2 => ['name' => 'Finn McCool',       'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 3 points for each ADJACENT LARGE PLANET.',                                                 'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            3 => ['name' => 'Masazul',           'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 3 points if you have the single MOST BLUE PLANETS.',                                       'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            4 => ['name' => 'Lunapalooza',       'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 5 points if you have the single MOST MOONS.',                                              'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            5 => ['name' => 'Krishna',           'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 1 point for each BLUE PLANET .',                                                           'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            6 => ['name' => 'Hedgehog',          'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 1 point for each PLANET WITH RING(S).',                                                    'rings' => 3,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            7 => ['name' => 'Halley',            'color' => 'BLUE',  'points' => 3,  'ability' => 'Score 1 point for each COMET.',                                                                  'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            8 => ['name' => 'Cometviewer',       'color' => 'BLUE',  'points' => 3,  'ability' => 'When played, you may immediately PLAY A COMET.',                                                 'rings' => 3,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            9 => ['name' => 'Diluna',            'color' => 'BLUE',  'points' => 1,  'ability' => 'When played, you may immediately PLAY up TWO MOONS onto this planet.',                           'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            10 => ['name' => 'Lone Wolf',        'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 8 points if this is your ONLY MEDIUM PLANET.',                                             'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            11 => ['name' => 'Luke',             'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 8 points if this planet has exactly 2 MOONS orbiting it.',                                 'rings' => 2,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            12 => ['name' => 'Diazure',          'color' => 'BLUE',  'points' => 4,  'ability' => 'This counts as two MEDIUM BLUE PLANETS.',                                                        'rings' => 0,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            13 => ['name' => 'Octo',             'color' => 'BLUE',  'points' => 1,  'ability' => 'Score 5 points if you have at least 8 PLANETS.',                                                 'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            14 => ['name' => 'Repeat double',    'color' => 'BLUE',  'points' => 2,  'ability' => 'When you play a comet adjacent to this planet, do its effect twice.',                            'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            15 => ['name' => 'Jötnar',           'color' => 'BLUE',  'points' => 2,  'ability' => 'Score 1 point for each LARGE PLANET.',                                                           'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            16 => ['name' => 'cometon',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if you have the single MOST COMETS.',                                             'rings' => 3,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            17 => ['name' => 'Twocolored',       'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if your Solar System only has three different colored planets.',                  'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            18 => ['name' => 'Degrassius',       'color' => 'GREEN', 'points' => 2,  'ability' => 'Score 3 points if you have the single MOST GREEN PLANETS.',                                      'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            19 => ['name' => 'Sagan',            'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 5 points if you have the single MOST PLANETS.',                                            'rings' => 2,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            20 => ['name' => 'Hathor',           'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 3 points if this is your SEVENTH PLANET.',                                                 'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            21 => ['name' => 'Echo',             'color' => 'GREEN', 'points' => 0,  'ability' => 'Copies the POINTS VALUE and ABILITY of the previously played planet.',                           'rings' => 1,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            22 => ['name' => 'Gaia',             'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 1 point for each GREEN PLANET.',                                                           'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            23 => ['name' => 'Gizmo',            'color' => 'GREEN', 'points' => 1,  'ability' => 'Score 1 point for each SMALL PLANET.',                                                           'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            24 => ['name' => 'Goldilocks',       'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 1 point for each MEDIUM PLANET.',                                                          'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            25 => ['name' => 'Pluetto',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 1 point for each PLANET BEFORE this one.',                                                 'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            26 => ['name' => 'Artemiz',          'color' => 'GREEN', 'points' => 3,  'ability' => 'Score 2 points for each MOON orbiting this planet. This planet may have two additional MOONS.',  'rings' => 0,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            27 => ['name' => 'Diverde',          'color' => 'GREEN', 'points' => 4,  'ability' => 'This counts as two MEDIUM GREEN PLANETS.',                                                       'rings' => 0,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            28 => ['name' => 'Sasquatch',        'color' => 'GREEN', 'points' => 6,  'ability' => 'NO MOONS may orbit this planet.',                                                                'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            29 => ['name' => 'Gogmagog',         'color' => 'GREEN', 'points' => 8,  'ability' => 'To play this card, you must DISCARD A CARD from your hand.',                                     'rings' => 1,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            30 => ['name' => 'Rahu',             'color' => 'GREEN', 'points' => 1,  'ability' => 'TRIPLE the POINTS VALUE of each adjacent COMET.',                                                'rings' => 2,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            31 => ['name' => 'Masrojo',          'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points if you have the single MOST RED PLANETS.',                                        'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            32 => ['name' => 'Planetta',         'color' => 'RED',   'points' => 2,  'ability' => 'Score 2 point for every 2 PLANETS.',                                                             'rings' => 1,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            33 => ['name' => 'Bigtin',           'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points for each  ADJACENT SMALL PLANET.',                                                'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            34 => ['name' => 'Trescom',          'color' => 'RED',   'points' => 3,  'ability' => 'Score 3 points for every 3 COMETS.',                                                             'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            35 => ['name' => 'Ocho',             'color' => 'RED',   'points' => 1,  'ability' => 'Score 4 points if this is your EIGHTH PLANET.',                                                  'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            36 => ['name' => 'Amaterasu',        'color' => 'RED',   'points' => 4,  'ability' => 'Score 5 points if you have at most THREE PLANETS of EACH COLOR.',                                'rings' => 2,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            37 => ['name' => 'Scarlett',         'color' => 'RED',   'points' => 1,  'ability' => 'Score 1 point for each RED PLANET.',                                                             'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            38 => ['name' => 'Trifecten',        'color' => 'RED',   'points' => 0,  'ability' => 'Copy the points and ability of the planet played after this one.',                               'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            39 => ['name' => 'Midmed',           'color' => 'RED',   'points' => 2,  'ability' => 'Score 3 points for each ADJACENT MEDIUM PLANET.',                                                'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            40 => ['name' => 'Quatro',           'color' => 'RED',   'points' => 1,  'ability' => 'Score 3 points for every other 1 POINT VALUE PLANET.',                                           'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            41 => ['name' => 'Dirojo',           'color' => 'RED',   'points' => 4,  'ability' => 'This counts as two MEDIUM RED PLANETS.',                                                         'rings' => 0,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            42 => ['name' => 'Hectate',          'color' => 'RED',   'points' => 1,  'ability' => 'DOUBLE the POINTS BONUS of one MOON orbiting this planet.',                                      'rings' => 2,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            43 => ['name' => 'Lunamania',        'color' => 'RED',   'points' => 1,  'ability' => 'DOUBLE the POINTS scored from ABILITIES of one MOON orbiting this planet.',                      'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            44 => ['name' => 'Lycanthropia',     'color' => 'RED',   'points' => 3,  'ability' => 'Each time you play a MOON onto this planet, DRAFT A CARD.',                                      'rings' => 2,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            45 => ['name' => 'Giganta',          'color' => 'RED',   'points' => 10, 'ability' => 'The planet played after this one scores NO POINTS.',                                             'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            46 => ['name' => 'Arthuria',         'color' => 'TAN',   'points' => 2,  'ability' => 'Score 5 points if you have the single MOST total RINGS.',                                        'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            47 => ['name' => 'Solbrown',         'color' => 'TAN',   'points' => 2,  'ability' => 'Score 3 points if you have the single MOST TAN PLANETS.',                                        'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            48 => ['name' => 'Rokugan',          'color' => 'TAN',   'points' => 1,  'ability' => 'Score 4 points if this is your NINTH PLANET.',                                                   'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            49 => ['name' => 'Blopper',          'color' => 'TAN',   'points' => 1,  'ability' => 'Score 5 points if this is your ONLY LARGE PLANET.',                                              'rings' => 0,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            50 => ['name' => 'Carnival',         'color' => 'TAN',   'points' => 3,  'ability' => 'Score 6 points if this is the ONLY PLANET with AT LEAST 3 RINGS.',                               'rings' => 3,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            51 => ['name' => 'Baldu',            'color' => 'TAN',   'points' => 7,  'ability' => 'COMETS CANNOT be adjacent to this planet.',                                                      'rings' => 2,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            52 => ['name' => 'Lunaria',          'color' => 'TAN',   'points' => 1,  'ability' => 'Each time you play a MOON onto this planet, gain ANOTHER ACTION.',                               'rings' => 1,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            53 => ['name' => 'Geb',              'color' => 'TAN',   'points' => 1,  'ability' => 'Score 1 point for each TAN PLANET.',                                                             'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            54 => ['name' => 'Mani',             'color' => 'TAN',   'points' => 4,  'ability' => 'Score 2 points for each PLANET with AT LEAST 1 MOON orbiting it.',                               'rings' => 1,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            55 => ['name' => 'Lasten',           'color' => 'TAN',   'points' => 3,  'ability' => 'Score 4 points if this is your LAST PLANET.',                                                    'rings' => 0,   'size' => 'SMALL',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            56 => ['name' => 'Threemoona',       'color' => 'TAN',   'points' => 2,  'ability' => 'Score 1 point for each MOON.',                                                                   'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            57 => ['name' => 'Merconius',        'color' => 'TAN',   'points' => 1,  'ability' => 'Score 1 point for each PLANET AFTER this one.',                                                  'rings' => 1,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            58 => ['name' => 'Dimarron',         'color' => 'TAN',   'points' => 4,  'ability' => 'This counts as two MEDIUM TAN PLANETS.',                                                         'rings' => 0,   'size' => 'MEDIUM',     'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            59 => ['name' => 'Ishtar',           'color' => 'TAN',   'points' => 0,  'ability' => 'Each time you play COMET, DRAW A CARD.',                                                         'rings' => 2,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
            60 => ['name' => 'Echo',             'color' => 'TAN',   'points' => -2, 'ability' => 'Score either the POINT VALUE or ABILITY of an adjacent planet again.',                           'rings' => 3,   'size' => 'LARGE',      'moonUnlock'=> false,   'moonUnlockReq'=> null, 'moonUnlockAbility'=> null],
        ],
            //declare the 25 comets in base deck
        'comet' => [
            61 => [ 'name'=>'Comet1',  'points'=>1, 'ability'=>'DRAFT 2 CARDS.' ],
            62 => [ 'name'=>'Comet2',  'points'=>1, 'ability'=>'DRAFT A CARD and then PLAY A CARD.' ],
            63 => [ 'name'=>'Comet3',  'points'=>1, 'ability'=>'DRAFT A CARD for each bonus token you currently have.' ],
            64 => [ 'name'=>'Comet4',  'points'=>0, 'ability'=>'DRAFT A CARD for each MOON orbiting adjacent planets.' ],
            65 => [ 'name'=>'Comet5',  'points'=>0, 'ability'=>'DRAFT A CARD for each RING adjacent planet has.' ],
            66 => [ 'name'=>'Comet6',  'points'=>2, 'ability'=>'PLAY A CARD of cost 1 or less from your discard.' ],
            67 => [ 'name'=>'Comet7',  'points'=>1, 'ability'=>'Return a played MOON to your hand, then PLAY A MOON.' ],
            68 => [ 'name'=>'Comet8',  'points'=>0, 'ability'=>'Choose a planet: it CANNOT HAVE MOONS for the rest of game.' ],
            69 => [ 'name'=>'Comet9',  'points'=>2, 'ability'=>'Discard 1 card: GAIN 2 POINTS.' ],
            70 => [ 'name'=>'Comet10', 'points'=>0, 'ability'=>'Move one of your MOONS to another planet.' ],
            71 => [ 'name'=>'Comet11', 'points'=>2, 'ability'=>'PLAY any SMALL planet from your hand for free.' ],
            72 => [ 'name'=>'Comet12', 'points'=>1, 'ability'=>'Choose ANY planet: its POINT VALUE becomes 2.' ],
            73 => [ 'name'=>'Comet13', 'points'=>1, 'ability'=>'STEAL 1 MOON from another player (if possible).' ],
            74 => [ 'name'=>'Comet14', 'points'=>2, 'ability'=>'DRAW 3 CARDS, keep 1, discard the rest.' ],
            75 => [ 'name'=>'Comet15', 'points'=>0, 'ability'=>'Choose an OPPONENT: they DISCARD 1 CARD.' ],
            76 => [ 'name'=>'Comet16', 'points'=>1, 'ability'=>'If adjacent to a BLUE planet: GAIN 3 POINTS.' ],
            77 => [ 'name'=>'Comet17', 'points'=>1, 'ability'=>'If adjacent to a GREEN planet: GAIN 3 POINTS.' ],
            78 => [ 'name'=>'Comet18', 'points'=>1, 'ability'=>'If adjacent to a RED planet: GAIN 3 POINTS.' ],
            79 => [ 'name'=>'Comet19', 'points'=>2, 'ability'=>'Score DOUBLE POINTS for this COMET.' ],
            80 => [ 'name'=>'Comet20', 'points'=>0, 'ability'=>'SWAP two adjacent planets in your system.' ],
            81 => [ 'name'=>'Comet21', 'points'=>0, 'ability'=>'Move a planet in your system to a new location.' ],
            82 => [ 'name'=>'Comet22', 'points'=>1, 'ability'=>'Reveal top 3 cards of deck: take 1, discard the others.' ],
            83 => [ 'name'=>'Comet23', 'points'=>2, 'ability'=>'PLAY a MEDIUM planet for free.' ],
            84 => [ 'name'=>'Comet24', 'points'=>1, 'ability'=>'Each opponent discards 1 MOON (if they have one).' ],
            85 => [ 'name'=>'Comet25', 'points'=>0, 'ability'=>'Copy the effect of the LAST PLAYED COMET.' ],
            ],
            //declare the 25 moons in base deck
        'moon' => [
            86 => [ 'name'=>'Moon1',  'points'=>1, 'ability'=>'Score +1 extra point if orbiting a BLUE planet.' ],
            87 => [ 'name'=>'Moon2',  'points'=>1, 'ability'=>'When played, DRAW A CARD.' ],
            88 => [ 'name'=>'Moon3',  'points'=>2, 'ability'=>'Score +2 points if orbiting a SMALL planet.' ],
            89 => [ 'name'=>'Moon4',  'points'=>1, 'ability'=>'When played, you may MOVE ANOTHER MOON.' ],
            90 => [ 'name'=>'Moon5',  'points'=>0, 'ability'=>'This MOON counts as TWO MOONS for scoring.' ],
            91 => [ 'name'=>'Moon6',  'points'=>2, 'ability'=>'Score +2 if orbiting a GREEN planet.' ],
            92 => [ 'name'=>'Moon7',  'points'=>1, 'ability'=>'Score +1 for each other MOON orbiting same planet.' ],
            93 => [ 'name'=>'Moon8',  'points'=>2, 'ability'=>'Score +2 if orbiting a LARGE planet.' ],
            94 => [ 'name'=>'Moon9',  'points'=>1, 'ability'=>'Each time a COMET is played, DRAW A CARD.' ],
            95 => [ 'name'=>'Moon10', 'points'=>1, 'ability'=>'You may MOVE this moon after scoring.' ],
            96 => [ 'name'=>'Moon11', 'points'=>2, 'ability'=>'If orbiting a planet with 3+ rings: GAIN +2.' ],
            97 => [ 'name'=>'Moon12', 'points'=>0, 'ability'=>'Cannot orbit RED planets.' ],
            98 => [ 'name'=>'Moon13', 'points'=>1, 'ability'=>'Counts as having 1 ring for adjacency effects.' ],
            99 => [ 'name'=>'Moon14', 'points'=>1, 'ability'=>'When played, gain 1 bonus token.' ],
            100 => [ 'name'=>'Moon15','points'=>2, 'ability'=>'Score +2 if this is your 5th MOON.' ],
            101 => [ 'name'=>'Moon16','points'=>1, 'ability'=>'This MOON makes its planet LARGE for scoring.' ],
            102 => [ 'name'=>'Moon17','points'=>1, 'ability'=>'This MOON makes its planet SMALL for scoring.' ],
            103 => [ 'name'=>'Moon18','points'=>0, 'ability'=>'Opponent of your choice discards 1 card.' ],
            104 => [ 'name'=>'Moon19','points'=>1, 'ability'=>'Score +1 point for each GREEN planet you have.' ],
            105 => [ 'name'=>'Moon20','points'=>1, 'ability'=>'Score DOUBLE if orbiting a MEDIUM planet.' ],
            106 => [ 'name'=>'Moon21','points'=>2, 'ability'=>'When played, SWITCH TWO planets in your system.' ],
            107 => [ 'name'=>'Moon22','points'=>1, 'ability'=>'Count as a COMET for scoring.' ],
            108 => [ 'name'=>'Moon23','points'=>1, 'ability'=>'Score +1 for each COMET adjacent to its planet.' ],
            109 => [ 'name'=>'Moon24','points'=>0, 'ability'=>'This MOON cannot be moved once placed.' ],
            110 => [ 'name'=>'Moon25','points'=>2, 'ability'=>'Copy the ability of the LAST MOON you played.' ],
            ],
    ];

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]); // mandatory, even if the array is empty

        $this->blue_planet_count = $this->counterFactory->createPlayerCounter('blue_planet_count');
        $this->green_planet_count = $this->counterFactory->createPlayerCounter('green_planet_count');
        $this->red_planet_count = $this->counterFactory->createPlayerCounter('red_planet_count');
        $this->tan_planet_count = $this->counterFactory->createPlayerCounter('tan_planet_count');
        $this->comet_count = $this->counterFactory->createPlayerCounter('comet_count');
        $this->moon_count = $this->counterFactory->createPlayerCounter('moon_count');
        $this->ring_count = $this->counterFactory->createPlayerCounter('ring_count');
        $this->cards = $this->deckFactory->createDeck('card');
        $this->cards->init('card');

        /* example of notification decorator.
        // automatically complete notification args when needed
        $this->notify->addDecorator(function(string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }
        
            if (isset($args['card_id']) && !isset($args['card_name']) && str_contains($message, '${card_name}')) {
                $args['card_name'] = self::$CARD_TYPES[$args['card_id']]['card_name'];
                $args['i18n'][] = ['card_name'];
            }
            
            return $args;
        });*/
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
        protected function getAllDatas(): array
        {
            $result = [];

            $current_player_id = (int) $this->getCurrentPlayerId();

            // ----------------------
            // PLAYERS
            // ----------------------
            $result["players"] = $this->getCollectionFromDb(
                "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
            );

            $this->blue_planet_count->fillResult($result);
            $this->green_planet_count->fillResult($result);
            $this->red_planet_count->fillResult($result);
            $this->tan_planet_count->fillResult($result);
            $this->comet_count->fillResult($result);
            $this->moon_count->fillResult($result);
            $this->ring_count->fillResult($result);

            // ----------------------
            // TABLEAU (planet / moon / comet)
            // ----------------------
            $result['tableau'] = [];
            foreach ($result['players'] as $p_id => $player) {

                $cards = $this->getCollectionFromDb(
                    "SELECT
                        card_id AS id,
                        card_type AS type,
                        card_type_arg AS type_arg,
                        card_location AS location,
                        card_location_arg AS location_arg,
                        parent_id,
                        parent_slot,
                        planet_order
                    FROM card
                    WHERE card_location = 'tableau'
                    AND card_location_arg = $p_id
                    ORDER BY card_id ASC"
                );


                // Add parent info + enrich sprite info
                $cards = array_map(function ($c) {
                    return [
                        'id'            => (int)$c['id'],
                        'type'          => $c['type'],
                        'type_arg'      => (int)$c['type_arg'],
                        'location'      => $c['location'],
                        'location_arg'  => (int)$c['location_arg'],
                        'parent_id'     => $c['parent_id'] ? (int)$c['parent_id'] : null,
                        'parent_slot'   => $c['parent_slot'] ? (int)$c['parent_slot'] : null,
                        'planet_order'   => $c['planet_order'] ? (int)$c['planet_order'] : null,
                    ];
                }, $cards);

                $result['tableau'][$p_id] = $this->enrichCards($cards);
            }

            
            // ----------------------
            // HAND COUNTS
            // ----------------------
            $result['cardsInHand'] = [];
            foreach ($result['players'] as $p_id => $player) {
                $result['cardsInHand'][$p_id] =
                    $this->cards->countCardsInLocation('hand', $p_id);
            }

            // ----------------------
            // DECK / DISCARD
            // ----------------------
            $discardPile = $this->cards->getCardsInLocation(self::LOCATION_DISCARD);
            $top = $this->cards->getCardOnTop(self::LOCATION_DECK);

            $result['cardsInDiscard'] = $this->cards->countCardsInLocation(self::LOCATION_DISCARD);
            $result['cardsRemaining'] = $this->cards->countCardsInLocation('deck');
            $result['deckTop'] = $top ? $this->enrichCard($top) : null;
            $result['hand'] = $this->enrichCards(
                $this->cards->getCardsInLocation('hand', $current_player_id)
            );
            $result['discardPile'] = $this->enrichCards($discardPile);

            // ----------------------
            // SOLAR ROWS
            // ----------------------
            $solarRow1 = $this->cards->getCardsInLocation(self::LOCATION_SOLARROW1);
            $solarRow2 = $this->cards->getCardsInLocation(self::LOCATION_SOLARROW2);

            $solarRow1Slots = [null, null, null];
            $solarRow2Slots = [null, null, null];

            foreach ($solarRow1 as $card) {
                $slot = intval($card['location_arg']);
                $solarRow1Slots[$slot] = $this->enrichCard($card);
            }

            foreach ($solarRow2 as $card) {
                $slot = intval($card['location_arg']);
                $solarRow2Slots[$slot] = $this->enrichCard($card);
            }

            $result['solarRow1'] = $solarRow1Slots;
            $result['solarRow2'] = $solarRow2Slots;

            return $result;
        }


    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {   
        //put all counters into the DB
        $this->blue_planet_count->initDb(array_keys($players));
        $this->green_planet_count->initDb(array_keys($players));
        $this->red_planet_count->initDb(array_keys($players));
        $this->tan_planet_count->initDb(array_keys($players));
        $this->comet_count->initDb(array_keys($players));
        $this->moon_count->initDb(array_keys($players));
        $this->ring_count->initDb(array_keys($players));                     
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: Setup the initial game situation here.


        /*******************************
        *           SOLAR DECK         *
        *******************************/
        //TO DO - change deck steup according to player count
        //create inital solarDeck with 110 cards (60 planets, 25 comets, 25 moons)
        $solarCards = [];

        // ---------- PLANETS 1-60 ----------
        for ($i = 1; $i <= 60; $i++) {
            $solarCards[] = [
                'type' => 'planet',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        // ---------- COMETS 61-85 ----------
        for ($i = 61; $i <= 85; $i++) {
            $solarCards[] = [
                'type' => 'comet',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        // ---------- MOONS 86-110 ----------
        for ($i = 86; $i <= 110; $i++) {
            $solarCards[] = [
                'type' => 'moon',
                'type_arg' => $i,
                'nbr' => 1
            ];
        }

        $this->cards->createCards($solarCards, 'deck');
        $this->cards->shuffle('deck');


        /*******************************
        *           SOLAR ROWS         *
        *******************************/
        for ($slot = 0; $slot < 3; $slot++) {
            $this->cards->pickCardForLocation('deck', self::LOCATION_SOLARROW1, $slot);
            $this->cards->pickCardForLocation('deck', self::LOCATION_SOLARROW2, $slot);
        }

        /*******************************
        *          DISCARD PILE        *
        *******************************/
        // ****will need to chagnes this to be a stack of cards, starting with three cards and one of each type****
        //***** will need to go before deck creation and shuffling once ready
        for ($i = 0; $i < 3; $i++) {
            $this->cards->pickCardForLocation('deck', self::LOCATION_DISCARD, 3);
        }

        /*******************************
        *          PLAYER HANDS        *
        *******************************/
        // ****will need to chagnes this to be three cards and one of each type for players****
        //***** will need to go before deck creation and shuffling once ready
        foreach ($players as $player_id => $player) {      
                $this->cards->pickCards(3, 'deck', $player_id);
        }

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();

        return PlayerTurn::class;
    }

    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3)
    {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playAutomatically(int $moves = 50)
    {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int)$playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }

    /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }     
    */


    /*************************************************
     *               CARD INFO HELPERS                
     *************************************************/

    //Get full card info (name, points, ability, etc.)
    public function getCardInfo($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']];
    }

    //Get the display name of a card
    public function getCardName($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['name'];
    }

    //Get the points for a card (if you store points)
    public function getCardPoints($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['points'] ?? null;
    }
    //Get a card's ability text, if it has one
    public function getCardAbility($card)
    {
        return self::$CARD_INFO[$card['type']][$card['type_arg']]['ability'] ?? null;
    }

    //Attach name/points/ability to a card array before sending to client
    public function enrichCard($card)
    {
        $info = $this->getCardInfo($card);

        $card['name'] = $info['name'] ?? null;
        $card['color'] = $info['color'] ?? null;
        $card['points'] = $info['points'] ?? null;
        $card['rings'] = $info['rings'] ?? null;
        $card['ability'] = $info['ability'] ?? null;
        $card['moonUnlock'] = $info['moonUnlock'] ?? null;
        $card['moonUnlockReq'] = $info['moonUnlockReq'] ?? null;
        $card['moonUnlockAbility'] = $info['moonUnlock'] ?? null;
        $card['moonUnlockAbility'] = $info['moonUnlock'] ?? null;
        return $card;
    }

    //Enrich a list of cards
    public function enrichCards($cards)
    {
        foreach ($cards as &$card) {
            $card = $this->enrichCard($card);
        }
        return $cards;
    }

    /*************************************************
     * ABILITY ROUTING TABLE
     *************************************************/


    /*************************************************
     * DISPATCH ABILITY BASED ON CARD TYPE + ARG
     *************************************************/


}
