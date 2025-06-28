# TI4 API:
Api to handle time and actions of a Twilight Imperium 4 game.

Live test:
https://server.gabr.tel/ti4/api/java.html

# **Avaiable Methods**

## **newGame**

Create a new game. At least 1 of the players should be an admin.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| new_players | Array of player | Yes | Array of players **starting with the Speaker** |
| time_action | Integer | Optional | Time for each player choose and make the action of the turn. Default 60 seconds. |
| time_secondary | Integer | Optional | Time for each player choose to use secondary. Default 10 seconds. |
| starter_time | Integer | Optional | Time each player will start with. Default 120 seconds |

Request example:

`POST https://server.gabr.tel/ti4/api?method=newGame`

```json
{ 
  "new_players": [
	  [
	    "admin": true,
	    "name": "Gabriel",
	    "faction": "arborec"
	  ],
	  [
	    "admin": true,
	    "name": "Tadeu",
	    "faction": "letnev"
	  ],
	  [
	    "admin": false,
	    "name": "Honorato",
	    "faction": "saar"
	  ]
	]
]
```

Answer example:

```json
{
	"ok":true,
	"result":"Game was created",
	"data":[
		{
			"player_type":"player",
			"hash":"5:681940e88d40cmCcRx6_E0keF"
		},
		{
			"player_type":"spectator",
			"hash":"5:681940e88d448huWjB-DAI33z"
		}
	]
}
```

## infoRoom

Get information about the game and the players of the room. You can call this method up to once every 2 seconds.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Optional | Faction the player. |

Request example:

query`POST https://server.gabr.tel/ti4/api?method=infoRoom&hash=1:68192eea460e8QSUDJqBPKbth&faction=letnev`

Answer example:

```json
{
    "ok": true,
    "result": "",
    "data": {
        "game": {
            "current_phase": "action_phase",
            "current_turn": 2,
            "unix_waiting_secondary_until": null,
            "last_strategy": "1",
            "unix_time_in_phase": 1746485219,
            "unix_paused_since": 0,
            "unix_paused_until": 0,
            "paused": false,
            "faction_to_give_pause_time": null,
            "time_action": 60,
            "time_secondary": 10,
            "time_secondary_delay": 10
        },
        "you": {
            "name": "Gabriel",
            "faction": "arborec",
            "admin": true,
            "speaker": false,
            "strategy_card": "3",
            "already_used_strategy_card": false,
            "already_passed_turn": false,
            "will_use_secondary": null,
            "time_playing": 531,
            "currently_playing": true,
            "unix_playing_since": 1746485243,
            "unix_playing_until": 1746485317,
            "bonus_time": 14,
            "last_online": 1746485246
        },
        "players": [
            {
                "name": "Gabriel",
                "faction": "arborec",
                "admin": true,
                "speaker": false,
                "strategy_card": "3",
                "already_used_strategy_card": false,
                "already_passed_turn": false,
                "will_use_secondary": null,
                "time_playing": 531,
                "currently_playing": true,
                "unix_playing_since": 1746485243,
                "unix_playing_until": 1746485317,
                "bonus_time": 14,
                "last_online": 1746485246
            },
            {
                "name": "Thiago",
                "faction": "saar",
                "admin": false,
                "speaker": false,
                "strategy_card": "5",
                "already_used_strategy_card": false,
                "already_passed_turn": false,
                "will_use_secondary": null,
                "time_playing": 26,
                "currently_playing": false,
                "unix_playing_since": 0,
                "unix_playing_until": 0,
                "bonus_time": 5,
                "last_online": 1746485247
            },
            {
                "name": "Tadeu",
                "faction": "letnev",
                "admin": true,
                "speaker": true,
                "strategy_card": "1",
                "already_used_strategy_card": true,
                "already_passed_turn": false,
                "will_use_secondary": null,
                "time_playing": 21,
                "currently_playing": false,
                "unix_playing_since": 1746485219,
                "unix_playing_until": 1746485287,
                "bonus_time": 68,
                "last_online": 0
            }
        ],
        "server_time": 1746485248,
        "what_front_should_do": {
            "title": "Choose an action",
            "countdown": 1746485317,
            "options": [
                [
                    {
                        "option": "Tatic",
                        "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=tatic",
                        "method": "POST",
                        "data": {
                            "method": "chooseAction",
                            "hash": "1:68192eea460e8QSUDJqBPKbth",
                            "faction": "arborec",
                            "action": "tatic"
                        }
                    }
                ],
                [
                    {
                        "option": "Component",
                        "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=component",
                        "method": "POST",
                        "data": {
                            "method": "chooseAction",
                            "hash": "1:68192eea460e8QSUDJqBPKbth",
                            "faction": "arborec",
                            "action": "component"
                        }
                    }
                ],
                [
                    {
                        "option": "Faction",
                        "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=faction",
                        "method": "POST",
                        "data": {
                            "method": "chooseAction",
                            "hash": "1:68192eea460e8QSUDJqBPKbth",
                            "faction": "arborec",
                            "action": "component"
                        }
                    }
                ],
                [
                    {
                        "option": "Use Politics",
                        "m_title": "Choose new speaker:",
                        "m_options": [
                            {
                                "option": "Gabriel(arborec)",
                                "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=strategy&speaker=arborec",
                                "method": "POST",
                                "data": {
                                    "method": "chooseAction",
                                    "hash": "1:68192eea460e8QSUDJqBPKbth",
                                    "faction": "arborec",
                                    "action": "politics",
                                    "speaker": "arborec"
                                }
                            },
                            {
                                "option": "Thiago(saar)",
                                "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=strategy&speaker=saar",
                                "method": "POST",
                                "data": {
                                    "method": "chooseAction",
                                    "hash": "1:68192eea460e8QSUDJqBPKbth",
                                    "faction": "arborec",
                                    "action": "politics",
                                    "speaker": "saar"
                                }
                            },
                            {
                                "option": "Tadeu(letnev)",
                                "link": "https:\/\/server.gabr.tel\/ti4\/api\/api1.php?method=chooseAction&hash=1:68192eea460e8QSUDJqBPKbth&faction=arborec&action=strategy&speaker=letnev",
                                "method": "POST",
                                "data": {
                                    "method": "chooseAction",
                                    "hash": "1:68192eea460e8QSUDJqBPKbth",
                                    "faction": "arborec",
                                    "action": "politics",
                                    "speaker": "letnev"
                                }
                            }
                        ]
                    }
                ]
            ]
        }
    }
}
```

## chooseStrategy

Choose strategy card.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |
| strategy_card | Integer | Yes | Strategy card of the player (1-Leadership, 2-Diplomacy, 3-Politics, etc) |

Request example:

`POST https://server.gabr.tel/ti4/api?method=chooseStrategy&hash=fde55678uijhhg&faction=sol`

```json
{
	"strategy_card": 1
}
```

Answer example:

```json
{
	"ok":true,
	"result": "Strategy card choosen."
}
```

## chooseAction

Choose action of active player in action phase.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |
| action | String | Yes | Action of the player, can be one of: "tatic", "component", "faction", "strategy" or "pass_turn". |
| speaker | String | optional | If the action is “strategy”, and the player has Politics, this must be the faction of new Speaker |

Request example:

`POST https://server.gabr.tel/ti4/api?method=chooseAction&hash=fde55678uijhhg&faction=sol`

```json
{
	"action": "strategy",
	"speaker": "letnev"
}
```

Answer example:

```json
{
	"ok":true,
	"result": "Strategy card used. Speaker has changed."
}
```

## chooseSecondary

Choose if active secondary will be used.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |
| secondary | Integer | Yes | Number of strategic secondary that will be used. Or 0 if no secondary will be used. |

Request example:

`POST https://server.gabr.tel/ti4/api?method=chooseSecondary&hash=fde55678uijhhg&faction=sol`

```json
{
	"secondary": 1
}
```

Answer example:

```json
{
	"ok":true,
	"result": "Secondary was confirmed."
}
```

## nextPhase

An **admin** can make the game go to the next phase.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |
| phase | String | Yes | Can be: "waiting_start","strategy_phase", "action_phase", "status_phase", "agenda_phase" or "game_end”. |

Request example:

`POST https://server.gabr.tel/ti4/api?method=nextPhase&hash=fde55678uijhhg&faction=sol`

```json
{
	"phase": "strategy_phase"
}
```

Answer example:

```json
{
	"ok":true,
	"result": "Game is now in the Strategy Phase."
}
```

## pauseGame

An admin can pause the game.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |
| pause | Boolean | Yes | “True” pauses the game. “False” resumes the game. |

Request example:

`POST https://server.gabr.tel/ti4/api?method=pauseGame&hash=fde55678uijhhg&faction=sol`

```json
{
	"pause": true
}
```

Answer example:

```json
{
	"ok":true,
	"result": "Current time is paused."
}
```

## undoAction

An admin can undo any player last action

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| hash | String | Yes | Hash of the room. |
| faction | String | Yes | Faction the player. |

Request example:

`POST https://server.gabr.tel/ti4/api?method=undoLastAction&hash=fde55678uijhhg&faction=sol`

Answer example:

```json
{
	"ok":true,
	"result": "Last action was undone."
}
```

# **Avaiable Types**

Soon…
